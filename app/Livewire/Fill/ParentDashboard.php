<?php

namespace App\Livewire\Fill;

use App\Livewire\Fill\Concerns\HasEvaluatorDashboardMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class ParentDashboard extends Component
{
    use HasEvaluatorDashboardMetrics;

    public function render()
    {
        $roleSlug = (string) config('rbac.dashboard_role_slugs.parent');

        return view('livewire.fill.parent-dashboard', [
            'payload' => $this->getDashboardMetricsByRole($roleSlug),
        ]);
    }
}
