<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[#191b1d] text-[#f3f0e7]">
            <header class="border-b border-[#41474b] bg-[#141618]">
                <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('dashboard') }}" class="font-semibold">Bakehouse Routes</a>
                    <nav class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-xs sm:gap-4 sm:text-sm">
                        <a href="{{ route('dashboard') }}" class="text-[#d0d1cd] hover:text-white">Dashboard</a>
                        @can('users')
                            <a href="{{ route('admin.users') }}" class="text-[#d0d1cd] hover:text-white">Users</a>
                        @endcan
                        @can('routes')
                            <a href="{{ route('admin.routes') }}" class="text-[#d0d1cd] hover:text-white">Routes</a>
                        @endcan
                        @can('items')
                            <a href="{{ route('admin.items') }}" class="text-[#d0d1cd] hover:text-white">Items</a>
                        @endcan
                        @can('shops')
                            <a href="{{ route('admin.shops') }}" class="text-[#d0d1cd] hover:text-white">Shops</a>
                        @endcan
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="min-h-10 rounded-lg border border-[#51595d] px-3 hover:bg-[#2b3033]">Log out</button>
                        </form>
                    </nav>
                </div>
            </header>

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-[#41474b] bg-[#222629]">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
