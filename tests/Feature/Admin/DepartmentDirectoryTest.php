<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DepartmentDirectory;
use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class DepartmentDirectoryTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_create_department_from_livewire(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $this->actingAs($admin);

        Livewire::test(DepartmentDirectory::class)
            ->call('startCreate')
            ->set('name', 'Kurikulum')
            ->set('urut', 1)
            ->set('description', 'Unit kurikulum')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departements', [
            'name' => 'Kurikulum',
            'urut' => 1,
        ]);
    }

    public function test_search_department_on_livewire_table(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $this->actingAs($admin);

        Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        Departement::query()->create(['name' => 'Administrasi', 'urut' => 2]);

        Livewire::test(DepartmentDirectory::class)
            ->set('search', 'Akademik')
            ->assertSee('Akademik')
            ->assertDontSee('Administrasi');
    }
}
