<?php

namespace Tests\Feature\Admin;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_via_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $this->actingAs($admin);

        $response = $this->postJson(route('admin.users.store'), [
            'name' => 'User Baru',
            'email' => 'userbaru@example.test',
            'phone_number' => '081234567890',
            'password' => 'password123',
            'role' => 'guru',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'userbaru@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'userbaru@example.test',
            'phone_number' => '081234567890',
            'role' => 'guru',
            'department_id' => $department->id,
        ]);
    }

    public function test_admin_can_update_user_and_password_is_optional(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Departement::query()->create(['name' => 'Administrasi', 'urut' => 1]);
        $target = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $this->actingAs($admin);

        $response = $this->patchJson(route('admin.users.update', $target), [
            'name' => 'Nama Update',
            'email' => 'updated@example.test',
            'phone_number' => '081111111111',
            'role' => 'tata_usaha',
            'department_id' => $department->id,
            'is_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Update')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'updated@example.test',
            'phone_number' => '081111111111',
            'role' => 'tata_usaha',
            'department_id' => $department->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'orang_tua']);
        $this->actingAs($admin);

        $response = $this->deleteJson(route('admin.users.destroy', $target));

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_non_admin_cannot_access_user_management_api(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $this->actingAs($guru);

        $response = $this->getJson(route('admin.users.data'));

        $response->assertForbidden();
    }

    public function test_admin_can_filter_and_search_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentA = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $departmentB = Departement::query()->create(['name' => 'Administrasi', 'urut' => 2]);
        User::factory()->create(['name' => 'Alice Guru', 'email' => 'alice.guru@example.test', 'phone_number' => '082222222222', 'role' => 'guru', 'department_id' => $departmentA->id, 'is_active' => true]);
        User::factory()->create(['name' => 'Bob TU', 'email' => 'bob.tu@example.test', 'role' => 'tata_usaha', 'department_id' => $departmentB->id, 'is_active' => false]);

        $this->actingAs($admin);

        $response = $this->getJson(route('admin.users.data', [
            'search' => 'alice',
            'role' => 'guru',
            'department_id' => $departmentA->id,
            'phone' => '0822',
            'status' => 'active',
            'sort_by' => 'phone_number',
            'sort_direction' => 'asc',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.email', 'alice.guru@example.test');
    }

    public function test_admin_full_crud_flow_integration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentA = Departement::query()->create(['name' => 'Kurikulum', 'urut' => 1]);
        $departmentB = Departement::query()->create(['name' => 'Kesiswaan', 'urut' => 2]);
        $this->actingAs($admin);

        $create = $this->postJson(route('admin.users.store'), [
            'name' => 'Flow User',
            'email' => 'flow.user@example.test',
            'phone_number' => '083333333333',
            'password' => 'password123',
            'role' => 'guru',
            'department_id' => $departmentA->id,
            'is_active' => true,
        ])->assertCreated();

        $userId = (int) $create->json('data.id');

        $this->patchJson(route('admin.users.update', $userId), [
            'name' => 'Flow User Updated',
            'email' => 'flow.user.updated@example.test',
            'phone_number' => '084444444444',
            'role' => 'orang_tua',
            'department_id' => $departmentB->id,
            'is_active' => false,
            'password' => 'newpassword123',
        ])->assertOk();

        $this->deleteJson(route('admin.users.destroy', $userId))->assertOk();
        $this->assertSoftDeleted('users', ['id' => $userId]);
    }
}
