<?php

namespace App\Livewire\Admin;

use App\Models\Questionnaire;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class QuestionnaireList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $targetGroup = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Questionnaire::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingTargetGroup(): void
    {
        $this->resetPage();
    }

    public function publish(int $questionnaireId): void
    {
        $questionnaire = Questionnaire::query()->findOrFail($questionnaireId);
        $this->authorize('publish', $questionnaire);

        $questionnaire->update(['status' => 'active']);
    }

    public function close(int $questionnaireId): void
    {
        $questionnaire = Questionnaire::query()->findOrFail($questionnaireId);
        $this->authorize('close', $questionnaire);

        $questionnaire->update(['status' => 'closed']);
    }

    public function delete(int $questionnaireId): void
    {
        $questionnaire = Questionnaire::query()->findOrFail($questionnaireId);
        $this->authorize('delete', $questionnaire);

        $questionnaire->delete();
        $this->resetPage();
    }

    public function render()
    {
        $questionnaires = Questionnaire::query()
            ->with(['targets'])
            ->withCount(['questions', 'responses'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery
                        ->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', fn($query) => $query->where('status', $this->status))
            ->when($this->targetGroup !== '', fn($query) => $query->whereHas('targets', fn($q) => $q->where('target_group', $this->targetGroup)))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.questionnaire-list', [
            'questionnaires' => $questionnaires,
            'targetGroupOptions' => Questionnaire::targetGroupOptions(),
        ]);
    }
}
