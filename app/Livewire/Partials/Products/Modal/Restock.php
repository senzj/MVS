<?php

namespace App\Livewire\Partials\Products\Modal;

use App\Models\ItemRestocks;
use App\Models\Product;
use App\Services\Products\InventoryService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Restock extends Component
{
    public bool $showModal = false;
    public ?int $productId = null;

    public int    $quantity  = 1;
    public float  $unit_cost = 0.00;
    public string $unit_type = 'pcs';
    public string $remarks   = '';

    /** Built-in units always shown in the dropdown. */
    private function presetUnits(): array
    {
        return [
            __('pcs'),
            __('kg'),
            __('g'),
            __('box'),
            __('liter'),
            __('dozen'),
        ];
    }

    protected function rules(): array
    {
        return [
            'quantity'  => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:30',
            'remarks'   => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'quantity.required'  => 'Please enter a restock quantity.',
        'quantity.min'       => 'Quantity must be at least 1.',
        'unit_cost.required' => 'Please enter the unit cost.',
        'unit_cost.min'      => 'Unit cost cannot be negative.',
        'unit_type.required' => 'Please enter a unit type.',
        'unit_type.max'      => 'Unit type must be 30 characters or less.',
    ];

    #[On('open-restock-modal')]
    public function openModal(int $id): void
    {
        $product = Product::find($id);

        if (! $product) {
            $this->dispatch('show-error', ['message' => __('Product not found.')]);
            return;
        }

        $this->productId = $product->id;
        $this->quantity  = 1;
        $this->unit_cost = (float) ($product->cost ?? 0);
        $this->unit_type = 'pcs';
        $this->remarks   = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->productId = null;
        $this->resetErrorBag();
    }

    public function saveRestock(InventoryService $inventory): void
    {
        $this->unit_type = trim($this->unit_type);
        $this->validate();

        $product = $this->productId ? Product::find($this->productId) : null;

        if (! $product) {
            $this->dispatch('show-error', ['message' => __('Product not found.')]);
            return;
        }

        try {
            $inventory->restockProduct(
                productId: $product->id,
                qty:       $this->quantity,
                unitCost:  $this->unit_cost,
                unitType:  $this->unit_type,
                notes:     $this->remarks ?: null,
            );

            $this->dispatch('show-success', [
                'message' => __(':name restocked. :qty :unit added to inventory.', [
                    'name' => $product->name,
                    'qty'  => $this->quantity,
                    'unit' => $this->unit_type,
                ]),
            ]);

            $this->closeModal();
            $this->dispatch('product-restocked');

        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->dispatch('show-error', ['message' => $message]);
        } catch (\Throwable $e) {
            $this->dispatch('show-error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Merges preset units with every distinct unit_type already used in the
     * item_restocks table, deduped and sorted alphabetically.
     */
    private function unitOptions(): array
    {
        $fromDb = ItemRestocks::query()
            ->distinct()
            ->whereNotNull('unit_type')
            ->where('unit_type', '!=', '')
            ->pluck('unit_type')
            ->toArray();

        return collect(array_merge($this->presetUnits(), $fromDb))
            ->map(fn ($u) => strtolower(trim($u)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function render()
    {
        $product = $this->productId ? Product::find($this->productId) : null;

        return view('livewire.partials.products.modal.restock', [
            'product'     => $product,
            'subtotal'    => round(($this->quantity ?? 0) * ($this->unit_cost ?? 0), 2),
            'unitOptions' => $this->unitOptions(),
        ]);
    }
}
