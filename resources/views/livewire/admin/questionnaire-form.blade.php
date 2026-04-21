<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">
                {{ $this->isEdit ? 'Edit Questionnaire' : 'Buat Questionnaire' }}
            </h1>
            <p class="text-sm text-zinc-500">
                Lengkapi informasi kuisioner dan tentukan target group penilai.
            </p>
        </div>

        <a href="{{ route('admin.questionnaires.index') }}" wire:navigate>
            <flux:button variant="ghost" icon="arrow-left">Kembali ke List</flux:button>
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 md:grid-cols-2">
            <label class="space-y-1 text-sm text-zinc-700 md:col-span-2">
                <span class="font-medium">Title</span>
                <input type="text" wire:model.live.debounce.300ms="title"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                    placeholder="Contoh: Penilaian Kinerja Kepala Sekolah Semester Ganjil">
                @error('title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-1 text-sm text-zinc-700 md:col-span-2">
                <span class="font-medium">Description</span>
                <textarea wire:model.live.debounce.300ms="description" rows="4"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                    placeholder="Deskripsi singkat kuisioner"></textarea>
                @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-1 text-sm text-zinc-700">
                <span class="font-medium">Start Date</span>
                <input type="datetime-local" wire:model.live="start_date"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                @error('start_date') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-1 text-sm text-zinc-700">
                <span class="font-medium">End Date</span>
                <input type="datetime-local" wire:model.live="end_date"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                @error('end_date') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-1 text-sm text-zinc-700">
                <span class="font-medium">Time Limit (menit)</span>
                <input type="number" wire:model.live.debounce.300ms="time_limit_minutes" min="1" max="10080"
                    placeholder="Kosongkan jika tidak ada batas waktu"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                <span class="text-xs text-zinc-400">Biarkan kosong untuk tanpa batas waktu. Maks 10080 menit (7
                    hari).</span>
                @error('time_limit_minutes') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="space-y-1 text-sm text-zinc-700">
                <span class="font-medium">Status</span>
                <select wire:model.live="status"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
                @error('status') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            @if (!$this->isEdit)
                <fieldset class="space-y-2 text-sm text-zinc-700">
                    <legend class="font-medium">Target Groups</legend>
                    @foreach ($availableTargetGroups as $group)
                        <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2">
                            <input type="checkbox" value="{{ $group['slug'] }}" wire:model.live="target_groups"
                                @disabled(count($target_groups) === 1 && in_array($group['slug'], $target_groups, true))
                                class="rounded border-zinc-300">
                            <span>{{ $group['name'] }}</span>
                        </label>
                    @endforeach
                    @error('target_groups') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    @error('target_groups.*') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-zinc-500">Minimal 1 target group harus tetap terpilih.</p>
                </fieldset>
            @else
                <div class="space-y-2 text-sm text-zinc-700">
                    <span class="font-medium">Target Groups</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($target_groups as $group)
                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700">
                                {{ $targetGroupLabels[$group] ?? str_replace('_', ' ', $group) }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-zinc-500">Ubah assignment target via panel "Target Group Assignment" di bawah.
                    </p>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold text-zinc-800">Preview Sederhana</h2>
            <div class="space-y-1 text-sm text-zinc-600">
                <p><span class="font-medium text-zinc-800">Judul:</span> {{ $title ?: '-' }}</p>
                <p><span class="font-medium text-zinc-800">Periode:</span> {{ $start_date ?: '-' }} s/d
                    {{ $end_date ?: '-' }}</p>
                <p><span class="font-medium text-zinc-800">Status:</span> {{ strtoupper($status) }}</p>
                <p><span class="font-medium text-zinc-800">Batas Waktu:</span>
                    {{ $time_limit_minutes ? $time_limit_minutes . ' menit' : 'Tidak ada' }}</p>
                <p><span class="font-medium text-zinc-800">Target:</span>
                    {{ $target_groups ? implode(', ', array_map(fn($slug) => $targetGroupLabels[$slug] ?? str_replace('_', ' ', $slug), $target_groups)) : '-' }}
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.questionnaires.index') }}" wire:navigate>
                <flux:button variant="ghost">Batal</flux:button>
            </a>
            <flux:button variant="primary" type="submit" icon="check">
                {{ $this->isEdit ? 'Update Questionnaire' : 'Simpan Questionnaire' }}
            </flux:button>
        </div>
    </form>

    @if ($this->isEdit)
        <livewire:admin.questionnaire-assignment :questionnaire="$questionnaire" />

        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-zinc-900">Kelola Pertanyaan</h2>
                <a href="{{ route('admin.questionnaires.questions', $questionnaire) }}" wire:navigate>
                    <flux:button variant="outline" size="sm">Halaman Penuh Question Manager</flux:button>
                </a>
            </div>

            <livewire:admin.question-manager :questionnaire="$questionnaire" />
        </div>
    @endif
</div>