@php
    $toastMessage = session('success') ?? session('error') ?? session('warning');
    $toastType = session('success') ? 'success' : (session('error') ? 'error' : (session('warning') ? 'warning' : null));
@endphp

@if ($toastMessage)
    <div
        x-data="{ open: true }"
        x-show="open"
        x-init="setTimeout(() => open = false, 2200)"
        x-transition.opacity.duration.200ms
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="fixed right-4 top-4 z-[100] flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-white shadow-lg"
        @class([
            'bg-emerald-600' => $toastType === 'success',
            'bg-rose-600' => $toastType === 'error',
            'bg-amber-600' => $toastType === 'warning',
        ])
    >
        <span aria-hidden="true">
            {{ $toastType === 'success' ? '✓' : ($toastType === 'error' ? '!' : '!') }}
        </span>
        <span>{{ $toastMessage }}</span>
        <button type="button" class="ml-1 opacity-80 hover:opacity-100" x-on:click="open = false" aria-label="Tutup notifikasi">x</button>
    </div>
@endif
