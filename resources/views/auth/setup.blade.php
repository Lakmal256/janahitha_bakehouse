<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create admin account · Bakehouse Routes</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#191b1d] font-sans text-[#f3f0e7] antialiased">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-5 py-12">
        <section class="w-full rounded-xl border border-[#41474b] bg-[#222629] p-6 shadow-2xl sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#aab0b3]">Bakehouse Routes · Initial setup</p>
            <h1 class="mt-3 text-3xl font-bold">Create admin account</h1>
            <p class="mt-2 text-sm text-[#c3c7c9]">This one-time setup closes as soon as the first account is created.</p>

            <form method="POST" action="{{ route('setup.store') }}" class="mt-7 space-y-5">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="mt-1 min-h-12 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7] focus:border-[#d0d1cd] focus:outline-none" />
                    @error('name') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" autocapitalize="none" class="mt-1 min-h-12 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7] focus:border-[#d0d1cd] focus:outline-none" />
                    @error('username') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 min-h-12 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7] focus:border-[#d0d1cd] focus:outline-none" />
                    @error('password') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 min-h-12 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7] focus:border-[#d0d1cd] focus:outline-none" />
                </div>
                <button type="submit" class="min-h-12 w-full rounded-lg bg-[#f3f0e7] px-5 font-semibold text-[#191b1d] hover:bg-white">Create admin account</button>
            </form>
        </section>
    </main>
</body>
</html>
