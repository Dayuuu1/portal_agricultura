<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Analytics\AnalyticsPeriod;
use App\Support\Analytics\StatisticsReport;
use App\Support\Analytics\StatisticsSpreadsheet;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reports of «Estadísticas del portal» for the period chosen in the panel: a page laid out to print or save as PDF,
 * and an Excel workbook.
 */
class StatisticsReportController extends Controller
{
    public function show(Request $request): View
    {
        $user = $this->authorizeStatistics($request);

        return view('analytics.report', [
            'report' => new StatisticsReport($this->period($request)),
            'generatedBy' => $user->name,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $this->authorizeStatistics($request);
        $spreadsheet = new StatisticsSpreadsheet(new StatisticsReport($this->period($request)), $user->name);

        return response()
            ->download($spreadsheet->store(), $spreadsheet->fileName(), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend();
    }

    private function period(Request $request): AnalyticsPeriod
    {
        return AnalyticsPeriod::fromFilters($request->only(['period', 'start', 'end']));
    }

    private function authorizeStatistics(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasPermission('estadisticas.view'), 403);

        return $user;
    }
}
