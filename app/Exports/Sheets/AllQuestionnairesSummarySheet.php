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
        return [
            'questionnaire_id',
            'title',
            'status',
            'average_overall',
            'avg_guru',
            'avg_tata_usaha',
            'avg_orang_tua',
            'respondent_guru',
            'respondent_tata_usaha',
            'respondent_orang_tua',
            'generated_at',
        ];
    }

    public function array(): array
    {
        return Questionnaire::query()
            ->orderByDesc('id')
            ->get()
            ->map(function (Questionnaire $questionnaire): array {
                $analytics = $this->scorer->summarizeQuestionnaire($questionnaire);

                return [
                    $questionnaire->id,
                    $questionnaire->title,
                    $questionnaire->status,
                    $analytics['averages']['overall'],
                    $analytics['averages']['per_group']['guru'],
                    $analytics['averages']['per_group']['tata_usaha'],
                    $analytics['averages']['per_group']['orang_tua'],
                    $analytics['respondent_breakdown']['guru'],
                    $analytics['respondent_breakdown']['tata_usaha'],
                    $analytics['respondent_breakdown']['orang_tua'],
                    now()->toDateTimeString(),
                ];
            })
            ->values()
            ->all();
    }

    public function title(): string
    {
        return 'All Summary';
    }
}
