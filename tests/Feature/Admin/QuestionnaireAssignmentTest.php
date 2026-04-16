<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\QuestionnaireAssignment;
use App\Models\Questionnaire;
use App\Models\QuestionnaireTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionnaireAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_target_groups_from_assignment_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $questionnaire = Questionnaire::query()->create([
            'title' => 'Kuisioner Test',
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        QuestionnaireTarget::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'target_group' => 'guru',
        ]);

        $this->actingAs($admin);

        Livewire::test(QuestionnaireAssignment::class, ['questionnaire' => $questionnaire])
            ->set('selectedTargetGroups', ['guru', 'orang_tua'])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => 'guru',
        ]);

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => 'orang_tua',
        ]);
    }

    public function test_assignment_rejects_empty_target_groups_and_keeps_existing_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $questionnaire = Questionnaire::query()->create([
            'title' => 'Kuisioner Test',
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        QuestionnaireTarget::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'target_group' => 'guru',
        ]);

        $this->actingAs($admin);

        Livewire::test(QuestionnaireAssignment::class, ['questionnaire' => $questionnaire])
            ->set('selectedTargetGroups', [])
            ->assertHasErrors(['selectedTargetGroups' => 'min']);

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => 'guru',
        ]);
    }
}
