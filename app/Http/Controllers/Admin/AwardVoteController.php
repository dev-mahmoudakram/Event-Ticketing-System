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
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An event's award votes: who voted for whom, which IPs look like a burst, and removing votes.
 *
 * Removing never deletes the row — it stays, marked removed, so the email cannot vote again.
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
        ]);
    }

    public function destroy(Request $request, Event $event, AwardVote $vote): RedirectResponse
    {
        if ($vote->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }

        if (! $vote->isRemoved()) {
            $vote->update(['removed_at' => now(), 'removed_by' => $request->user()->id]);
        }

        return back()->with('success', __('Vote removed. The email still cannot vote again in this award.'));
    }

    /**
     * Remove every counted vote one IP cast in one award; other awards are left alone.
     */
    public function destroyIp(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'award' => ['required', 'integer', Rule::exists('awards', 'id')->where('event_id', $event->id)],
            'ip' => ['required', 'string', 'max:45'],
        ]);

        $removed = AwardVote::query()
            ->where('event_id', $event->id)
            ->where('award_id', $data['award'])
            ->where('ip_address', $data['ip'])
            ->counted()
            ->update(['removed_at' => now(), 'removed_by' => $request->user()->id]);

        return back()->with('success', __('Votes removed from this IP: :count', ['count' => $removed]));
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
