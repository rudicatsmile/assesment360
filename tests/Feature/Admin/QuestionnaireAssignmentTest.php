<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\QuestionnaireAssignment;
use App\Models\Questionnaire;
use App\Models\QuestionnaireTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class QuestionnaireAssignmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_update_target_groups_from_assignment_component(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);

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
            'target_group' => $this->teacherSlug(),
        ]);

        $this->actingAs($admin);

        Livewire::test(QuestionnaireAssignment::class, ['questionnaire' => $questionnaire])
            ->set('selectedTargetGroups', [$this->teacherSlug(), $this->parentSlug()])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => $this->teacherSlug(),
        ]);

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => $this->parentSlug(),
        ]);
    }

    public function test_assignment_rejects_empty_target_groups_and_keeps_existing_data(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);

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
            'target_group' => $this->teacherSlug(),
        ]);

        $this->actingAs($admin);

        Livewire::test(QuestionnaireAssignment::class, ['questionnaire' => $questionnaire])
            ->set('selectedTargetGroups', [])
            ->assertHasErrors(['selectedTargetGroups' => 'min']);

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaire->id,
            'target_group' => $this->teacherSlug(),
        ]);
    }
}
