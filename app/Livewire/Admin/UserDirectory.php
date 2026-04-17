<?php

namespace App\Livewire\Admin;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class UserDirectory extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public ?string $departmentFilter = '';

    public int $perPage = 10;

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'guru';

    public ?string $department_id = '';

    public bool $is_active = true;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->department_id = $user->department_id ? (string) $user->department_id : '';
        $this->is_active = (bool) $user->is_active;
        $this->password = '';
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function saveUser(): void
    {
        $this->name = trim($this->name);
        $this->email = strtolower(trim($this->email));
        $this->department_id = $this->department_id !== '' ? (string) ((int) $this->department_id) : null;
        $validated = $this->validate($this->rules(), $this->messages());

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $before = $user->only(['name', 'email', 'role', 'department_id', 'is_active']);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->department_id = $validated['department_id'] !== null ? (int) $validated['department_id'] : null;
            $user->department = $this->resolveDepartmentName($validated['department_id']);
            $user->is_active = (bool) $validated['is_active'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            Log::info('admin.users.update.livewire', [
                'actor_id' => auth()->id(),
                'target_user_id' => $user->id,
                'before' => $before,
                'after' => $user->only(['name', 'email', 'role', 'department_id', 'is_active']),
            ]);

            session()->flash('success', 'Pengguna berhasil diperbarui.');
        } else {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'department_id' => $validated['department_id'] !== null ? (int) $validated['department_id'] : null,
                'department' => $this->resolveDepartmentName($validated['department_id']),
                'is_active' => (bool) $validated['is_active'],
                'email_verified_at' => now(),
            ]);

            Log::info('admin.users.create.livewire', [
                'actor_id' => auth()->id(),
                'target_user_id' => $user->id,
            ]);

            session()->flash('success', 'Pengguna berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ((int) auth()->id() === (int) $user->id) {
            session()->flash('error', 'Anda tidak dapat menghapus akun sendiri.');

            return;
        }

        $user->delete();

        Log::warning('admin.users.delete.livewire', [
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'target_email' => $user->email,
        ]);

        session()->flash('success', 'Pengguna berhasil dihapus (soft delete).');
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $passwordRule = $this->editingUserId
            ? ['nullable', 'string', 'min:8', 'max:100']
            : ['required', 'string', 'min:8', 'max:100'];

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'password' => $passwordRule,
            'role' => ['required', Rule::in(['admin', 'guru', 'tata_usaha', 'orang_tua'])],
            'department_id' => ['nullable', 'integer', 'exists:departements,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan pengguna lain.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'guru';
        $this->department_id = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function resolveDepartmentName(?string $departmentId): ?string
    {
        if (!$departmentId) {
            return null;
        }

        return Departement::query()
            ->where('id', (int) $departmentId)
            ->value('name');
    }

    public function sortUsers(string $field): void
    {
        $allowed = ['id', 'name', 'email', 'role', 'department', 'is_active', 'created_at'];

        if (!in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $users = User::query()
            ->whereIn('role', ['admin', 'guru', 'tata_usaha', 'orang_tua'])
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('departmentRef', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->roleFilter !== '', fn($query) => $query->where('role', $this->roleFilter))
            ->when($this->departmentFilter !== '', function ($query): void {
                $query->where('department_id', (int) $this->departmentFilter);
            })
            ->when($this->statusFilter !== '', function ($query): void {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($this->sortBy === 'department', function ($query): void {
                $query->orderBy(
                    Departement::query()
                        ->select('name')
                        ->whereColumn('departements.id', 'users.department_id')
                        ->limit(1),
                    $this->sortDirection
                );
            }, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->with('departmentRef:id,name')
            ->paginate($this->perPage);

        $departments = Departement::query()
            ->orderBy('urut')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.user-directory', [
            'users' => $users,
            'departments' => $departments,
        ]);
    }
}
