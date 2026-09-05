<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventReport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventReportController extends Controller
{
    public function show(Event $event): View
    {
        $report = new EventReport($event);

        return view('admin.reports.show', [
            'event' => $event,
            'statuses' => $report->ticketsByStatus(),
            'funnel' => $report->funnel(),
            'revenue' => $report->revenue(),
            'ticketTypes' => $report->revenueByTicketType(),
            'checkIns' => $report->checkIns(),
            'coupons' => $report->coupons(),
        ]);
    }

    /**
     * The attendee list, streamed rather than built in memory so a sold-out event does not
     * have to fit in a PHP array before the download starts.
     */
    public function export(Event $event): StreamedResponse
    {
        $rows = (new EventReport($event))->attendeeRows();
        $filename = $event->slug.'-attendees-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');

            // A BOM, so Excel opens Arabic names as UTF-8 rather than mojibake.
            fwrite($handle, "\xEF\xBB\xBF");

            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys($rows->first()));
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
