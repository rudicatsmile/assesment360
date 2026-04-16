<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Kuisioner Tersedia</h2>
        <p class="text-sm text-zinc-500">Hanya kuisioner aktif yang sesuai role Anda dan belum pernah disubmit ditampilkan di sini.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($questionnaires as $questionnaire)
            <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3 class="text-base font-semibold text-zinc-900">{{ $questionnaire->title }}</h3>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">ACTIVE</span>
                </div>

                <p class="line-clamp-3 text-sm text-zinc-600">{{ $questionnaire->description ?: 'Tanpa deskripsi.' }}</p>

                <div class="mt-3 space-y-1 text-xs text-zinc-500">
                    <div>Aktif: {{ $questionnaire->start_date?->format('d M Y H:i') }} - {{ $questionnaire->end_date?->format('d M Y H:i') }}</div>
                    <div>Jumlah pertanyaan: {{ $questionnaire->questions_count }}</div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    @if ($questionnaire->responses->isNotEmpty())
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">Draft tersimpan</span>
                        <a href="{{ route('fill.questionnaires.show', $questionnaire) }}" wire:navigate>
                            <flux:button variant="primary" size="sm">Lanjut Isi</flux:button>
                        </a>
                    @else
                        <span class="text-xs text-zinc-500">Belum diisi</span>
                        <a href="{{ route('fill.questionnaires.show', $questionnaire) }}" wire:navigate>
                            <flux:button variant="primary" size="sm">Mulai Isi</flux:button>
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-center text-sm text-zinc-500 md:col-span-2">
                Tidak ada kuisioner aktif untuk role Anda saat ini.
            </div>
        @endforelse
    </div>
</div>
