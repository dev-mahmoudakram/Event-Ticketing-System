<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwardVote;
use App\Models\Event;
use App\Services\SuspiciousVotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An event's award votes: who voted for whom, which IPs look like a burst, and removing votes.
 *
 * Removing never deletes the row — it stays, marked removed, so the email cannot vote again.
 * A removal can be undone (one vote or a whole IP in an award), because a shared venue
 * network looks just like a burst.
 */
class AwardVoteController extends Controller
{
    public function index(Request $request, Event $event, SuspiciousVotes $suspicious): View
    {
        $awards = $event->awards()->with('nominees')->get();
        $selectedAward = $awards->firstWhere('id', (int) $request->query('award'));
        $selectedNominee = $selectedAward?->nominees->firstWhere('id', (int) $request->query('nominee'));

        $votes = $this->votesQuery($event, $selectedAward?->id)
            ->when($selectedNominee, fn (Builder $query) => $query->where('award_nominee_id', $selectedNominee->id))
            ->with('award', 'nominee')
            ->latest()
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.award-votes.index', [
            'event' => $event,
            'awards' => $awards,
            'selectedAward' => $selectedAward,
            'selectedNominee' => $selectedNominee,
            'votes' => $votes,
            'suspicious' => $suspicious,
            'restorableIps' => $this->restorableIps($event, collect($votes->items())),
        ]);
    }

    public function destroy(Request $request, Event $event, AwardVote $vote): RedirectResponse
    {
        $this->ensureVoteBelongsTo($event, $vote);

        if (! $vote->isRemoved()) {
            $vote->update(['removed_at' => now(), 'removed_by' => $request->user()->id]);
        }

        return back()->with('success', __('Vote removed. The email still cannot vote again in this award.'));
    }

    /**
     * Undo a removal: the row never went away, so it simply counts again — no duplicate is possible.
     */
    public function restore(Event $event, AwardVote $vote): RedirectResponse
    {
        $this->ensureVoteBelongsTo($event, $vote);

        if ($vote->isRemoved()) {
            $vote->update(['removed_at' => null, 'removed_by' => null]);
        }

        return back()->with('success', __('Vote restored. It counts again.'));
    }

    /**
     * Remove every counted vote one IP cast in one award; other awards are left alone.
     */
    public function destroyIp(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validateAwardAndIp($request, $event);

        $removed = $this->awardIpVotes($event, $data)
            ->counted()
            ->update(['removed_at' => now(), 'removed_by' => $request->user()->id]);

        return back()->with('success', __('Votes removed from this IP: :count', ['count' => $removed]));
    }

    /**
     * Restore every removed vote one IP cast in one award — e.g. a whole venue on one network.
     */
    public function restoreIp(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validateAwardAndIp($request, $event);

        $restored = $this->awardIpVotes($event, $data)
            ->whereNotNull('removed_at')
            ->update(['removed_at' => null, 'removed_by' => null]);

        return back()->with('success', __('Votes restored from this IP: :count', ['count' => $restored]));
    }

    /**
     * Every vote, removed ones included, streamed with a BOM so Excel reads Arabic names.
     */
    public function export(Request $request, Event $event): StreamedResponse
    {
        $award = $event->awards()->find((int) $request->query('award'));
        $votes = $this->votesQuery($event, $award?->id)->with('award', 'nominee')->orderBy('id');
        $filename = $event->slug.'-award-votes-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($votes) {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [__('Email'), __('Award'), __('Nominee'), __('Time'), __('IP'), __('Status')]);

            foreach ($votes->lazy() as $vote) {
                fputcsv($handle, array_map($this->defuseFormula(...), [
                    $vote->email,
                    $vote->award->name(),
                    $vote->nominee?->name() ?? '',
                    $vote->created_at?->toDateTimeString() ?? '',
                    $vote->ip_address ?? '',
                    $vote->isRemoved() ? __('Removed') : __('Counted'),
                ]));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * "award id|ip" for each award + IP on this page that has removed votes to bring back.
     *
     * @param  Collection<int, AwardVote>  $pageVotes
     * @return array<string, true>
     */
    private function restorableIps(Event $event, Collection $pageVotes): array
    {
        $ips = $pageVotes->pluck('ip_address')->filter()->unique()->values();

        if ($ips->isEmpty()) {
            return [];
        }

        return AwardVote::query()
            ->where('event_id', $event->id)
            ->whereIn('ip_address', $ips)
            ->whereNotNull('removed_at')
            ->distinct()
            ->get(['award_id', 'ip_address'])
            ->mapWithKeys(fn (AwardVote $vote): array => [$vote->award_id.'|'.$vote->ip_address => true])
            ->all();
    }

    private function ensureVoteBelongsTo(Event $event, AwardVote $vote): void
    {
        if ($vote->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * @return array{award: int|string, ip: string}
     */
    private function validateAwardAndIp(Request $request, Event $event): array
    {
        return $request->validate([
            'award' => ['required', 'integer', Rule::exists('awards', 'id')->where('event_id', $event->id)],
            'ip' => ['required', 'string', 'max:45'],
        ]);
    }

    /**
     * @param  array{award: int|string, ip: string}  $data
     * @return Builder<AwardVote>
     */
    private function awardIpVotes(Event $event, array $data): Builder
    {
        return AwardVote::query()
            ->where('event_id', $event->id)
            ->where('award_id', $data['award'])
            ->where('ip_address', $data['ip']);
    }

    /**
     * @return Builder<AwardVote>
     */
    private function votesQuery(Event $event, ?int $awardId): Builder
    {
        return AwardVote::query()
            ->where('event_id', $event->id)
            ->when($awardId, fn (Builder $query) => $query->where('award_id', $awardId));
    }

    /**
     * Stop a spreadsheet treating a cell as a formula: voters type their own emails, and award
     * and nominee names come from the CMS, so a leading =, +, -, @, tab or CR is made literal.
     */
    private function defuseFormula(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
