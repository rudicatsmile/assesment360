<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center justify-center p-6">
        <section class="w-full rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500">Error 404</p>
            <h1 class="mt-2 text-3xl font-semibold">Halaman Tidak Ditemukan</h1>
            <p class="mt-3 text-sm text-zinc-600">URL yang Anda buka tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ url('/dashboard') }}" class="mt-6 inline-block rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">
                Kembali ke Dashboard
            </a>
        </section>
    </main>
</body>
</html>
