<div
    class="space-y-6"
    x-data="{
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        validationErrors: [],
        invalidQuestionIds: [],
        invalidEssayQuestionIds: [],
        clearValidationState() {
            this.validationErrors = [];
            this.invalidQuestionIds = [];
            this.invalidEssayQuestionIds = [];
        },
        validateBeforeSubmitAll() {
            this.clearValidationState();

            const root = this.$root;
            const blocks = Array.from(root.querySelectorAll('[data-question-block]'));

            blocks.forEach((block) => {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) return;

                const questionId = Number(block.dataset.questionId || 0);
                const questionType = String(block.dataset.questionType || '');
                const questionLabel = String(block.dataset.questionLabel || '').trim();
                const questionnaireTitle = String(block.dataset.questionnaireTitle || '').trim();
                const displayName = (questionnaireTitle !== '' ? questionnaireTitle + ' - ' : '') + (questionLabel !== '' ? questionLabel : 'Pertanyaan ' + block.dataset.questionNumber);

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
                    this.validationErrors.push(displayName + ' belum diisi.');
                    if (questionType === 'essay') {
                        this.invalidEssayQuestionIds.push(questionId);
                    }
                }
            });

            if (this.validationErrors.length > 0) {
                this.$nextTick(() => {
                    const panel = document.getElementById('global-validation-errors');
                    panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                return;
            }

            $wire.openSubmitAllConfirmation();
        },
    }"
    @autosave-status.window="
        toastMessage = $event.detail.message;
        toastType = $event.detail.type ?? 'success';
        showToast = true;
        setTimeout(() => showToast = false, 2500);
    "
