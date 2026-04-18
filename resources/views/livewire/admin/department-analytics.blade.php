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
                                    <td class="px-4 py-3 font-medium text-zinc-900">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 text-left hover:underline"
                                            wire:click="toggleRole({{ $roleRow['role_id'] }})"
                                        >
                                            <span>{{ $roleRow['role_name'] }}</span>
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                                style="
                                                    width: 14px;
                                                    height: 14px;
                                                    min-width: 14px;
                                                    display: inline-block;
                                                    vertical-align: middle;
                                                    transition: transform 320ms ease;
                                                    transform-origin: 50% 50%;
                                                    transform: {{ $expandedRoleId === $roleRow['role_id'] ? 'rotate(180deg)' : 'rotate(0deg)' }};
                                                "
                                            >
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['total_respondents'], 0) }}</td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['participation_rate'], 1) }}%</td>
                                    <td class="px-4 py-3 text-zinc-700">{{ number_format($roleRow['average_score'], 2) }}</td>
                                </tr>

                                @if ($expandedRoleId === $roleRow['role_id'])
                                    <tr>
                                        <td colspan="4" class="bg-zinc-50 px-4 py-3">
                                            <div x-data="{ open: true }" x-show="open" x-transition.duration.350ms>
                                                <div wire:init="loadRoleUsers({{ $roleRow['role_id'] }})" class="space-y-2">
                                                    @if (!array_key_exists($roleRow['role_id'], $roleUsersByRole) && !array_key_exists($roleRow['role_id'], $roleUsersErrorByRole))
                                                        <div class="space-y-2">
                                                            <div class="h-4 animate-pulse rounded bg-zinc-200"></div>
                                                            <div class="h-4 animate-pulse rounded bg-zinc-200"></div>
                                                            <div class="h-4 animate-pulse rounded bg-zinc-200"></div>
                                                        </div>
                                                    @elseif (array_key_exists($roleRow['role_id'], $roleUsersErrorByRole))
                                                        <div class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                                            {{ $roleUsersErrorByRole[$roleRow['role_id']] }}
                                                        </div>
                                                    @elseif (($roleUsersByRole[$roleRow['role_id']] ?? []) === [])
                                                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-600">
                                                            Tidak ada user pada role ini.
                                                        </div>
                                                    @else
                                                        <div class="rounded border border-zinc-200 bg-white">
                                                            @foreach (($roleUsersByRole[$roleRow['role_id']] ?? []) as $userRow)
                                                                <div class="border-b border-zinc-100 px-3 py-2 last:border-b-0">
                                                                    <p class="text-sm text-zinc-800">
                                                                        {{ $userRow['user_name'] }} - Submit: {{ number_format($userRow['total_submissions'], 0) }} - Avg Score: {{ number_format($userRow['average_score'], 2) }}
                                                                    </p>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
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
