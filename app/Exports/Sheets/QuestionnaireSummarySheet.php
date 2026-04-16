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
     *   respondent_breakdown: array{guru:int,tata_usaha:int,orang_tua:int},
     *   averages: array{overall:float,per_group:array{guru:float,tata_usaha:float,orang_tua:float}},
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
            'total_questions_scored',
            'generated_at',
        ];
    }

    public function array(): array
    {
        return [[
            $this->questionnaire->id,
            $this->questionnaire->title,
            $this->questionnaire->status,
            $this->analytics['averages']['overall'],
            $this->analytics['averages']['per_group']['guru'],
            $this->analytics['averages']['per_group']['tata_usaha'],
            $this->analytics['averages']['per_group']['orang_tua'],
            $this->analytics['respondent_breakdown']['guru'],
            $this->analytics['respondent_breakdown']['tata_usaha'],
            $this->analytics['respondent_breakdown']['orang_tua'],
            count($this->analytics['question_scores']),
            now()->toDateTimeString(),
        ]];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
