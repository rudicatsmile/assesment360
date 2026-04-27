<div class="space-y-6" x-data="{
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        validationErrors: [],
        invalidQuestionIds: [],
        invalidEssayQuestionIds: [],
        nextButtonEnabled: {{ $currentQuestionnaireComplete ? 'true' : 'false' }},
        timeExpired: {{ $timeExpired ? 'true' : 'false' }},
        timerInterval: null,
        hasTimeLimit: {{ ($timeLimitInfo !== null) ? 'true' : 'false' }},
        remainingSeconds: {{ $timeLimitInfo['remaining_seconds'] ?? 0 }},
        initTimer() {
            // Clear any existing interval to avoid duplicates on Livewire re-render
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }

            // Check conditions at runtime (client-side) instead of server-render time
            if (this.remainingSeconds > 0 && !this.timeExpired) {
                this.timerInterval = setInterval(() => {
                    this.remainingSeconds = this.remainingSeconds - 1;
                    // Also sync to hidden input so Livewire morph can restore correct value
                    const el = document.getElementById('timer-remaining-seconds');
                    if (el) el.value = this.remainingSeconds;
                    if (this.remainingSeconds <= 0) {
                        this.remainingSeconds = 0;
                        this.timeExpired = true;
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                        $wire.autoSubmitOnTimeExpired();
                    }
                }, 1000);
            } else if (this.hasTimeLimit && this.remainingSeconds <= 0 && !this.timeExpired) {
                this.timeExpired = true;
                $wire.autoSubmitOnTimeExpired();
            }
        },
        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            const pad = (n) => String(n).padStart(2, '0');
            return h > 0 ? h + ':' + pad(m) + ':' + pad(s) : pad(m) + ':' + pad(s);
        },
        clearValidationState() {
            this.validationErrors = [];
            this.invalidQuestionIds = [];
            this.invalidEssayQuestionIds = [];
        },
        currentQuestionnaireComplete() {
            const root = this.$root;
            const blocks = Array.from(root.querySelectorAll('[data-question-block]'));
            if (blocks.length === 0) return false;
            for (const block of blocks) {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) continue;
                const questionType = String(block.dataset.questionType || '');
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
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown;
                } else {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown || hasText;
                }
                if (!isAnswered) return false;
            }
            return true;
        },
        checkNextButton() {
            const el = document.getElementById('current-questionnaire-complete');
            this.nextButtonEnabled = el ? el.value === '1' : false;
        },
        validateBeforeSubmitAll() {
            if (this.timeExpired) return;
            this.clearValidationState();

            const root = this.$root;
            const blocks = Array.from(root.querySelectorAll('[data-question-block]'));

            for (const block of blocks) {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) continue;

                const questionId = Number(block.dataset.questionId || 0);
                const questionType = String(block.dataset.questionType || '');

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
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown;
                } else {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown || hasText;
                }

                if (!isAnswered) {
                    this.invalidQuestionIds.push(questionId);
                    this.validationErrors.push('Pertanyaan wajib belum terisi. Silakan isi pertanyaan yang ditandai.');
                    if (questionType === 'essay') {
                        this.invalidEssayQuestionIds.push(questionId);
                    }
                    break;
                }
            }

            if (this.validationErrors.length > 0) {
                this.$nextTick(() => {
                    const firstInvalid = document.getElementById('q-' + this.invalidQuestionIds[0]);
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                return;
            }

            $wire.openSubmitAllConfirmation();
        },
        validateBeforeGoTo(targetIndex) {
            if (this.timeExpired) return;
            const currentIndex = parseInt(document.getElementById('current-index')?.value || '0', 10);

            // Allow going back to previously visited tabs without validation
            if (targetIndex <= currentIndex) {
                $wire.goToQuestionnaire(targetIndex);
                return;
            }

            // Going forward: validate current questionnaire first
            this.clearValidationState();

            const root = this.$root;
            const blocks = Array.from(root.querySelectorAll('[data-question-block]'));

            for (const block of blocks) {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) continue;

                const questionId = Number(block.dataset.questionId || 0);
                const questionType = String(block.dataset.questionType || '');

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
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown;
                } else {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown || hasText;
                }

                if (!isAnswered) {
                    this.invalidQuestionIds.push(questionId);
                    this.validationErrors.push('Pertanyaan wajib belum terisi. Silakan isi pertanyaan yang ditandai.');
                    if (questionType === 'essay') {
                        this.invalidEssayQuestionIds.push(questionId);
                    }
                    break;
                }
            }

            if (this.validationErrors.length > 0) {
                this.$nextTick(() => {
                    const firstInvalid = document.getElementById('q-' + this.invalidQuestionIds[0]);
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                return;
            }

            $wire.goToQuestionnaire(targetIndex);
        },
        validateBeforeNext() {
            if (this.timeExpired) return;
            this.clearValidationState();

            const root = this.$root;
            const blocks = Array.from(root.querySelectorAll('[data-question-block]'));

            for (const block of blocks) {
                const isRequired = block.dataset.required === '1';
                if (!isRequired) continue;

                const questionId = Number(block.dataset.questionId || 0);
                const questionType = String(block.dataset.questionType || '');

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
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown;
                } else {
                    isAnswered = hasSelectedRadio || hasCheckedBox || hasSelectedDropdown || hasText;
                }

                if (!isAnswered) {
                    this.invalidQuestionIds.push(questionId);
                    this.validationErrors.push('Pertanyaan wajib belum terisi. Silakan isi pertanyaan yang ditandai.');
                    if (questionType === 'essay') {
                        this.invalidEssayQuestionIds.push(questionId);
                    }
                    break;
                }
            }

            if (this.validationErrors.length > 0) {
                this.$nextTick(() => {
                    const firstInvalid = document.getElementById('q-' + this.invalidQuestionIds[0]);
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                return;
            }

            $wire.nextQuestionnaire();
        },
    }" x-init="$nextTick(() => { initTimer(); checkNextButton(); })" x-on:livewire:morph="setTimeout(() => {
        const timerEl = document.getElementById('timer-remaining-seconds');
        if (timerEl) {
            remainingSeconds = parseInt(timerEl.value, 10) || 0;
            hasTimeLimit = remainingSeconds > 0;
        }
        checkNextButton();
    }, 50)" @autosave-status.window="
        toastMessage = $event.detail.message;
        toastType = $event.detail.type ?? 'success';
        showToast = true;
        setTimeout(() => showToast = false, 2500);
    " @start-timer.window="
        $nextTick(() => {
            hasTimeLimit = true;
            remainingSeconds = $event.detail[0]
                || (document.getElementById('timer-remaining-seconds')?.value
                    ? parseInt(document.getElementById('timer-remaining-seconds').value, 10)
                    : 0);
            initTimer();
            checkNextButton();
        });
    " @questionnaire-changed.window="
        $nextTick(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    ">
    {{-- Hidden inputs that Livewire morphs with server-side values --}}
    <input type="hidden" id="timer-remaining-seconds" value="{{ $timeLimitInfo['remaining_seconds'] ?? 0 }}">
    <input type="hidden" id="current-questionnaire-complete" value="{{ $currentQuestionnaireComplete ? '1' : '0' }}">
    <input type="hidden" id="current-index" value="{{ $currentIndex }}">

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

    {{-- Start Confirmation Popup --}}
    @if ($showStartConfirmation)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-zinc-900 p-4" x-data="{ show: false }"
            x-init="$nextTick(() => show = true)" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            style="display:none;">
            <div class="w-full max-w-md rounded-2xl bg-white p-0 shadow-2xl overflow-hidden">
                {{-- Header with gradient --}}
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-7.548 0 2.25 2.25 0 00-1.976 2.192V19.5a2.25 2.25 0 002.25 2.25h.75" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Siap Mengisi Kuisioner?</h3>
                            <p class="text-sm text-blue-100">Baca informasi berikut sebelum memulai</p>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-4">
                    {{-- Time limit card --}}
                    @php
                        $hours = intdiv($timeLimitMinutes ?? 0, 60);
                        $mins = ($timeLimitMinutes ?? 0) % 60;
                        $timeDisplay = '';
                        if ($hours > 0)
                            $timeDisplay .= $hours . ' jam ';
                        if ($mins > 0)
                            $timeDisplay .= $mins . ' menit';
                        if ($timeDisplay === '')
                            $timeDisplay = 'Tanpa batas';

                        $totalQuestions = 0;
                        foreach ($questionnaireMeta as $qId => $meta) {
                            if ($meta['status'] !== 'submitted') {
                                $totalQuestions += $meta['questions_count'];
                            }
                        }
                        $fillableCount = count($questionnaireIds);
                    @endphp

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-amber-800">Batas Waktu Pengisian</p>
                                <p class="mt-1 text-2xl font-bold text-amber-900">{{ $timeDisplay }}</p>
                                <p class="mt-1 text-xs text-amber-600">Timer akan mulai berjalan setelah Anda menekan tombol
                                    "Mulai Sekarang"</p>
                            </div>
                        </div>
                    </div>

                    {{-- Info cards --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
                            <p class="text-2xl font-bold text-zinc-900">{{ $fillableCount }}</p>
                            <p class="text-xs text-zinc-500">Kuisioner</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
                            <p class="text-2xl font-bold text-zinc-900">{{ $totalQuestions }}</p>
                            <p class="text-xs text-zinc-500">Total Pertanyaan</p>
                        </div>
                    </div>

                    {{-- Important notices --}}
                    <div class="space-y-2">
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <p class="text-sm text-zinc-700">Jawaban akan <strong>otomatis dikirim</strong> saat waktu
                                habis.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-zinc-700">Isi semua pertanyaan wajib, lalu klik <strong>Kirim
                                    Semua</strong> untuk menyelesaikan.</p>
                        </div>
                    </div>
                </div>

                {{-- Footer buttons --}}
                <div class="flex gap-3 border-t border-zinc-100 bg-zinc-50 px-6 py-4">
                    <flux:button variant="outline" size="sm" wire:click="cancelStart" class="flex-1">
                        Batal
                    </flux:button>
                    <flux:button variant="primary" size="sm" wire:click="confirmStart" class="flex-1">
                        Mulai Sekarang
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Time Expired Notice --}}
    @if ($timeExpired)
        {{-- Attractive expired popup --}}
        <div x-show="timeExpired" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" style="display:none;">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-red-700">Waktu Habis!</h3>
                <p class="mt-2 text-sm text-zinc-600">Batas waktu pengisian kuisioner telah berakhir.</p>
                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                    Jawaban yang sudah Anda isi telah <strong>disimpan dan dikirim</strong> secara otomatis.
                </div>
                <p class="mt-3 text-xs text-zinc-400">Anda tidak dapat melanjutkan pengisian kuisioner.</p>
            </div>
        </div>
    @endif

    {{-- Already Submitted Notice --}}
    @if ($totalFillable === 0 && $submittedCount > 0)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4" x-data="{ show: false }"
            x-init="$nextTick(() => show = true)" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            style="display:none;">
            <div class="w-full max-w-md rounded-2xl bg-white p-0 shadow-2xl overflow-hidden">
                {{-- Header with gradient --}}
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Kuisioner Berhasil Dikirim!</h3>
                            <p class="text-sm text-emerald-100">Semua jawaban Anda telah tersimpan</p>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-emerald-800">Akses Terkunci</p>
                                <p class="mt-1 text-sm text-emerald-700">Kuisioner yang sudah dikirim <strong>tidak dapat
                                        diakses atau diubah lagi</strong>. Pastikan semua jawaban sudah benar sebelum
                                    mengirim.</p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-zinc-500">Total kuisioner terkirim: <strong
                                class="text-zinc-700">{{ $submittedCount }}</strong></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-zinc-100 bg-zinc-50 px-6 py-4">
                    {{-- <a href="{{ route('role.dashboard') }}" wire:navigate>
                        <flux:button variant="primary" class="w-full">Kembali ke Dashboard</flux:button>
                    </a> --}}
                    <a href="/fill/dashboard/guru" wire:navigate>
                        <flux:button variant="primary" class="w-full">Kembali ke Dashboard</flux:button>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Step Navigation & Progress --}}
    @if ($totalFillable > 0 && !$showStartConfirmation)
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
                    <button type="button" @click="validateBeforeGoTo({{ $i }})"
                        class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition {{ $isCurrent ? 'border-zinc-900 bg-zinc-900 text-white' : ($isVisited ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50') }}"
                        :disabled="timeExpired">
                        @if ($isVisited)
                            <span class="text-emerald-600">&#10003;</span>
                        @else
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full {{ $isCurrent ? 'bg-white text-zinc-900' : 'bg-zinc-200 text-zinc-600' }} text-xs font-bold">
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
                <span
                    class="text-sm font-bold {{ $progressPercent >= 100 ? 'text-emerald-600' : 'text-zinc-900' }}">{{ $progressPercent }}%</span>
            </div>
            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                <div class="h-full rounded-full transition-all duration-300 {{ $progressPercent >= 100 ? 'bg-emerald-600' : 'bg-zinc-800' }}"
                    style="width: {{ $progressPercent }}%;"></div>
            </div>
        </div>
    @endif

    {{-- Global Validation Errors Panel --}}
    <div id="global-validation-errors" x-show="validationErrors.length > 0" x-transition.opacity.duration.200ms
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" style="display:none;">
        <p class="font-semibold" x-text="validationErrors[0]"></p>
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
                                <span
                                    class="rounded bg-zinc-900 px-2 py-0.5 text-xs font-bold text-white">{{ $currentIndex + 1 }}</span>
                                <span class="text-xs text-zinc-500">{{ $currentMeta['target_label'] }}</span>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900">{{ $currentMeta['title'] }}</h3>
                            @if ($currentMeta['description'])
                                <p class="mt-1 text-sm text-zinc-600">{{ $currentMeta['description'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700">Perlu
                            Diisi</span>
                    </div>
                </div>

                {{-- Questions --}}
                @if ($currentQuestions->count() > 0)
                    <div class="space-y-4 p-4">
                        @foreach ($currentQuestions as $index => $question)
                            @php
                                $isRequiredQuestion = $question->is_required;
                            @endphp
                            <section id="q-{{ $question->id }}" wire:key="q-{{ $question->id }}" data-question-block
                                data-question-id="{{ $question->id }}" data-question-number="{{ $index + 1 }}"
                                data-question-label="{{ trim($question->question_text) }}"
                                data-question-type="{{ $question->type }}" data-questionnaire-title="{{ $currentMeta['title'] }}"
                                data-required="{{ $isRequiredQuestion ? '1' : '0' }}"
                                x-on:input="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}); $nextTick(() => checkNextButton())"
                                x-on:change="invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}); invalidEssayQuestionIds = invalidEssayQuestionIds.filter(v => v !== {{ $question->id }}); $nextTick(() => checkNextButton())"
                                :class="invalidQuestionIds.includes({{ $question->id }}) ? 'ring-2 ring-rose-400 bg-rose-50/60' : ''"
                                class="space-y-3 rounded-lg border border-zinc-200 bg-white p-4 transition">
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
                                    <div class="space-y-2" :class="timeExpired ? 'pointer-events-none opacity-50' : ''">
                                        @foreach ($question->answerOptions as $option)
                                            <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-700">
                                                <input type="radio" wire:model.live="answers.{{ $question->id }}.answer_option_id"
                                                    name="question_{{ $question->id }}" value="{{ $option->id }}"
                                                    class="mt-0.5 border-zinc-300" :disabled="timeExpired">
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
                                    <div class="space-y-2" :class="timeExpired ? 'pointer-events-none opacity-50' : ''">
                                        <textarea data-essay-input
                                            wire:model.live.debounce.250ms="answers.{{ $question->id }}.essay_answer" rows="3"
                                            maxlength="2000"
                                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                            placeholder="Tulis jawaban Anda..." :disabled="timeExpired"
                                            x-on:input="if (String($el.value || '').trim() !== '') { invalidEssayQuestionIds = invalidEssayQuestionIds.filter(v => v !== {{ $question->id }}); invalidQuestionIds = invalidQuestionIds.filter(v => v !== {{ $question->id }}) }; $nextTick(() => checkNextButton())"></textarea>
                                        <div class="text-xs text-zinc-500">
                                            {{ strlen($answers[$question->id]['essay_answer'] ?? '') }} / 2000 karakter
                                        </div>
                                        <p x-show="invalidEssayQuestionIds.includes({{ $question->id }})" class="text-xs text-rose-700"
                                            style="display:none;">
                                            Jawaban untuk pertanyaan esai ini masih kosong. Silakan isi terlebih dahulu.
                                        </p>
                                        @error("answers.$question->id.essay_answer")
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Combined --}}
                                @if ($question->type === 'combined')
                                    <div class="space-y-3" :class="timeExpired ? 'pointer-events-none opacity-50' : ''">
                                        <div class="space-y-2">
                                            @foreach ($question->answerOptions as $option)
                                                <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-700">
                                                    <input type="radio" wire:model.live="answers.{{ $question->id }}.answer_option_id"
                                                        name="question_combined_{{ $question->id }}" value="{{ $option->id }}"
                                                        class="mt-0.5 border-zinc-300" :disabled="timeExpired">
                                                    <span>{{ $option->option_text }}</span>
                                                </label>
                                            @endforeach
                                            @error("answers.$question->id.answer_option_id")
                                                <p class="text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        @if (($answers[$question->id]['answer_option_id'] ?? null) !== null)
                                            <div class="space-y-2">
                                                <textarea wire:model.live.debounce.250ms="answers.{{ $question->id }}.essay_answer" rows="3"
                                                    maxlength="2000"
                                                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none"
                                                    placeholder="Tuliskan alasan Anda..." :disabled="timeExpired"></textarea>
                                                <div class="text-xs text-zinc-500">
                                                    {{ strlen($answers[$question->id]['essay_answer'] ?? '') }} / 2000 karakter
                                                </div>
                                                @error("answers.$question->id.essay_answer")
                                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @else
                                            <p class="text-xs text-zinc-500">Pilih opsi jawaban terlebih dahulu untuk menampilkan area
                                                alasan.</p>
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
    @if ($totalFillable > 0 && !$timeExpired && !$showStartConfirmation)
        <div class="sticky bottom-4 z-40 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg">
            <div class="flex items-center justify-between gap-4">
                {{-- Back Button --}}
                <div>
                    {{-- Back button hidden --}}
                </div>

                {{-- Center: Timer Display --}}
                <div x-show="!timeExpired && hasTimeLimit" x-cloak class="flex items-center gap-2">
                    <div class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold shadow-sm"
                        :class="remainingSeconds <= 300 ? 'bg-red-100 text-red-700 ring-2 ring-red-300 animate-pulse' : (remainingSeconds <= 600 ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-300' : 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span x-text="formatTime(remainingSeconds)"></span>
                    </div>
                </div>

                {{-- Right side: Save Draft + Next/Submit --}}
                <div class="flex items-center gap-2">
                    @if ($lastDraftSavedAt)
                        <span class="text-xs text-zinc-400">Draft tersimpan {{ $lastDraftSavedAt }}</span>
                    @endif
                    {{-- Simpan Draft button hidden --}}

                    @if ($isLast)
                        <flux:button variant="primary" x-on:click.prevent="validateBeforeSubmitAll()"
                            :disabled="$totalQuestions === 0">
                            Submit Semua
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="arrow-right" @click="validateBeforeNext()">
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
                    Pastikan jawaban sudah benar. Setelah submit, Anda <strong>tidak dapat mengakses atau mengubah</strong>
                    kuisioner lagi.
                </p>

                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <div class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <span><strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!</span>
                    </div>
                </div>

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
    <div x-show="showToast" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-3"
        class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-xl px-5 py-3.5 text-sm font-semibold text-white shadow-2xl ring-1 ring-white/10"
        :class="toastType === 'success' ? 'bg-emerald-600' : (toastType === 'error' ? 'bg-red-600' : 'bg-sky-700')"
        role="alert" aria-live="assertive" aria-atomic="true" style="display: none;">
        <span x-show="toastType === 'success'" aria-hidden="true" class="text-lg">&#10003;</span>
        <span x-show="toastType === 'error'" aria-hidden="true" class="text-lg">&#9888;</span>
        <span x-show="toastType !== 'success' && toastType !== 'error'" aria-hidden="true"
            class="text-lg">&#8635;</span>
        <span x-text="toastMessage"></span>
    </div>
</div>