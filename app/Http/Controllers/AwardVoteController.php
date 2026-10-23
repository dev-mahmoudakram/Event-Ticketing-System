<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\VotingRefused;
use App\Http\Requests\AwardCodeRequest;
use App\Http\Requests\AwardConfirmRequest;
use App\Models\Award;
use App\Models\AwardNominee;
use App\Models\Event;
use App\Services\AwardVoting;
use App\Services\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * The public side of award voting, called with fetch from the awards page: live counts,
 * emailing a code, and turning that code into a vote. No login — the email is the voter.
 */
class AwardVoteController extends Controller
{
    public function __construct(private AwardVoting $voting) {}

    public function counts(Event $event): JsonResponse
    {
        return response()->json([
            'counts' => (object) $this->voting->counts($event),
            'open' => $event->votingIsOpen(),
        ]);
    }

    public function sendCode(AwardCodeRequest $request, Event $event, Award $award, TurnstileVerifier $turnstile): JsonResponse
    {
        if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return $this->refused('captcha', __('Please complete the security check.'));
        }

        $email = (string) $request->validated('email');

        try {
            $this->voting->sendCode($award, $this->nominee($request->validated('nominee_id')), $email, (string) $request->ip());
        } catch (VotingRefused $refused) {
            return $this->refused($refused->field, $refused->getMessage());
        } catch (TransportExceptionInterface $failure) {
            Log::error('Could not email an award voting code.', [
                'award_id' => $award->id,
                'message' => $failure->getMessage(),
            ]);

            return response()->json(['message' => __('We could not send the email. Please try again in a minute.')], 503);
        }

        return response()->json([
            'message' => __('We emailed you a six-digit code. Enter it to confirm your vote.'),
        ]);
    }

    public function vote(AwardConfirmRequest $request, Event $event, Award $award): JsonResponse
    {
        $nominee = $this->nominee($request->validated('nominee_id'));

        try {
            $vote = $this->voting->confirm(
                $award,
                $nominee,
                (string) $request->validated('email'),
                (string) $request->validated('code'),
                (string) $request->ip(),
            );
        } catch (VotingRefused $refused) {
            return $this->refused($refused->field, $refused->getMessage());
        }

        $sessionKey = 'awarded.'.$event->id;
        $votedAwardIds = (array) $request->session()->get($sessionKey, []);

        if (! in_array($award->id, $votedAwardIds, true)) {
            $request->session()->push($sessionKey, $award->id);
        }

        return response()->json([
            'message' => __('Thank you! Your vote has been counted.'),
            'nominee_id' => $vote->award_nominee_id,
            'count' => $this->voting->counts($event)[$vote->award_nominee_id] ?? 0,
        ]);
    }

    private function nominee(mixed $nomineeId): AwardNominee
    {
        return AwardNominee::query()->findOrFail((int) $nomineeId);
    }

    private function refused(string $field, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }
}
