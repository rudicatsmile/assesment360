<?php

namespace App\Livewire\Fill;

use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Services\QuestionnaireScorer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class QuestionnaireFill extends Component
{
    public Questionnaire $questionnaire;

    public Response $response;

    /** @var Collection<int, \App\Models\Question> */
    public Collection $questions;

    public int $currentIndex = 0;

    /**
     * @var array<int|string, array{answer_option_id: int|null, essay_answer: string}>
     */
    public array $answers = [];

    public bool $showSubmitConfirmation = false;

    public bool $showThankYou = false;

    public ?string $lastDraftSavedAt = null;

    /** @var array<int, bool> */
    public array $dirtyQuestionIds = [];

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException('Anda harus login untuk mengakses halaman ini.');
        }

        if ($this->questionnaire->status !== 'active') {
            throw new AccessDeniedHttpException('Kuisioner ini tidak aktif.');
        }

        $userRoleSlug = $user->roleSlug();

        $isTargeted = $this->questionnaire
            ->targets()
            ->where('target_group', $userRoleSlug)
            ->exists();

        if (!$isTargeted) {
            throw new AccessDeniedHttpException('Kuisioner ini tidak ditujukan untuk role Anda.');
        }

        $alreadySubmitted = $this->questionnaire
            ->responses()
            ->where('user_id', $user->id)
            ->where('status', 'submitted')
            ->exists();

        if ($alreadySubmitted) {
            session()->flash('error', 'Anda sudah mengirim kuisioner ini dan tidak bisa mengisi ulang.');
            $this->redirectRoute('fill.questionnaires.index', navigate: true);

            return;
        }

        $this->questions = $this->questionnaire
            ->questions()
            ->with('answerOptions')
            ->orderBy('order')
            ->get();

        foreach ($this->questions as $question) {
            $this->answers[$question->id] = [
                'answer_option_id' => null,
                'essay_answer' => '',
            ];
        }

        $this->response = Response::query()->firstOrCreate(
            [
                'questionnaire_id' => $this->questionnaire->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'draft',
                'submitted_at' => null,
            ]
        );

        $draftAnswers = $this->response
            ->answers()
            ->get()
            ->keyBy('question_id');

        foreach ($this->questions as $question) {
            $existing = $draftAnswers->get($question->id);

            if (!$existing) {
                continue;
            }

            $this->answers[$question->id] = [
                'answer_option_id' => $existing->answer_option_id,
                'essay_answer' => (string) ($existing->essay_answer ?? ''),
            ];
        }
    }

    public function previousQuestion(): void
    {
        if ($this->showThankYou) {
            return;
        }

        $this->markCurrentQuestionDirty();
        $this->currentIndex = max(0, $this->currentIndex - 1);
        $this->dispatch('queue-autosave');
    }

    public function nextQuestion(): void
    {
        if ($this->showThankYou) {
            return;
        }

        $this->markCurrentQuestionDirty();
        $this->currentIndex = min($this->lastQuestionIndex(), $this->currentIndex + 1);
        $this->dispatch('queue-autosave');
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        if ($this->showThankYou) {
            return;
        }

        $questionId = (int) explode('.', $key)[0];
        $this->dirtyQuestionIds[$questionId] = true;
    }

    public function autosaveHeartbeat(): void
    {
        // Tetap disediakan untuk kompatibilitas, tetapi autosave utama sekarang dilakukan saat navigasi.
    }

    public function goToQuestion(int $index): void
    {
        if ($this->showThankYou) {
            return;
        }

        $this->markCurrentQuestionDirty();
        $this->currentIndex = max(0, min($this->lastQuestionIndex(), $index));
        $this->dispatch('queue-autosave');
    }

    public function openSubmitConfirmation(): void
    {
        if ($this->showThankYou) {
            return;
        }

        $this->persistDraftForQuestions($this->questions->pluck('id')->map(fn($id): int => (int) $id)->all());
        $this->dirtyQuestionIds = [];

        if (!$this->validateAllRequiredQuestions()) {
            return;
        }

        $this->showSubmitConfirmation = true;
    }

    public function closeSubmitConfirmation(): void
    {
        $this->showSubmitConfirmation = false;
    }

    public function submitFinal(): void
    {
        if ($this->showThankYou) {
            return;
        }

        if (!$this->validateAllRequiredQuestions()) {
            return;
        }

        DB::transaction(function (): void {
            $this->response->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($this->questions as $question) {
                $state = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                $optionId = $this->normalizeOptionId($question, Arr::get($state, 'answer_option_id'));
                $essayAnswer = trim((string) Arr::get($state, 'essay_answer', ''));
                $essayValue = $essayAnswer !== '' ? $essayAnswer : null;

                if ($optionId === null && $essayValue === null) {
                    Answer::query()
                        ->where('response_id', $this->response->id)
                        ->where('question_id', $question->id)
                        ->delete();
                    continue;
                }

                $timestamp = now();
                Answer::query()->upsert(
                    [
                        [
                            'response_id' => $this->response->id,
                            'question_id' => $question->id,
                            'department_id' => Auth::user()?->department_id,
                            'answer_option_id' => $optionId,
                            'essay_answer' => $essayValue,
                            'calculated_score' => $this->scorer()->calculateScoreForAnswer($question, $optionId),
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]
                    ],
                    ['response_id', 'question_id'],
                    ['department_id', 'answer_option_id', 'essay_answer', 'calculated_score', 'updated_at']
                );
            }
        });

        $this->showSubmitConfirmation = false;
        $this->showThankYou = true;
    }

    public function getCurrentQuestionProperty(): ?\App\Models\Question
    {
        return $this->questions->get($this->currentIndex);
    }

    public function getAnsweredCountProperty(): int
    {
        return $this->questions->filter(function ($question): bool {
            $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];

            return match ($question->type) {
                'single_choice' => $answer['answer_option_id'] !== null,
                'essay' => trim($answer['essay_answer']) !== '',
                'combined' => $answer['answer_option_id'] !== null && trim($answer['essay_answer']) !== '',
                default => false,
            };
        })->count();
    }

    public function getProgressPercentProperty(): int
    {
        $total = max(1, $this->questions->count());

        return (int) round(($this->answeredCount / $total) * 100);
    }

    public function getRequiredQuestionCountProperty(): int
    {
        return $this->questions
            ->filter(fn($question): bool => $question->is_required || in_array($question->type, ['essay', 'combined'], true))
            ->count();
    }

    public function getAnsweredRequiredCountProperty(): int
    {
        return $this->questions
            ->filter(function ($question): bool {
                if (!$question->is_required && !in_array($question->type, ['essay', 'combined'], true)) {
                    return true;
                }

                $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                $essay = trim((string) ($answer['essay_answer'] ?? ''));

                return match ($question->type) {
                    'single_choice' => $answer['answer_option_id'] !== null,
                    'essay' => $essay !== '',
                    'combined' => $answer['answer_option_id'] !== null && $essay !== '',
                    default => false,
                };
            })
            ->count();
    }

    private function validateCurrentQuestion(): void
    {
        $question = $this->currentQuestion;

        if (!$question) {
            return;
        }

        $rules = [];
        $messages = [];
        $prefix = 'answers.' . $question->id;

        if ($question->type === 'single_choice') {
            $rules[$prefix . '.answer_option_id'] = $question->is_required
                ? ['required', 'integer']
                : ['nullable', 'integer'];
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

        if ($rules !== []) {
            $this->validate($rules, $messages);
        }
    }

    private function lastQuestionIndex(): int
    {
        return max(0, $this->questions->count() - 1);
    }

    private function validateAllRequiredQuestions(): bool
    {
        $rules = [];
        $messages = [];

        foreach ($this->questions as $question) {
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

        try {
            if ($rules !== []) {
                $this->validate($rules, $messages);
            }

            return true;
        } catch (ValidationException $exception) {
            $errorKeys = array_keys($exception->errors());
            $firstErrorKey = $errorKeys[0] ?? null;
            $questionId = $this->extractQuestionIdFromErrorKey($firstErrorKey);

            if ($questionId !== null) {
                $index = $this->questions->search(fn($question): bool => (int) $question->id === $questionId);
                if (is_int($index)) {
                    $this->currentIndex = $index;
                }
            }

            return false;
        }
    }

    private function extractQuestionIdFromErrorKey(?string $errorKey): ?int
    {
        if (!$errorKey) {
            return null;
        }

        $parts = explode('.', $errorKey);

        if (count($parts) < 3 || $parts[0] !== 'answers') {
            return null;
        }

        return is_numeric($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * @param array<int, int> $questionIds
     */
    private function persistDraftForQuestions(array $questionIds): void
    {
        if ($questionIds === []) {
            return;
        }

        $timestamp = now();
        $upsertRows = [];
        $deleteQuestionIds = [];

        foreach ($questionIds as $questionId) {
            $question = $this->questions->firstWhere('id', $questionId);

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
                'response_id' => $this->response->id,
                'question_id' => (int) $questionId,
                'department_id' => Auth::user()?->department_id,
                'answer_option_id' => $optionId,
                'essay_answer' => $essayValue,
                'calculated_score' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($upsertRows, $deleteQuestionIds): void {
            if ($upsertRows !== []) {
                Answer::query()->upsert(
                    $upsertRows,
                    ['response_id', 'question_id'],
                    ['department_id', 'answer_option_id', 'essay_answer', 'calculated_score', 'updated_at']
                );
            }

            if ($deleteQuestionIds !== []) {
                Answer::query()
                    ->where('response_id', $this->response->id)
                    ->whereIn('question_id', $deleteQuestionIds)
                    ->delete();
            }
        });

        $this->response->update([
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        $this->lastDraftSavedAt = now()->format('H:i:s');
    }

    private function markCurrentQuestionDirty(): void
    {
        $current = $this->currentQuestion;

        if (!$current) {
            return;
        }

        $this->dirtyQuestionIds[(int) $current->id] = true;
    }

    private function normalizeOptionId(\App\Models\Question $question, mixed $optionId): ?int
    {
        if (!is_numeric($optionId)) {
            return null;
        }

        $normalized = (int) $optionId;
        $exists = $question->answerOptions->contains(fn($option): bool => (int) $option->id === $normalized);

        return $exists ? $normalized : null;
    }

    private function scorer(): QuestionnaireScorer
    {
        return app(QuestionnaireScorer::class);
    }

    public function render()
    {
        $singleQuestionMode = (bool) config('features.questionnaire_single_question_mode', false);

        return view('livewire.fill.questionnaire-fill', [
            'currentQuestion' => $this->currentQuestion,
            'totalQuestions' => $this->questions->count(),
            'answeredCount' => $this->answeredCount,
            'progressPercent' => $this->progressPercent,
            'requiredQuestionCount' => $this->requiredQuestionCount,
            'answeredRequiredCount' => $this->answeredRequiredCount,
            'singleQuestionMode' => $singleQuestionMode,
        ]);
    }
}
