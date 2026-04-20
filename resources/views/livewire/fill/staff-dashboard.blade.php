<div class="space-y-5">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Dashboard
            {{ config('rbac.role_labels.' . config('rbac.dashboard_role_slugs.staff'), 'Evaluator') }}</h2>
        <p class="text-sm text-zinc-500">Pantau kuisioner yang tersedia dan histori pengisian.</p>
    </div>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Kuisioner Aktif</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $payload['stats']['active_questionnaires'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Tersedia Untuk Diisi</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $payload['stats']['available_to_fill'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Sudah Diisi</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $payload['stats']['completed_total'] }}</p>
        </article>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-zinc-800">Kuisioner Tersedia</h3>
        <div class="space-y-2">
            @forelse ($payload['available'] as $questionnaire)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">{{ $questionnaire->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $questionnaire->questions_count }} pertanyaan</p>
                    </div>
                    <a href="{{ route('fill.questionnaires.index') }}" wire:navigate>
                        <flux:button size="sm" variant="primary">Isi</flux:button>
                    </a>
                </div>
            @empty
                <p class="text-sm text-zinc-500">Tidak ada kuisioner aktif saat ini.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-zinc-800">Riwayat Pengisian</h3>
        <div class="space-y-2">
            @forelse ($payload['completed'] as $response)
                <div class="rounded-lg border border-zinc-200 px-3 py-2">
                    <p class="text-sm font-medium text-zinc-900">{{ $response->questionnaire->title }}</p>
                    <p class="text-xs text-zinc-500">Disubmit: {{ optional($response->submitted_at)->format('d M Y H:i') }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-zinc-500">Belum ada kuisioner yang Anda submit.</p>
            @endforelse
        </div>
    </section>
</div>