<?php

namespace Tests\Feature\Admin;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class DepartmentManagementApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_crud_department_api(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $this->actingAs($admin);

        $create = $this->postJson(route('admin.departments.store'), [
            'name' => 'Akademik',
            'urut' => 1,
            'description' => 'Unit akademik',
        ])->assertCreated();

        $id = (int) $create->json('data.id');

        $this->patchJson(route('admin.departments.update', $id), [
            'name' => 'Akademik Update',
            'urut' => 2,
            'description' => 'Unit akademik update',
        ])->assertOk();

        $this->getJson(route('admin.departments.data', [
            'search' => 'Akademik',
            'sort_by' => 'urut',
            'sort_direction' => 'asc',
        ]))->assertOk();

        $this->deleteJson(route('admin.departments.destroy', $id))->assertOk();
    }

    public function test_non_admin_cannot_access_department_api(): void
    {
        $user = User::factory()->create(['role' => $this->teacherSlug()]);
        $this->actingAs($user);

        $this->getJson(route('admin.departments.data'))->assertForbidden();
    }

    public function test_department_cannot_be_deleted_when_used_by_user(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $dep = Departement::query()->create([
            'name' => 'Administrasi',
            'urut' => 1,
            'description' => null,
        ]);

        User::factory()->create(['role' => $this->teacherSlug(), 'department_id' => $dep->id]);
        $this->actingAs($admin);

        $this->deleteJson(route('admin.departments.destroy', $dep))
            ->assertStatus(422);
    }
}
