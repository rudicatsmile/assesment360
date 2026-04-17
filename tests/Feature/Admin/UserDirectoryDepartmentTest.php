<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserDirectory;
use App\Models\Departement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class UserDirectoryDepartmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    private function seedCoreRoles(): array
    {
        $labels = (array) config('rbac.role_labels', []);
        $adminSlug = $this->adminSlug();
        $teacherSlug = $this->teacherSlug();

        return [
            $adminSlug => Role::query()->create(['name' => (string) ($labels[$adminSlug] ?? 'Admin'), 'slug' => $adminSlug, 'prosentase' => 90, 'is_active' => true]),
            $teacherSlug => Role::query()->create(['name' => (string) ($labels[$teacherSlug] ?? 'Evaluator'), 'slug' => $teacherSlug, 'prosentase' => 70, 'is_active' => true]),
        ];
    }

    public function test_admin_can_create_user_with_department_from_livewire(): void
    {
        $roles = $this->seedCoreRoles();
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'role_id' => $roles[$this->adminSlug()]->id]);
        $department = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $this->actingAs($admin);

        Livewire::test(UserDirectory::class)
            ->call('startCreate')
            ->set('name', 'User Dept')
            ->set('email', 'user.dept@example.test')
            ->set('phone_number', '081200000000')
            ->set('password', 'password123')
            ->set('role_id', (string) $roles[$this->teacherSlug()]->id)
            ->set('department_id', (string) $department->id)
            ->set('is_active', true)
            ->call('saveUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'user.dept@example.test',
            'phone_number' => '081200000000',
            'department_id' => $department->id,
        ]);
    }

    public function test_department_filter_and_sort_are_applied(): void
    {
        $roles = $this->seedCoreRoles();
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'role_id' => $roles[$this->adminSlug()]->id]);
        $this->actingAs($admin);

        $depA = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $depB = Departement::query()->create(['name' => 'Kurikulum', 'urut' => 2]);
        User::factory()->create(['role' => $this->teacherSlug(), 'role_id' => $roles[$this->teacherSlug()]->id, 'department_id' => $depB->id, 'name' => 'Zeta']);
        User::factory()->create(['role' => $this->teacherSlug(), 'role_id' => $roles[$this->teacherSlug()]->id, 'department_id' => $depA->id, 'name' => 'Alpha']);

        Livewire::test(UserDirectory::class)
            ->set('departmentFilter', (string) $depA->id)
            ->call('sortUsers', 'department')
            ->assertViewHas('users', function ($paginator) use ($depA): bool {
                return $paginator->count() === 1
                    && (int) $paginator->items()[0]->department_id === (int) $depA->id;
            });
    }
}
