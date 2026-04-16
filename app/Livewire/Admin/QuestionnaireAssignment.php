<?php

namespace App\Livewire\Admin;

use App\Models\Questionnaire;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class QuestionnaireAssignment extends Component
{
    use AuthorizesRequests;

    public Questionnaire $questionnaire;

    /** @var array<int, string> */
    public array $selectedTargetGroups = [];

    /** @var array<int, string> */
    public array $availableTargetGroups = ['guru', 'tata_usaha', 'orang_tua'];

    public ?string $savedMessage = null;

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
        $this->authorize('update', $this->questionnaire);

        $this->selectedTargetGroups = $this->questionnaire
            ->targets()
            ->pluck('target_group')
            ->values()
            ->all();

        if ($this->selectedTargetGroups === []) {
            $this->selectedTargetGroups = ['guru'];
            $this->questionnaire->syncTargetGroups($this->selectedTargetGroups);
        }
    }

    public function updatedSelectedTargetGroups(): void
    {
        $this->validate([
            'selectedTargetGroups' => ['required', 'array', 'min:1'],
            'selectedTargetGroups.*' => ['required', 'string', 'distinct', Rule::in($this->availableTargetGroups)],
        ]);

        $this->questionnaire->syncTargetGroups($this->selectedTargetGroups);
        $this->savedMessage = 'Target group berhasil diperbarui.';
        $this->dispatch('target-groups-updated');
    }

    public function render()
    {
        return view('livewire.admin.questionnaire-assignment');
    }
}
