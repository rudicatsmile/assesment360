<?php

namespace App\Livewire\Admin;

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

    public int $perPage = 10;

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'guru';

    public bool $is_active = true;

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
        $validated = $this->validate($this->rules(), $this->messages());

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $before = $user->only(['name', 'email', 'role', 'is_active']);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->is_active = (bool) $validated['is_active'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            Log::info('admin.users.update.livewire', [
                'actor_id' => auth()->id(),
                'target_user_id' => $user->id,
                'before' => $before,
                'after' => $user->only(['name', 'email', 'role', 'is_active']),
            ]);

            session()->flash('success', 'Pengguna berhasil diperbarui.');
        } else {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
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
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $users = User::query()
            ->whereIn('role', ['admin', 'guru', 'tata_usaha', 'orang_tua'])
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($this->roleFilter !== '', fn($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', function ($query): void {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.user-directory', [
            'users' => $users,
        ]);
    }
}
