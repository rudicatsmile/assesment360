<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
        <h1 class="text-2xl font-semibold text-zinc-900">Users</h1>
        <p class="text-sm text-zinc-500">Daftar pengguna aktif berdasarkan role.</p>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="startCreate">Tambah Pengguna</flux:button>
    </div>

    @if ($showForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-800">{{ $editingUserId ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h2>
                <flux:button variant="ghost" size="xs" wire:click="cancelForm">Tutup</flux:button>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Nama Lengkap</span>
                    <input type="text" wire:model.live.debounce.300ms="name" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Email</span>
                    <input type="email" wire:model.live.debounce.300ms="email" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Password {{ $editingUserId ? '(opsional)' : '' }}</span>
                    <input type="password" wire:model.live.debounce.300ms="password" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Role</span>
                    <select wire:model.live="role" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                        <option value="admin">admin</option>
                        <option value="guru">guru</option>
                        <option value="tata_usaha">tata_usaha</option>
                        <option value="orang_tua">orang_tua</option>
                    </select>
                    @error('role') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Department</span>
                    <select wire:model.live="department_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                        <option value="">Tanpa Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm md:col-span-2">
                    <span class="font-medium text-zinc-700">Status Aktif</span>
                    <select wire:model.live="is_active" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    @error('is_active') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Batal</flux:button>
                <flux:button variant="primary" wire:click="saveUser">{{ $editingUserId ? 'Update' : 'Simpan' }}</flux:button>
            </div>
        </div>
    @endif

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm md:grid-cols-5">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama/email..." class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <select wire:model.live="roleFilter" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Semua role</option>
            <option value="admin">admin</option>
            <option value="guru">guru</option>
            <option value="tata_usaha">tata_usaha</option>
            <option value="orang_tua">orang_tua</option>
        </select>
        <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
        <select wire:model.live="departmentFilter" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Semua department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
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
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('id')" class="inline-flex items-center gap-1">ID <span class="text-[10px] text-zinc-500">{{ $sortBy === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('name')" class="inline-flex items-center gap-1">Nama <span class="text-[10px] text-zinc-500">{{ $sortBy === 'name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('email')" class="inline-flex items-center gap-1">Email <span class="text-[10px] text-zinc-500">{{ $sortBy === 'email' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('role')" class="inline-flex items-center gap-1">Role <span class="text-[10px] text-zinc-500">{{ $sortBy === 'role' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('department')" class="inline-flex items-center gap-1">Department <span class="text-[10px] text-zinc-500">{{ $sortBy === 'department' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('is_active')" class="inline-flex items-center gap-1">Status <span class="text-[10px] text-zinc-500">{{ $sortBy === 'is_active' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button></th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-zinc-500">{{ $user->id }}</td>
                            <td class="px-4 py-3 text-zinc-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $user->role }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $user->departmentRef?->name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-700' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="xs" variant="outline" wire:click="startEdit({{ $user->id }})">Edit</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Hapus pengguna ini? data akan soft delete."
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-zinc-500">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>
