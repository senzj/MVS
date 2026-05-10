<?php

namespace App\Livewire\Logs;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryAudit extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = 'all';
    public string $productFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'productFilter' => ['except' => 'all'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'productFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $query = InventoryMovement::query()
            ->with(['product', 'user', 'reference'])
            ->when($this->typeFilter !== 'all', fn ($builder) => $builder->where('type', $this->typeFilter))
            ->when($this->productFilter !== 'all', fn ($builder) => $builder->where('product_id', $this->productFilter))
            ->when($this->dateFrom !== '', fn ($builder) => $builder->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($builder) => $builder->whereDate('created_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function ($builder) {
                $search = '%' . trim($this->search) . '%';

                $builder->where(function ($subQuery) use ($search) {
                    $subQuery->where('type', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', $search))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search))
                        ->orWhereHasMorph('reference', [Order::class], fn ($referenceQuery) => $referenceQuery->where('receipt_number', 'like', $search));
                });
            });

        $stats = [
            'total' => (clone $query)->count(),
            'deducted' => (clone $query)->whereIn('type', ['order_created', 'order_updated'])->sum('quantity'),
            'restored' => (clone $query)->whereIn('type', ['order_cancelled', 'refund', 'restock', 'manual_adjustment'])->sum('quantity'),
        ];

        return view('livewire.logs.inventoryaudit', [
            'movements' => $query->latest()->paginate($this->perPage),
            'products' => Product::all()->sortBy('name')->mapWithKeys(fn ($product) => [$product->id => $product->name]),
            'movementTypes' => [
                'all' => __('All Types'),
                'order_created' => __('Order Created'),
                'order_updated' => __('Order Updated'),
                'order_cancelled' => __('Order Cancelled'),
                'refund' => __('Refund'),
                'manual_adjustment' => __('Manual Adjustment'),
                'restock' => __('Restock'),
            ],
            'stats' => $stats,
        ]);
    }
}
