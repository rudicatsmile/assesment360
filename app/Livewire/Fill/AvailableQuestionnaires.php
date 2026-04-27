<?php

namespace App\Livewire\Fill;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Services\QuestionnaireScorer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class AvailableQuestionnaires extends Component
{
    /**
     * Ordered list of fillable questionnaire IDs (excludes already submitted).
     *
     * @var list<int>
     */
    public array $questionnaireIds = [];

    /**
     * Ordered list of ALL questionnaire IDs (includes submitted).
     *
     * @var list<int>
     */
    public array $allQuestionnaireIds = [];

    /** @var array<int, array{status: string, response_id: int|null, title: string, description: string, start_date: string|null, end_date: string|null, questions_count: int, target_label: string}> */
    public array $questionnaireMeta = [];

    /** @var array<int|string, array{answer_option_id: int|null, essay_answer: string}> */
    public array $answers = [];

    public bool $confirmSubmitAll = false;

    public ?string $lastDraftSavedAt = null;

    /** @var array<int, bool> */
    public array $dirtyQuestionIds = [];

    /** Whether the form is locked due to time expiry */
    public bool $timeExpired = false;

    /** Whether to show the start confirmation popup before beginning */
    public bool $showStartConfirmation = false;

    /** Timestamp when the fill session started (from user.filling_started_at) */
    public ?string $fillingStartedAt = null;

    /** Total time limit in minutes (from user.time_limit_minutes) */
    public ?int $timeLimitMinutes = null;

    /** 0-based index into questionnaireIds (the fillable list) */
    public int $currentIndex = 0;

    public function mount(): void
    {
        $this->loadQuestionnaires();
        $this->initializeSessionTimer();
        $this->checkTimeExpiry();
    }

