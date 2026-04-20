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
        <article class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-800">Rata-rata Skor Antar Department</h2>
                <div wire:loading.remove wire:target="dateFrom,dateTo,departmentFilter" class="opacity-0 transition-opacity group-hover:opacity-100">
                    <button wire:click="refreshCharts" class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600" title="Refresh Chart">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div wire:loading wire:target="dateFrom,dateTo,departmentFilter" class="flex h-56 items-center justify-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-zinc-200 border-t-blue-600"></div>
                    <span class="text-xs text-zinc-500">Memuat grafik...</span>
                </div>
            </div>
            <canvas wire:loading.remove wire:target="dateFrom,dateTo,departmentFilter" id="department-score-bar" class="transition-opacity group-hover:opacity-[0.98]" height="220"></canvas>
            @if (count($chart['labels']) === 0)
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-50 text-center">
                    <svg class="mb-2 h-12 w-12 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm text-zinc-500">Belum ada data department</p>
                    <p class="mt-1 text-xs text-zinc-400">Data akan muncul setelah ada responden</p>
                </div>
            @endif
        </article>

        <article class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-800">Tingkat Partisipasi per Department (%)</h2>
                <div wire:loading.remove wire:target="dateFrom,dateTo,departmentFilter" class="opacity-0 transition-opacity group-hover:opacity-100">
                    <button wire:click="refreshCharts" class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600" title="Refresh Chart">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div wire:loading wire:target="dateFrom,dateTo,departmentFilter" class="flex h-56 items-center justify-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-zinc-200 border-t-emerald-600"></div>
                    <span class="text-xs text-zinc-500">Memuat grafik...</span>
                </div>
            </div>
            <canvas wire:loading.remove wire:target="dateFrom,dateTo,departmentFilter" id="department-participation-donut" class="transition-opacity group-hover:opacity-[0.98]" height="220"></canvas>
            @if (count($chart['labels']) === 0)
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-50 text-center">
                    <svg class="mb-2 h-12 w-12 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    <p class="text-sm text-zinc-500">Belum ada data department</p>
                    <p class="mt-1 text-xs text-zinc-400">Data akan muncul setelah ada responden</p>
                </div>
            @endif
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
        <script>
            const chartTheme = {
                primary: '#2563eb',
                primaryLight: 'rgba(37, 99, 235, 0.1)',
                colors: [
                    '#2563eb', '#16a34a', '#9333ea', '#ea580c', '#0f766e', '#be185d',
                    '#0891b2', '#ca8a04', '#7c3aed', '#dc2626', '#65a30d', '#6366f1'
                ],
                textColor: '#3f3f46',
                gridColor: 'rgba(0, 0, 0, 0.06)',
            };

            const defaultTooltipConfig = {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#ffffff',
                bodyColor: '#e4e4e7',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 12,
                displayColors: true,
                boxPadding: 6,
            };

            function initializeBarChart(canvas, labels, data) {
                if (!canvas || !labels.length) return null;

                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Rata-rata Skor',
                            data,
                            backgroundColor: chartTheme.primary,
                            hoverBackgroundColor: '#1d4ed8',
                            borderColor: chartTheme.primary,
                            borderWidth: 0,
                            borderRadius: 10,
                            borderSkipped: false,
                            hoverOffset: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                ...defaultTooltipConfig,
                                callbacks: {
                                    title: (items) => items[0]?.label || '',
                                    label: (context) => {
                                        const value = context.parsed?.y ?? 0;
                                        const maxScore = 5;
                                        const percentage = Math.round((value / maxScore) * 100);
                                        return [
                                            `Skor: ${value.toFixed(2)}`,
                                            `Progress: ${percentage}%`
                                        ];
                                    },
                                    afterLabel: (context) => {
                                        const value = context.parsed?.y ?? 0;
                                        if (value >= 4) return '⭐ Excellent';
                                        if (value >= 3) return '👍 Good';
                                        if (value >= 2) return '⚠️ Needs Improvement';
                                        return '❌ Poor';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                grid: {
                                    color: chartTheme.gridColor,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: chartTheme.textColor,
                                    font: { size: 11, weight: '500' },
                                    stepSize: 1,
                                    callback: function(value) {
                                        return value.toFixed(0);
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    color: chartTheme.textColor,
                                    font: { size: 10 },
                                    maxRotation: 45,
                                    minRotation: 0,
                                }
                            }
                        },
                        animation: {
                            duration: 800,
                            easing: 'easeOutQuart',
                        }
                    }
                });
            }

            function initializeDoughnutChart(canvas, labels, data) {
                if (!canvas || !labels.length) return null;

                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data,
                            backgroundColor: chartTheme.colors.slice(0, labels.length),
                            hoverBackgroundColor: chartTheme.colors.slice(0, labels.length).map(c => c),
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 12,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: chartTheme.textColor,
                                    font: { size: 11, weight: '500' },
                                    padding: 16,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return {
                                                    text: `${label} (${percentage}%)`,
                                                    fillStyle: chartTheme.colors[i % chartTheme.colors.length],
                                                    strokeStyle: chartTheme.colors[i % chartTheme.colors.length],
                                                    hidden: false,
                                                    index: i,
                                                    pointStyle: 'circle',
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                ...defaultTooltipConfig,
                                callbacks: {
                                    title: (items) => items[0]?.label || '',
                                    label: (context) => {
                                        const value = context.parsed ?? 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return [
                                            `Partisipasi: ${value.toFixed(1)}%`,
                                            `Jumlah: ${total > 0 ? (value / 100 * total).toFixed(1) : 0} responden`
                                        ];
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 1000,
                            easing: 'easeOutQuart',
                        }
                    }
                });
            }

            const scoreCanvas = document.getElementById('department-score-bar');
            const participationCanvas = document.getElementById('department-participation-donut');

            if (window.departmentScoreChart) {
                window.departmentScoreChart.destroy();
                window.departmentScoreChart = null;
            }
            if (window.departmentParticipationChart) {
                window.departmentParticipationChart.destroy();
                window.departmentParticipationChart = null;
            }

            const labels = @json($chart['labels']);
            const averageScores = @json($chart['average_scores']);
            const participationRates = @json($chart['participation_rates']);

            if (scoreCanvas && labels.length > 0) {
                 window.departmentScoreChart = initializeBarChart(scoreCanvas, labels, averageScores);
             }

             if (participationCanvas && labels.length > 0) {
                 window.departmentParticipationChart = initializeDoughnutChart(participationCanvas, labels, participationRates);
             }

             if (typeof Chart !== 'undefined') {
                 const chartjsStatus = document.getElementById('chartjs-status');
                 if (chartjsStatus) {
                     chartjsStatus.textContent = 'YES ✓';
                     chartjsStatus.className = 'text-green-700 font-semibold';
                 }
             } else {
                 const chartjsStatus = document.getElementById('chartjs-status');
                 if (chartjsStatus) {
                     chartjsStatus.textContent = 'NO ✗ - CDN Failed';
                     chartjsStatus.className = 'text-red-700 font-semibold';
                 }
             }

             window.addEventListener('chart-data-refreshed', async function() {
                try {
                    const response = await fetch('?chart=1', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        const result = await response.json();

                        if (result.success && result.data) {
                            const newLabels = result.data.labels;
                            const newScores = result.data.average_scores;
                            const newRates = result.data.participation_rates;

                            if (window.departmentScoreChart) {
                                window.departmentScoreChart.data.labels = newLabels;
                                window.departmentScoreChart.data.datasets[0].data = newScores;
                                window.departmentScoreChart.update('active');
                            }

                            if (window.departmentParticipationChart) {
                                window.departmentParticipationChart.data.labels = newLabels;
                                window.departmentParticipationChart.data.datasets[0].data = newRates;
                                window.departmentParticipationChart.data.datasets[0].backgroundColor = chartTheme.colors.slice(0, newLabels.length);
                                window.departmentParticipationChart.update('active');
                            }
                        }
                    }
                } catch (error) {
                    console.error('Failed to refresh chart data:', error);
                }
            });

            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', (message, component) => {
                    if (component.name === 'admin.department-analytics') {
                        const newLabels = @json($chart['labels']);
                        const newScores = @json($chart['average_scores']);
                        const newRates = @json($chart['participation_rates']);

                        if (window.departmentScoreChart) {
                            window.departmentScoreChart.data.labels = newLabels;
                            window.departmentScoreChart.data.datasets[0].data = newScores;
                            window.departmentScoreChart.update('active');
                        }

                        if (window.departmentParticipationChart) {
                            window.departmentParticipationChart.data.labels = newLabels;
                            window.departmentParticipationChart.data.datasets[0].data = newRates;
                            window.departmentParticipationChart.data.datasets[0].backgroundColor = chartTheme.colors.slice(0, newLabels.length);
                            window.departmentParticipationChart.update('active');
                        }
                    }
                });
            }
        </script>
    @endscript
</div>
