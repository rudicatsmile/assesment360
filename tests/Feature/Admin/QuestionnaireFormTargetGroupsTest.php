<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\QuestionnaireForm;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionnaireFormTargetGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_group_options_exclude_super_admin_and_admin(): void
    {
        $this->seedRoles();

        $options = Questionnaire::targetGroupOptions();
        $optionIds = DB::table('roles')
            ->whereIn('slug', collect($options)->pluck('slug')->all())
            ->pluck('id')
            ->all();

        $this->assertNotContains(1, $optionIds);
        $this->assertNotContains(2, $optionIds);
        $this->assertContains(3, $optionIds);
    }

    public function test_questionnaire_form_saves_selected_filtered_target_group(): void
    {
        $this->seedRoles();
        $adminSlugs = (array) config('rbac.admin_slugs', []);
        $adminSlug = (string) ($adminSlugs[1] ?? $adminSlugs[0] ?? '');

        $admin = User::factory()->create([
            'role' => $adminSlug,
            'role_id' => 2,
        ]);

        $this->actingAs($admin);

        Livewire::test(QuestionnaireForm::class)
            ->assertDontSee('Super Admin')
            ->assertSee('Pengurus Yayasan')
            ->set('title', 'Q Filtered Roles')
            ->set('description', 'Test save by filtered role')
            ->set('start_date', now()->format('Y-m-d\TH:i'))
            ->set('end_date', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('status', 'draft')
            ->set('target_groups', ['pengurus_yayasan'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questionnaires', [
            'title' => 'Q Filtered Roles',
            'created_by' => $admin->id,
        ]);

        $questionnaireId = (int) DB::table('questionnaires')->where('title', 'Q Filtered Roles')->value('id');

        $this->assertDatabaseHas('questionnaire_targets', [
            'questionnaire_id' => $questionnaireId,
            'target_group' => 'pengurus_yayasan',
        ]);
    }

    private function seedRoles(): void
    {
        $adminSlugs = (array) config('rbac.admin_slugs', []);
        $superAdminSlug = (string) ($adminSlugs[0] ?? '');
        $adminSlug = (string) ($adminSlugs[1] ?? $superAdminSlug);

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Super Admin', 'slug' => $superAdminSlug, 'description' => 'Super', 'prosentase' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Admin', 'slug' => $adminSlug, 'description' => 'Admin', 'prosentase' => 90, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Pengurus Yayasan', 'slug' => 'pengurus_yayasan', 'description' => 'Yayasan', 'prosentase' => 80, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Guru Staf', 'slug' => 'guru_staf', 'description' => 'Guru', 'prosentase' => 70, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
