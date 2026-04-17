<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Departement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('search', ''));
        $roleId = (int) $request->query('role_id', 0);
        $departmentId = (int) $request->query('department_id', 0);
        $status = (string) $request->query('status', '');
        $phone = trim((string) $request->query('phone', ''));
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(5, min((int) $request->query('per_page', 10), 50));
        $allowedSort = ['id', 'name', 'email', 'phone_number', 'role', 'department', 'is_active', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereHas('roleRef', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('departmentRef', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($roleId > 0, fn($query) => $query->where('role_id', $roleId))
            ->when($departmentId > 0, fn($query) => $query->where('department_id', $departmentId))
            ->when($phone !== '', fn($query) => $query->where('phone_number', 'like', "%{$phone}%"))
            ->when($status !== '', function ($query) use ($status): void {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($sortBy === 'department', function ($query) use ($sortDirection): void {
                $query->orderBy(
                    Departement::query()
                        ->select('name')
                        ->whereColumn('departements.id', 'users.department_id')
                        ->limit(1),
                    $sortDirection
                );
            }, function ($query) use ($sortBy, $sortDirection): void {
                if ($sortBy === 'role') {
                    $query->orderBy(
                        Role::query()
                            ->select('name')
                            ->whereColumn('roles.id', 'users.role_id')
                            ->limit(1),
                        $sortDirection
                    );

                    return;
                }

                $query->orderBy($sortBy, $sortDirection);
            })
            ->with(['departmentRef:id,name', 'roleRef:id,name,slug'])
            ->paginate($perPage);

        return response()->json($users);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role_id' => $user->role_id,
            'role_name' => $user->roleRef?->name,
            'role_slug' => $user->roleSlug(),
            'department_id' => $user->department_id,
            'department_name' => $user->departmentRef?->name,
            'is_active' => (bool) $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] !== '' ? $data['phone_number'] : null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'role' => $this->resolveLegacyRoleSlug($data['role_id']),
            'department_id' => $data['department_id'] ?? null,
            'department' => $this->resolveDepartmentName($data['department_id'] ?? null),
            'is_active' => (bool) $data['is_active'],
            'email_verified_at' => now(),
        ]);

        Log::info('admin.users.create', [
            'actor_id' => $request->user()?->id,
            'target_user_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'data' => $user,
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $before = $user->only(['name', 'email', 'phone_number', 'role_id', 'role', 'department_id', 'is_active']);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone_number = $data['phone_number'] !== '' ? $data['phone_number'] : null;
        $user->role_id = $data['role_id'];
        $user->role = $this->resolveLegacyRoleSlug($data['role_id']);
        $user->department_id = $data['department_id'] ?? null;
        $user->department = $this->resolveDepartmentName($data['department_id'] ?? null);
        $user->is_active = (bool) $data['is_active'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        Log::info('admin.users.update', [
            'actor_id' => $request->user()?->id,
            'target_user_id' => $user->id,
            'before' => $before,
            'after' => $user->only(['name', 'email', 'phone_number', 'role_id', 'role', 'department_id', 'is_active']),
            'password_updated' => !empty($data['password']),
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ((int) $request->user()?->id === (int) $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        $user->delete();

        Log::warning('admin.users.delete', [
            'actor_id' => $request->user()?->id,
            'target_user_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil dihapus (soft delete).',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdminRole(), 403);
    }

    private function resolveDepartmentName(?int $departmentId): ?string
    {
        if (!$departmentId) {
            return null;
        }

        return Departement::query()
            ->where('id', $departmentId)
            ->value('name');
    }

    private function resolveLegacyRoleSlug(?int $roleId): string
    {
        if (!$roleId) {
            return (string) config('rbac.default_legacy_role_slug', '');
        }

        $slug = (string) Role::query()->where('id', $roleId)->value('slug');
        $aliases = (array) config('rbac.role_aliases', []);
        $allowed = (array) config('rbac.legacy_allowed_slugs', []);
        $resolved = (string) ($aliases[$slug] ?? $slug);

        return in_array($resolved, $allowed, true)
            ? $resolved
            : (string) config('rbac.default_legacy_role_slug', '');
    }
}
