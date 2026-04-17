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
    public array $availableTargetGroups = [];

    /** @var array<string, string> */
    public array $targetGroupLabels = [];

    public ?string $savedMessage = null;

    public function mount(Questionnaire $questionnaire): void
    {
        $targetGroupOptions = Questionnaire::targetGroupOptions();
        $this->availableTargetGroups = array_map(
            static fn(array $item): string => (string) ($item['slug'] ?? ''),
            $targetGroupOptions
        );
        $this->targetGroupLabels = collect($targetGroupOptions)
            ->mapWithKeys(fn(array $item): array => [(string) $item['slug'] => (string) $item['name']])
            ->all();
        $defaultTargetGroup = (string) ($this->availableTargetGroups[0] ?? '');
        $this->questionnaire = $questionnaire;
        $this->authorize('update', $this->questionnaire);

        $originalSelectedTargetGroups = $this->questionnaire
            ->targets()
            ->pluck('target_group')
            ->values()
            ->all();
        $this->selectedTargetGroups = $this->normalizeSelectedTargetGroups($originalSelectedTargetGroups);

        if ($this->selectedTargetGroups === []) {
            $this->selectedTargetGroups = $defaultTargetGroup !== '' ? [$defaultTargetGroup] : [];
            $this->questionnaire->syncTargetGroups($this->selectedTargetGroups);
        } elseif ($this->selectedTargetGroups !== $originalSelectedTargetGroups) {
            $this->questionnaire->syncTargetGroups($this->selectedTargetGroups);
        }
    }

    public function updatedSelectedTargetGroups(): void
    {
        $this->selectedTargetGroups = $this->normalizeSelectedTargetGroups($this->selectedTargetGroups);

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

    /**
     * @param array<int, string> $selected
     * @return array<int, string>
     */
    private function normalizeSelectedTargetGroups(array $selected): array
    {
        $aliases = (array) config('rbac.questionnaire_target_aliases', []);

        return collect($selected)
            ->map(fn(string $slug): string => (string) ($aliases[$slug] ?? $slug))
            ->filter(fn(string $slug): bool => in_array($slug, $this->availableTargetGroups, true))
            ->unique()
            ->values()
            ->all();
    }
}
