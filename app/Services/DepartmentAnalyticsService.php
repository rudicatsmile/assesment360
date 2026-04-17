<?php

namespace App\Services;

use App\Models\Departement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentAnalyticsService
{
    /**
     * @return array{
     *   rows: LengthAwarePaginator<int, object>,
     *   chart: array{labels: array<int, string>, average_scores: array<int, float>, participation_rates: array<int, float>}
     * }
     */
    public function summarize(
        ?string $dateFrom,
        ?string $dateTo,
        ?int $departmentId,
        string $sortBy = 'participation_rate',
        string $sortDirection = 'desc',
        int $perPage = 10,
        int $page = 1
    ): array {
        $employeesSub = DB::table('users')
            ->selectRaw('department_id, COUNT(*) as total_employees')
            ->whereNotNull('department_id')
            ->where('is_active', true)
            ->whereIn('role', (array) config('rbac.evaluator_slugs', []))
            ->groupBy('department_id');

        $respondentsSub = DB::table('responses')
            ->join('users', 'users.id', '=', 'responses.user_id')
            ->selectRaw('users.department_id, COUNT(DISTINCT responses.user_id) as total_respondents')
            ->where('responses.status', 'submitted')
            ->whereNotNull('users.department_id')
            ->when($dateFrom, fn($query) => $query->whereDate('responses.submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('responses.submitted_at', '<=', $dateTo))
            ->groupBy('users.department_id');

        $scoresSub = DB::table('answers')
            ->join('responses', 'responses.id', '=', 'answers.response_id')
            ->selectRaw('answers.department_id, AVG(answers.calculated_score) as average_score')
            ->where('responses.status', 'submitted')
            ->whereNotNull('answers.department_id')
            ->whereNotNull('answers.calculated_score')
            ->when($dateFrom, fn($query) => $query->whereDate('responses.submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('responses.submitted_at', '<=', $dateTo))
            ->groupBy('answers.department_id');

        $query = Departement::query()
            ->leftJoinSub($employeesSub, 'emp', fn($join) => $join->on('emp.department_id', '=', 'departements.id'))
            ->leftJoinSub($respondentsSub, 'resp', fn($join) => $join->on('resp.department_id', '=', 'departements.id'))
            ->leftJoinSub($scoresSub, 'sc', fn($join) => $join->on('sc.department_id', '=', 'departements.id'))
            ->when($departmentId, fn($q) => $q->where('departements.id', $departmentId))
            ->selectRaw('
                departements.id,
                departements.name,
                departements.urut,
                COALESCE(emp.total_employees, 0) as total_employees,
                COALESCE(resp.total_respondents, 0) as total_respondents,
                ROUND(COALESCE(sc.average_score, 0), 2) as average_score,
                CASE
                    WHEN COALESCE(emp.total_employees, 0) = 0 THEN 0
                    ELSE ROUND((COALESCE(resp.total_respondents, 0) / emp.total_employees) * 100, 2)
                END as participation_rate
            ');

        $allowedSort = ['name', 'total_respondents', 'participation_rate', 'average_score', 'urut'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'participation_rate';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $rowsCollection = $query->orderBy($sortBy, $sortDirection)->get();

        $rows = $this->paginateCollection($rowsCollection, $perPage, $page);

        $chartRows = $rowsCollection
            ->sortBy('urut')
            ->values();

        return [
            'rows' => $rows,
            'chart' => [
                'labels' => $chartRows->pluck('name')->map(fn($name): string => (string) $name)->all(),
                'average_scores' => $chartRows->pluck('average_score')->map(fn($score): float => (float) $score)->all(),
                'participation_rates' => $chartRows->pluck('participation_rate')->map(fn($rate): float => (float) $rate)->all(),
            ],
        ];
    }

    /**
     * @param Collection<int, object> $items
     */
    private function paginateCollection(Collection $items, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $items->count();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return new Paginator(
            $items->slice($offset, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
}
