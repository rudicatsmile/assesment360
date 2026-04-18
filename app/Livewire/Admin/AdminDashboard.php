<?php

namespace App\Livewire\Admin;

use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\Role;
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
            $adminSlugs = (array) config('rbac.admin_slugs', ['super_admin', 'admin']);
            $targetAliases = (array) config('rbac.questionnaire_target_aliases', []);

            $allRoles = Role::query()
                ->where('is_active', true)
                ->whereNotIn('slug', $adminSlugs)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            $roles = $allRoles->pluck('slug')->toArray();
            $roleLabels = $allRoles->mapWithKeys(fn(Role $role): array => [$role->slug => $role->name])->toArray();

            $activeQuestionnaires = Questionnaire::query()
                ->where('status', 'active')
                ->with(['targets:id,questionnaire_id,target_group'])
                ->get();

            $userCountByRole = User::query()
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->whereIn('roles.slug', $roles)
                ->selectRaw('roles.slug, COUNT(*) as total')
                ->groupBy('roles.slug')
                ->pluck('total', 'roles.slug');

            $totalTargetSlots = $activeQuestionnaires->sum(function (Questionnaire $questionnaire) use ($userCountByRole, $targetAliases): int {
                return $questionnaire->targets
                    ->unique('target_group')
                    ->sum(function ($target) use ($userCountByRole, $targetAliases): int {
                        $targetGroup = $target->target_group;
                        $count = (int) ($userCountByRole[$targetGroup] ?? 0);

                        foreach ($targetAliases as $primarySlug => $aliasSlug) {
                            if ($aliasSlug === $targetGroup && isset($userCountByRole[$primarySlug])) {
                                $count += (int) $userCountByRole[$primarySlug];
                            }
                        }

                        return $count;
                    });
            });

            $totalSubmittedActiveResponses = Response::query()
                ->where('status', 'submitted')
                ->whereHas('questionnaire', fn($query) => $query->where('status', 'active'))
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
                ->whereHas('response', fn($query) => $query->where('status', 'submitted'))
                ->avg('calculated_score');

            $breakdown = Response::query()
                ->join('users', 'users.id', '=', 'responses.user_id')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->whereNull('users.deleted_at')
                ->where('responses.status', 'submitted')
                ->whereIn('roles.slug', $roles)
                ->selectRaw('roles.slug, COUNT(DISTINCT responses.user_id) as total')
                ->groupBy('roles.slug')
                ->pluck('total', 'roles.slug');

            $breakdownByRole = collect($roles)
                ->mapWithKeys(fn(string $slug): array => [$slug => (int) ($breakdown[$slug] ?? 0)])
                ->all();

            $breakdownCards = collect($allRoles)
                ->map(function (Role $role) use ($breakdown, $targetAliases): array {
                    $slug = $role->slug;
                    $total = (int) ($breakdown[$slug] ?? 0);

                    foreach ($targetAliases as $primarySlug => $aliasSlug) {
                        if ($primarySlug === $slug && isset($breakdown[$aliasSlug])) {
                            $total += (int) $breakdown[$aliasSlug];
                        }
                    }

                    return [
                        'slug' => $slug,
                        'label' => $role->name,
                        'total' => $total,
                    ];
                })
                ->values()
                ->all();

            return [
                'total_active_questionnaires' => $activeQuestionnaires->count(),
                'total_respondents' => $totalRespondentUsers,
                'participation_rate' => $participationRate,
                'average_score' => round($averageOverallScore, 2),
                'breakdown' => $breakdownByRole,
                'breakdown_cards' => $breakdownCards,
            ];
        });

        return view('livewire.admin.admin-dashboard', [
            'metrics' => $metrics,
        ]);
    }
}
