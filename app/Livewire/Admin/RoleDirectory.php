<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class RoleDirectory extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public string $prosentase = '0';
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canManageRoles(), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $role = Role::query()->findOrFail($id);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->description = (string) ($role->description ?? '');
        $this->prosentase = (string) $role->prosentase;
        $this->is_active = (bool) $role->is_active;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->description = trim($this->description);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'prosentase' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'slug' => str($validated['name'])->snake()->lower()->toString(),
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'prosentase' => (float) $validated['prosentase'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if ($this->editingId) {
            Role::query()->findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Role berhasil diperbarui.');
        } else {
            Role::query()->create($payload);
            session()->flash('success', 'Role berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $role = Role::query()->findOrFail($id);
        if ($role->users()->exists()) {
            session()->flash('error', 'Role tidak dapat dihapus karena masih digunakan user.');

            return;
        }

        $role->delete();
        session()->flash('success', 'Role berhasil dihapus.');
    }

    public function sortByColumn(string $field): void
    {
        $allowed = ['id', 'name', 'prosentase', 'is_active', 'created_at'];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->prosentase = '0';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $roles = Role::query()
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('users')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.role-directory', [
            'roles' => $roles,
        ]);
    }
}
