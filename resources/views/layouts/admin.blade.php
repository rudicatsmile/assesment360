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
    <title>{{ config('app.name', 'KepsekEval') }} - Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div
        class="flex min-h-screen"
        x-data="{ dark: document.documentElement.classList.contains('dark') }"
    >
        <aside class="w-72 border-r border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-6">
                <p class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Admin Panel</p>
                <h1 class="text-lg font-semibold">{{ config('app.name', 'KepsekEval') }}</h1>
            </div>

            <nav class="space-y-2" aria-label="Admin Navigation">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="block">
                    <flux:button :variant="request()->routeIs('admin.dashboard') ? 'primary' : 'ghost'" icon="home" class="w-full justify-start">
                        Dashboard
                    </flux:button>
                </a>
                <a href="{{ route('admin.questionnaires.index') }}" wire:navigate class="block">
                    <flux:button variant="ghost" icon="clipboard-document-list" class="w-full justify-start">
                        Daftar Kuisioner
                    </flux:button>
                </a>
                <a href="{{ route('admin.analytics.index') }}" wire:navigate class="block">
                    <flux:button :variant="request()->routeIs('admin.analytics.*') ? 'primary' : 'ghost'" icon="chart-bar" class="w-full justify-start">
                        Analytics
                    </flux:button>
                </a>
                @if (auth()->user()?->isAdminRole())
                    <a href="{{ route('admin.departments.index') }}" wire:navigate class="block">
                        <flux:button :variant="request()->routeIs('admin.departments.*') ? 'primary' : 'ghost'" icon="building-office" class="w-full justify-start">
                            Departments
                        </flux:button>
                    </a>
                    <a href="{{ route('admin.users.index') }}" wire:navigate class="block">
                        <flux:button :variant="request()->routeIs('admin.users.*') ? 'primary' : 'ghost'" icon="users" class="w-full justify-start">
                            Users
                        </flux:button>
                    </a>
                    @if (auth()->user()?->canManageRoles())
                        <a href="{{ route('admin.roles.index') }}" wire:navigate class="block">
                            <flux:button :variant="request()->routeIs('admin.roles.*') ? 'primary' : 'ghost'" icon="shield-check" class="w-full justify-start">
                                Role Management
                            </flux:button>
                        </a>
                    @endif
                @endif
            </nav>

            <div class="mt-6 space-y-2">
                <flux:button
                    variant="outline"
                    icon="moon"
                    class="w-full justify-start"
                    x-on:click="
                        dark = !dark;
                        document.documentElement.classList.toggle('dark', dark);
                        localStorage.setItem('kepsakeval-theme', dark ? 'dark' : 'light');
                    "
                >
                    Dark Mode
                </flux:button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="danger" icon="arrow-right-start-on-rectangle" class="w-full justify-start">
                        Logout
                    </flux:button>
                </form>
            </div>
        </aside>

        <main class="flex-1 p-6">
            <x-session-toast />
            {{ $slot }}

            <footer class="mt-8 border-t border-zinc-200 pt-4 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                {{ config('app.name', 'KepsekEval') }} - {{ config('app.copyright', 'Yayasan Al-Wathoniyah 9') }}
            </footer>
        </main>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>
