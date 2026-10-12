<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 text-[#f3f0e7] sm:px-6 lg:px-8">
    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#aab0b3]">Administration</p>
        <h1 class="mt-2 text-3xl font-bold">Users</h1>
        <p class="mt-2 text-sm text-[#c3c7c9]">Create staff accounts, set permissions, and limit route access.</p>
    </header>

    @if (session('status'))
        <p role="status" class="rounded-lg border border-[#41474b] bg-[#222629] px-4 py-3 text-sm">{{ session('status') }}</p>
    @endif

    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-5 sm:p-6">
        <h2 class="text-xl font-semibold">Add user</h2>
        <form wire:submit="save" class="mt-5 grid gap-6 lg:grid-cols-2">
            <div class="space-y-4">
                <label class="block text-sm font-medium">Name
                    <input wire:model="name" autocomplete="name" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    @error('name') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium">Username
                    <input wire:model="username" autocomplete="off" autocapitalize="none" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    @error('username') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium">Password
                    <input wire:model="password" type="password" autocomplete="new-password" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    @error('password') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium">Confirm password
                    <input wire:model="password_confirmation" type="password" autocomplete="new-password" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                </label>
                <label class="block text-sm font-medium">Role
                    <select wire:model.live="role" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                        @foreach (array_keys($roles) as $roleName)
                            <option value="{{ $roleName }}">{{ ucfirst($roleName) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="space-y-5">
                <fieldset>
                    <legend class="text-sm font-semibold">Permissions</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex min-h-10 items-center gap-3 rounded-lg border border-[#41474b] px-3 text-sm">
                                <input type="checkbox" wire:model="perms.{{ $permission }}" class="h-4 w-4 accent-[#f3f0e7]">
                                <span>{{ str_replace('_', ' ', ucfirst($permission)) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('perms') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-semibold">Route access</legend>
                    <label class="mt-2 flex min-h-11 items-center gap-3 rounded-lg border border-[#41474b] px-3 text-sm">
                        <input type="checkbox" wire:model.live="allRoutes" class="h-4 w-4 accent-[#f3f0e7]">
                        <span>All routes</span>
                    </label>
                    @if (! $allRoutes)
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($routes as $route)
                                <label class="flex min-h-11 items-center gap-3 rounded-lg border border-[#41474b] px-3 text-sm">
                                    <input type="checkbox" wire:model="selectedRoutes" value="{{ $route->id }}" class="h-4 w-4 accent-[#f3f0e7]">
                                    <span>{{ $route->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('selectedRoutes.*') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </fieldset>

                <button type="submit" class="min-h-12 rounded-lg bg-[#f3f0e7] px-5 font-semibold text-[#191b1d] hover:bg-white">Create user</button>
            </div>
        </form>
    </section>

    <section>
        <h2 class="mb-3 text-xl font-semibold">Existing users</h2>
        <div class="overflow-hidden rounded-xl border border-[#41474b] bg-[#222629]">
            @foreach ($users as $user)
                <article wire:key="user-{{ $user->id }}" class="flex flex-wrap items-center justify-between gap-4 border-b border-[#41474b] px-4 py-4 last:border-b-0">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $user->name }} @if ($user->is_owner)<span class="ml-2 text-xs text-[#aab0b3]">Owner</span>@endif</p>
                        <p class="text-sm text-[#aab0b3]">{{ '@'.$user->username }} · {{ ucfirst($user->role) }} · {{ $user->all_routes ? 'All routes' : $user->routes->pluck('name')->join(', ') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full border border-[#51595d] px-3 py-1 text-xs">{{ $user->active ? 'Active' : 'Blocked' }}</span>
                        @unless ($user->is_owner || $user->is(auth()->user()))
                            <button type="button" wire:click="toggleBlock({{ $user->id }})" class="min-h-10 rounded-lg border border-[#51595d] px-4 text-sm font-medium hover:bg-[#2b3033]">{{ $user->active ? 'Block' : 'Unblock' }}</button>
                        @endunless
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
