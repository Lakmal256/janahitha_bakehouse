<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-[#41474b] bg-[#222629] p-6 sm:p-8">
            <p class="text-sm text-[#aab0b3]">{{ now()->timezone('Asia/Colombo')->format('D, d M Y') }}</p>
            <h2 class="mt-2 text-3xl font-bold">Welcome, {{ auth()->user()->name }}</h2>
            <p class="mt-3 max-w-2xl text-[#c3c7c9]">Your account is ready. The bakery order and billing workspaces will appear here as they are built.</p>

            @can('users')
                <a href="{{ route('admin.users') }}" class="mt-6 inline-flex min-h-12 items-center rounded-lg bg-[#f3f0e7] px-5 font-semibold text-[#191b1d] hover:bg-white">Manage users</a>
            @endcan
        </div>
    </section>
</x-app-layout>
