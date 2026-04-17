<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'KepsekEval') }} - Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900">
    <main class="mx-auto flex min-h-screen max-w-5xl items-center justify-center p-4">
        <section class="grid w-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm md:grid-cols-2">
            <aside class="hidden bg-zinc-900 p-8 text-white md:block">
                <p class="text-xs uppercase tracking-wider text-zinc-400">KepsekEval</p>
                <h1 class="mt-2 text-3xl font-semibold">Masuk Ke Dashboard</h1>
                <p class="mt-3 text-sm text-zinc-300">Login menggunakan nomor telepon dan verifikasi kode OTP via WhatsApp.</p>
                <div class="mt-6 space-y-1 text-xs text-zinc-300">
                    <p>Tahap 1: Isi kode negara + nomor telepon.</p>
                    <p>Tahap 2: Masukkan kode 6 digit dari WhatsApp.</p>
                </div>
            </aside>

            <div class="p-6 md:p-8">
                <h2 class="text-xl font-semibold text-zinc-900">Login Verifikasi WhatsApp</h2>
                <p class="mt-1 text-sm text-zinc-500">Masukkan nomor telepon aktif untuk menerima kode verifikasi.</p>

                @if (session('error'))
                    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.send_verification') }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-3 gap-3">
                        <label class="col-span-1 block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Kode Negara</span>
                            <input
                                type="text"
                                name="country_code"
                                value="{{ old('country_code', session('phone_login_country_code', '+62')) }}"
                                required
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                                placeholder="+62"
                            >
                            @error('country_code') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="col-span-2 block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Nomor Telepon</span>
                            <input
                                type="text"
                                name="phone_number"
                                value="{{ old('phone_number', session('phone_login_number')) }}"
                                required
                                autofocus
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                                placeholder="81234567890"
                            >
                            @error('phone_number') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                        Kirim verifikasi
                    </button>
                </form>

                @if ($verificationPending)
                    <form method="POST" action="{{ route('login.verify_code') }}" class="mt-4 space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                        @csrf
                        <p class="text-sm text-zinc-600">
                            Kode OTP dikirim ke <span class="font-medium text-zinc-900">{{ $maskedPhone }}</span>.
                        </p>
                        <label class="block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Verifikasi Nomor</span>
                            <input
                                type="text"
                                name="verification_code"
                                required
                                maxlength="6"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 tracking-[0.3em] focus:border-zinc-500 focus:outline-none"
                                placeholder="123456"
                            >
                            @error('verification_code') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Verifikasi & Masuk
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
