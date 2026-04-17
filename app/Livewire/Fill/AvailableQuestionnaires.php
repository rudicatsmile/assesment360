<?php

namespace App\Livewire\Fill;

use App\Models\Questionnaire;
use App\Models\Response;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class AvailableQuestionnaires extends Component
{
    public function render()
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
            ->whereDoesntHave('responses', function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', 'submitted');
            })
            ->withCount('questions')
            ->with([
                'responses' => fn($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'draft')
            ])
            ->orderBy('start_date')
            ->get();

        $draftHistory = Response::query()
            ->where('user_id', $user->id)
            ->where('status', 'draft')
            ->whereHas('questionnaire.targets', fn($query) => $query->whereIn('target_group', $targetGroups))
            ->with(['questionnaire:id,title,status,start_date,end_date'])
            ->latest('updated_at')
            ->get();

        $submittedHistory = Response::query()
            ->where('user_id', $user->id)
            ->where('status', 'submitted')
            ->whereHas('questionnaire.targets', fn($query) => $query->whereIn('target_group', $targetGroups))
            ->with(['questionnaire:id,title,status,start_date,end_date'])
            ->latest('submitted_at')
            ->get();

        return view('livewire.fill.available-questionnaires', [
            'questionnaires' => $questionnaires,
            'draftHistory' => $draftHistory,
            'submittedHistory' => $submittedHistory,
        ]);
    }
}
