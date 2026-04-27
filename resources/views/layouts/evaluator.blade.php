<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (() => {
            const theme = localStorage.getItem('kepsakeval-theme');
            if (theme === 'dark') document.documentElement.classList.add('dark');
            if (theme === 'light') document.documentElement.classList.remove('dark');
        })();
    </script>
    <title>{{ config('app.name', 'KepsekEval') }} - Penilai</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>

<body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    @php
        $role = auth()->user()?->roleRef?->name;
        $roleSlug = auth()->user()?->roleSlug();
        $department = auth()->user()?->departmentRef?->name;
        $dashboardPath = (string) (config("rbac.dashboard_paths.{$roleSlug}") ?? '/fill/questionnaires');
        $dashboardRoute = url($dashboardPath);
        $isFillingQuestionnaire = request()->routeIs('fill.questionnaires.index');
    @endphp

    <div class="mx-auto max-w-5xl p-4 md:p-6">
        <x-session-toast />

        <header
            class="mb-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            x-data="{ dark: document.documentElement.classList.contains('dark') }">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Dashboard Penilai</p>
                    <h1 class="text-lg font-semibold">{{ config('app.name', 'KepsekEval') }}</h1>
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        {{ $role ?: '-' }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $department ?: '-' }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span @class(['opacity-50 pointer-events-none' => $isFillingQuestionnaire])>
                        <a href="{{ route('fill.questionnaires.index') }}" wire:navigate>
                            <flux:button variant="ghost" icon="clipboard-document-list">Kuisioner Saya</flux:button>
                        </a>
                    </span>

                    <span @class(['opacity-50 pointer-events-none' => $isFillingQuestionnaire])>
                        <a href="/fill/dashboard/guru" wire:navigate>
                            <flux:button variant="ghost" icon="clock">Riwayat Pengisian</flux:button>
                        </a>
                    </span>

                    <span @class(['opacity-50 pointer-events-none' => $isFillingQuestionnaire])>
                        <a href="{{ route('profile') }}" wire:navigate>
                            <flux:button variant="ghost" icon="user-circle">Profil</flux:button>
                        </a>
                    </span>

                    <flux:button variant="outline" icon="moon" x-on:click="
                            dark = !dark;
                            document.documentElement.classList.toggle('dark', dark);
                            localStorage.setItem('kepsakeval-theme', dark ? 'dark' : 'light');
                        ">
                        Dark
                    </flux:button>

                    <span @class(['opacity-50 pointer-events-none' => $isFillingQuestionnaire])>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:button type="submit" variant="danger" icon="arrow-right-start-on-rectangle">Logout
                            </flux:button>
                        </form>
                    </span>
                </div>
            </div>
        </header>

        {{ $slot }}

        <footer
            class="mt-8 border-t border-zinc-200 pt-4 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            {{ config('app.name', 'KepsekEval') }} - {{ config('app.copyright', 'Yayasan Al-Wathoniyah 9') }}
        </footer>
    </div>

    @fluxScripts
    @livewireScripts
</body>

</html>