>
    {{-- Page Header --}}
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Kuisioner Saya</h2>
        <p class="text-sm text-zinc-500">Isi semua kuisioner aktif yang tersedia untuk Anda, lalu kirim sekaligus.</p>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Already Submitted Notice --}}
    @if ($totalFillable === 0 && $submittedCount > 0)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <h3 class="text-lg font-semibold text-emerald-800">Semua kuisioner sudah dikirim!</h3>
            <p class="mt-2 text-sm text-emerald-700">Anda sudah mengirim semua kuisioner yang tersedia.</p>
        </div>
    @endif

    {{-- Step Navigation & Progress --}}
    @if ($totalFillable > 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            {{-- Questionnaire step dots --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                @for ($i = 0; $i < $totalFillable; $i++)
                    @php
                        $stepId = $questionnaireIds[$i] ?? null;
                        $stepMeta = $stepId !== null ? ($questionnaireMeta[$stepId] ?? null) : null;
                        $stepTitle = $stepMeta ? $stepMeta['title'] : '';
                        $isCurrent = $i === $currentIndex;
                        $isVisited = $i < $currentIndex;
                    @endphp
                    <button
                        type="button"
                        wire:click="goToQuestionnaire({{ $i }})"
                        class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition {{ $isCurrent ? 'border-zinc-900 bg-zinc-900 text-white' : ($isVisited ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50') }}"
                    >
                        @if ($isVisited)
                            <span class="text-emerald-600">&#10003;</span>
                        @else
                            <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $isCurrent ? 'bg-white text-zinc-900' : 'bg-zinc-200 text-zinc-600' }} text-xs font-bold">
                                {{ $i + 1 }}
                            </span>
                        @endif
                        <span class="max-w-[120px] truncate">{{ $stepTitle }}</span>
                    </button>
                @endfor
            </div>

            {{-- Overall Progress --}}
            <div class="mt-3 flex items-center justify-between gap-4">
                <p class="text-xs text-zinc-500">
                    Kuisioner {{ $currentIndex + 1 }} dari {{ $totalFillable }}
                    &middot; {{ $answeredCount }}/{{ $totalQuestions }} pertanyaan terisi
                    &middot; Wajib: {{ $answeredRequiredCount }}/{{ $requiredQuestionCount }}
                </p>
                <span class="text-sm font-bold {{ $progressPercent >= 100 ? 'text-emerald-600' : 'text-zinc-900' }}">{{ $progressPercent }}%</span>
            </div>
            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                <div
                    class="h-full rounded-full transition-all duration-300 {{ $progressPercent >= 100 ? 'bg-emerald-600' : 'bg-zinc-800' }}"
                    style="width: {{ $progressPercent }}%;"
                ></div>
            </div>
        </div>
    @endif

    {{-- Global Validation Errors Panel --}}
    <div
        id="global-validation-errors"
        x-show="validationErrors.length > 0"
        x-transition.opacity.duration.200ms
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        style="display:none;"
    >
        <p class="font-semibold">Masih ada pertanyaan wajib yang belum terisi:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <template x-for="(error, idx) in validationErrors" :key="idx">
                <li x-text="error"></li>
            </template>
        </ul>
    </div>

    {{-- Current Questionnaire Content --}}
    @if ($currentMeta)
        <section class="space-y-4">
            {{-- Questionnaire Header --}}
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <span class="rounded bg-zinc-900 px-2 py-0.5 text-xs font-bold text-white">{{ $currentIndex + 1 }}</span>
                                <span class="text-xs text-zinc-500">{{ $currentMeta['target_label'] }}</span>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900">{{ $currentMeta['title'] }}</h3>
                            @if ($currentMeta['description'])
                                <p class="mt-1 text-sm text-zinc-600">{{ $currentMeta['description'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">Perlu Diisi</span>
                    </div>
                </div>

                {{-- Questions --}}
                @if ($currentQuestions->count() > 0)
                    <div class="space-y-4 p-4">
                        @foreach ($currentQuestions as $index => $question)
                            @php
                                $isRequiredQuestion = $question->is_required || in_array($question->type, ['essay', 'combined'], true);
                            @endphp
                            <section
                                id="q-{{ $question->id }}"
                                wire:key="q-{{ $question->id }}"
                                data-question-block
                                data-question-id="{{ $question->id }}"
                                data-question-number="{{ $index + 1 }}"
                                data-question-label="{{ trim($question->question_text) }}"
                                data-question-type="{{ $question->type }}"
                                data-questionnaire-title="{{ $currentMeta['title'] }}"
                                data-required="{{ $isRequiredQuestion ? '1' : '0' }}"
                                x-on:input="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }})"
                                x-on:change="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}); invalidEssayQuestionIds = invalidEssayQuestionIds.filter(v => v !== {{ $question->id }})"
                                :class="invalidQuestionIds.includes({{ $question->id }}) ? 'ring-2 ring-rose-400 bg-rose-50/60' : ''"
                                class="space-y-3 rounded-lg border border-zinc-200 bg-white p-4 transition"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        Pertanyaan {{ $index + 1 }}
                                    </span>
                                    <span class="text-xs text-zinc-400">|</span>
                                    <span class="text-xs text-zinc-500">{{ $question->type }}</span>
                                    @if ($isRequiredQuestion)
                                        <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">Wajib</span>
                                    @else
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-500">Opsional</span>
                                    @endif
                                </div>

                                <h3 class="text-sm font-semibold text-zinc-900">{{ $question->question_text }}</h3>

                                {{-- Single Choice --}}
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

                                {{-- Essay --}}
                                @if ($question->type === 'essay')
                                    <div class="space-y-2">
                                        <textarea
                                            data-essay-input
                                            wire:model.live.debounce.250ms="answers.{{ $question->id }}.essay_answer"
                                            rows="3"
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
                                            Jawaban untuk pertanyaan esai ini masih kosong. Silakan isi terlebih dahulu.
                                        </p>
                                        @error("answers.$question->id.essay_answer")
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Combined --}}
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
                                                    rows="3"
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
                @else
                    <div class="p-4 text-sm text-zinc-500">
                        Tidak ada pertanyaan pada kuisioner ini.
                    </div>
                @endif
            </div>
        </section>
    @elseif ($totalFillable === 0 && $submittedCount === 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 text-center text-sm text-zinc-500">
            Tidak ada kuisioner aktif untuk role Anda saat ini.
        </div>
    @endif

    {{-- Navigation & Submit Bar --}}
    @if ($totalFillable > 0)
        <div class="sticky bottom-4 z-40 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg">
            <div class="flex items-center justify-between gap-4">
                {{-- Back Button --}}
                <div>
                    @if ($currentIndex > 0)
                        <flux:button
                            variant="ghost"
                            icon="arrow-left"
                            wire:click="previousQuestionnaire"
                        >
                            Kembali
                        </flux:button>
                    @endif
                </div>

                {{-- Right side: Save Draft + Next/Submit --}}
                <div class="flex items-center gap-2">
                    @if ($lastDraftSavedAt)
                        <span class="text-xs text-zinc-400">Draft tersimpan {{ $lastDraftSavedAt }}</span>
                    @endif
                    <flux:button
                        variant="outline"
                        size="sm"
                        wire:click="saveAllDrafts"
                        wire:loading.attr="disabled"
                        wire:target="saveAllDrafts"
                    >
                        Simpan Draft
                    </flux:button>

                    @if ($isLast)
                        <flux:button
                            variant="primary"
                            x-on:click.prevent="validateBeforeSubmitAll()"
                            :disabled="$totalQuestions === 0"
                        >
                            Submit Semua
                        </flux:button>
                    @else
                        <flux:button
                            variant="primary"
                            icon="arrow-right"
                            wire:click="nextQuestionnaire"
                        >
                            Selanjutnya
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Submit All Confirmation Modal --}}
    @if ($confirmSubmitAll)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold text-zinc-900">Konfirmasi Submit Semua</h3>
                <p class="mt-2 text-sm text-zinc-600">
                    Pastikan jawaban sudah benar. Setelah submit, Anda tidak dapat mengubah jawaban lagi.
                </p>

                <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm text-zinc-700">
                    <div>Total kuisioner: {{ $totalFillable }}</div>
                    <div>Total pertanyaan: {{ $totalQuestions }}</div>
                    <div>Jawaban terisi: {{ $answeredCount }}</div>
                    <div>Wajib terisi: {{ $answeredRequiredCount }} / {{ $requiredQuestionCount }}</div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeSubmitAllConfirmation">Batal</flux:button>
                    <flux:button variant="primary" wire:click="submitAllFinal">Ya, Submit Semua</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Toast Notification --}}
    <div
        x-show="showToast"
        x-transition.opacity.duration.200ms
        class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-white shadow-lg"
        :class="toastType === 'success' ? 'bg-emerald-600' : 'bg-sky-700'"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        style="display: none;"
    >
        <span x-show="toastType === 'success'" aria-hidden="true">&#10003;</span>
        <span x-show="toastType !== 'success'" aria-hidden="true">&#8635;</span>
        <span x-text="toastMessage"></span>
    </div>
</div>
