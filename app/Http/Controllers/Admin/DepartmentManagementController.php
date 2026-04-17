<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartementRequest;
use App\Http\Requests\UpdateDepartementRequest;
use App\Models\Departement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepartmentManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('search', ''));
        $perPage = max(5, min((int) $request->query('per_page', 10), 50));
        $sortBy = (string) $request->query('sort_by', 'urut');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['id', 'name', 'urut', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'urut';
        }

        $departements = Departement::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('users')
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);

        return response()->json($departements);
    }

    public function show(Request $request, Departement $departement): JsonResponse
    {
        $this->authorizeAdmin($request);

        $departement->loadCount('users');

        return response()->json($departement);
    }

    public function store(StoreDepartementRequest $request): JsonResponse
    {
        $departement = Departement::query()->create($request->validated());

        Log::info('admin.departements.create', [
            'actor_id' => $request->user()?->id,
            'departement_id' => $departement->id,
        ]);

        return response()->json([
            'message' => 'Department berhasil ditambahkan.',
            'data' => $departement,
        ], 201);
    }

    public function update(UpdateDepartementRequest $request, Departement $departement): JsonResponse
    {
        $before = $departement->only(['name', 'urut', 'description']);
        $departement->update($request->validated());

        Log::info('admin.departements.update', [
            'actor_id' => $request->user()?->id,
            'departement_id' => $departement->id,
            'before' => $before,
            'after' => $departement->only(['name', 'urut', 'description']),
        ]);

        return response()->json([
            'message' => 'Department berhasil diperbarui.',
            'data' => $departement,
        ]);
    }

    public function destroy(Request $request, Departement $departement): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($departement->users()->exists()) {
            return response()->json([
                'message' => 'Department tidak dapat dihapus karena masih dipakai data users.',
            ], 422);
        }

        $departement->delete();

        Log::warning('admin.departements.delete', [
            'actor_id' => $request->user()?->id,
            'departement_id' => $departement->id,
        ]);

        return response()->json([
            'message' => 'Department berhasil dihapus.',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdminRole(), 403);
    }
}
