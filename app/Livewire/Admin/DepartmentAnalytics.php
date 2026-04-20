<?php

namespace App\Livewire\Admin;

use App\Models\Departement;
use App\Services\DepartmentAnalyticsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class DepartmentAnalytics extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $departmentFilter = null;

    public string $sortBy = 'urut';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public ?string $errorMessage = null;

    public ?int $selectedDepartmentId = null;

    public string $selectedDepartmentName = '';

    /** @var array<int, array{role_id:int, role_name:string, total_respondents:int, participation_rate:float, average_score:float}> */
    public array $roleRows = [];

    public ?int $expandedRoleId = null;

    /** @var array<int, array<int, array{user_id:int, user_name:string, total_submissions:int, average_score:float}>> */
    public array $roleUsersByRole = [];

    /** @var array<int, string> */
    public array $roleUsersErrorByRole = [];

    public ?string $roleErrorMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdminRole(), 403);
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->refreshSelectedDepartmentRoles();
    }

    public function updatedDateTo(): void
    {
        $this->refreshSelectedDepartmentRoles();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
        if ($this->selectedDepartmentId !== null && (int) $this->departmentFilter !== $this->selectedDepartmentId) {
            $this->clearSelectedDepartment();
        }
    }

    public function sort(string $field): void
    {
        $allowed = ['name', 'total_respondents', 'participation_rate', 'average_score', 'urut'];
        if (!in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function selectDepartment(int $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
        $this->selectedDepartmentName = '';
        $this->roleRows = [];
        $this->expandedRoleId = null;
        $this->roleUsersByRole = [];
        $this->roleUsersErrorByRole = [];
        $this->roleErrorMessage = null;

        try {
            $result = app(DepartmentAnalyticsService::class)->summarizeRolesByDepartment(
                $departmentId,
                $this->dateFrom,
                $this->dateTo
            );

            $this->selectedDepartmentName = $result['department_name'];
            $this->roleRows = $result['rows'];

            if ($this->roleRows === []) {
                $this->roleErrorMessage = 'Department ini belum memiliki data role atau metrik responden.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->roleErrorMessage = 'Gagal memuat analitik role untuk department yang dipilih.';
            $this->roleRows = [];
        }
    }

    public function clearSelectedDepartment(): void
    {
        $this->selectedDepartmentId = null;
        $this->selectedDepartmentName = '';
        $this->roleRows = [];
        $this->expandedRoleId = null;
        $this->roleUsersByRole = [];
        $this->roleUsersErrorByRole = [];
        $this->roleErrorMessage = null;
    }

    public function toggleRole(int $roleId): void
    {
        if ($this->expandedRoleId === $roleId) {
            $this->expandedRoleId = null;

            return;
        }

        $this->expandedRoleId = $roleId;
    }

    public function loadRoleUsers(int $roleId): void
    {
        if ($this->selectedDepartmentId === null) {
            return;
        }

        if (array_key_exists($roleId, $this->roleUsersByRole)) {
            return;
        }

        try {
            $this->roleUsersByRole[$roleId] = app(DepartmentAnalyticsService::class)->summarizeUsersByDepartmentRole(
                $this->selectedDepartmentId,
                $roleId,
                $this->dateFrom,
                $this->dateTo
            );
            unset($this->roleUsersErrorByRole[$roleId]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->roleUsersErrorByRole[$roleId] = 'Gagal memuat daftar user untuk role ini.';
            $this->roleUsersByRole[$roleId] = [];
        }
    }

    private function refreshSelectedDepartmentRoles(): void
    {
        if ($this->selectedDepartmentId !== null) {
            $this->selectDepartment($this->selectedDepartmentId);
        }
    }

    public function getChartData(): array
    {
        try {
            $result = app(DepartmentAnalyticsService::class)->summarize(
                $this->dateFrom,
                $this->dateTo,
                $this->departmentFilter,
                'urut',
                'asc',
                100,
                1
            );

            return [
                'success' => true,
                'data' => $result['chart'],
            ];
        } catch (\Throwable $exception) {
            report($exception);
            return [
                'success' => false,
                'error' => 'Gagal memuat data grafik.',
            ];
        }
    }

    public function refreshCharts(): void
    {
        $this->dispatch('chart-data-refreshed');
    }

    public function exportExcelUrl(): string
    {
        return route('admin.exports.department-analytics.excel', $this->queryParams());
    }

    public function exportPdfUrl(): string
    {
        return route('admin.exports.department-analytics.pdf', $this->queryParams());
    }

    /**
     * @return array<string, mixed>
     */
    private function queryParams(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'department_id' => $this->departmentFilter,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ], fn($value) => $value !== null && $value !== '');
    }

    public function render()
    {
        $departments = Departement::query()
            ->orderBy('urut')
            ->orderBy('name')
            ->get(['id', 'name']);

        try {
            $result = app(DepartmentAnalyticsService::class)->summarize(
                $this->dateFrom,
                $this->dateTo,
                $this->departmentFilter,
                $this->sortBy,
                $this->sortDirection,
                $this->perPage,
                $this->getPage()
            );

            $this->errorMessage = null;
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Terjadi kesalahan saat memuat data analitik.';
            $result = [
                'rows' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage),
                'chart' => ['labels' => [], 'average_scores' => [], 'participation_rates' => []],
            ];
        }

        return view('livewire.admin.department-analytics', [
            'departments' => $departments,
            'rows' => $result['rows'],
            'chart' => $result['chart'],
        ]);
    }
}
