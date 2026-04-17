<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DepartmentAnalyticsExport;
use App\Http\Controllers\Controller;
use App\Services\DepartmentAnalyticsService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DepartmentAnalyticsExportController extends Controller
{
    public function excel(Request $request): BinaryFileResponse
    {
        $this->authorizeAdmin($request);

        $filename = 'department_analytics_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new DepartmentAnalyticsExport(
                app(DepartmentAnalyticsService::class),
                $request->query('date_from'),
                $request->query('date_to'),
                $request->query('department_id') ? (int) $request->query('department_id') : null
            ),
            $filename
        );
    }

    public function pdf(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $result = app(DepartmentAnalyticsService::class)->summarize(
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('department_id') ? (int) $request->query('department_id') : null,
            'name',
            'asc',
            10000,
            1
        );

        $html = view('admin.exports.department-analytics-pdf', [
            'rows' => $result['rows']->items(),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="department-analytics-print.html"',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdminRole(), 403);
    }
}
