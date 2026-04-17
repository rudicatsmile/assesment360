<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Admin Dashboard</h1>
            <p class="text-sm text-zinc-500">Ringkasan partisipasi dan skor kuisioner secara keseluruhan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.exports.all') }}">
                <flux:button variant="filled" icon="arrow-down-tray">Export Semua (Excel)</flux:button>
            </a>
            <a href="{{ route('admin.questionnaires.index') }}" wire:navigate>
                <flux:button variant="outline" icon="clipboard-document-list">Lihat Daftar Kuisioner</flux:button>
            </a>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Total Kuisioner Aktif</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $metrics['total_active_questionnaires'] }}</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Total Responden</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $metrics['total_respondents'] }}</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Tingkat Partisipasi</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ number_format($metrics['participation_rate'], 2) }}%</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Rata-rata Skor</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ number_format($metrics['average_score'], 2) }}</p>
        </article>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-zinc-800">Breakdown Responden Per Kelompok</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-3">
            @foreach ($metrics['breakdown_cards'] as $item)
                <article class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $item['total'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
</div>
