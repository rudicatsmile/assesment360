<?php

namespace App\Livewire\Admin;

use App\Http\Requests\StoreQuestionnaireRequest;
use App\Http\Requests\UpdateQuestionnaireRequest;
use App\Models\Questionnaire;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.admin')]
class QuestionnaireForm extends Component
{
    use AuthorizesRequests;

    public ?Questionnaire $questionnaire = null;

    public string $title = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $status = 'draft';

    public ?int $time_limit_minutes = null;

    /** @var array<int, string> */
    public array $target_groups = [];

    /** @var array<int, array{slug:string, name:string}> */
    public array $availableTargetGroups = [];

    /** @var array<string, string> */
    public array $targetGroupLabels = [];

    public function mount(?Questionnaire $questionnaire = null): void
    {
        $this->availableTargetGroups = Questionnaire::targetGroupOptions();
        $this->targetGroupLabels = collect($this->availableTargetGroups)
            ->mapWithKeys(fn(array $group): array => [(string) $group['slug'] => (string) $group['name']])
            ->all();
        $defaultTargetGroup = (string) ($this->availableTargetGroups[0]['slug'] ?? '');
        $this->target_groups = $defaultTargetGroup !== '' ? [$defaultTargetGroup] : [];
        $this->questionnaire = $questionnaire;

        if ($this->questionnaire) {
            $this->authorize('update', $this->questionnaire);

            $this->title = $this->questionnaire->title;
            $this->description = (string) $this->questionnaire->description;
            $this->start_date = $this->questionnaire->start_date?->format('Y-m-d\TH:i') ?? '';
            $this->end_date = $this->questionnaire->end_date?->format('Y-m-d\TH:i') ?? '';
            $this->status = $this->questionnaire->status;
            $this->time_limit_minutes = $this->questionnaire->time_limit_minutes;
            $this->target_groups = $this->questionnaire
                ->targets()
                ->pluck('target_group')
                ->values()
                ->all();

            if ($this->target_groups === []) {
                $fallback = $defaultTargetGroup !== '' ? [$defaultTargetGroup] : [];
                $this->questionnaire->syncTargetGroups($fallback);
                $this->target_groups = $fallback;
            }
        } else {
            $this->authorize('create', Questionnaire::class);
        }
    }

    public function save(): void
    {
        if ($this->questionnaire) {
            $data = $this->validate((new UpdateQuestionnaireRequest())->rules());

            $this->questionnaire->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'status' => $data['status'],
            ]);

            $questionnaire = $this->questionnaire;
            $data['target_groups'] = $questionnaire->targets()->pluck('target_group')->values()->all();
        } else {
            $data = $this->validate((new StoreQuestionnaireRequest())->rules());

            $questionnaire = Questionnaire::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'status' => $data['status'],
                'created_by' => Auth::id(),
            ]);
        }

        $questionnaire->syncTargetGroups($data['target_groups']);

        session()->flash('success', 'Kuisioner berhasil disimpan.');

        $this->redirectRoute('admin.questionnaires.edit', ['questionnaire' => $questionnaire->id], navigate: true);
    }

    #[On('target-groups-updated')]
    public function refreshTargetGroups(): void
    {
        if (!$this->questionnaire) {
            return;
        }

        $this->target_groups = $this->questionnaire
            ->targets()
            ->pluck('target_group')
            ->values()
            ->all();
    }

    public function getIsEditProperty(): bool
    {
        return $this->questionnaire !== null;
    }

    public function render()
    {
        return view('livewire.admin.questionnaire-form');
    }
}
