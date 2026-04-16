<?php

namespace App\Livewire\Admin;

use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class AdminDashboard extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', Questionnaire::class);
    }

    public function render()
    {
        $metrics = Cache::remember('admin_dashboard_overview_v1', now()->addMinutes(5), function (): array {
            $roles = ['guru', 'tata_usaha', 'orang_tua'];

            $activeQuestionnaires = Questionnaire::query()
                ->where('status', 'active')
                ->with(['targets:id,questionnaire_id,target_group'])
                ->get();

            $userCountByRole = User::query()
                ->whereIn('role', $roles)
                ->selectRaw('role, COUNT(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role');

            $totalTargetSlots = $activeQuestionnaires->sum(function (Questionnaire $questionnaire) use ($userCountByRole): int {
                return $questionnaire->targets
                    ->unique('target_group')
                    ->sum(fn ($target): int => (int) ($userCountByRole[$target->target_group] ?? 0));
            });

            $totalSubmittedActiveResponses = Response::query()
                ->where('status', 'submitted')
                ->whereHas('questionnaire', fn ($query) => $query->where('status', 'active'))
                ->count();

            $participationRate = $totalTargetSlots > 0
                ? round(($totalSubmittedActiveResponses / $totalTargetSlots) * 100, 2)
                : 0.0;

            $totalRespondentUsers = Response::query()
                ->where('status', 'submitted')
                ->distinct('user_id')
                ->count('user_id');

            $averageOverallScore = (float) Answer::query()
                ->whereNotNull('calculated_score')
                ->whereHas('response', fn ($query) => $query->where('status', 'submitted'))
                ->avg('calculated_score');

            $breakdown = Response::query()
                ->join('users', 'users.id', '=', 'responses.user_id')
                ->whereNull('users.deleted_at')
                ->where('responses.status', 'submitted')
                ->whereIn('users.role', $roles)
                ->selectRaw('users.role, COUNT(DISTINCT responses.user_id) as total')
                ->groupBy('users.role')
                ->pluck('total', 'users.role');

            return [
                'total_active_questionnaires' => $activeQuestionnaires->count(),
                'total_respondents' => $totalRespondentUsers,
                'participation_rate' => $participationRate,
                'average_score' => round($averageOverallScore, 2),
                'breakdown' => [
                    'guru' => (int) ($breakdown['guru'] ?? 0),
                    'tata_usaha' => (int) ($breakdown['tata_usaha'] ?? 0),
                    'orang_tua' => (int) ($breakdown['orang_tua'] ?? 0),
                ],
            ];
        });

        return view('livewire.admin.admin-dashboard', [
            'metrics' => $metrics,
        ]);
    }
}
