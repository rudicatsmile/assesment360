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
                <p class="mt-3 text-sm text-zinc-300">Silakan login menggunakan akun role Anda untuk mengakses dashboard sesuai hak akses.</p>
                <div class="mt-6 space-y-1 text-xs text-zinc-300">
                    <p>Demo Admin: admin@kepsekeval.test / password</p>
                    <p>Demo Guru: guru1@kepsekeval.test / password</p>
                </div>
            </aside>

            <div class="p-6 md:p-8">
                <h2 class="text-xl font-semibold text-zinc-900">Login</h2>
                <p class="mt-1 text-sm text-zinc-500">Masukkan email dan password.</p>

                @if (session('error'))
                    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="mt-5 space-y-4">
                    @csrf

                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-zinc-700">Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                            placeholder="contoh@kepsekeval.test"
                        >
                        @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-zinc-700">Password</span>
                        <input
                            type="password"
                            name="password"
                            required
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-zinc-500 focus:outline-none"
                            placeholder="********"
                        >
                        @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex items-center gap-2 text-sm text-zinc-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300">
                        <span>Ingat saya</span>
                    </label>

                    <button type="submit" class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                        Masuk
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
