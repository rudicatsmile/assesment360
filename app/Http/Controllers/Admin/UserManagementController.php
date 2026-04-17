<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Departement;
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
        $role = (string) $request->query('role', '');
        $departmentId = (int) $request->query('department_id', 0);
        $status = (string) $request->query('status', '');
        $sortBy = (string) $request->query('sort_by', 'created_at');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(5, min((int) $request->query('per_page', 10), 50));
        $allowedSort = ['id', 'name', 'email', 'role', 'department', 'is_active', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('departmentRef', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($role !== '', fn($query) => $query->where('role', $role))
            ->when($departmentId > 0, fn($query) => $query->where('department_id', $departmentId))
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
            }, fn($query) => $query->orderBy($sortBy, $sortDirection))
            ->with('departmentRef:id,name')
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
            'role' => $user->role,
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
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
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
        $before = $user->only(['name', 'email', 'role', 'department_id', 'is_active']);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
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
            'after' => $user->only(['name', 'email', 'role', 'department_id', 'is_active']),
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
        abort_unless($request->user()?->role === 'admin', 403);
    }

    private function resolveDepartmentName(?int $departmentId): ?string
    {
        if (! $departmentId) {
            return null;
        }

        return Departement::query()
            ->where('id', $departmentId)
            ->value('name');
    }
}
