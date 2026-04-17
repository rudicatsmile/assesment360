<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RoleDirectory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class RoleDirectoryTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_create_role_from_livewire(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => $this->adminSlug(),
            'description' => 'Admin',
            'prosentase' => 90,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'role_id' => $adminRole->id]);
        $this->actingAs($admin);

        Livewire::test(RoleDirectory::class)
            ->call('startCreate')
            ->set('name', 'Operator')
            ->set('prosentase', '55')
            ->set('description', 'Role operator')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'Operator', 'slug' => 'operator']);
    }
}
