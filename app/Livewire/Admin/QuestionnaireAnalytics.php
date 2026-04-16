<?php

namespace App\Livewire\Admin;

use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Services\QuestionnaireScorer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class QuestionnaireAnalytics extends Component
{
    use AuthorizesRequests;

    public Questionnaire $questionnaire;

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
        $this->authorize('view', $this->questionnaire);
    }

    public function render()
    {
        $analytics = Cache::remember(
            $this->analyticsCacheKey(),
            now()->addMinutes(5),
            fn (): array => app(QuestionnaireScorer::class)->summarizeQuestionnaire($this->questionnaire)
        );

        return view('livewire.admin.questionnaire-analytics', [
            'analytics' => $analytics,
            'chartQuestionLabels' => collect($analytics['question_scores'])->pluck('question_text')->values()->all(),
            'chartQuestionAverages' => collect($analytics['question_scores'])->pluck('average_score')->values()->all(),
            'chartGroupLabels' => ['Guru', 'Tata Usaha', 'Orang Tua'],
            'chartGroupAverages' => [
                $analytics['averages']['per_group']['guru'],
                $analytics['averages']['per_group']['tata_usaha'],
                $analytics['averages']['per_group']['orang_tua'],
            ],
        ]);
    }

    private function analyticsCacheKey(): string
    {
        $lastResponseUpdate = Response::query()
            ->where('questionnaire_id', $this->questionnaire->id)
            ->max('updated_at');

        $lastAnswerUpdate = Answer::query()
            ->whereHas('response', fn ($query) => $query->where('questionnaire_id', $this->questionnaire->id))
            ->max('updated_at');

        $version = md5((string) $lastResponseUpdate.'|'.(string) $lastAnswerUpdate.'|'.(string) $this->questionnaire->updated_at);

        return 'questionnaire_analytics_'.$this->questionnaire->id.'_'.$version;
    }
}
