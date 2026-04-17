<?php

namespace App\Exports\Sheets;

use App\Models\Questionnaire;
use App\Services\QuestionnaireScorer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AllQuestionnairesSummarySheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly QuestionnaireScorer $scorer
    ) {
    }

    public function headings(): array
    {
        $headings = [
            'questionnaire_id',
            'title',
            'status',
            'average_overall',
            'generated_at',
        ];

        foreach ($this->roleSlugs() as $slug) {
            $headings[] = 'avg_' . $slug;
            $headings[] = 'respondent_' . $slug;
        }

        return $headings;
    }

    public function array(): array
    {
        return Questionnaire::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (Questionnaire $questionnaire): array {
                $analytics = $this->scorer->summarizeQuestionnaire($questionnaire);
                $row = [
                    $questionnaire->id,
                    $questionnaire->title,
                    $questionnaire->status,
                    $analytics['averages']['overall'],
                    now()->toDateTimeString(),
                ];

                foreach ($this->roleSlugs() as $slug) {
                    $row[] = (float) ($analytics['averages']['per_group'][$slug] ?? 0);
                    $row[] = (int) ($analytics['respondent_breakdown'][$slug] ?? 0);
                }

                return $row;
            })
            ->values()
            ->all();
    }

    public function title(): string
    {
        return 'All Summary';
    }

    /**
     * @return array<int, string>
     */
    private function roleSlugs(): array
    {
        return array_values(array_unique(array_filter((array) config('rbac.questionnaire_target_slugs', []))));
    }
}
