<?php

namespace App\Livewire\Fill;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Services\QuestionnaireScorer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class AvailableQuestionnaires extends Component
{
    /** @var array<string, array{label: string, slug: string, questionnaire_ids: list<int>}> */
    public array $groups = [];

    /** @var array<int, array{status: string, response_id: int|null, title: string, description: string, start_date: string|null, end_date: string|null, questions_count: int}> */
    public array $questionnaireMeta = [];

    /** @var array<int|string, array{answer_option_id: int|null, essay_answer: string}> */
    public array $answers = [];

    public bool $confirmSubmitAll = false;

    public ?string $lastDraftSavedAt = null;

    /** @var array<int, bool> */
    public array $dirtyQuestionIds = [];

    /** @var array<int, int> questionId => questionnaireId mapping */
    public array $questionToQuestionnaire = [];

    public function mount(): void
    {
        $this->loadGroupedQuestionnaires();
    }

    public function render()
    {
        $allQuestions = $this->getAllQuestionsGrouped();

        $totalQuestions = 0;
        $answeredCount = 0;
        $requiredQuestionCount = 0;
        $answeredRequiredCount = 0;

        foreach ($allQuestions as $questionnaireId => $questions) {
            $meta = $this->questionnaireMeta[$questionnaireId] ?? null;
            if (!$meta || $meta['status'] === 'submitted') {
                continue;
            }

            foreach ($questions as $question) {
                $totalQuestions++;
                $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                $isRequired = $question->is_required || in_array($question->type, ['essay', 'combined'], true);

                $isAnswered = match ($question->type) {
                    'single_choice' => $answer['answer_option_id'] !== null,
                    'essay' => trim($answer['essay_answer'] ?? '') !== '',
                    'combined' => $answer['answer_option_id'] !== null && trim($answer['essay_answer'] ?? '') !== '',
                    default => false,
                };

                if ($isAnswered) {
                    $answeredCount++;
                }

                if ($isRequired) {
                    $requiredQuestionCount++;
                    if ($isAnswered) {
                        $answeredRequiredCount++;
                    }
                }
            }
        }

        $progressPercent = $totalQuestions > 0
            ? (int) round(($answeredCount / $totalQuestions) * 100)
            : 0;

        $fillableQuestionnaireCount = collect($this->questionnaireMeta)
            ->filter(fn(array $meta): bool => $meta['status'] !== 'submitted')
            ->count();

        $submittedCount = collect($this->questionnaireMeta)
            ->filter(fn(array $meta): bool => $meta['status'] === 'submitted')
            ->count();

        return view('livewire.fill.available-questionnaires', [
            'allQuestions' => $allQuestions,
            'totalQuestions' => $totalQuestions,
            'answeredCount' => $answeredCount,
            'progressPercent' => $progressPercent,
            'requiredQuestionCount' => $requiredQuestionCount,
            'answeredRequiredCount' => $answeredRequiredCount,
            'fillableQuestionnaireCount' => $fillableQuestionnaireCount,
            'submittedCount' => $submittedCount,
        ]);
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        $questionId = (int) explode('.', $key)[0];
        $this->dirtyQuestionIds[$questionId] = true;
    }

    public function saveAllDrafts(): void
    {
        $this->persistAllDrafts();
    }

    public function openSubmitAllConfirmation(): void
    {
        $this->persistAllDrafts();
        $this->validateAllRequired();
        $this->confirmSubmitAll = true;
    }

    public function closeSubmitAllConfirmation(): void
    {
        $this->confirmSubmitAll = false;
    }

