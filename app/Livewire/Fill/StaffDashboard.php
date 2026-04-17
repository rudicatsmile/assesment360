<?php

namespace App\Livewire\Fill;

use App\Livewire\Fill\Concerns\HasEvaluatorDashboardMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class StaffDashboard extends Component
{
    use HasEvaluatorDashboardMetrics;

    public function render()
    {
        $roleSlug = (string) config('rbac.dashboard_role_slugs.staff');

        return view('livewire.fill.staff-dashboard', [
            'payload' => $this->getDashboardMetricsByRole($roleSlug),
        ]);
    }
}