    public function render()
    {
        $currentId = $this->questionnaireIds[$this->currentIndex] ?? null;
        $currentMeta = $currentId !== null ? ($this->questionnaireMeta[$currentId] ?? null) : null;
        $currentQuestions = collect();

        if ($currentId !== null && $currentMeta !== null && $currentMeta['status'] !== 'submitted' && !$this->timeExpired) {
            $currentQuestions = Question::where('questionnaire_id', $currentId)
                ->with('answerOptions')
                ->orderBy('order')
                ->get();
        }

        // Overall progress across ALL fillable questionnaires
        $totalQuestions = 0;
        $answeredCount = 0;
        $requiredQuestionCount = 0;
        $answeredRequiredCount = 0;

        foreach ($this->questionnaireMeta as $qId => $meta) {
            if ($meta['status'] === 'submitted') {
                continue;
            }

            $questions = Question::where('questionnaire_id', $qId)->orderBy('order')->get();

            foreach ($questions as $question) {
                $totalQuestions++;
                $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                $isRequired = $question->is_required;

                $isAnswered = match ($question->type) {
                    'single_choice' => $answer['answer_option_id'] !== null,
                    'essay' => trim($answer['essay_answer'] ?? '') !== '',
                    'combined' => $answer['answer_option_id'] !== null,
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

        // Check if ALL required questions in the CURRENT questionnaire are answered
        $currentQuestionnaireComplete = false;
        if ($currentId !== null && $currentQuestions->count() > 0) {
            $currentComplete = true;
            foreach ($currentQuestions as $question) {
                $isRequired = $question->is_required;
                if (!$isRequired) continue;

                $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
                $isAnswered = match ($question->type) {
                    'single_choice' => $answer['answer_option_id'] !== null,
                    'essay' => trim($answer['essay_answer'] ?? '') !== '',
                    'combined' => $answer['answer_option_id'] !== null,
                    default => false,
                };

                if (!$isAnswered) {
                    $currentComplete = false;
                    break;
                }
            }
            $currentQuestionnaireComplete = $currentComplete;
        }

        $totalFillable = count($this->questionnaireIds);
        $submittedCount = count($this->allQuestionnaireIds) - $totalFillable;
        $isLast = $this->currentIndex >= $totalFillable - 1;

        // Build session-based time limit info (from user-level settings)
        // Do NOT build timeLimitInfo when start confirmation popup is showing –
        // the timer must not tick until the user explicitly clicks "Mulai Sekarang".
        $timeLimitInfo = null;
        if (!$this->showStartConfirmation && $this->timeLimitMinutes !== null && $this->fillingStartedAt !== null) {
            $deadline = CarbonImmutable::parse($this->fillingStartedAt)->addMinutes($this->timeLimitMinutes);
            $remainingSeconds = max(0, (int) now()->diffInSeconds($deadline, false));
            $timeLimitInfo = [
                'deadline' => $deadline->toIso8601String(),
                'remaining_seconds' => $remainingSeconds,
                'expired' => $remainingSeconds <= 0,
                'time_limit_minutes' => $this->timeLimitMinutes,
            ];
        }

        return view('livewire.fill.available-questionnaires', [
            'currentId' => $currentId,
            'currentMeta' => $currentMeta,
            'currentQuestions' => $currentQuestions,
            'totalFillable' => $totalFillable,
            'isLast' => $isLast,
            'totalQuestions' => $totalQuestions,
            'answeredCount' => $answeredCount,
            'progressPercent' => $progressPercent,
            'requiredQuestionCount' => $requiredQuestionCount,
            'answeredRequiredCount' => $answeredRequiredCount,
            'submittedCount' => $submittedCount,
            'timeLimitInfo' => $timeLimitInfo,
            'currentQuestionnaireComplete' => $currentQuestionnaireComplete,
        ]);
    }

    public function nextQuestionnaire(): void
    {
        if (!empty($this->dirtyQuestionIds)) {
            $this->persistAllDrafts();
        }

        $currentId = $this->questionnaireIds[$this->currentIndex] ?? null;
        if ($currentId !== null && !$this->isQuestionnaireComplete($currentId)) {
            return;
        }

        $max = count($this->questionnaireIds) - 1;
        if ($this->currentIndex < $max) {
            $this->currentIndex++;
            $this->dispatch('questionnaire-changed');
        }
    }

    private function isQuestionnaireComplete(int $questionnaireId): bool
    {
        $questions = Question::where('questionnaire_id', $questionnaireId)->orderBy('order')->get();
        foreach ($questions as $question) {
            $isRequired = $question->is_required;
            if (!$isRequired) {
                continue;
            }

            $answer = $this->answers[$question->id] ?? ['answer_option_id' => null, 'essay_answer' => ''];
            $isAnswered = match ($question->type) {
                'single_choice' => $answer['answer_option_id'] !== null,
                'essay' => trim($answer['essay_answer'] ?? '') !== '',
                'combined' => $answer['answer_option_id'] !== null,
                default => false,
            };

            if (!$isAnswered) {
                return false;
            }
        }
        return true;
    }

    public function previousQuestionnaire(): void
    {
        if (!empty($this->dirtyQuestionIds)) {
            $this->persistAllDrafts();
        }

        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
    }

    public function goToQuestionnaire(int $index): void
    {
        if (!empty($this->dirtyQuestionIds)) {
            $this->persistAllDrafts();
        }

        $max = count($this->questionnaireIds) - 1;
        $this->currentIndex = max(0, min($index, $max));
    }

    public function updatedAnswers(mixed $value, string $key): void
    {
        if ($this->timeExpired) {
            return;
        }

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
        $this->doSubmitAll(false);
    }

    /**
     * Called by client-side timer when time expires.
     * Auto-submits whatever answers have been filled so far.
     */
    public function autoSubmitOnTimeExpired(): void
    {
        if ($this->timeExpired) {
            return;
        }

        $this->doSubmitAll(true);
    }

    private function loadQuestionnaires(): void
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
        $this->questionnaireMeta = [];
        $this->questionnaireIds = [];
        $this->allQuestionnaireIds = [];

        foreach ($questionnaires as $questionnaire) {
            $matchedTarget = $questionnaire->targets
                ->whereIn('target_group', $targetGroups)
                ->first()?->target_group ?? 'other';

            $targetLabel = $roleLabels[$matchedTarget] ?? ucfirst(str_replace('_', ' ', $matchedTarget));

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

            $this->allQuestionnaireIds[] = $questionnaire->id;

            if ($status !== 'submitted') {
                $this->questionnaireIds[] = $questionnaire->id;
            }

            $this->questionnaireMeta[$questionnaire->id] = [
                'status' => $status,
                'response_id' => $responseId,
                'title' => $questionnaire->title,
                'description' => $questionnaire->description ?? '',
                'start_date' => $questionnaire->start_date?->format('d M Y H:i'),
                'end_date' => $questionnaire->end_date?->format('d M Y H:i'),
                'questions_count' => $questionnaire->questions_count,
                'target_label' => $targetLabel,
            ];
        }

        $this->currentIndex = 0;
        $this->loadAllAnswers();
    }

    private function loadAllAnswers(): void
    {
        $this->answers = [];

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

        // Get all question IDs grouped by their questionnaire
        $allQuestions = Question::whereIn('id', array_keys($this->dirtyQuestionIds))
            ->select(['id', 'questionnaire_id'])
            ->get()
            ->groupBy('questionnaire_id');

        foreach ($allQuestions as $questionnaireId => $questions) {
            $questionIds = $questions->pluck('id')->map(fn($id) => (int) $id)->all();
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

                if ($question->type === 'essay' && $question->is_required) {
                    $rules[$prefix . '.essay_answer'] = ['required', 'string', 'min:3', 'max:2000'];
                    $messages[$prefix . '.essay_answer.required'] = 'Jawaban esai wajib diisi.';
                }

                if ($question->type === 'combined' && $question->is_required) {
                    $rules[$prefix . '.answer_option_id'] = ['required', 'integer'];
                    $messages[$prefix . '.answer_option_id.required'] = 'Pilih salah satu opsi jawaban.';
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

    /**
     * Initialize the session timer from the authenticated user's time_limit_minutes.
     * Sets filling_started_at on the user record if not already set.
     */
    private function initializeSessionTimer(): void
    {
        $user = Auth::user();
        $this->timeLimitMinutes = $user?->time_limit_minutes;
        $this->fillingStartedAt = $user?->filling_started_at?->toIso8601String();

        // If user has a time limit and hasn't started yet, show the start confirmation popup
        // instead of auto-starting the timer. The timer only starts when they confirm.
        if ($this->timeLimitMinutes !== null && $this->fillingStartedAt === null) {
            $this->showStartConfirmation = true;
        }
    }

    /**
     * User confirmed to start the fill session – start the timer.
     */
    public function confirmStart(): void
    {
        $user = Auth::user();
        $user->update(['filling_started_at' => now()]);
        $user->refresh();
        $this->fillingStartedAt = $user->filling_started_at?->toIso8601String();
        $this->showStartConfirmation = false;

        // Dispatch browser event with remaining seconds so Alpine.js timer starts
        // reliably without depending on DOM hidden-input state after morph.
        $deadline = CarbonImmutable::parse($this->fillingStartedAt)->addMinutes($this->timeLimitMinutes);
        $remainingSeconds = max(0, (int) now()->diffInSeconds($deadline, false));
        $this->dispatch('start-timer', remainingSeconds: $remainingSeconds);
    }

    /**
     * User cancelled – redirect back to role-appropriate dashboard.
     */
    public function cancelStart()
    {
        return redirect()->to('/fill/dashboard/guru');
    }

    /**
     * Check if the session time limit has expired.
     * If so, auto-submit all answers and lock the form.
     */
    private function checkTimeExpiry(): void
    {
        if ($this->timeLimitMinutes === null || $this->fillingStartedAt === null) {
            return;
        }

        $deadline = CarbonImmutable::parse($this->fillingStartedAt)->addMinutes($this->timeLimitMinutes);

        if (now()->isAfter($deadline)) {
            $this->timeExpired = true;
            $this->doSubmitAll(true);
        }
    }

    /**
     * Shared submit logic for both manual and auto-submit.
     *
     * @param bool $isAutoSubmit When true, skips validation and forces submit of whatever is filled
     */
    private function doSubmitAll(bool $isAutoSubmit): void
    {
        if (!$isAutoSubmit) {
            $this->validateAllRequired();
        }

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
        $this->questionnaireIds = [];

        if ($isAutoSubmit) {
            $this->timeExpired = true;
            session()->flash('error', 'Batas waktu pengisian kuisioner telah habis. Jawaban yang sudah diisi telah dikirim secara otomatis.');
        } else {
            $this->timeExpired = true;
            $count = count($this->allQuestionnaireIds);
            session()->flash('success', "Semua {$count} kuisioner berhasil dikirim!");
        }
    }
}
