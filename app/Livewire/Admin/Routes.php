<?php

namespace App\Livewire\Admin;

use App\Models\Route as BakeryRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Routes extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $driver = '';

    public function mount(): void
    {
        Gate::authorize('routes');
    }

    public function edit(int $routeId): void
    {
        Gate::authorize('routes');
        $route = BakeryRoute::query()->findOrFail($routeId);
        $this->editingId = $route->id;
        $this->name = $route->name;
        $this->driver = $route->driver ?? '';
    }

    public function cancelEdit(): void
    {
        Gate::authorize('routes');
        $this->reset(['editingId', 'name', 'driver']);
    }

    public function save(): void
    {
        Gate::authorize('routes');
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('routes', 'name')->ignore($this->editingId)],
            'driver' => ['nullable', 'string', 'max:120'],
        ]);

        if ($this->editingId) {
            BakeryRoute::query()->findOrFail($this->editingId)->update($validated);
            session()->flash('status', 'Route updated.');
        } else {
            $validated['sort'] = (int) BakeryRoute::max('sort') + 1;
            BakeryRoute::create($validated);
            session()->flash('status', 'Route created.');
        }

        $this->reset(['editingId', 'name', 'driver']);
    }

    public function delete(int $routeId): void
    {
        Gate::authorize('routes');
        $route = BakeryRoute::query()->withCount('shops')->findOrFail($routeId);

        if ($route->shops_count > 0) {
            $this->addError('delete', 'Move or remove this route\'s shops before deleting it.');
            return;
        }

        $route->delete();
        session()->flash('status', 'Route deleted.');
    }

    public function render()
    {
        return view('livewire.admin.routes', [
            'routes' => BakeryRoute::query()->withCount('shops')->orderBy('sort')->get(),
        ]);
    }
}
