<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class RoleManagementApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_crud_roles_via_api(): void
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

        $create = $this->postJson('/api/roles', [
            'name' => 'Supervisor',
            'description' => 'Pengawas',
            'prosentase' => 65,
            'is_active' => true,
        ])->assertCreated();

        $id = (int) $create->json('data.id');

        $this->putJson("/api/roles/{$id}", [
            'name' => 'Supervisor Updated',
            'description' => 'Pengawas Update',
            'prosentase' => 70,
            'is_active' => true,
        ])->assertOk();

        $this->getJson('/api/roles?search=Supervisor')
            ->assertOk();

        $this->deleteJson("/api/roles/{$id}")
            ->assertOk();
    }

    public function test_cannot_delete_role_still_used_by_user(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => $this->adminSlug(),
            'description' => 'Admin',
            'prosentase' => 90,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'role_id' => $adminRole->id]);
        $targetRole = Role::query()->create([
            'name' => 'Guru',
            'slug' => $this->teacherSlug(),
            'description' => 'Guru',
            'prosentase' => 60,
            'is_active' => true,
        ]);
        User::factory()->create(['role' => $this->teacherSlug(), 'role_id' => $targetRole->id]);
        $this->actingAs($admin);

        $this->deleteJson("/api/roles/{$targetRole->id}")
            ->assertStatus(422);
    }

    public function test_cannot_create_duplicate_role_name(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => $this->adminSlug(),
            'description' => 'Admin',
            'prosentase' => 90,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'role_id' => $adminRole->id]);
        Role::query()->create([
            'name' => 'Operator',
            'slug' => 'operator',
            'description' => 'Operator',
            'prosentase' => 55,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $this->postJson('/api/roles', [
            'name' => 'Operator',
            'description' => 'Duplikat',
            'prosentase' => 40,
            'is_active' => true,
        ])->assertStatus(422);
    }
}
