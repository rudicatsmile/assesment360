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
            fn(): array => app(QuestionnaireScorer::class)->summarizeQuestionnaire($this->questionnaire)
        );

        $roleSlugs = array_keys($analytics['respondent_breakdown']);
        $roleLabels = $analytics['role_labels'] ?? [];

        $chartGroupLabels = collect($roleSlugs)
            ->map(fn(string $slug): string => (string) ($roleLabels[$slug] ?? str($slug)->replace('_', ' ')->title()))
            ->values()
            ->all();

        $chartGroupAverages = collect($roleSlugs)
            ->map(fn(string $slug): float => (float) ($analytics['averages']['per_group'][$slug] ?? 0))
            ->values()
            ->all();

        return view('livewire.admin.questionnaire-analytics', [
            'analytics' => $analytics,
            'roleSlugs' => $roleSlugs,
            'roleLabels' => $roleLabels,
            'chartQuestionLabels' => collect($analytics['question_scores'])->pluck('question_text')->values()->all(),
            'chartQuestionAverages' => collect($analytics['question_scores'])->pluck('average_score')->values()->all(),
            'chartGroupLabels' => $chartGroupLabels,
            'chartGroupAverages' => $chartGroupAverages,
        ]);
    }

    private function analyticsCacheKey(): string
    {
        $lastResponseUpdate = Response::query()
            ->where('questionnaire_id', $this->questionnaire->id)
            ->max('updated_at');

        $lastAnswerUpdate = Answer::query()
            ->whereHas('response', fn($query) => $query->where('questionnaire_id', $this->questionnaire->id))
            ->max('updated_at');

        $version = md5((string) $lastResponseUpdate . '|' . (string) $lastAnswerUpdate . '|' . (string) $this->questionnaire->updated_at);

        return 'questionnaire_analytics_' . $this->questionnaire->id . '_' . $version;
    }
}
