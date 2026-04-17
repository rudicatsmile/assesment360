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
        if (! $user) {
            return [
                'available' => collect(),
                'completed' => collect(),
                'stats' => [
                    'active_questionnaires' => 0,
                    'available_to_fill' => 0,
                    'completed_total' => 0,
                ],
            ];
        }

        $roleSlug = $user->roleSlug();
        $targetAliases = (array) config('rbac.questionnaire_target_aliases', []);
        $targetGroups = array_values(array_unique(array_filter([
            $roleSlug,
            (string) ($targetAliases[$roleSlug] ?? ''),
        ])));
        if ($targetGroups === []) {
            $targetGroups = [$role];
        }

        $available = Questionnaire::query()
            ->select(['id', 'title', 'description', 'start_date', 'end_date', 'status', 'created_by'])
            ->where('status', 'active')
            ->whereHas('targets', fn($query) => $query->whereIn('target_group', $targetGroups))
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
            ->whereHas('questionnaire.targets', fn($query) => $query->whereIn('target_group', $targetGroups))
            ->with(['questionnaire:id,title,status,start_date,end_date'])
            ->latest('submitted_at')
            ->get();

        $activeCount = Questionnaire::query()
            ->where('status', 'active')
            ->whereHas('targets', fn($query) => $query->whereIn('target_group', $targetGroups))
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
