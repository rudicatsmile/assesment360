<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\RoleController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_index_returns_json_when_request_expects_json(): void
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
            'prosentase' => 60,
            'is_active' => true,
        ]);

        $request = Request::create('/api/roles', 'GET', ['search' => 'Oper']);
        $request->setUserResolver(fn() => $admin);
        $request->headers->set('Accept', 'application/json');

        $response = app(RoleController::class)->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Operator', $response->getContent());
    }
}
