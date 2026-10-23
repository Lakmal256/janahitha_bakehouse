<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Items extends Component
{
    public string $name = '';

    public string $defaultPrice = '';

    public array $edits = [];

    public function mount(): void
    {
        Gate::authorize('items');
        $this->loadEdits();
    }

    public function add(): void
    {
        Gate::authorize('items');
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120', 'unique:items,name'],
            'defaultPrice' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        Item::create([
            'name' => $validated['name'],
            'default_price' => $validated['defaultPrice'],
            'sort' => (int) Item::max('sort') + 1,
        ]);

        $this->reset(['name', 'defaultPrice']);
        $this->loadEdits();
        session()->flash('status', 'Item and default price added.');
    }

    public function save(int $itemId): void
    {
        Gate::authorize('items');
        $item = Item::query()->findOrFail($itemId);
        $values = $this->edits[$itemId] ?? [];
        $validated = validator($values, [
            'name' => ['required', 'string', 'max:120', Rule::unique('items', 'name')->ignore($itemId)],
            'default_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ])->validate();

        $item->update($validated);
        $this->loadEdits();
        session()->flash('status', 'Item and default price saved.');
    }

    public function delete(int $itemId): void
    {
        Gate::authorize('items');
        $item = Item::query()->withCount(['shops', 'orderLines', 'billLines'])->findOrFail($itemId);

        if ($item->shops_count || $item->order_lines_count || $item->bill_lines_count) {
            $this->addError('delete', 'This item is used by a shop, order, or saved bill and cannot be deleted.');

            return;
        }

        $item->delete();
        $this->loadEdits();
    }

    private function loadEdits(): void
    {
        $this->edits = Item::query()->orderBy('sort')->get()
            ->mapWithKeys(fn (Item $item): array => [$item->id => [
                'name' => $item->name,
                'default_price' => $item->default_price,
            ]])->all();
    }

    public function render()
    {
        return view('livewire.admin.items', ['items' => Item::query()->orderBy('sort')->get()]);
    }
}
