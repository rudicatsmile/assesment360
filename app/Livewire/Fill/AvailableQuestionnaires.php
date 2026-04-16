<?php

namespace App\Livewire\Fill;

use App\Models\Questionnaire;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class AvailableQuestionnaires extends Component
{
    public function render()
    {
        $user = Auth::user();

        $questionnaires = Questionnaire::query()
            ->where('status', 'active')
            ->whereHas('targets', fn ($query) => $query->where('target_group', $user->role))
            ->whereDoesntHave('responses', function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', 'submitted');
            })
            ->withCount('questions')
            ->with(['responses' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'draft')])
            ->orderBy('start_date')
            ->get();

        return view('livewire.fill.available-questionnaires', [
            'questionnaires' => $questionnaires,
        ]);
    }
}
