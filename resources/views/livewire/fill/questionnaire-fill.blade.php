<div
    class="space-y-5"
    x-data="{
        showToast: false,
        toastMessage: '',
        toastType: 'heartbeat',
        validationErrors: [],
        invalidQuestionIds: [],
        invalidEssayQuestionIds: [],
        clearValidationState() {
            this.validationErrors = [];
            this.invalidQuestionIds = [];
            this.invalidEssayQuestionIds = [];
        },
        validateBeforeSubmit() {
            this.clearValidationState();

            const blocks = Array.from(this.$root.querySelectorAll('[data-question-block]'));

            blocks.forEach((block) => {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) return;

                const questionId = Number(block.dataset.questionId || 0);
                const questionType = String(block.dataset.questionType || '');
                const questionNumber = String(block.dataset.questionNumber || '');
                const questionLabel = String(block.dataset.questionLabel || '').trim();
                const displayName = questionLabel !== '' ? questionLabel : `Pertanyaan ${questionNumber}`;

                const hasSelectedRadio = block.querySelector('input[type=radio]:checked') !== null;
                const hasCheckedBox = block.querySelector('input[type=checkbox]:checked') !== null;
                const hasSelectedDropdown = Array.from(block.querySelectorAll('select')).some((el) => String(el.value || '').trim() !== '');
                const essayTextareas = Array.from(block.querySelectorAll('textarea[data-essay-input]'));
                const hasEssayText = essayTextareas.some((el) => String(el.value || '').trim() !== '');
                const hasText = Array.from(block.querySelectorAll('textarea, input[type=text], input[type=email], input[type=number], input:not([type])'))
                    .some((el) => String(el.value || '').trim() !== '');

                let isAnswered = false;
                if (questionType === 'single_choice') {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown;
                } else if (questionType === 'essay') {
                    isAnswered = hasEssayText;
                } else if (questionType === 'combined') {
                    isAnswered = (hasSelectedRadio || hasCheckedBox || hasSelectedDropdown) && hasText;
                } else {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown || hasText;
                }

                if (!isAnswered) {
                    this.invalidQuestionIds.push(questionId);
                    this.validationErrors.push(`Pertanyaan ${questionNumber}: ${displayName} belum diisi.`);
                    if (questionType === 'essay') {
                        this.invalidEssayQuestionIds.push(questionId);
                    }
                }
            });

            if (this.validationErrors.length > 0) {
                this.$nextTick(() => {
                    const panel = this.$root.querySelector('#questionnaire-validation-errors');
                    panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                return;
            }

            $wire.openSubmitConfirmation();
        },
    }"
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
                    <flux:button variant="primary">Kembali ke Daftar Kuisioner</flux:button>
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

    <div
        id="questionnaire-validation-errors"
        x-show="validationErrors.length > 0"
        x-transition.opacity.duration.200ms
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        style="display:none;"
    >
        <p class="font-semibold">Masih ada pertanyaan wajib yang belum terisi:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <template x-for="(error, idx) in validationErrors" :key="idx">
                <li x-text="error"></li>
            </template>
        </ul>
    </div>

    @if ($totalQuestions > 0)
        <div class="sticky top-3 z-30 -mx-1 rounded-lg border border-zinc-200 bg-white/95 px-2 py-2 shadow-sm backdrop-blur">
            <div class="flex items-center gap-2 overflow-x-auto">
                @for ($i = 0; $i < $totalQuestions; $i++)
                    @php $questionId = $questions->get($i)?->id; @endphp
                    <button
                        type="button"
                        class="shrink-0 rounded-md border px-2 py-1 text-xs font-medium transition {{ $i === $currentIndex ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}"
                        wire:click="goToQuestion({{ $i }})"
                        x-on:click="$nextTick(() => document.getElementById('q-{{ $questionId }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                        wire:loading.attr="disabled"
                        wire:target="nextQuestion,previousQuestion,goToQuestion"
                    >
                        {{ $i + 1 }}
                    </button>
                @endfor
            </div>
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

    @if ($totalQuestions > 0)
        @php
            $displayQuestions = $singleQuestionMode && $currentQuestion ? collect([$currentQuestion]) : $questions;
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-4 md:p-5">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {{ $singleQuestionMode ? 'Mode Satu Pertanyaan' : 'Mode Penilaian Satu Halaman' }}
                </div>
                <div class="text-xs text-zinc-500">Aktif: Pertanyaan {{ $currentIndex + 1 }}</div>
            </div>

            <div class="space-y-6">
                @foreach ($displayQuestions as $question)
                    @php
                        $actualIndex = $questions->search(fn($q) => (int) $q->id === (int) $question->id);
                        $actualIndex = is_int($actualIndex) ? $actualIndex : 0;
                        $isActive = $actualIndex === $currentIndex;
                        $isRequiredQuestion = $question->is_required || in_array($question->type, ['essay', 'combined'], true);
                    @endphp
                    <section
                        id="q-{{ $question->id }}"
                        wire:key="question-inline-{{ $question->id }}"
                        data-question-block
                        data-question-id="{{ $question->id }}"
                        data-question-number="{{ $actualIndex + 1 }}"
                        data-question-label="{{ trim($question->question_text) }}"
                        data-question-type="{{ $question->type }}"
                        data-required="{{ $isRequiredQuestion ? '1' : '0' }}"
                        x-on:input="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }})"
                        x-on:change="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}); invalidEssayQuestionIds = invalidEssayQuestionIds.filter(v => v !== {{ $question->id }})"
                        :class="invalidQuestionIds.includes({{ $question->id }}) ? 'ring-2 ring-rose-400 bg-rose-50/60' : ''"
                        class="space-y-3 border-l-2 pl-3 transition {{ $isActive ? 'border-zinc-900 bg-zinc-50/60' : 'border-zinc-200' }}"
                    >
                        <div class="text-xs font-semibold uppercase tracking-wide {{ $actualIndex === $currentIndex ? 'text-zinc-900' : 'text-zinc-500' }}">
                            Pertanyaan {{ $actualIndex + 1 }} / {{ $totalQuestions }}
                        </div>

                        <h3 class="text-base font-semibold text-zinc-900">{{ $question->question_text }}</h3>
                        <div class="text-xs text-zinc-500">
                            Tipe: {{ $question->type }} | {{ $question->is_required ? 'Wajib diisi' : 'Opsional' }}
                        </div>

                        @if ($question->type === 'single_choice')
                            <div class="space-y-2">
                                @foreach ($question->answerOptions as $option)
                                    <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-700">
                                        <input
                                            type="radio"
                                            wire:model.live="answers.{{ $question->id }}.answer_option_id"
                                            name="question_{{ $question->id }}"
                                            value="{{ $option->id }}"
                                            class="mt-0.5 border-zinc-300"
                                        >
                                        <span>{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                                @error("answers.$question->id.answer_option_id")
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        @if ($question->type === 'essay')
                            <div class="space-y-2">
                                <textarea
                                    data-essay-input
                                    wire:model.live.debounce.250ms="answers.{{ $question->id }}.essay_answer"
                                    rows="4"
                                    maxlength="2000"
                                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                    placeholder="Tulis jawaban Anda..."
                                    x-on:input="if (String($el.value || '').trim() !== '') { invalidEssayQuestionIds = invalidEssayQuestionIds.filter(v => v !== {{ $question->id }}); invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}) }"
                                ></textarea>
                                <div class="text-xs text-zinc-500">
                                    {{ strlen($answers[$question->id]['essay_answer'] ?? '') }} / 2000 karakter
                                </div>
                                <p
                                    x-show="invalidEssayQuestionIds.includes({{ $question->id }})"
                                    class="text-xs text-rose-700"
                                    style="display:none;"
                                >
                                    Jawaban untuk pertanyaan essay ini masih kosong. Silakan isi terlebih dahulu.
                                </p>
                                @error("answers.$question->id.essay_answer")
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        @if ($question->type === 'combined')
                            <div class="space-y-3">
                                <div class="space-y-2">
                                    @foreach ($question->answerOptions as $option)
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-700">
                                            <input
                                                type="radio"
                                                wire:model.live="answers.{{ $question->id }}.answer_option_id"
                                                name="question_combined_{{ $question->id }}"
                                                value="{{ $option->id }}"
                                                class="mt-0.5 border-zinc-300"
                                            >
                                            <span>{{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                    @error("answers.$question->id.answer_option_id")
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if (($answers[$question->id]['answer_option_id'] ?? null) !== null)
                                    <div class="space-y-2">
                                        <textarea
                                            wire:model.live.debounce.250ms="answers.{{ $question->id }}.essay_answer"
                                            rows="4"
                                            maxlength="2000"
                                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                            placeholder="Tuliskan alasan Anda..."
                                        ></textarea>
                                        <div class="text-xs text-zinc-500">
                                            {{ strlen($answers[$question->id]['essay_answer'] ?? '') }} / 2000 karakter
                                        </div>
                                        @error("answers.$question->id.essay_answer")
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-500">Pilih opsi jawaban terlebih dahulu untuk menampilkan area alasan.</p>
                                @endif
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>

        @if ($singleQuestionMode)
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <div class="flex items-center justify-between gap-2">
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
        @endif
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm text-zinc-600">
            Tidak ada pertanyaan pada kuisioner ini.
        </div>
    @endif

    @if ($singleQuestionMode)
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Navigasi Cepat</div>
            <div class="flex flex-wrap gap-2">
                @for ($i = 0; $i < $totalQuestions; $i++)
                    <flux:button
                        size="xs"
                        :variant="$i === $currentIndex ? 'primary' : 'outline'"
                        wire:click="goToQuestion({{ $i }})"
                        x-on:click="$nextTick(() => document.getElementById('q-{{ $questions->get($i)?->id }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                        wire:loading.attr="disabled"
                        wire:target="nextQuestion,previousQuestion,goToQuestion"
                    >
                        {{ $i + 1 }}
                    </flux:button>
                @endfor
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-5">
        <div class="mb-2 text-sm font-semibold text-zinc-800">Finalisasi Pengisian</div>
        <p class="text-xs text-zinc-500">
            Terjawab wajib: {{ $answeredRequiredCount }} / {{ $requiredQuestionCount }}
        </p>
        <div class="mt-4 flex justify-end">
            <flux:button
                variant="primary"
                icon="check"
                x-on:click.prevent="validateBeforeSubmit()"
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
