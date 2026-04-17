<div
    class="space-y-5"
    x-data="{ showToast: false, toastMessage: '', toastType: 'heartbeat' }"
    @autosave-status.window="
        toastMessage = $event.detail.message;
        toastType = $event.detail.type ?? 'heartbeat';
        showToast = true;
        setTimeout(() => showToast = false, 1800);
    "
    @queue-autosave.window="setTimeout(() => { $wire.autosaveHeartbeat() }, 60)"
>
    @if ($showThankYou)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <h2 class="text-xl font-semibold text-emerald-800">Terima kasih!</h2>
            <p class="mt-2 text-sm text-emerald-700">Jawaban Anda sudah berhasil dikirim dan tidak dapat diubah lagi.</p>
            <div class="mt-4">
                <a href="{{ route('fill.questionnaires.index') }}" wire:navigate>
                    <flux:button variant="primary">Kembali ke Dashboard</flux:button>
                </a>
            </div>
        </div>
    @else
    <div class="rounded-xl border border-zinc-200 bg-white p-5">
        <h2 class="text-xl font-semibold text-zinc-900">{{ $questionnaire->title }}</h2>
        <p class="mt-1 text-sm text-zinc-600">{{ $questionnaire->description ?: 'Silakan isi kuisioner berikut dengan jujur dan objektif.' }}</p>
        @if ($lastDraftSavedAt)
            <p class="mt-2 text-xs text-zinc-500">Draft tersimpan otomatis pada {{ $lastDraftSavedAt }}</p>
        @endif
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between gap-2">
            <div class="text-sm font-medium text-zinc-800">
                Progress: {{ $answeredCount }} dari {{ $totalQuestions }} pertanyaan
            </div>
            <div class="text-xs text-zinc-500">{{ $progressPercent }}%</div>
        </div>

        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200">
            <div
                class="h-full rounded-full bg-zinc-800 transition-all duration-300"
                style="width: {{ $progressPercent }}%;"
            ></div>
        </div>
    </div>

    @if ($currentQuestion)
        <div
            wire:key="question-card-{{ $currentQuestion->id }}-{{ $currentIndex }}"
            class="relative rounded-xl border border-zinc-200 bg-white p-5"
            wire:loading.class="opacity-70"
            wire:target="nextQuestion,previousQuestion,goToQuestion"
        >
            <div
                wire:loading.flex
                wire:target="nextQuestion,previousQuestion,goToQuestion"
                class="absolute inset-0 z-20 items-center justify-center rounded-xl bg-white/70 text-sm font-medium text-zinc-700 backdrop-blur-[1px]"
            >
                Memuat pertanyaan...
            </div>

            <div class="mb-4 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Pertanyaan {{ $currentIndex + 1 }} / {{ $totalQuestions }}
            </div>

            <h3 class="text-base font-semibold text-zinc-900">{{ $currentQuestion->question_text }}</h3>
            <div class="mt-1 text-xs text-zinc-500">
                Tipe: {{ $currentQuestion->type }} | {{ $currentQuestion->is_required ? 'Wajib diisi' : 'Opsional' }}
            </div>

            @if ($currentQuestion->type === 'single_choice')
                <div class="mt-4 space-y-2">
                    @foreach ($currentQuestion->answerOptions as $option)
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700">
                            <input
                                type="radio"
                                wire:model.live="answers.{{ $currentQuestion->id }}.answer_option_id"
                                name="question_{{ $currentQuestion->id }}"
                                value="{{ $option->id }}"
                                class="mt-0.5 border-zinc-300"
                            >
                            <span>{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                    @error("answers.$currentQuestion->id.answer_option_id")
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if ($currentQuestion->type === 'essay')
                <div class="mt-4 space-y-2">
                    <textarea
                        wire:model.live.debounce.250ms="answers.{{ $currentQuestion->id }}.essay_answer"
                        rows="5"
                        maxlength="2000"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                        placeholder="Tulis jawaban Anda..."
                    ></textarea>
                    <div class="text-xs text-zinc-500">
                        {{ strlen($answers[$currentQuestion->id]['essay_answer'] ?? '') }} / 2000 karakter
                    </div>
                    @error("answers.$currentQuestion->id.essay_answer")
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if ($currentQuestion->type === 'combined')
                <div class="mt-4 space-y-3">
                    <div class="space-y-2">
                        @foreach ($currentQuestion->answerOptions as $option)
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700">
                                <input
                                    type="radio"
                                    wire:model.live="answers.{{ $currentQuestion->id }}.answer_option_id"
                                    name="question_combined_{{ $currentQuestion->id }}"
                                    value="{{ $option->id }}"
                                    class="mt-0.5 border-zinc-300"
                                >
                                <span>{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                        @error("answers.$currentQuestion->id.answer_option_id")
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (($answers[$currentQuestion->id]['answer_option_id'] ?? null) !== null)
                        <div class="space-y-2">
                            <textarea
                                wire:model.live.debounce.250ms="answers.{{ $currentQuestion->id }}.essay_answer"
                                rows="4"
                                maxlength="2000"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                placeholder="Tuliskan alasan Anda..."
                            ></textarea>
                            <div class="text-xs text-zinc-500">
                                {{ strlen($answers[$currentQuestion->id]['essay_answer'] ?? '') }} / 2000 karakter
                            </div>
                            @error("answers.$currentQuestion->id.essay_answer")
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">Pilih opsi jawaban terlebih dahulu untuk menampilkan area alasan.</p>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex items-center justify-between gap-2">
                <flux:button
                    variant="ghost"
                    icon="arrow-left"
                    wire:click="previousQuestion"
                    :disabled="$currentIndex === 0"
                    wire:loading.attr="disabled"
                    wire:target="nextQuestion,previousQuestion,goToQuestion"
                >
                    Sebelumnya
                </flux:button>

                <div class="flex items-center gap-2">
                    <a href="{{ route('fill.questionnaires.index') }}" wire:navigate>
                        <flux:button variant="outline">Kembali</flux:button>
                    </a>
                    <flux:button
                        variant="primary"
                        icon="arrow-right"
                        wire:click="nextQuestion"
                        :disabled="$currentIndex >= $totalQuestions - 1"
                        wire:loading.attr="disabled"
                        wire:target="nextQuestion,previousQuestion,goToQuestion"
                    >
                        Berikutnya
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm text-zinc-600">
            Tidak ada pertanyaan pada kuisioner ini.
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Navigasi Cepat</div>
        <div class="flex flex-wrap gap-2">
            @for ($i = 0; $i < $totalQuestions; $i++)
                <flux:button
                    size="xs"
                    :variant="$i === $currentIndex ? 'primary' : 'outline'"
                    wire:click="goToQuestion({{ $i }})"
                    wire:loading.attr="disabled"
                    wire:target="nextQuestion,previousQuestion,goToQuestion"
                >
                    {{ $i + 1 }}
                </flux:button>
            @endfor
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5">
        <div class="mb-2 text-sm font-semibold text-zinc-800">Finalisasi Pengisian</div>
        <p class="text-xs text-zinc-500">
            Terjawab wajib: {{ $answeredRequiredCount }} / {{ $requiredQuestionCount }}
        </p>
        <div class="mt-4 flex justify-end">
            <flux:button
                variant="primary"
                icon="check"
                wire:click="openSubmitConfirmation"
                :disabled="$totalQuestions === 0"
            >
                Submit Kuisioner
            </flux:button>
        </div>
    </div>

    @if ($showSubmitConfirmation)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold text-zinc-900">Konfirmasi Submit</h3>
                <p class="mt-2 text-sm text-zinc-600">
                    Pastikan jawaban sudah benar. Setelah submit, Anda tidak dapat mengubah jawaban lagi.
                </p>

                <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm text-zinc-700">
                    <div>Total pertanyaan: {{ $totalQuestions }}</div>
                    <div>Jawaban terisi: {{ $answeredCount }}</div>
                    <div>Wajib terisi: {{ $answeredRequiredCount }} / {{ $requiredQuestionCount }}</div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeSubmitConfirmation">Batal</flux:button>
                    <flux:button variant="primary" wire:click="submitFinal">Ya, Submit Sekarang</flux:button>
                </div>
            </div>
        </div>
    @endif
    @endif

    <div
        x-show="showToast"
        x-transition.opacity.duration.200ms
        class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-white shadow-lg"
        :class="toastType === 'manual' ? 'bg-emerald-600' : 'bg-sky-700'"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        style="display: none;"
    >
        <span x-show="toastType === 'manual'" aria-hidden="true">✓</span>
        <span x-show="toastType !== 'manual'" aria-hidden="true">⟳</span>
        <span x-text="toastMessage"></span>
    </div>
</div>
