<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformReport;
use Illuminate\View\View;

class PlatformReportController extends Controller
{
    public function show(PlatformReport $report): View
    {
        return view('admin.reports.platform', [
            'totals' => $report->totals(),
            'byEvent' => $report->byEvent(),
            'growth' => $report->growth(),
            'quality' => $report->quality(),
            'reach' => $report->reach(),
        ]);
    }
}
