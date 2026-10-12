<?php

namespace App\Livewire\Admin;

use App\Models\Route as BakeryRoute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Users extends Component
{
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'viewer';
    public bool $allRoutes = true;
    public array $selectedRoutes = [];
    public array $perms = [];

    public function mount(): void
    {
        Gate::authorize('users');
        $this->perms = User::permissionsForRole($this->role);
    }

    public function updatedRole(): void
    {
        $this->perms = User::permissionsForRole($this->role);
    }

    public function save(): void
    {
        Gate::authorize('create', User::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'lowercase', 'min:3', 'max:40', 'unique:users,username'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'string'],
            'role' => ['required', Rule::in(array_keys(User::rolePresets()))],
            'allRoutes' => ['required', 'boolean'],
            'selectedRoutes' => ['array'],
            'selectedRoutes.*' => ['integer', 'distinct', 'exists:routes,id'],
            'perms' => ['required', 'array'],
            'perms.*' => ['boolean'],
        ]);

        $unknownPermissions = array_diff(array_keys($validated['perms']), User::PERMISSIONS);
        if ($unknownPermissions !== []) {
            throw ValidationException::withMessages(['perms' => 'One or more selected permissions are invalid.']);
        }

        $permissions = array_fill_keys(User::PERMISSIONS, false);
        foreach (User::PERMISSIONS as $permission) {
            $permissions[$permission] = (bool) ($validated['perms'][$permission] ?? false);
        }

        DB::transaction(function () use ($validated, $permissions): void {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'perms' => $permissions,
                'all_routes' => $validated['allRoutes'],
                'active' => true,
                'is_owner' => false,
            ]);

            if (! $user->all_routes) {
                $user->routes()->sync($validated['selectedRoutes'] ?? []);
            }
        });

        $this->reset(['name', 'username', 'password', 'password_confirmation', 'selectedRoutes']);
        $this->role = 'viewer';
        $this->allRoutes = true;
        $this->perms = User::permissionsForRole($this->role);
        session()->flash('status', 'User account created.');
    }

    public function toggleBlock(int $userId): void
    {
        Gate::authorize('users');

        $target = User::query()->findOrFail($userId);
        Gate::authorize('update', $target);

        abort_if($target->is(auth()->user()), 403);

        $target->update(['active' => ! $target->active]);

        if (! $target->active) {
            DB::table('sessions')->where('user_id', $target->id)->delete();
        }
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::query()->with('routes')->orderBy('name')->get(),
            'routes' => BakeryRoute::query()->orderBy('sort')->get(),
            'roles' => User::rolePresets(),
            'permissions' => User::PERMISSIONS,
        ]);
    }
}
