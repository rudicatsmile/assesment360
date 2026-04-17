<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Departments</h1>
            <p class="text-sm text-zinc-500">Master data department untuk referensi users.</p>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="startCreate">Tambah Department</flux:button>
    </div>

    @if ($showForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-800">{{ $editingId ? 'Edit Department' : 'Tambah Department' }}</h2>
                <flux:button variant="ghost" size="xs" wire:click="cancelForm">Tutup</flux:button>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Nama</span>
                    <input type="text" wire:model.live.debounce.300ms="name" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Urut</span>
                    <input type="number" min="0" wire:model.live="urut" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('urut') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm md:col-span-2">
                    <span class="font-medium text-zinc-700">Deskripsi</span>
                    <textarea rows="3" wire:model.live.debounce.300ms="description" class="w-full rounded-lg border border-zinc-300 px-3 py-2"></textarea>
                    @error('description') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Batal</flux:button>
                <flux:button variant="primary" wire:click="save">{{ $editingId ? 'Update' : 'Simpan' }}</flux:button>
            </div>
        </div>
    @endif

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm md:grid-cols-2">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama/deskripsi..." class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            <option value="10">10 / halaman</option>
            <option value="20">20 / halaman</option>
            <option value="50">50 / halaman</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3"><button type="button" wire:click="sortByColumn('id')" class="inline-flex items-center gap-1">ID</button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortByColumn('name')" class="inline-flex items-center gap-1">Name</button></th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Users</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($departements as $department)
                        <tr>
                            <td class="px-4 py-3 text-zinc-500">{{ $department->id }}</td>
                            <td class="px-4 py-3 text-zinc-900">{{ $department->name }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $department->description ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $department->users_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="xs" variant="outline" wire:click="startEdit({{ $department->id }})">Edit</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete({{ $department->id }})"
                                        wire:confirm="Hapus department ini?"
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Belum ada data department.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $departements->links() }}
</div>
