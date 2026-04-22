<?php

namespace App\Livewire\Admin;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class QuestionManager extends Component
{
    use AuthorizesRequests;

    public Questionnaire $questionnaire;

    public ?int $editingQuestionId = null;

    public bool $showForm = false;

    public string $question_text = '';

    public string $type = 'single_choice';

    public bool $is_required = true;

    /** @var array<int, array{id: int|null, option_text: string, score: int|null}> */
    public array $options = [];

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
        $this->authorize('update', $this->questionnaire);
        $this->resetForm();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $questionId): void
    {
        $question = $this->questionnaire->questions()->with('answerOptions')->findOrFail($questionId);

        $this->editingQuestionId = $question->id;
        $this->question_text = $question->question_text;
        $this->type = $question->type;
        $this->is_required = (bool) $question->is_required;
        $this->options = $question->answerOptions
            ->sortBy('order')
            ->values()
            ->map(fn(AnswerOption $option): array => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'score' => $option->score,
            ])
            ->all();

        if ($this->options === [] && $this->usesSelectableOptions()) {
            $this->options = [$this->blankOption(), $this->blankOption()];
        }

        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function addOption(): void
    {
        $this->options[] = $this->blankOption();
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);

        if ($this->usesSelectableOptions() && count($this->options) < 2) {
            $this->options[] = $this->blankOption();
        }
    }

    public function updatedType(): void
    {
        if ($this->usesSelectableOptions() && $this->options === []) {
            $this->options = $this->defaultOptions();
        }

        if (!$this->usesSelectableOptions()) {
            $this->options = [];
        }
    }

    public function saveQuestion(): void
    {
        $rules = $this->editingQuestionId
            ? (new UpdateQuestionRequest())->rules()
            : (new StoreQuestionRequest())->rules();

        $data = $this->validate($rules);
        $preparedOptions = $this->prepareOptions($data['options'] ?? []);

        if (in_array($data['type'], ['single_choice', 'combined'], true)) {
            if (count($preparedOptions) < 2) {
                $this->addError('options', 'Minimal harus ada 2 opsi jawaban untuk tipe ini.');

                return;
            }

            foreach ($preparedOptions as $idx => $option) {
                if ($option['score'] === null) {
                    $this->addError("options.$idx.score", 'Skor wajib diisi untuk tipe pilihan.');

                    return;
                }
            }
        }

        if ($this->editingQuestionId) {
            $question = $this->questionnaire->questions()->findOrFail($this->editingQuestionId);
            $question->update([
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'is_required' => $data['is_required'],
            ]);
        } else {
            $nextOrder = ((int) $this->questionnaire->questions()->max('order')) + 1;
            $question = $this->questionnaire->questions()->create([
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'is_required' => $data['is_required'],
                'order' => $nextOrder,
            ]);
        }

        if (!in_array($data['type'], ['single_choice', 'combined'], true)) {
            $question->answerOptions()->delete();
        } else {
            $keptIds = [];
            $departmentId = $this->questionnaire->creator?->department_id;

            foreach ($preparedOptions as $index => $optionData) {
                $option = $question->answerOptions()->updateOrCreate(
                    ['id' => $optionData['id']],
                    [
                        'department_id' => $departmentId,
                        'option_text' => $optionData['option_text'],
                        'score' => $optionData['score'],
                        'order' => $index + 1,
                    ]
                );

                $keptIds[] = $option->id;
            }

            $question->answerOptions()
                ->when($keptIds !== [], fn($query) => $query->whereNotIn('id', $keptIds))
                ->delete();
        }

        session()->flash('success', 'Pertanyaan berhasil disimpan.');
        $this->resetForm();
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = $this->questionnaire->questions()->findOrFail($questionId);
        $question->delete();

        session()->flash('success', 'Pertanyaan berhasil dihapus.');
    }

    public function moveUp(int $questionId): void
    {
        $current = $this->questionnaire->questions()->findOrFail($questionId);
        $previous = $this->questionnaire->questions()
            ->where('order', '<', $current->order)
            ->orderByDesc('order')
            ->first();

        if (!$previous) {
            return;
        }

        $this->swapOrder($current, $previous);
    }

    public function moveDown(int $questionId): void
    {
        $current = $this->questionnaire->questions()->findOrFail($questionId);
        $next = $this->questionnaire->questions()
            ->where('order', '>', $current->order)
            ->orderBy('order')
            ->first();

        if (!$next) {
            return;
        }

        $this->swapOrder($current, $next);
    }

    private function swapOrder(Question $first, Question $second): void
    {
        DB::transaction(function () use ($first, $second): void {
            $tempOrder = ((int) $this->questionnaire->questions()->max('order')) + 1000;
            $firstOrder = $first->order;
            $secondOrder = $second->order;

            $first->update(['order' => $tempOrder]);
            $second->update(['order' => $firstOrder]);
            $first->update(['order' => $secondOrder]);
        });
    }

    private function usesSelectableOptions(): bool
    {
        return in_array($this->type, ['single_choice', 'combined'], true);
    }

    /**
     * @param array<int, mixed> $rawOptions
     * @return array<int, array{id: int|null, option_text: string, score: int|null}>
     */
    private function prepareOptions(array $rawOptions): array
    {
        return collect($rawOptions)
            ->map(function ($option): array {
                $score = $option['score'] ?? null;

                return [
                    'id' => isset($option['id']) ? (int) $option['id'] : null,
                    'option_text' => trim((string) ($option['option_text'] ?? '')),
                    'score' => ($score === '' || $score === null) ? null : (int) $score,
                ];
            })
            ->filter(fn(array $option): bool => $option['option_text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{id: int|null, option_text: string, score: int|null}
     */
    private function blankOption(): array
    {
        return [
            'id' => null,
            'option_text' => '',
            'score' => null,
        ];
    }

    private function resetForm(): void
    {
        $this->editingQuestionId = null;
        $this->showForm = false;
        $this->question_text = '';
        $this->type = 'single_choice';
        $this->is_required = true;
        $this->options = $this->defaultOptions();
        $this->resetErrorBag();
    }

    /**
     * @return array<int, array{id: int|null, option_text: string, score: int|null}>
     */
    private function defaultOptions(): array
    {
        return [
            ['id' => null, 'option_text' => 'Sangat Tidak Setuju', 'score' => 1],
            ['id' => null, 'option_text' => 'Tidak Setuju',        'score' => 2],
            ['id' => null, 'option_text' => 'Netral',              'score' => 3],
            ['id' => null, 'option_text' => 'Setuju',              'score' => 4],
            ['id' => null, 'option_text' => 'Sangat Setuju',       'score' => 5],
        ];
    }

    public function render()
    {
        return view('livewire.admin.question-manager', [
            'questions' => $this->questionnaire->questions()->with('answerOptions')->orderBy('order')->get(),
        ]);
    }
}
