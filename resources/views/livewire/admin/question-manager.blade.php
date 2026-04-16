<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-zinc-900">Question Manager</h2>
            <p class="text-sm text-zinc-500">Kelola pertanyaan, opsi jawaban, dan urutan tampil kuisioner.</p>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="startCreate">
            Tambah Pertanyaan
        </flux:button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($showForm)
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-800">
                    {{ $editingQuestionId ? 'Edit Pertanyaan' : 'Tambah Pertanyaan' }}
                </h3>
                <flux:button variant="ghost" size="xs" wire:click="cancelForm">Tutup</flux:button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1 text-sm text-zinc-700 md:col-span-2">
                    <span class="font-medium">Question Text</span>
                    <textarea
                        wire:model.live.debounce.300ms="question_text"
                        rows="3"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                        placeholder="Tulis pertanyaan..."
                    ></textarea>
                    @error('question_text') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm text-zinc-700">
                    <span class="font-medium">Tipe Pertanyaan</span>
                    <select
                        wire:model.live="type"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                    >
                        <option value="single_choice">Single Choice</option>
                        <option value="essay">Essay</option>
                        <option value="combined">Combined</option>
                    </select>
                    @error('type') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 text-sm text-zinc-700">
                    <span class="font-medium">Is Required</span>
                    <select
                        wire:model.live="is_required"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                    >
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                    @error('is_required') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            @if (in_array($type, ['single_choice', 'combined'], true))
                <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-zinc-800">Answer Options</h4>
                        <flux:button variant="outline" size="xs" wire:click="addOption">Tambah Opsi</flux:button>
                    </div>

                    @error('options') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <div class="space-y-2">
                        @foreach ($options as $index => $option)
                            <div class="grid grid-cols-12 gap-2">
                                <input type="hidden" wire:model="options.{{ $index }}.id">
                                <div class="col-span-7">
                                    <input
                                        type="text"
                                        wire:model.live.debounce.300ms="options.{{ $index }}.option_text"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                        placeholder="Teks opsi"
                                    >
                                    @error("options.$index.option_text") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-3">
                                    <input
                                        type="number"
                                        wire:model.live.debounce.300ms="options.{{ $index }}.score"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                        placeholder="Skor"
                                    >
                                    @error("options.$index.score") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2">
                                    <flux:button
                                        variant="danger"
                                        size="xs"
                                        class="w-full"
                                        wire:click="removeOption({{ $index }})"
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 bg-white p-3">
                <h4 class="mb-1 text-sm font-semibold text-zinc-800">Preview Pertanyaan</h4>
                <p class="text-sm text-zinc-600">{{ $question_text ?: '-' }}</p>
                <p class="mt-1 text-xs text-zinc-500">Tipe: {{ $type }} | Required: {{ $is_required ? 'Ya' : 'Tidak' }}</p>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Batal</flux:button>
                <flux:button variant="primary" wire:click="saveQuestion">Simpan Pertanyaan</flux:button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Pertanyaan</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Required</th>
                        <th class="px-4 py-3">Preview Opsi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($questions as $question)
                        <tr class="align-top">
                            <td class="px-4 py-3">{{ $question->order }}</td>
                            <td class="px-4 py-3 text-zinc-800">{{ $question->question_text }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $question->type }}</td>
                            <td class="px-4 py-3 text-zinc-700">{{ $question->is_required ? 'Ya' : 'Tidak' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600">
                                @if ($question->answerOptions->isEmpty())
                                    <span>-</span>
                                @else
                                    <div class="space-y-1">
                                        @foreach ($question->answerOptions->sortBy('order')->take(3) as $option)
                                            <div>{{ $option->option_text }} ({{ $option->score ?? '-' }})</div>
                                        @endforeach
                                        @if ($question->answerOptions->count() > 3)
                                            <div>... +{{ $question->answerOptions->count() - 3 }} opsi</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="xs" variant="outline" wire:click="moveUp({{ $question->id }})">Up</flux:button>
                                    <flux:button size="xs" variant="outline" wire:click="moveDown({{ $question->id }})">Down</flux:button>
                                    <flux:button size="xs" variant="filled" wire:click="startEdit({{ $question->id }})">Edit</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="deleteQuestion({{ $question->id }})"
                                        wire:confirm="Hapus pertanyaan ini?"
                                    >
                                        Hapus
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                Belum ada pertanyaan untuk kuisioner ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
