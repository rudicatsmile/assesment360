<?php

namespace App\Exports\Sheets;

use App\Models\Questionnaire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestionnaireSummarySheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param array{
     *   respondent_breakdown: array<string,int>,
     *   averages: array{overall:float,per_group:array<string,float>},
     *   question_scores: array<int, array{question_id:int,question_text:string,type:string,average_score:float,responses_count:int}>,
     *   distribution: array<int, array{question_id:int,question_text:string,option_text:string,score:int|null,count:int,percentage:float}>
     * } $analytics
     */
    public function __construct(
        private readonly Questionnaire $questionnaire,
        private readonly array $analytics
    ) {
    }

    public function headings(): array
    {
        $headings = [
            'questionnaire_id',
            'title',
            'status',
            'average_overall',
            'total_questions_scored',
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
        $row = [
            $this->questionnaire->id,
            $this->questionnaire->title,
            $this->questionnaire->status,
            $this->analytics['averages']['overall'],
            count($this->analytics['question_scores']),
            now()->toDateTimeString(),
        ];

        foreach ($this->roleSlugs() as $slug) {
            $row[] = (float) ($this->analytics['averages']['per_group'][$slug] ?? 0);
            $row[] = (int) ($this->analytics['respondent_breakdown'][$slug] ?? 0);
        }

        return [$row];
    }

    public function title(): string
    {
        return 'Summary';
    }

    /**
     * @return array<int, string>
     */
    private function roleSlugs(): array
    {
        return array_values(array_unique(array_filter((array) config('rbac.questionnaire_target_slugs', []))));
    }
}
