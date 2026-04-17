<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Questionnaire Analytics</h1>
            <p class="text-sm text-zinc-500">{{ $questionnaire->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.exports.questionnaire', $questionnaire) }}">
                <flux:button variant="filled" icon="arrow-down-tray">Export Excel</flux:button>
            </a>
            <a href="{{ route('admin.questionnaires.index') }}" wire:navigate>
                <flux:button variant="outline" icon="arrow-left">Kembali ke Daftar</flux:button>
            </a>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Rata-rata Keseluruhan</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ number_format($analytics['averages']['overall'], 2) }}</p>
        </article>
        @foreach ($roleSlugs as $slug)
            <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-zinc-500">Responden {{ $roleLabels[$slug] ?? str_replace('_', ' ', $slug) }}</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-900">{{ $analytics['respondent_breakdown'][$slug] ?? 0 }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-zinc-800">Rata-rata Skor per Kelompok</h2>
            <canvas id="group-average-chart" height="220"></canvas>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-zinc-800">Rata-rata Skor per Pertanyaan</h2>
            <canvas id="question-average-chart" height="220"></canvas>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-zinc-800">Detail Rata-rata Per Pertanyaan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3">Pertanyaan</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Jumlah Jawaban</th>
                        <th class="px-4 py-3">Rata-rata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($analytics['question_scores'] as $row)
                        <tr>
                            <td class="px-4 py-3 text-zinc-800">{{ $row['question_text'] }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $row['type'] }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $row['responses_count'] }}</td>
                            <td class="px-4 py-3 text-zinc-900">{{ number_format($row['average_score'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Belum ada data skor per pertanyaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-zinc-800">Distribusi Jawaban Pilihan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3">Pertanyaan</th>
                        <th class="px-4 py-3">Opsi</th>
                        <th class="px-4 py-3">Skor</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($analytics['distribution'] as $item)
                        <tr>
                            <td class="px-4 py-3 text-zinc-800">{{ $item['question_text'] }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $item['option_text'] }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $item['score'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $item['count'] }}</td>
                            <td class="px-4 py-3 text-zinc-900">{{ number_format($item['percentage'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Belum ada data distribusi jawaban.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @script
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        const groupCtx = document.getElementById('group-average-chart');
        const questionCtx = document.getElementById('question-average-chart');

        const groupLabels = @json($chartGroupLabels);
        const groupAverages = @json($chartGroupAverages);
        const questionLabels = @json($chartQuestionLabels);
        const questionAverages = @json($chartQuestionAverages);

        if (groupCtx) {
            const groupColors = groupLabels.map((_, idx) => ['#2563eb', '#16a34a', '#9333ea', '#ea580c', '#0d9488'][idx % 5]);
            new Chart(groupCtx, {
                type: 'bar',
                data: {
                    labels: groupLabels,
                    datasets: [{
                        label: 'Rata-rata skor',
                        data: groupAverages,
                        backgroundColor: groupColors,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, suggestedMax: 5 }
                    }
                }
            });
        }

        if (questionCtx) {
            new Chart(questionCtx, {
                type: 'line',
                data: {
                    labels: questionLabels,
                    datasets: [{
                        label: 'Rata-rata per pertanyaan',
                        data: questionAverages,
                        borderColor: '#111827',
                        backgroundColor: '#11182722',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, suggestedMax: 5 }
                    }
                }
            });
        }
    </script>
    @endscript
</div>
