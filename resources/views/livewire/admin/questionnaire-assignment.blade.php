<div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4">
    <div>
        <h2 class="text-base font-semibold text-zinc-900">Target Group Assignment</h2>
        <p class="text-sm text-zinc-500">Pilih kelompok penilai yang dapat melihat dan mengisi kuisioner ini.</p>
    </div>

    @if ($savedMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
            {{ $savedMessage }}
        </div>
    @endif

    <div class="grid gap-2 md:grid-cols-3">
        @foreach ($availableTargetGroups as $group)
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700">
                <input
                    type="checkbox"
                    value="{{ $group }}"
                    wire:model.live="selectedTargetGroups"
                    @disabled(count($selectedTargetGroups) === 1 && in_array($group, $selectedTargetGroups, true))
                    class="rounded border-zinc-300"
                >
                <span>{{ $targetGroupLabels[$group] ?? str_replace('_', ' ', $group) }}</span>
            </label>
        @endforeach
    </div>

    @error('selectedTargetGroups')
        <span class="text-xs text-red-600">{{ $message }}</span>
    @enderror
    @error('selectedTargetGroups.*')
        <span class="text-xs text-red-600">{{ $message }}</span>
    @enderror

    <p class="text-xs text-zinc-500">Minimal 1 target group harus tetap terpilih.</p>
</div>