    public function submitAllFinal(): void
    {
        $this->validateAllRequired();

        $user = Auth::user();
        $scorer = app(QuestionnaireScorer::class);
        $timestamp = now();

        foreach ($this->questionnaireMeta as $questionnaireId => $meta) {
            if ($meta['status'] === 'submitted') {
                continue;
            }

            $questions = Question::where('questionnaire_id', $questionnaireId)
                ->with('answerOptions')
                ->orderBy('order')
                ->get();

            $responseId = $meta['response_id'] ?? null;

            if (!$responseId) {
                $response = Response::create([
                    'questionnaire_id' => $questionnaireId,
                    'user_id' => $user->id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
                $responseId = $response->id;
            } else {
                Response::where('id', $responseId)->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            DB::transaction(function () use ($questions, $responseId, $scorer, $timestamp, $user): void {
                foreach ($questions as $question) {
                    $state = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                    $optionId = $this->normalizeOptionId($question, Arr::get($state, 'answer_option_id'));
                    $essayAnswer = trim((string) Arr::get($state, 'essay_answer', ''));
                    $essayValue = $essayAnswer !== '' ? $essayAnswer : null;

                    if ($optionId === null && $essayValue === null) {
                        Answer::where('response_id', $responseId)
                            ->where('question_id', $question->id)
                            ->delete();

                        continue;
                    }

                    Answer::upsert(
                        [[
                            'response_id' => $responseId,
                            'question_id' => $question->id,
                            'department_id' => $user?->department_id,
                            'answer_option_id' => $optionId,
                            'essay_answer' => $essayValue,
                            'calculated_score' => $scorer->calculateScoreForAnswer($question, $optionId),
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]],
                        ['response_id', 'question_id'],
                        ['department_id', 'answer_option_id', 'essay_answer', 'calculated_score', 'updated_at']
                    );
                }
            });

            $this->questionnaireMeta[$questionnaireId]['status'] = 'submitted';
            $this->questionnaireMeta[$questionnaireId]['response_id'] = $responseId;
        }

        $this->confirmSubmitAll = false;
        $this->dirtyQuestionIds = [];

        $count = collect($this->questionnaireMeta)
            ->filter(fn(array $meta): bool => $meta['status'] === 'submitted')
            ->count();

        session()->flash('success', "Semua {$count} kuisioner berhasil dikirim!");
    }

    /**
     * @return array<int, \Illuminate\Database\Eloquent\Collection<int, Question>>
     */
    private function getAllQuestionsGrouped(): array
    {
        $fillableIds = collect($this->questionnaireMeta)
            ->filter(fn(array $meta): bool => $meta['status'] !== 'submitted')
            ->keys()
            ->all();

        if ($fillableIds === []) {
            return [];
        }

        $questions = Question::whereIn('questionnaire_id', $fillableIds)
            ->with('answerOptions')
            ->orderBy('order')
            ->get()
            ->groupBy('questionnaire_id');

        return $questions->all();
    }

    private function loadGroupedQuestionnaires(): void
    {
        $user = Auth::user();
        $roleSlug = $user?->roleSlug() ?? '';
        $targetAliases = (array) config('rbac.questionnaire_target_aliases', []);
        $targetGroups = array_values(array_unique(array_filter([
            $roleSlug,
            (string) ($targetAliases[$roleSlug] ?? ''),
        ])));

        $questionnaires = Questionnaire::query()
            ->where('status', 'active')
            ->whereHas('targets', fn($query) => $query->whereIn('target_group', $targetGroups))
            ->with(['targets'])
            ->withCount('questions')
            ->orderBy('start_date')
            ->get();

        $roleLabels = (array) config('rbac.role_labels', []);
        $this->groups = [];
        $this->questionnaireMeta = [];

        foreach ($questionnaires as $questionnaire) {
            $matchedTarget = $questionnaire->targets
                ->whereIn('target_group', $targetGroups)
                ->first()?->target_group ?? 'other';

            if (!isset($this->groups[$matchedTarget])) {
                $label = $roleLabels[$matchedTarget] ?? ucfirst(str_replace('_', ' ', $matchedTarget));
                $this->groups[$matchedTarget] = [
                    'label' => $label,
                    'slug' => $matchedTarget,
                    'questionnaire_ids' => [],
                ];
            }

            $this->groups[$matchedTarget]['questionnaire_ids'][] = $questionnaire->id;

            $response = Response::query()
                ->where('questionnaire_id', $questionnaire->id)
                ->where('user_id', $user->id)
                ->first();

            $status = 'not_started';
            $responseId = null;

            if ($response) {
                $status = $response->status === 'submitted' ? 'submitted' : 'draft';
                $responseId = $response->id;
            }

            $this->questionnaireMeta[$questionnaire->id] = [
                'status' => $status,
                'response_id' => $responseId,
                'title' => $questionnaire->title,
                'description' => $questionnaire->description ?? '',
                'start_date' => $questionnaire->start_date?->format('d M Y H:i'),
                'end_date' => $questionnaire->end_date?->format('d M Y H:i'),
                'questions_count' => $questionnaire->questions_count,
            ];
        }

        $this->loadAllAnswers();
    }

    private function loadAllAnswers(): void
    {
        $this->answers = [];
        $this->questionToQuestionnaire = [];

        foreach ($this->questionnaireMeta as $questionnaireId => $meta) {
            if ($meta['status'] === 'submitted') {
                continue;
            }

            $questions = Question::where('questionnaire_id', $questionnaireId)
                ->orderBy('order')
                ->get();

            foreach ($questions as $question) {
                $this->answers[$question->id] = [
                    'answer_option_id' => null,
                    'essay_answer' => '',
                ];
                $this->questionToQuestionnaire[$question->id] = $questionnaireId;
            }

            $responseId = $meta['response_id'] ?? null;

            if ($responseId) {
                $draftAnswers = Answer::where('response_id', $responseId)->get()->keyBy('question_id');

                foreach ($questions as $question) {
                    $existing = $draftAnswers->get($question->id);

                    if ($existing) {
                        $this->answers[$question->id] = [
                            'answer_option_id' => $existing->answer_option_id,
                            'essay_answer' => (string) ($existing->essay_answer ?? ''),
                        ];
                    }
                }
            }
        }
    }

    private function persistAllDrafts(): void
    {
        if (empty($this->dirtyQuestionIds)) {
            return;
        }

        $user = Auth::user();

        // Group dirty question IDs by their questionnaire
        $dirtyByQuestionnaire = [];
        foreach ($this->dirtyQuestionIds as $questionId => $isDirty) {
            if (!$isDirty) {
                continue;
            }

            $qId = $this->questionToQuestionnaire[$questionId] ?? null;
            if ($qId === null) {
                continue;
            }

            $dirtyByQuestionnaire[$qId][] = (int) $questionId;
        }

        foreach ($dirtyByQuestionnaire as $questionnaireId => $questionIds) {
            $this->persistDraftForQuestions($questionnaireId, $questionIds, $user);
        }

        $this->dirtyQuestionIds = [];
        $this->lastDraftSavedAt = now()->format('H:i:s');
    }

    /**
     * @param array<int, int> $questionIds
     */
    private function persistDraftForQuestions(int $questionnaireId, array $questionIds, $user): void
    {
        $questions = Question::where('questionnaire_id', $questionnaireId)
            ->with('answerOptions')
            ->orderBy('order')
            ->get();

        $responseId = $this->questionnaireMeta[$questionnaireId]['response_id'] ?? null;

        if (!$responseId) {
            $response = Response::create([
                'questionnaire_id' => $questionnaireId,
                'user_id' => $user->id,
                'status' => 'draft',
                'submitted_at' => null,
            ]);
            $responseId = $response->id;
            $this->questionnaireMeta[$questionnaireId]['response_id'] = $responseId;
        }

        $timestamp = now();
        $upsertRows = [];
        $deleteQuestionIds = [];

        foreach ($questionIds as $questionId) {
            $question = $questions->firstWhere('id', $questionId);

            if (!$question) {
                continue;
            }

            $state = $this->answers[$questionId] ?? ['answer_option_id' => null, 'essay_answer' => ''];
            $optionId = $this->normalizeOptionId($question, Arr::get($state, 'answer_option_id'));
            $essayAnswer = trim((string) Arr::get($state, 'essay_answer', ''));
            $essayValue = $essayAnswer !== '' ? $essayAnswer : null;

            if ($optionId === null && $essayValue === null) {
                $deleteQuestionIds[] = (int) $questionId;

                continue;
            }

            $upsertRows[] = [
                'response_id' => $responseId,
                'question_id' => (int) $questionId,
                'department_id' => $user?->department_id,
                'answer_option_id' => $optionId,
                'essay_answer' => $essayValue,
                'calculated_score' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($upsertRows, $deleteQuestionIds, $responseId): void {
            if (!empty($upsertRows)) {
                Answer::upsert(
                    $upsertRows,
                    ['response_id', 'question_id'],
                    ['department_id', 'answer_option_id', 'essay_answer', 'calculated_score', 'updated_at']
                );
            }

            if (!empty($deleteQuestionIds)) {
                Answer::where('response_id', $responseId)
                    ->whereIn('question_id', $deleteQuestionIds)
                    ->delete();
            }
        });

        Response::where('id', $responseId)->update([
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        $this->questionnaireMeta[$questionnaireId]['status'] = 'draft';
    }

    private function validateAllRequired(): void
    {
        $rules = [];
        $messages = [];

        foreach ($this->questionnaireMeta as $questionnaireId => $meta) {
            if ($meta['status'] === 'submitted') {
                continue;
            }

            $questions = Question::where('questionnaire_id', $questionnaireId)
                ->with('answerOptions')
                ->orderBy('order')
                ->get();

            foreach ($questions as $question) {
                $prefix = 'answers.' . $question->id;

                if ($question->type === 'single_choice' && $question->is_required) {
                    $rules[$prefix . '.answer_option_id'] = ['required', 'integer'];
                    $messages[$prefix . '.answer_option_id.required'] = 'Pilih salah satu opsi jawaban.';
                }

                if ($question->type === 'essay') {
                    $rules[$prefix . '.essay_answer'] = ['required', 'string', 'min:3', 'max:2000'];
                    $messages[$prefix . '.essay_answer.required'] = 'Jawaban esai wajib diisi.';
                }

                if ($question->type === 'combined') {
                    $rules[$prefix . '.answer_option_id'] = ['required', 'integer'];
                    $rules[$prefix . '.essay_answer'] = ['required', 'string', 'min:3', 'max:2000'];
                    $messages[$prefix . '.answer_option_id.required'] = 'Pilih salah satu opsi jawaban.';
                    $messages[$prefix . '.essay_answer.required'] = 'Alasan/esai wajib diisi untuk tipe combined.';
                }
            }
        }

        if ($rules !== []) {
            $this->validate($rules, $messages);
        }
    }

    private function normalizeOptionId(Question $question, mixed $optionId): ?int
    {
        if (!is_numeric($optionId)) {
            return null;
        }

        $normalized = (int) $optionId;
        $exists = $question->answerOptions->contains(fn($option): bool => (int) $option->id === $normalized);

        return $exists ? $normalized : null;
    }
}
