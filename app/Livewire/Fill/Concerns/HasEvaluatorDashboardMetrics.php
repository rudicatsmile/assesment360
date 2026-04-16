<?php

namespace App\Livewire\Fill\Concerns;

use App\Models\Questionnaire;
use App\Models\Response;
use Illuminate\Support\Facades\Auth;

trait HasEvaluatorDashboardMetrics
{
    protected function getDashboardMetricsByRole(string $role): array
    {
        $user = Auth::user();

        $available = Questionnaire::query()
            ->select(['id', 'title', 'description', 'start_date', 'end_date', 'status', 'created_by'])
            ->where('status', 'active')
            ->whereHas('targets', fn($query) => $query->where('target_group', $role))
            ->whereDoesntHave('responses', function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', 'submitted');
            })
            ->withCount('questions')
            ->orderBy('start_date')
            ->get();

        $completed = Response::query()
            ->where('user_id', $user->id)
            ->where('status', 'submitted')
            ->whereHas('questionnaire.targets', fn($query) => $query->where('target_group', $role))
            ->with(['questionnaire:id,title,status,start_date,end_date'])
            ->latest('submitted_at')
            ->get();

        $activeCount = Questionnaire::query()
            ->where('status', 'active')
            ->whereHas('targets', fn($query) => $query->where('target_group', $role))
            ->count();

        return [
            'available' => $available,
            'completed' => $completed,
            'stats' => [
                'active_questionnaires' => $activeCount,
                'available_to_fill' => $available->count(),
                'completed_total' => $completed->count(),
            ],
        ];
    }
}
