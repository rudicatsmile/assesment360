<?php

namespace App\Livewire\Admin;

use App\Models\Departement;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class DepartmentDirectory extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortBy = 'urut';

    public string $sortDirection = 'asc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public int $urut = 0;

    public string $description = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdminRole(), 403);
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
        $item = Departement::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->urut = (int) $item->urut;
        $this->description = (string) ($item->description ?? '');
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
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('departements', 'name')->ignore($this->editingId)],
            'urut' => ['required', 'integer', 'min:0', 'max:99999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId) {
            $item = Departement::query()->findOrFail($this->editingId);
            $before = $item->only(['name', 'urut', 'description']);
            $item->update($validated);
            Log::info('admin.departements.update.livewire', [
                'actor_id' => auth()->id(),
                'departement_id' => $item->id,
                'before' => $before,
                'after' => $item->only(['name', 'urut', 'description']),
            ]);
            session()->flash('success', 'Department berhasil diperbarui.');
        } else {
            $item = Departement::query()->create($validated);
            Log::info('admin.departements.create.livewire', [
                'actor_id' => auth()->id(),
                'departement_id' => $item->id,
            ]);
            session()->flash('success', 'Department berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $item = Departement::query()->findOrFail($id);

        if ($item->users()->exists()) {
            session()->flash('error', 'Department masih dipakai oleh data users.');

            return;
        }

        $item->delete();
        Log::warning('admin.departements.delete.livewire', [
            'actor_id' => auth()->id(),
            'departement_id' => $item->id,
        ]);
        session()->flash('success', 'Department berhasil dihapus.');
    }

    public function sortByColumn(string $field): void
    {
        $allowed = ['id', 'name', 'urut', 'created_at'];
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
        $this->urut = 0;
        $this->description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $departements = Departement::query()
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('users')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.department-directory', [
            'departements' => $departements,
        ]);
    }
}
