<?php

namespace App\Livewire\Admin;

use App\Models\Departement;
use App\Models\Response;
use App\Models\Role;
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

    public string $phoneFilter = '';

    public int $perPage = 10;

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $phone_number = '';

    public string $password = '';

    public string $role_id = '';

    public ?string $department_id = '';

    public bool $is_active = true;

    /** @var int|null Hours component of time limit */
    public ?int $time_limit_hours = null;

    /** @var int|null Minutes component of time limit */
    public ?int $time_limit_minutes = null;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdminRole(), 403);
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

    public function updatingPhoneFilter(): void
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
        $this->phone_number = (string) ($user->phone_number ?? '');
        $this->role_id = (string) ($user->role_id ?? '');
        $this->department_id = $user->department_id ? (string) $user->department_id : '';
        $this->is_active = (bool) $user->is_active;
        $this->password = '';

        // Populate time limit from user's stored minutes
        $totalMinutes = $user->time_limit_minutes;
        if ($totalMinutes !== null && $totalMinutes > 0) {
            $this->time_limit_hours = intdiv($totalMinutes, 60);
            $this->time_limit_minutes = $totalMinutes % 60;
        } else {
            $this->time_limit_hours = null;
            $this->time_limit_minutes = null;
        }

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
        $this->phone_number = trim($this->phone_number);
        $this->department_id = $this->department_id !== '' ? (string) ((int) $this->department_id) : null;
        $validated = $this->validate($this->rules(), $this->messages());

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $before = $user->only(['name', 'email', 'phone_number', 'role_id', 'role', 'department_id', 'is_active']);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->phone_number = $validated['phone_number'] !== '' ? $validated['phone_number'] : null;
            $user->role_id = (int) $validated['role_id'];
            $user->role = $this->resolveLegacyRoleSlug((int) $validated['role_id']);
            $user->department_id = $validated['department_id'] !== null ? (int) $validated['department_id'] : null;
            $user->department = $this->resolveDepartmentName($validated['department_id']);
            $user->is_active = (bool) $validated['is_active'];
            $user->time_limit_minutes = $this->resolveTimeLimitMinutes();
            $user->filling_started_at = $user->filling_started_at; // preserve existing

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            Log::info('admin.users.update.livewire', [
                'actor_id' => auth()->id(),
                'target_user_id' => $user->id,
                'before' => $before,
                'after' => $user->only(['name', 'email', 'phone_number', 'role_id', 'role', 'department_id', 'is_active']),
            ]);

            session()->flash('success', 'Pengguna berhasil diperbarui.');
        } else {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] !== '' ? $validated['phone_number'] : null,
                'password' => Hash::make($validated['password']),
                'role_id' => (int) $validated['role_id'],
                'role' => $this->resolveLegacyRoleSlug((int) $validated['role_id']),
                'department_id' => $validated['department_id'] !== null ? (int) $validated['department_id'] : null,
                'department' => $this->resolveDepartmentName($validated['department_id']),
                'is_active' => (bool) $validated['is_active'],
                'time_limit_minutes' => $this->resolveTimeLimitMinutes(),
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
     * Reset a user's fill session so they can re-do questionnaires.
     * Sets filling_started_at to null and reverts all submitted responses to draft.
     */
    public function resetUserSession(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        // Reset the fill session timer
        $user->filling_started_at = null;
        $user->save();

        // Revert all submitted responses back to draft so questionnaires become fillable again
        $revertedCount = Response::query()
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->update([
                'status' => 'draft',
                'submitted_at' => null,
            ]);

        Log::info('admin.users.reset-session', [
            'actor_id' => auth()->id(),
            'target_user_id' => $userId,
            'responses_reverted' => $revertedCount,
        ]);

        session()->flash('success', "Sesi pengisian {$user->name} berhasil direset. {$revertedCount} response dikembalikan ke draft.");
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
            'phone_number' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => $passwordRule,
            'role_id' => ['required', 'integer', 'exists:roles,id'],
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
            'phone_number.regex' => 'Format nomor telepon tidak valid.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->phone_number = '';
        $this->password = '';
        $this->role_id = '';
        $this->department_id = '';
        $this->is_active = true;
        $this->time_limit_hours = null;
        $this->time_limit_minutes = null;
        $this->resetErrorBag();
    }

    /**
     * Convert hours + minutes input into total minutes.
     * Returns null if both are empty (no time limit).
     */
    private function resolveTimeLimitMinutes(): ?int
    {
        $hours = (int) ($this->time_limit_hours ?? 0);
        $minutes = (int) ($this->time_limit_minutes ?? 0);
        $total = ($hours * 60) + $minutes;

        return $total > 0 ? $total : null;
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

    private function resolveLegacyRoleSlug(int $roleId): string
    {
        $slug = (string) Role::query()->where('id', $roleId)->value('slug');
        $aliases = (array) config('rbac.role_aliases', []);
        $allowed = (array) config('rbac.legacy_allowed_slugs', []);
        $resolved = (string) ($aliases[$slug] ?? $slug);

        return in_array($resolved, $allowed, true)
            ? $resolved
            : (string) config('rbac.default_legacy_role_slug', '');
    }

    public function sortUsers(string $field): void
    {
        $allowed = ['id', 'name', 'email', 'phone_number', 'role', 'department', 'is_active', 'created_at'];

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
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereHas('roleRef', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('departmentRef', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->roleFilter !== '', fn($query) => $query->where('role_id', (int) $this->roleFilter))
            ->when($this->departmentFilter !== '', function ($query): void {
                $query->where('department_id', (int) $this->departmentFilter);
            })
            ->when($this->phoneFilter !== '', fn($query) => $query->where('phone_number', 'like', '%' . trim($this->phoneFilter) . '%'))
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
            }, function ($query): void {
                if ($this->sortBy === 'role') {
                    $query->orderBy(
                        Role::query()
                            ->select('name')
                            ->whereColumn('roles.id', 'users.role_id')
                            ->limit(1),
                        $this->sortDirection
                    );

                    return;
                }

                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->with(['departmentRef:id,name', 'roleRef:id,name,slug'])
            ->paginate($this->perPage);

        $departments = Departement::query()
            ->orderBy('urut')
            ->orderBy('name')
            ->get(['id', 'name']);

        $roles = Role::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('livewire.admin.user-directory', [
            'users' => $users,
            'departments' => $departments,
            'roles' => $roles,
        ]);
    }
}
