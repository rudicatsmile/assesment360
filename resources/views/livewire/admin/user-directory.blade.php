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
                <h2 class="text-sm font-semibold text-zinc-800">{{ $editingUserId ? 'Edit Pengguna' : 'Tambah Pengguna' }}
                </h2>
                <flux:button variant="ghost" size="xs" wire:click="cancelForm">Tutup</flux:button>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Nama Lengkap</span>
                    <input type="text" wire:model.live.debounce.300ms="name"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Email</span>
                    <input type="email" wire:model.live.debounce.300ms="email"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Nomor Telepon</span>
                    <input type="text" wire:model.live.debounce.300ms="phone_number"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2" placeholder="08xxxxxxxxxx">
                    @error('phone_number') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Password {{ $editingUserId ? '(opsional)' : '' }}</span>
                    <input type="password" wire:model.live.debounce.300ms="password"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                    @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-medium text-zinc-700">Role</span>
                    <select wire:model.live="role_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                        <option value="">Pilih role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
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

                <div class="space-y-1 text-sm md:col-span-2">
                    <span class="font-medium text-zinc-700">Batas Waktu Pengisian Kuisioner</span>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <input type="number" wire:model.live="time_limit_hours" min="0" max="168" placeholder="0"
                                class="w-20 rounded-lg border border-zinc-300 px-3 py-2 text-center">
                            <span class="text-xs text-zinc-500">Jam</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <input type="number" wire:model.live="time_limit_minutes" min="0" max="59" placeholder="0"
                                class="w-20 rounded-lg border border-zinc-300 px-3 py-2 text-center">
                            <span class="text-xs text-zinc-500">Menit</span>
                        </div>
                        @if($time_limit_hours || $time_limit_minutes)
                            <span class="text-xs text-zinc-400">=
                                {{ ($time_limit_hours ?? 0) * 60 + ($time_limit_minutes ?? 0) }} menit total</span>
                        @endif
                    </div>
                    <span class="text-xs text-zinc-400">Kosongkan jika tidak ada batas waktu. Timer berlaku untuk satu sesi
                        pengisian semua kuisioner.</span>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Batal</flux:button>
                <flux:button variant="primary" wire:click="saveUser">{{ $editingUserId ? 'Update' : 'Simpan' }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm md:grid-cols-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama/email..."
            class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <select wire:model.live="roleFilter" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Semua role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
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
        <input type="text" wire:model.live.debounce.300ms="phoneFilter" placeholder="Filter no. telepon..."
            class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
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
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('id')"
                                class="inline-flex items-center gap-1">ID <span
                                    class="text-[10px] text-zinc-500">{{ $sortBy === 'id' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                        </th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('name')"
                                class="inline-flex items-center gap-1">Nama <span
                                    class="text-[10px] text-zinc-500">{{ $sortBy === 'name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                        </th>
                        <th class="px-4 py-3">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" wire:click="sortUsers('email')"
                                    class="inline-flex items-center gap-1">Kontak <span
                                        class="text-[10px] text-zinc-500">{{ $sortBy === 'email' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                                <button type="button" wire:click="sortUsers('phone_number')"
                                    class="inline-flex items-center gap-1 text-zinc-500">No. HP <span
                                        class="text-[10px] text-zinc-500">{{ $sortBy === 'phone_number' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                            </div>
                        </th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('role')"
                                class="inline-flex items-center gap-1">Role <span
                                    class="text-[10px] text-zinc-500">{{ $sortBy === 'role' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                        </th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('department')"
                                class="inline-flex items-center gap-1">Department <span
                                    class="text-[10px] text-zinc-500">{{ $sortBy === 'department' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                        </th>
                        <th class="px-4 py-3"><button type="button" wire:click="sortUsers('is_active')"
                                class="inline-flex items-center gap-1">Status <span
                                    class="text-[10px] text-zinc-500">{{ $sortBy === 'is_active' ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button>
                        </th>
                        <th class="px-4 py-3">Batas Waktu</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-zinc-500">{{ $user->id }}</td>
                            <td class="px-4 py-3 text-zinc-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-700">
                                <div class="flex min-w-[220px] flex-col">
                                    <span class="truncate">{{ $user->email }}</span>
                                    <span class="text-xs text-zinc-500">{{ $user->phone_number ?: '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-700">{{ $user->roleRef?->name ?: $user->role }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $user->departmentRef?->name ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-700' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->time_limit_minutes)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">
                                        {{ intdiv($user->time_limit_minutes, 60) }}j {{ $user->time_limit_minutes % 60 }}m
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-400">Tanpa batas</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if($user->filling_started_at || $user->responses()->where('status', 'submitted')->exists())
                                        <flux:button size="xs" variant="outline" wire:click="resetUserSession({{ $user->id }})"
                                            wire:confirm="Reset sesi pengisian {{ $user->name }}? Timer akan direset dan semua jawaban yang sudah dikirim akan dikembalikan ke draft."
                                            class="text-amber-700 ring-amber-300 hover:bg-amber-50">
                                            Reset Sesi
                                        </flux:button>
                                    @endif
                                    <flux:button size="xs" variant="outline" wire:click="startEdit({{ $user->id }})">Edit
                                    </flux:button>
                                    <flux:button size="xs" variant="danger" wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Hapus pengguna ini? data akan soft delete.">
                                        Hapus
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-zinc-500">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>