<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Analytics</h1>
            <p class="text-sm text-zinc-500">Analisis hasil penilaian berdasarkan departemen.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $this->exportExcelUrl() }}">
                <flux:button variant="filled" icon="arrow-down-tray">Export Excel</flux:button>
            </a>
            <a href="{{ $this->exportPdfUrl() }}" target="_blank">
                <flux:button variant="outline" icon="document">Export PDF</flux:button>
            </a>
        </div>
    </div>

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm md:grid-cols-4">
        <label class="space-y-1 text-sm">
            <span class="font-medium text-zinc-700">Dari Tanggal</span>
            <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
        </label>
        <label class="space-y-1 text-sm">
            <span class="font-medium text-zinc-700">Sampai Tanggal</span>
            <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
        </label>
        <label class="space-y-1 text-sm">
            <span class="font-medium text-zinc-700">Department</span>
            <select wire:model.live="departmentFilter" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                <option value="">Semua Department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-1 text-sm">
            <span class="font-medium text-zinc-700">Data / Halaman</span>
            <select wire:model.live="perPage" class="w-full rounded-lg border border-zinc-300 px-3 py-2">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </label>
    </div>

    @if ($errorMessage)
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errorMessage }}
        </div>
    @endif

    <div wire:loading.flex class="items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
        <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-zinc-500"></span>
        <span>Memuat analitik...</span>
    </div>

    <div wire:loading.flex wire:target="selectDepartment" class="items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
        <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
        <span>Memuat analitik role department...</span>
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-zinc-800">Rata-rata Skor Antar Department</h2>
            <canvas id="department-score-bar" height="220"></canvas>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-zinc-800">Tingkat Partisipasi per Department (%)</h2>
            <canvas id="department-participation-donut" height="220"></canvas>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3"><button type="button" wire:click="sort('name')">Nama Departemen</button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sort('total_respondents')">Total Responden</button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sort('participation_rate')">Tingkat Partisipasi</button></th>
                        <th class="px-4 py-3"><button type="button" wire:click="sort('average_score')">Rata-rata Skor</button></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($rows as $row)
                        <tr
                            class="cursor-pointer hover:bg-zinc-50"
                            wire:click="selectDepartment({{ (int) $row->id }})"
                        >
                            <td class="px-4 py-3 font-medium text-zinc-900">
                                <button type="button" class="text-left hover:underline">
                                    {{ $row->name }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-zinc-700">{{ (int) $row->total_respondents }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ number_format((float) $row->participation_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-zinc-700">{{ number_format((float) $row->average_score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Belum ada data analitik department.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $rows->links() }}

    @if ($selectedDepartmentId)
        <section class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-zinc-900">
                    Role Analytics - {{ $selectedDepartmentName !== '' ? $selectedDepartmentName : 'Department #' . $selectedDepartmentId }}
                </h2>
                <flux:button variant="ghost" size="sm" wire:click="clearSelectedDepartment">
                    Kembali Ke Semua Department
                </flux:button>
            </div>

            @if ($roleErrorMessage)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    {{ $roleErrorMessage }}
                </div>
            @elseif ($roleRows === [])
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                    Data role tidak tersedia.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            <tr>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Total Responden</th>
                                <th class="px-4 py-3">Tingkat Partisipasi</th>
                                <th class="px-4 py-3">Rata-rata Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($roleRows as $roleRow)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-zinc-900">{{ $roleRow['role_name'] }}</td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['total_respondents'], 0) }}</td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['participation_rate'], 1) }}%</td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['average_score'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @script
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            const labels = @json($chart['labels']);
            const averageScores = @json($chart['average_scores']);
            const participationRates = @json($chart['participation_rates']);

            const scoreCanvas = document.getElementById('department-score-bar');
            const participationCanvas = document.getElementById('department-participation-donut');

            if (window.departmentScoreChart) window.departmentScoreChart.destroy();
            if (window.departmentParticipationChart) window.departmentParticipationChart.destroy();

            if (scoreCanvas) {
                window.departmentScoreChart = new Chart(scoreCanvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Rata-rata skor',
                            data: averageScores,
                            backgroundColor: '#2563eb',
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, suggestedMax: 5 } }
                    }
                });
            }

            if (participationCanvas) {
                window.departmentParticipationChart = new Chart(participationCanvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: participationRates,
                            backgroundColor: ['#16a34a', '#2563eb', '#9333ea', '#ea580c', '#0f766e', '#be185d'],
                        }]
                    },
                    options: { responsive: true }
                });
            }
        </script>
    @endscript
</div>
