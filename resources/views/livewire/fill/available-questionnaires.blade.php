<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Kuisioner Saya</h2>
        <p class="text-sm text-zinc-500">Daftar kuisioner aktif serta riwayat pengisian Anda.</p>
    </div>

    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-zinc-800">Tersedia Untuk Diisi</h3>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($questionnaires as $questionnaire)
                <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <h4 class="text-base font-semibold text-zinc-900">{{ $questionnaire->title }}</h4>
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
    </section>

    {{-- <section class="space-y-3">
        <h3 class="text-sm font-semibold text-zinc-800">Riwayat Pengisian (Belum Selesai)</h3>
        <div class="space-y-2">
            @forelse ($draftHistory as $response)
                <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm">
                    <div>
                        <p class="font-medium text-zinc-900">{{ $response->questionnaire?->title ?? 'Kuisioner' }}</p>
                        <p class="text-xs text-zinc-500">Terakhir diubah: {{ optional($response->updated_at)->format('d M Y H:i') }}</p>
                    </div>
                    @if ($response->questionnaire)
                        <a href="{{ route('fill.questionnaires.show', $response->questionnaire) }}" wire:navigate>
                            <flux:button variant="primary" size="sm">Lanjut Isi</flux:button>
                        </a>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm text-zinc-500">
                    Tidak ada draft pengisian.
                </div>
            @endforelse
        </div>
    </section>

    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-zinc-800">Riwayat Pengisian (Selesai)</h3>
        <div class="space-y-2">
            @forelse ($submittedHistory as $response)
                <div class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm">
                    <p class="font-medium text-zinc-900">{{ $response->questionnaire?->title ?? 'Kuisioner' }}</p>
                    <p class="text-xs text-zinc-500">Disubmit: {{ optional($response->submitted_at)->format('d M Y H:i') }}</p>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm text-zinc-500">
                    Belum ada kuisioner yang disubmit.
                </div>
            @endforelse
        </div>
    </section> --}}
</div>
