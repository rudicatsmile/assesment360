<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserDirectory;
use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserDirectoryDepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_department_from_livewire(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $this->actingAs($admin);

        Livewire::test(UserDirectory::class)
            ->call('startCreate')
            ->set('name', 'User Dept')
            ->set('email', 'user.dept@example.test')
            ->set('password', 'password123')
            ->set('role', 'guru')
            ->set('department_id', (string) $department->id)
            ->set('is_active', true)
            ->call('saveUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'user.dept@example.test',
            'department_id' => $department->id,
        ]);
    }

    public function test_department_filter_and_sort_are_applied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $depA = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $depB = Departement::query()->create(['name' => 'Kurikulum', 'urut' => 2]);
        User::factory()->create(['role' => 'guru', 'department_id' => $depB->id, 'name' => 'Zeta']);
        User::factory()->create(['role' => 'guru', 'department_id' => $depA->id, 'name' => 'Alpha']);

        Livewire::test(UserDirectory::class)
            ->set('departmentFilter', (string) $depA->id)
            ->call('sortUsers', 'department')
            ->assertViewHas('users', function ($paginator) use ($depA): bool {
                return $paginator->count() === 1
                    && (int) $paginator->items()[0]->department_id === (int) $depA->id;
            });
    }
}
