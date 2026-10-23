<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VotingRefused;
use App\Mail\AwardVoteCodeMail;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\AwardVote;
use App\Models\AwardVoteCode;
use App\Models\Event;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

/**
 * The rules of award voting: a visitor asks for a code by email, enters it, and their vote
 * counts. One vote per email per award; a removed vote keeps the email blocked.
 */
class AwardVoting
{
    public const CODE_LIFETIME_MINUTES = 10;

    public const MAX_WRONG_ATTEMPTS = 5;

    public const CODES_PER_WINDOW = 3;

    public const CODE_WINDOW_MINUTES = 10;

    public const WRONG_CODES_BEFORE_LOCKOUT = 10;

    public const LOCKOUT_MINUTES = 60;

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Email a fresh six-digit code for a vote, cancelling any earlier code for this email + award.
     *
     * @throws VotingRefused when voting is closed, the email already voted, is locked out or asked too often
     * @throws InvalidArgumentException when the nominee is not part of the award
     */
    public function sendCode(Award $award, AwardNominee $nominee, string $email, string $ip): void
    {
        if ($nominee->award_id !== $award->id) {
            throw new InvalidArgumentException('The nominee does not belong to this award.');
        }

        $this->ensureVotingIsOpen($award);

        $email = self::normalizeEmail($email);

        if ($this->hasVoted($award, $email)) {
            throw new VotingRefused(__('This email has already voted in this award.'));
        }

        $this->ensureNotLockedOut($award, $email);

        $limiterKey = $this->codeLimiterKey($award, $email);

        if (RateLimiter::tooManyAttempts($limiterKey, self::CODES_PER_WINDOW)) {
            throw new VotingRefused(__('Too many codes were requested for this email. Try again in a few minutes.'));
        }

        RateLimiter::hit($limiterKey, self::CODE_WINDOW_MINUTES * 60);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        AwardVoteCode::query()->where('award_id', $award->id)->where('email', $email)->delete();

        AwardVoteCode::create([
            'award_id' => $award->id,
            'award_nominee_id' => $nominee->id,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
            'ip_address' => $ip,
        ]);

        Mail::to($email)->send((new AwardVoteCodeMail($code, $award, $nominee))->locale(app()->getLocale()));
    }

    /**
     * Turn a correct code into a counted vote for the nominee it was sent for.
     *
     * Each check first reserves one of the code's attempts in a single conditional UPDATE, so
     * requests racing each other cannot guess more than MAX_WRONG_ATTEMPTS times between them.
     * Wrong codes also count toward an hour-long lockout per email + award that a new code
     * does not reset.
     *
     * @throws VotingRefused when voting is closed, the email is locked out, the code is wrong,
     *                       dead or for another nominee, or the email already voted
     * @throws InvalidArgumentException when the nominee is not part of the award
     */
    public function confirm(Award $award, AwardNominee $nominee, string $email, string $code, string $ip): AwardVote
    {
        if ($nominee->award_id !== $award->id) {
            throw new InvalidArgumentException('The nominee does not belong to this award.');
        }

        $this->ensureVotingIsOpen($award);

        $email = self::normalizeEmail($email);

        $this->ensureNotLockedOut($award, $email);

        $pending = AwardVoteCode::query()
            ->where('award_id', $award->id)
            ->where('email', $email)
            ->latest('id')
            ->first();

        if ($pending === null || $pending->expires_at->isPast() || ! $this->reserveAttempt($pending)) {
            throw new VotingRefused(__('That code has expired. Request a new one.'));
        }

        if ($pending->award_nominee_id !== $nominee->id) {
            throw new VotingRefused(__('That code was sent for a different choice. Request a new one.'));
        }

        if (! Hash::check(trim($code), $pending->code_hash)) {
            RateLimiter::hit($this->wrongCodeLimiterKey($award, $email), self::LOCKOUT_MINUTES * 60);

            throw new VotingRefused(__('That code is not right.'));
        }

        try {
            $vote = AwardVote::create([
                'event_id' => $award->event_id,
                'award_id' => $award->id,
                'award_nominee_id' => $pending->award_nominee_id,
                'email' => $email,
                'ip_address' => $ip,
            ]);
        } catch (UniqueConstraintViolationException) {
            $pending->delete();

            throw new VotingRefused(__('This email has already voted in this award.'));
        }

        $pending->delete();

        return $vote;
    }

    /**
     * Counted votes per nominee across the event's awards, with a zero for every nominee.
     *
     * @return array<int, int> nominee id => counted votes
     */
    public function counts(Event $event): array
    {
        $nomineeIds = AwardNominee::query()
            ->whereHas('award', fn ($query) => $query->where('event_id', $event->id))
            ->orderBy('id')
            ->pluck('id');

        $totals = AwardVote::query()
            ->counted()
            ->where('event_id', $event->id)
            ->groupBy('award_nominee_id')
            ->selectRaw('award_nominee_id, COUNT(*) as total')
            ->pluck('total', 'award_nominee_id');

        $counts = [];

        foreach ($nomineeIds as $nomineeId) {
            $counts[(int) $nomineeId] = (int) ($totals[$nomineeId] ?? 0);
        }

        return $counts;
    }

    private function ensureVotingIsOpen(Award $award): void
    {
        $event = $award->event()->first();

        if ($event === null || ! $event->votingIsOpen()) {
            throw new VotingRefused(__('Voting is not open.'));
        }
    }

    /**
     * Use up one attempt on the code, unless none are left. Atomic, so parallel checks
     * cannot share the last attempt.
     */
    private function reserveAttempt(AwardVoteCode $pending): bool
    {
        return AwardVoteCode::query()
            ->whereKey($pending->id)
            ->where('attempts', '<', self::MAX_WRONG_ATTEMPTS)
            ->increment('attempts') === 1;
    }

    /**
     * @throws VotingRefused when the email entered too many wrong codes in the last hour
     */
    private function ensureNotLockedOut(Award $award, string $email): void
    {
        if (RateLimiter::tooManyAttempts($this->wrongCodeLimiterKey($award, $email), self::WRONG_CODES_BEFORE_LOCKOUT)) {
            throw new VotingRefused(__('Too many wrong codes. Try again later.'));
        }
    }

    private function hasVoted(Award $award, string $email): bool
    {
        return AwardVote::query()->where('award_id', $award->id)->where('email', $email)->exists();
    }

    private function codeLimiterKey(Award $award, string $email): string
    {
        return 'award-vote-code:'.$award->id.':'.hash('sha256', $email);
    }

    private function wrongCodeLimiterKey(Award $award, string $email): string
    {
        return 'award-vote-fail|'.$award->id.'|'.hash('sha256', $email);
    }
}
