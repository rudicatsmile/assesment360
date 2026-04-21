<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Questionnaire Management</h1>
            <p class="text-sm text-zinc-500">Kelola kuisioner, status publikasi, dan target penilai.</p>
        </div>

        <a href="{{ route('admin.questionnaires.create') }}" wire:navigate>
            <flux:button variant="primary" icon="plus">Buat Kuisioner</flux:button>
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 md:grid-cols-3">
        <label class="space-y-1 text-sm text-zinc-700">
            <span class="font-medium">Cari Kuisioner</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Judul atau deskripsi..."
                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
        </label>

        <label class="space-y-1 text-sm text-zinc-700">
            <span class="font-medium">Status</span>
            <select wire:model.live="status"
                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="closed">Closed</option>
            </select>
        </label>

        <label class="space-y-1 text-sm text-zinc-700">
            <span class="font-medium">Target Group</span>
            <select wire:model.live="targetGroup"
                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                <option value="">Semua Target Group</option>
                @foreach ($targetGroupOptions as $option)
                    <option value="{{ $option['slug'] }}">{{ $option['name'] }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Target Group</th>
                        <th class="px-4 py-3">Statistik</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($questionnaires as $questionnaire)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900">{{ $questionnaire->title }}</div>
                                <div class="mt-1 line-clamp-2 text-xs text-zinc-500">{{ $questionnaire->description }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-700">
                                {{ $questionnaire->start_date?->format('d M Y H:i') }}<br>
                                <span class="text-xs text-zinc-500">s/d
                                    {{ $questionnaire->end_date?->format('d M Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($questionnaire->targets as $target)
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                                            {{ str_replace('_', ' ', $target->target_group) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-600">
                                <div>{{ $questionnaire->questions_count }} pertanyaan</div>
                                <div>{{ $questionnaire->responses_count }} respons</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">
                                    {{ strtoupper($questionnaire->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.questionnaires.show', $questionnaire) }}" wire:navigate>
                                        <flux:button size="xs" variant="filled" icon="chart-bar">Analytics</flux:button>
                                    </a>
                                    <a href="{{ route('admin.questionnaires.edit', $questionnaire) }}" wire:navigate>
                                        <flux:button size="xs" variant="outline" icon="pencil-square">Edit</flux:button>
                                    </a>

                                    @if ($questionnaire->status !== 'active')
                                        <flux:button size="xs" variant="primary" wire:click="publish({{ $questionnaire->id }})"
                                            wire:confirm="Publish kuisioner ini?">
                                            Publish
                                        </flux:button>
                                    @endif

                                    @if ($questionnaire->status !== 'closed')
                                        <flux:button size="xs" variant="filled" wire:click="close({{ $questionnaire->id }})"
                                            wire:confirm="Tutup kuisioner ini?">
                                            Close
                                        </flux:button>
                                    @endif

                                    <flux:button size="xs" variant="danger" wire:click="delete({{ $questionnaire->id }})"
                                        wire:confirm="Hapus kuisioner ini?">
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                Belum ada data kuisioner.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $questionnaires->links() }}
    </div>
</div>