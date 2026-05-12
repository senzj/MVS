<?php

namespace App\Livewire\Product;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
                    $subQuery->whereHasMorph('reference', [Order::class], fn ($referenceQuery) => $referenceQuery->where('receipt_number', 'like', $search))
                        ->orWhere('remarks', 'like', $search)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search));
                });
            });

        // KPI calculations
        $total = (clone $query)->count();
        $removed = (clone $query)->whereIn('type', ['order_created', 'order_updated'])->sum('quantity');
        $added = (clone $query)->whereIn('type', ['order_cancelled', 'refund', 'restock'])->sum('quantity');

        // manual corrections count and today's adjustments
        $manualCorrections = (clone $query)->where('type', 'manual_adjustment')->count();
        $todaysAdjustments = InventoryMovement::whereDate('created_at', Carbon::now()->toDateString())->count();

        // Most adjusted product by absolute quantity (aggregate)
        $mostAdjusted = InventoryMovement::select('product_id', DB::raw('SUM(ABS(quantity)) as total_adjusted'))
            ->groupBy('product_id')
            ->orderByDesc('total_adjusted')
            ->with('product')
            ->first();

        $mostAdjustedProduct = $mostAdjusted?->product?->name ?? null;

        $stats = [
            'total' => $total,
            'added' => $added,
            'removed' => $removed,
            'most_adjusted_product' => $mostAdjustedProduct,
            'manual_corrections' => $manualCorrections,
            'todays_adjustments' => $todaysAdjustments,
        ];

        // Chart dataset: use user date filters if provided, otherwise last 14 days
        $start = $this->dateFrom ? Carbon::parse($this->dateFrom) : Carbon::now()->subDays(13);
        $end = $this->dateTo ? Carbon::parse($this->dateTo) : Carbon::now();
        $period = $start->copy();

        $labels = [];
        $addedSeries = [];
        $removedSeries = [];

        $addedTypes = ['order_cancelled', 'refund', 'restock'];
        $removedTypes = ['order_created', 'order_updated'];

        while ($period->lte($end)) {
            $dateLabel = $period->format('M d');
            $labels[] = $dateLabel;

            $dayAdded = (clone $query)->whereDate('created_at', $period->toDateString())->whereIn('type', $addedTypes)->sum('quantity');
            $dayRemoved = (clone $query)->whereDate('created_at', $period->toDateString())->whereIn('type', $removedTypes)->sum('quantity');

            $addedSeries[] = (int) $dayAdded;
            $removedSeries[] = (int) $dayRemoved;

            $period->addDay();
        }

        $chart = [
            'labels' => $labels,
            'added' => $addedSeries,
            'removed' => $removedSeries,
        ];

        return view('livewire.product.inventoryaudit', [
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
            'chart' => $chart,
        ]);
    }
}
