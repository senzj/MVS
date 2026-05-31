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
        $tz = config('app.timezone') ?? 'UTC';
        $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();

        // ── Filtered query (for the table/pagination) ─────────────────────────
        $query = InventoryMovement::query()
            ->with(['product', 'user', 'reference'])
            ->when($this->typeFilter !== 'all', fn ($b) => $b->where('type', $this->typeFilter))
            ->when($this->productFilter !== 'all', fn ($b) => $b->where('product_id', $this->productFilter))
            ->when($this->dateFrom !== '', fn ($b) => $b->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($b) => $b->whereDate('created_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function ($b) {
                $search = '%' . trim($this->search) . '%';
                $b->where(function ($sub) use ($search) {
                    $sub->whereHasMorph('reference', [Order::class], fn ($q) => $q->where('receipt_number', 'like', $search))
                        ->orWhere('remarks', 'like', $search)
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $search));
                });
            });

        // ── Clean aggregate query (NO eager loads — safe for sum/count) ───────
        $aggQuery = InventoryMovement::query()
            ->when($this->typeFilter !== 'all', fn ($b) => $b->where('type', $this->typeFilter))
            ->when($this->productFilter !== 'all', fn ($b) => $b->where('product_id', $this->productFilter))
            ->when($this->dateFrom !== '', fn ($b) => $b->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($b) => $b->whereDate('created_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function ($b) {
                $search = '%' . trim($this->search) . '%';
                $b->where(function ($sub) use ($search) {
                    $sub->whereHasMorph('reference', [Order::class], fn ($q) => $q->where('receipt_number', 'like', $search))
                        ->orWhere('remarks', 'like', $search)
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $search));
                });
            });

        $addedTypes   = ['order_cancelled', 'refund', 'restock'];
        $removedTypes = ['order_created', 'order_updated'];

        // ── KPIs ──────────────────────────────────────────────────────────────
        $total   = (clone $aggQuery)->count();
        $added   = (clone $aggQuery)->whereIn('type', $addedTypes)->sum(DB::raw('ABS(quantity)'));
        $removed = (clone $aggQuery)->whereIn('type', $removedTypes)->sum(DB::raw('ABS(quantity)'));

        $manualCorrections = (clone $aggQuery)->where('type', 'manual_adjustment')->count();
        $todaysAdjustments = InventoryMovement::whereDate('created_at', Carbon::now()->toDateString())->count();

        $mostAdjusted = InventoryMovement::select('product_id', DB::raw('SUM(ABS(quantity)) as total_adjusted'))
            ->groupBy('product_id')
            ->orderByDesc('total_adjusted')
            ->with('product')
            ->first();

        $stats = [
            'total'                  => $total,
            'added'                  => $added,
            'removed'                => $removed,
            'most_adjusted_product'  => $mostAdjusted?->product?->name ?? null,
            'manual_corrections'     => $manualCorrections,
            'todays_adjustments'     => $todaysAdjustments,
        ];

        // ── Chart: Movements Over Time ────────────────────────────────────────
        $start  = $this->dateFrom ? Carbon::parse($this->dateFrom) : Carbon::now()->subDays(13);
        $end    = $this->dateTo   ? Carbon::parse($this->dateTo)   : Carbon::now();
        $period = $start->copy();

        $labels = $addedSeries = $removedSeries = $netSeries = [];

        while ($period->lte($end)) {
            $dateStr = $period->toDateString();
            $labels[] = $period->locale($loc)->isoFormat('MMM D');

            // Use clean aggregate query — no eager loads, no GROUP BY conflicts
            $dayAdded   = (clone $aggQuery)
                ->whereDate('created_at', $dateStr)
                ->whereIn('type', $addedTypes)
                ->sum(DB::raw('ABS(quantity)'));

            $dayRemoved = (clone $aggQuery)
                ->whereDate('created_at', $dateStr)
                ->whereIn('type', $removedTypes)
                ->sum(DB::raw('ABS(quantity)'));

            $addedSeries[]   = (int) $dayAdded;
            $removedSeries[] = (int) $dayRemoved;
            $netSeries[]     = (int) ($dayAdded - $dayRemoved);

            $period->addDay();
        }

        $chart = [
            'labels'  => $labels,
            'added'   => $addedSeries,
            'removed' => $removedSeries,
            'net'     => $netSeries,
            'series'  => [
                'added'   => __('Addition'),
                'removed' => __('Deduction'),
                'net'     => __('Net Movement'),
            ],
        ];

        // ── Stock Distribution Chart ──────────────────────────────────────────
        $stockDistribution = Product::where('stocks', '>', 0)
            ->orderByDesc('stocks')
            ->take(10)
            ->pluck('stocks', 'name')
            ->toArray();

        $stockChart = [
            'labels' => array_keys($stockDistribution),
            'data'   => array_values($stockDistribution),
        ];

        // ── Movement Type Distribution Chart ─────────────────────────────────
        $typeDistribution = InventoryMovement::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $typeLabels = array_map(fn ($type) => match ($type) {
            'order_created'    => __('Order Created'),
            'order_updated'    => __('Order Updated'),
            'order_cancelled'  => __('Order Cancelled'),
            'refund'           => __('Refund'),
            'manual_adjustment'=> __('Manual Adjustment'),
            'restock'          => __('Restock'),
            default            => ucfirst(str_replace('_', ' ', $type)),
        }, array_keys($typeDistribution));

        $typeChart = [
            'labels' => $typeLabels,
            'data'   => array_values($typeDistribution),
        ];

        return view('livewire.product.inventoryaudit', [
            'movements'    => $query->latest()->paginate($this->perPage),
            'products'     => Product::all()->sortBy('name')->mapWithKeys(fn ($p) => [$p->id => $p->name]),
            'movementTypes' => [
                'all'               => __('All Types'),
                'order_created'     => __('Order Created'),
                'order_updated'     => __('Order Updated'),
                'order_cancelled'   => __('Order Cancelled'),
                'refund'            => __('Refund'),
                'manual_adjustment' => __('Manual Adjustment'),
                'restock'           => __('Restock'),
            ],
            'stats'       => $stats,
            'chart'       => $chart,
            'stockChart'  => $stockChart,
            'typeChart'   => $typeChart,
        ]);
    }
}
