<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);
        $search = trim((string) $request->query('search', ''));
        $sortBy = (string) $request->query('sort_by', 'name');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(5, min((int) $request->query('per_page', 10), 100));
        $allowedSort = ['id', 'name', 'prosentase', 'is_active', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'name';
        }

        $roles = Role::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('users')
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($roles);
        }

        return redirect()->route('admin.roles.index');
    }

    public function create(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        return redirect()->route('admin.roles.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $this->validateRole($request);
        $data['slug'] = str($data['name'])->snake()->lower()->toString();
        $role = Role::query()->create($data);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Role berhasil ditambahkan.', 'data' => $role], 201);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAdmin($request);

        return redirect()->route('admin.roles.index');
    }

    public function update(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $this->validateRole($request, $role->id);
        $data['slug'] = str($data['name'])->snake()->lower()->toString();
        $role->update($data);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Role berhasil diperbarui.', 'data' => $role]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);
        if ($role->users()->exists()) {
            $payload = ['message' => 'Role tidak dapat dihapus karena masih digunakan user.'];
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json($payload, 422);
            }

            return back()->with('error', $payload['message']);
        }

        $role->delete();
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Role berhasil dihapus.']);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRole(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'prosentase' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->canManageRoles(), 403);
    }
}
