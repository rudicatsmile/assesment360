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
        <section
            class="grid w-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm md:grid-cols-2">
            <aside class="hidden bg-zinc-900 p-8 text-white md:block">
                <p class="text-xs uppercase tracking-wider text-zinc-400">{{ config('app.name', 'KepsekEval') }}</p>
                <h1 class="mt-2 text-3xl font-semibold">Masuk Ke Dashboard</h1>
                <p class="mt-3 text-sm text-zinc-300">
                    @if ($loginMode === 'both')
                        Anda dapat login dengan email/password atau verifikasi WhatsApp.
                    @elseif ($loginMode === 'password')
                        Silahkan login menggunakan email dan password.
                    @else
                        Silahkan login menggunakan nomor telepon dan verifikasi kode OTP via WhatsApp.
                    @endif
                </p>
                <div class="mt-6 space-y-1 text-xs text-zinc-300">
                    @if ($passwordLoginEnabled)
                        {{-- <p>Mode Password: Masukkan email dan password akun.</p> --}}
                    @endif
                    @if ($whatsAppLoginEnabled)
                        {{-- <p>Mode WhatsApp: Isi nomor lalu verifikasi kode 6 digit.</p> --}}
                    @endif
                </div>
            </aside>

            <div class="p-6 md:p-8">
                <h2 class="text-xl font-semibold text-zinc-900">Login</h2>
                <p class="mt-1 text-sm text-zinc-500">
                    @if ($loginMode === 'both')
                        Pilih salah satu metode login di bawah.
                    @elseif ($loginMode === 'password')
                        Gunakan email dan password Anda.
                    @else
                        Masukkan nomor telepon aktif untuk menerima kode verifikasi.
                    @endif
                </p>

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

                @if ($passwordLoginEnabled)
                    <form method="POST"
                        action="{{ request()->routeIs('login.admin') ? route('login.admin.attempt') : route('login.attempt') }}"
                        class="mt-5 space-y-4">
                        @csrf
                        <label class="block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Email</span>
                            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                                placeholder="contoh@assesment.test">
                            @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Password</span>
                            <input type="password" name="password" required autocomplete="current-password"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                                placeholder="********">
                            @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="flex items-center gap-2 text-sm text-zinc-600">
                            <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300">
                            <span>Ingat saya</span>
                        </label>

                        <button type="submit"
                            class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                            L o g i n
                        </button>
                    </form>
                @endif

                @if ($passwordLoginEnabled && $whatsAppLoginEnabled)
                    <div class="my-5 flex items-center gap-3">
                        <div class="h-px flex-1 bg-zinc-200"></div>
                        <span class="text-xs uppercase tracking-wide text-zinc-500">Atau</span>
                        <div class="h-px flex-1 bg-zinc-200"></div>
                    </div>
                @endif

                @if ($whatsAppLoginEnabled)
                    <form method="POST" action="{{ route('login.send_verification') }}" class="space-y-4">
                        @csrf

                        <label class="block space-y-1 text-sm">
                            <span class="font-medium text-zinc-700">Nomor Telepon</span>
                            <input type="text" name="phone_number"
                                value="{{ old('phone_number', session('phone_login_number')) }}" required autofocus
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                                placeholder="081234567890">
                            @error('phone_number') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <button type="submit"
                            class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                            Kirim verifikasi WhatsApp
                        </button>
                    </form>

                    @if ($verificationPending)
                        <form method="POST" action="{{ route('login.verify_code') }}"
                            class="mt-4 space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                            @csrf
                            <p class="text-sm text-zinc-600">
                                Kode OTP dikirim ke <span class="font-medium text-zinc-900">{{ $maskedPhone }}</span>.
                            </p>
                            <label class="block space-y-1 text-sm">
                                <span class="font-medium text-zinc-700">Verifikasi Nomor</span>
                                <input type="text" name="verification_code" required maxlength="6"
                                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 tracking-[0.3em] focus:border-zinc-500 focus:outline-none"
                                    placeholder="123456">
                                @error('verification_code') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </label>
                            <button type="submit"
                                class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Verifikasi & Masuk
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </section>
    </main>
</body>

</html>