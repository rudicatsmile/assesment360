<?php

namespace App\Livewire\Fill;

use App\Livewire\Fill\Concerns\HasEvaluatorDashboardMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.evaluator')]
class TeacherDashboard extends Component
{
    use HasEvaluatorDashboardMetrics;

    public function render()
    {
        return view('livewire.fill.teacher-dashboard', [
            'payload' => $this->getDashboardMetricsByRole('guru'),
        ]);
    }
}
