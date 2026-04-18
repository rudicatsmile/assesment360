<?php

namespace App\Services;

use App\Models\Departement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        string $sortBy = 'urut',
        string $sortDirection = 'asc',
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
            $sortBy = 'urut';
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
     * @return array{
     *   department_name: string,
     *   rows: array<int, array{
     *     role_id:int,
     *     role_name:string,
     *     total_respondents:int,
     *     participation_rate:float,
     *     average_score:float
     *   }>
     * }
     */
    public function summarizeRolesByDepartment(
        int $departmentId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $cacheKey = implode(':', [
            'department_role_analytics',
            $departmentId,
            $dateFrom ?: 'none',
            $dateTo ?: 'none',
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($departmentId, $dateFrom, $dateTo): array {
            $departmentName = (string) (Departement::query()->where('id', $departmentId)->value('name') ?? '');

            $totalByRoleSub = DB::table('users')
                ->selectRaw('role_id, COUNT(DISTINCT id) as total_users')
                ->where('department_id', $departmentId)
                ->whereNotNull('role_id')
                ->whereNull('deleted_at')
                ->groupBy('role_id');

            $activeRespondentsSub = DB::table('responses')
                ->join('users', 'users.id', '=', 'responses.user_id')
                ->selectRaw('users.role_id, COUNT(DISTINCT users.id) as active_respondents')
                ->where('users.department_id', $departmentId)
                ->whereNotNull('users.role_id')
                ->whereNull('users.deleted_at')
                ->where('responses.status', 'submitted')
                ->whereNull('responses.deleted_at')
                ->when($dateFrom, fn($query) => $query->whereDate('responses.submitted_at', '>=', $dateFrom))
                ->when($dateTo, fn($query) => $query->whereDate('responses.submitted_at', '<=', $dateTo))
                ->groupBy('users.role_id');

            $averageScoreSub = DB::table('answers')
                ->join('responses', 'responses.id', '=', 'answers.response_id')
                ->join('users', 'users.id', '=', 'responses.user_id')
                ->selectRaw('users.role_id, AVG(answers.calculated_score) as average_score')
                ->where('users.department_id', $departmentId)
                ->whereNotNull('users.role_id')
                ->whereNull('users.deleted_at')
                ->where('responses.status', 'submitted')
                ->whereNull('responses.deleted_at')
                ->whereNull('answers.deleted_at')
                ->whereNotNull('answers.calculated_score')
                ->when($dateFrom, fn($query) => $query->whereDate('responses.submitted_at', '>=', $dateFrom))
                ->when($dateTo, fn($query) => $query->whereDate('responses.submitted_at', '<=', $dateTo))
                ->groupBy('users.role_id');

            $rows = DB::table('roles')
                ->joinSub($totalByRoleSub, 'tot', fn($join) => $join->on('tot.role_id', '=', 'roles.id'))
                ->leftJoinSub($activeRespondentsSub, 'resp', fn($join) => $join->on('resp.role_id', '=', 'roles.id'))
                ->leftJoinSub($averageScoreSub, 'score', fn($join) => $join->on('score.role_id', '=', 'roles.id'))
                ->orderBy('roles.name')
                ->selectRaw('
                    roles.id as role_id,
                    roles.name as role_name,
                    COALESCE(tot.total_users, 0) as total_respondents,
                    CASE
                        WHEN COALESCE(tot.total_users, 0) = 0 THEN 0
                        ELSE ROUND((COALESCE(resp.active_respondents, 0) / tot.total_users) * 100, 1)
                    END as participation_rate,
                    ROUND(COALESCE(score.average_score, 0), 2) as average_score
                ')
                ->get()
                ->map(fn(object $row): array => [
                    'role_id' => (int) $row->role_id,
                    'role_name' => (string) $row->role_name,
                    'total_respondents' => (int) $row->total_respondents,
                    'participation_rate' => (float) $row->participation_rate,
                    'average_score' => (float) $row->average_score,
                ])
                ->values()
                ->all();

            return [
                'department_name' => $departmentName,
                'rows' => $rows,
            ];
        });
    }

    /**
     * @return array<int, array{
     *   user_id:int,
     *   user_name:string,
     *   total_submissions:int,
     *   average_score:float
     * }>
     */
    public function summarizeUsersByDepartmentRole(
        int $departmentId,
        int $roleId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $cacheKey = implode(':', [
            'department_role_users',
            $departmentId,
            $roleId,
            $dateFrom ?: 'none',
            $dateTo ?: 'none',
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($departmentId, $roleId, $dateFrom, $dateTo): array {
            $submissionSub = DB::table('responses')
                ->selectRaw('user_id, COUNT(*) as total_submissions')
                ->where('status', 'submitted')
                ->whereNull('deleted_at')
                ->when($dateFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $dateFrom))
                ->when($dateTo, fn ($query) => $query->whereDate('submitted_at', '<=', $dateTo))
                ->groupBy('user_id');

            $scoreSub = DB::table('answers')
                ->join('responses', 'responses.id', '=', 'answers.response_id')
                ->selectRaw('responses.user_id, AVG(answers.calculated_score) as average_score')
                ->where('responses.status', 'submitted')
                ->whereNull('responses.deleted_at')
                ->whereNull('answers.deleted_at')
                ->whereNotNull('answers.calculated_score')
                ->when($dateFrom, fn ($query) => $query->whereDate('responses.submitted_at', '>=', $dateFrom))
                ->when($dateTo, fn ($query) => $query->whereDate('responses.submitted_at', '<=', $dateTo))
                ->groupBy('responses.user_id');

            return DB::table('users')
                ->leftJoinSub($submissionSub, 'sub', fn ($join) => $join->on('sub.user_id', '=', 'users.id'))
                ->leftJoinSub($scoreSub, 'sc', fn ($join) => $join->on('sc.user_id', '=', 'users.id'))
                ->where('users.department_id', $departmentId)
                ->where('users.role_id', $roleId)
                ->whereNull('users.deleted_at')
                ->orderBy('users.name')
                ->selectRaw('
                    users.id as user_id,
                    users.name as user_name,
                    COALESCE(sub.total_submissions, 0) as total_submissions,
                    ROUND(COALESCE(sc.average_score, 0), 2) as average_score
                ')
                ->get()
                ->map(fn (object $row): array => [
                    'user_id' => (int) $row->user_id,
                    'user_name' => (string) $row->user_name,
                    'total_submissions' => (int) $row->total_submissions,
                    'average_score' => (float) $row->average_score,
                ])
                ->values()
                ->all();
        });
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
