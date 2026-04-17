<?php

namespace App\Exports;

use App\Services\DepartmentAnalyticsService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartmentAnalyticsExport implements FromArray, WithHeadings
{
    public function __construct(
        private readonly DepartmentAnalyticsService $service,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
        private readonly ?int $departmentId
    ) {
    }

    public function headings(): array
    {
        return [
            'Nama Departemen',
            'Total Responden',
            'Tingkat Partisipasi (%)',
            'Rata-rata Skor',
        ];
    }

    public function array(): array
    {
        $result = $this->service->summarize(
            $this->dateFrom,
            $this->dateTo,
            $this->departmentId,
            'name',
            'asc',
            10000,
            1
        );

        return collect($result['rows']->items())
            ->map(fn ($row): array => [
                (string) $row->name,
                (int) $row->total_respondents,
                (float) $row->participation_rate,
                (float) $row->average_score,
            ])
            ->all();
    }
}
