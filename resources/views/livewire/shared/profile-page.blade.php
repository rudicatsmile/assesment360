<div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
    <div>
        <h2 class="text-xl font-semibold text-zinc-900">Profil Pengguna</h2>
        <p class="text-sm text-zinc-500">Informasi akun yang sedang login.</p>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Nama</p>
            <p class="mt-1 text-sm font-medium text-zinc-900">{{ auth()->user()?->name ?? '-' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Email</p>
            <p class="mt-1 text-sm font-medium text-zinc-900">{{ auth()->user()?->email ?? '-' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Role</p>
            <p class="mt-1 text-sm font-medium text-zinc-900">{{ auth()->user()?->roleRef?->name ?? auth()->user()?->roleSlug() ?? '-' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Terakhir Login</p>
            <p class="mt-1 text-sm font-medium text-zinc-900">{{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>
