<?php

namespace App\Exports\Sheets;

use App\Models\Answer;
use App\Models\Questionnaire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestionnaireAnswersSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Questionnaire $questionnaire
    ) {
    }

    public function headings(): array
    {
        return [
            'questionnaire_id',
            'questionnaire_title',
            'response_id',
            'respondent_name',
            'respondent_email',
            'respondent_role',
            'response_status',
            'submitted_at',
            'question_id',
            'question_text',
            'question_type',
            'option_text',
            'option_score',
            'essay_answer',
            'calculated_score',
        ];
    }

    public function array(): array
    {
        return Answer::query()
            ->join('responses', 'responses.id', '=', 'answers.response_id')
            ->join('users', 'users.id', '=', 'responses.user_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('answer_options', 'answer_options.id', '=', 'answers.answer_option_id')
            ->where('responses.questionnaire_id', $this->questionnaire->id)
            ->where('responses.status', 'submitted')
            ->orderBy('responses.id')
            ->orderBy('questions.order')
            ->get([
                'responses.id as response_id',
                'users.name as respondent_name',
                'users.email as respondent_email',
                'users.role as respondent_role',
                'responses.status as response_status',
                'responses.submitted_at',
                'questions.id as question_id',
                'questions.question_text',
                'questions.type as question_type',
                'answer_options.option_text',
                'answer_options.score as option_score',
                'answers.essay_answer',
                'answers.calculated_score',
            ])
            ->map(fn ($row): array => [
                $this->questionnaire->id,
                $this->questionnaire->title,
                $row->response_id,
                $row->respondent_name,
                $row->respondent_email,
                $row->respondent_role,
                $row->response_status,
                $row->submitted_at,
                $row->question_id,
                $row->question_text,
                $row->question_type,
                $row->option_text,
                $row->option_score,
                $row->essay_answer,
                $row->calculated_score,
            ])
            ->values()
            ->all();
    }

    public function title(): string
    {
        return 'Answers';
    }
}
