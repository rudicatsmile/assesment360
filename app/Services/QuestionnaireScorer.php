<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionnaireScorer
{
    public function calculateScoreForAnswer(Question $question, ?int $optionId): ?int
    {
        if (!$optionId) {
            return null;
        }

        return $question->answerOptions
            ->firstWhere('id', $optionId)
                ?->score;
    }

    /**
     * @return array{
     *   respondent_breakdown: array{guru:int,tata_usaha:int,orang_tua:int},
     *   averages: array{overall:float,per_group:array{guru:float,tata_usaha:float,orang_tua:float}},
     *   question_scores: array<int, array{question_id:int,question_text:string,type:string,average_score:float,responses_count:int}>,
     *   distribution: array<int, array{question_id:int,question_text:string,option_text:string,score:int|null,count:int,percentage:float}>
     * }
     */
    public function summarizeQuestionnaire(Questionnaire $questionnaire): array
    {
        $roles = ['guru', 'tata_usaha', 'orang_tua'];
        $responseBase = Response::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', 'submitted');

        $respondentBreakdown = $responseBase
            ->clone()
            ->join('users', 'users.id', '=', 'responses.user_id')
            ->whereIn('users.role', $roles)
            ->selectRaw('users.role, COUNT(DISTINCT responses.user_id) as total')
            ->groupBy('users.role')
            ->pluck('total', 'users.role');

        $overallAverage = (float) Answer::query()
            ->join('responses', 'responses.id', '=', 'answers.response_id')
            ->where('responses.questionnaire_id', $questionnaire->id)
            ->where('responses.status', 'submitted')
            ->whereNotNull('answers.calculated_score')
            ->avg('answers.calculated_score');

        $groupAverages = [];

        foreach ($roles as $role) {
            $groupAverages[$role] = round((float) Answer::query()
                ->join('responses', 'responses.id', '=', 'answers.response_id')
                ->join('users', 'users.id', '=', 'responses.user_id')
                ->where('responses.questionnaire_id', $questionnaire->id)
                ->where('responses.status', 'submitted')
                ->where('users.role', $role)
                ->whereNotNull('answers.calculated_score')
                ->avg('answers.calculated_score'), 2);
        }

        $questionScores = Answer::query()
            ->join('responses', 'responses.id', '=', 'answers.response_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('responses.questionnaire_id', $questionnaire->id)
            ->where('responses.status', 'submitted')
            ->whereNotNull('answers.calculated_score')
            ->groupBy('questions.id', 'questions.question_text', 'questions.type')
            ->selectRaw('questions.id as question_id, questions.question_text, questions.type, ROUND(AVG(answers.calculated_score), 2) as average_score, COUNT(answers.id) as responses_count')
            ->orderByDesc('average_score')
            ->get()
            ->map(fn($item): array => [
                'question_id' => (int) $item->question_id,
                'question_text' => (string) $item->question_text,
                'type' => (string) $item->type,
                'average_score' => (float) $item->average_score,
                'responses_count' => (int) $item->responses_count,
            ])
            ->all();

        $distributionRows = DB::table('answers')
            ->join('responses', 'responses.id', '=', 'answers.response_id')
            ->join('answer_options', 'answer_options.id', '=', 'answers.answer_option_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('responses.questionnaire_id', $questionnaire->id)
            ->where('responses.status', 'submitted')
            ->groupBy('questions.id', 'questions.question_text', 'answer_options.id', 'answer_options.option_text', 'answer_options.score')
            ->selectRaw('questions.id as question_id, questions.question_text, answer_options.option_text, answer_options.score, COUNT(answers.id) as total')
            ->get();

        $distribution = $this->toDistributionWithPercentage($distributionRows);

        return [
            'respondent_breakdown' => [
                'guru' => (int) ($respondentBreakdown['guru'] ?? 0),
                'tata_usaha' => (int) ($respondentBreakdown['tata_usaha'] ?? 0),
                'orang_tua' => (int) ($respondentBreakdown['orang_tua'] ?? 0),
            ],
            'averages' => [
                'overall' => round($overallAverage, 2),
                'per_group' => [
                    'guru' => (float) ($groupAverages['guru'] ?? 0),
                    'tata_usaha' => (float) ($groupAverages['tata_usaha'] ?? 0),
                    'orang_tua' => (float) ($groupAverages['orang_tua'] ?? 0),
                ],
            ],
            'question_scores' => $questionScores,
            'distribution' => $distribution,
        ];
    }

    /**
     * @param Collection<int, object> $rows
     * @return array<int, array{question_id:int,question_text:string,option_text:string,score:int|null,count:int,percentage:float}>
     */
    private function toDistributionWithPercentage(Collection $rows): array
    {
        $totalsByQuestion = $rows
            ->groupBy('question_id')
            ->map(fn(Collection $items): int => (int) $items->sum('total'));

        return $rows->map(function ($row) use ($totalsByQuestion): array {
            $questionTotal = max(1, (int) ($totalsByQuestion[$row->question_id] ?? 0));
            $count = (int) $row->total;

            return [
                'question_id' => (int) $row->question_id,
                'question_text' => (string) $row->question_text,
                'option_text' => (string) $row->option_text,
                'score' => $row->score !== null ? (int) $row->score : null,
                'count' => $count,
                'percentage' => round(($count / $questionTotal) * 100, 2),
            ];
        })->all();
    }
}
