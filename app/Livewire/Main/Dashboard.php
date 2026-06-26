<?php

namespace App\Livewire\Main;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\InventoryMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public array $todayStats = [];
    public array $topSellingProducts = [];
    public array $salesVsProfitData = [];
    public array $ordersByDayData = [];
    public array $monthlyTrendsData = [];
    public array $categoryBreakdownData = [];
    public array $businessInsights = [];

    public function mount(): void
    {
        $orders = Order::all();
        $customers = Customer::all();
        $orderItems = OrderItem::all();
        $orderItems->load(['order', 'product']);
        $products = Product::all();
        $inventoryMovements = InventoryMovement::with('product')->get();

        $this->todayStats = $this->getTodayStats($orders, $orderItems);
        $this->topSellingProducts = $this->getTopSellingProducts($orderItems);
        $this->salesVsProfitData = $this->getSalesVsProfitData($orders, $orderItems, $customers);
        $this->ordersByDayData = $this->getOrdersByDayData($orders);
        $this->monthlyTrendsData = $this->getMonthlyTrendsData($orders);
        $this->categoryBreakdownData = $this->getCategoryBreakdownData($orderItems);
        $this->businessInsights = $this->getBusinessInsights($orders, $orderItems, $products, $inventoryMovements);

        $busiestMetrics = $this->getBusiestMetrics($orders, $orderItems);

        $this->dispatch('dashboard-charts-data', data: [
            'salesVsProfitData'    => $this->salesVsProfitData,
            'ordersByDayData'      => $this->ordersByDayData,
            'monthlyTrendsData'    => $this->monthlyTrendsData,
            'categoryBreakdownData'=> $this->categoryBreakdownData,
            'businessInsights'     => $this->businessInsights,
            'busiestMetrics'       => $busiestMetrics,
        ]);
    }

    // ─── Real cost / profit helpers ────────────────────────────────────────────

    /**
     * Sum of (product.cost × quantity) for each order item.
     * Uses the product's current weighted-average cost.
     */
    private function getItemsCost(Collection $orderItems): float
    {
        return round((float) $orderItems->sum(function (OrderItem $item) {
            return (float) ($item->product?->cost ?? 0) * (int) $item->quantity;
        }), 2);
    }

    /**
     * Gross profit = total_price (after item discount) − cost of goods.
     */
    private function getItemsProfit(Collection $orderItems): float
    {
        return round((float) $orderItems->sum(function (OrderItem $item) {
            $revenue = (float) $item->total_price;
            $cost    = (float) ($item->product?->cost ?? 0) * (int) $item->quantity;
            return $revenue - $cost;
        }), 2);
    }

    // ─── Discount helpers ──────────────────────────────────────────────────────

    /**
     * Sum of item-level discounts (order_items.discount_amount).
     */
    private function getItemDiscounts(Collection $orderItems): float
    {
        return round((float) $orderItems->sum('discount_amount'), 2);
    }

    /**
     * Compute the actual monetary value of order-level discounts.
     * For fixed type: discount_value is the direct amount.
     * For percentage type: back-calculate the deducted amount from order_total.
     */
    private function getOrderLevelDiscounts(Collection $orders): float
    {
        return round((float) $orders
            ->where('discount_type', '!=', 'none')
            ->sum(function (Order $order) {
                if ($order->discount_type === 'fixed') {
                    return (float) $order->discount_value;
                }

                if ($order->discount_type === 'percentage') {
                    $pct = (float) $order->discount_value / 100;
                    if ($pct > 0 && $pct < 1) {
                        // order_total is already after discount; reverse to find the deducted amount
                        $preTax = (float) $order->order_total / (1 - $pct);
                        return $preTax - (float) $order->order_total;
                    }
                }

                return 0.0;
            }), 2);
    }

    /**
     * Count orders that have any discount applied (any type != none
     * or any item with discount_amount > 0).
     */
    private function countDiscountedOrders(Collection $orders, Collection $orderItems): int
    {
        $orderLevelDiscounted = $orders
            ->where('discount_type', '!=', 'none')
            ->pluck('id')
            ->all();

        $itemLevelDiscounted = $orderItems
            ->filter(fn (OrderItem $i) => (float) $i->discount_amount > 0)
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->all();

        return collect(array_merge($orderLevelDiscounted, $itemLevelDiscounted))
            ->unique()
            ->count();
    }

    // ─── Busiest / peak metrics ────────────────────────────────────────────────

    private function getBusiestMetrics(Collection $orders, Collection $orderItems): array
    {
        $orders = $orders->filter(fn ($o) => $o && ($o->status ?? '') !== 'cancelled');

        // ── BY YEAR ──────────────────────────────────────────────────────────
        $allYears = $orders
            ->map(fn ($o) => Carbon::parse($o->created_at)->format('Y'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $yearLabels  = [];
        $yearOrders  = [];
        $yearRevenue = [];
        $yearProfit  = [];

        foreach ($allYears as $year) {
            $start = Carbon::createFromDate($year)->startOfYear();
            $end   = Carbon::createFromDate($year)->endOfYear();
            $items = $this->filterOrderItems($orderItems, $start, $end);

            $yearGroup = $this->filterOrders($orders, $start, $end);
            if ($yearGroup->isEmpty()) {
                continue;
            }

            $yearLabels[]  = $year;
            $yearOrders[]  = $yearGroup->count();
            $yearRevenue[] = round((float) $yearGroup->sum('order_total'), 2);
            $yearProfit[]  = $this->getItemsProfit($items);
        }

        // ── BY MONTH (last 12) ───────────────────────────────────────────────
        $monthLabels  = [];
        $monthOrders  = [];
        $monthProfit  = [];
        $monthRevenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $date  = Carbon::now()->subMonths($i)->startOfMonth();
            $start = $date->copy()->startOfMonth();
            $end   = $date->copy()->endOfMonth();

            $periodOrders = $this->filterOrders($orders, $start, $end);
            if ($periodOrders->isEmpty()) {
                continue;
            }

            $items = $this->filterOrderItems($orderItems, $start, $end);

            $monthLabels[]  = $date->format('M Y');
            $monthOrders[]  = $periodOrders->count();
            $monthRevenue[] = round((float) $periodOrders->sum('order_total'), 2);
            $monthProfit[]  = $this->getItemsProfit($items);
        }

        // ── BY WEEKDAY (last 90 days) ────────────────────────────────────────
        $weekdayRangeStart = Carbon::now()->subDays(89)->startOfDay();
        $orders90  = $this->filterOrders($orders, $weekdayRangeStart);
        $items90   = $this->filterOrderItems($orderItems, $weekdayRangeStart);

        $weekdayLabelsMap = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $openDays = array_values(array_unique(array_map(
            fn ($d) => (int) $d,
            config('storeconfig.store_open_days', [1, 2, 3, 4, 5, 6])
        )));
        sort($openDays);

        $weekDays       = [];
        $weekdayOrders  = [];
        $weekdayProfit  = [];
        $weekdayRevenue = [];

        foreach ($openDays as $dayIso) {
            $group = $orders90->filter(
                fn ($o) => Carbon::parse($o->created_at)->dayOfWeekIso === $dayIso
            );

            if ($group->isEmpty()) {
                continue;
            }

            $dayItems = $items90->filter(
                fn (OrderItem $i) => Carbon::parse($i->order?->created_at)->dayOfWeekIso === $dayIso
            );

            $weekDays[]       = $weekdayLabelsMap[$dayIso] ?? (string) $dayIso;
            $weekdayOrders[]  = $group->count();
            $weekdayRevenue[] = round((float) $group->sum('order_total'), 2);
            $weekdayProfit[]  = $this->getItemsProfit($dayItems);
        }

        // ── BY HOUR (last 30 days) ───────────────────────────────────────────
        $hourRangeStart = Carbon::now()->subDays(29)->startOfDay();
        $orders30 = $this->filterOrders($orders, $hourRangeStart);
        $items30  = $this->filterOrderItems($orderItems, $hourRangeStart);

        $storeStartHour    = config('storeconfig.store_open_hour', 7);
        $storeEndHour      = config('storeconfig.store_close_hour', 20);
        $latestHourWithData = $orders30->map(fn ($o) => (int) Carbon::parse($o->created_at)->format('G'))->max();
        $endHour = max($storeEndHour, is_null($latestHourWithData) ? $storeEndHour : (int) $latestHourWithData);

        $hourLabels  = [];
        $hourOrders  = [];
        $hourProfit  = [];
        $hourRevenue = [];

        for ($h = $storeStartHour; $h <= $endHour; $h++) {
            $group = $orders30->filter(
                fn ($o) => (int) Carbon::parse($o->created_at)->format('G') === $h
            );

            if ($group->isEmpty()) {
                continue;
            }

            $hItems = $items30->filter(
                fn (OrderItem $i) => (int) Carbon::parse($i->order?->created_at)->format('G') === $h
            );

            $hourLabels[]  = Carbon::createFromTime($h, 0, 0)->format('g A');
            $hourOrders[]  = $group->count();
            $hourRevenue[] = round((float) $group->sum('order_total'), 2);
            $hourProfit[]  = $this->getItemsProfit($hItems);
        }

        $makeSummary = function ($labels, $valuesOrders, $valuesProfit) {
            if (empty($labels)) {
                return ['most_orders' => null, 'least_orders' => null, 'most_profit' => null, 'least_profit' => null];
            }

            $maxOrders = max($valuesOrders);
            $minOrders = min($valuesOrders);
            $maxProfit = max($valuesProfit);
            $minProfit = min($valuesProfit);

            return [
                'most_orders'  => ['label' => $labels[array_search($maxOrders, $valuesOrders)], 'value' => $maxOrders],
                'least_orders' => ['label' => $labels[array_search($minOrders, $valuesOrders)], 'value' => $minOrders],
                'most_profit'  => ['label' => $labels[array_search($maxProfit, $valuesProfit)], 'value' => $maxProfit],
                'least_profit' => ['label' => $labels[array_search($minProfit, $valuesProfit)], 'value' => $minProfit],
            ];
        };

        return [
            'by_year'    => ['labels' => $yearLabels,    'orders' => $yearOrders,    'revenue' => $yearRevenue,    'profit' => $yearProfit,    'summary' => $makeSummary($yearLabels,    $yearOrders,    $yearProfit)],
            'by_month'   => ['labels' => $monthLabels,   'orders' => $monthOrders,   'revenue' => $monthRevenue,   'profit' => $monthProfit,   'summary' => $makeSummary($monthLabels,   $monthOrders,   $monthProfit)],
            'by_weekday' => ['labels' => $weekDays,       'orders' => $weekdayOrders, 'revenue' => $weekdayRevenue, 'profit' => $weekdayProfit, 'summary' => $makeSummary($weekDays,       $weekdayOrders, $weekdayProfit)],
            'by_hour'    => ['labels' => $hourLabels,     'orders' => $hourOrders,    'revenue' => $hourRevenue,    'profit' => $hourProfit,    'summary' => $makeSummary($hourLabels,     $hourOrders,    $hourProfit)],
        ];
    }

    // ─── Shared filter helpers ─────────────────────────────────────────────────

    private function dateInRange($date, Carbon $start, ?Carbon $end = null): bool
    {
        if (! $date) {
            return false;
        }

        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($carbonDate->lt($start)) {
            return false;
        }

        if ($end && $carbonDate->gt($end)) {
            return false;
        }

        return true;
    }

    private function filterOrders(Collection $orders, Carbon $start, ?Carbon $end = null): Collection
    {
        return $orders->filter(function (Order $order) use ($start, $end) {
            return $order->status !== 'cancelled'
                && $this->dateInRange($order->created_at, $start, $end);
        })->values();
    }

    private function filterOrderItems(Collection $orderItems, Carbon $start, ?Carbon $end = null): Collection
    {
        return $orderItems->filter(function (OrderItem $orderItem) use ($start, $end) {
            $order = $orderItem->order;

            return $order
                && $order->status !== 'cancelled'
                && $this->dateInRange($order->created_at, $start, $end);
        })->values();
    }

    private function isNoChargeItem(OrderItem $orderItem): bool
    {
        return (float) $orderItem->unit_price <= 0
            && (float) $orderItem->total_price <= 0
            && (int) $orderItem->quantity > 0;
    }

    private function summarizeNoChargeItems(Collection $orderItems, Carbon $start, ?Carbon $end = null): array
    {
        $freeItems = $this->filterOrderItems($orderItems, $start, $end)
            ->filter(fn (OrderItem $orderItem) => $this->isNoChargeItem($orderItem));

        return [
            'items'  => $freeItems->count(),
            'units'  => (int) $freeItems->sum('quantity'),
            'orders' => $freeItems->pluck('order_id')->filter()->unique()->count(),
        ];
    }

    private function formatCategoryName(?string $category): string
    {
        if (! $category) {
            return __('Uncategorized');
        }

        $categories = Product::getCategories();
        $label = $categories[$category] ?? ucfirst(str_replace('_', ' ', $category));

        return __($label);
    }

    private function summarizeTopProducts(Collection $orderItems, Carbon $start, ?Carbon $end = null, int $limit = 3): array
    {
        return $this->filterOrderItems($orderItems, $start, $end)
            ->groupBy('product_id')
            ->map(function (Collection $group) {
                $firstItem    = $group->first();
                $product      = $firstItem?->product;
                $totalSold    = (int) $group->sum('quantity');
                $totalRevenue = round((float) $group->sum('total_price'), 2);

                return [
                    'product_id'     => $firstItem?->product_id,
                    'name'           => $product?->name ?? __('Unknown product'),
                    'category'       => $product?->category,
                    'category_label' => $this->formatCategoryName($product?->category),
                    'total_sold'     => $totalSold,
                    'total_revenue'  => $totalRevenue,
                    'avg_price'      => $totalSold > 0 ? round($totalRevenue / $totalSold, 2) : 0,
                ];
            })
            ->sortByDesc('total_sold')
            ->values()
            ->take($limit)
            ->all();
    }

    private function summarizeMonthTopProducts(Collection $orderItems, int $limit = 5): array
    {
        $start = Carbon::now()->subWeeks(4)->startOfDay();

        return $this->filterOrderItems($orderItems, $start)
            ->groupBy('product_id')
            ->map(function (Collection $group) {
                $firstItem    = $group->first();
                $product      = $firstItem?->product;
                $totalSold    = (int) $group->sum('quantity');
                $totalRevenue = round((float) $group->sum('total_price'), 2);

                return [
                    'product_id'     => $firstItem?->product_id,
                    'name'           => $product?->name ?? __('Unknown product'),
                    'category'       => $product?->category,
                    'category_label' => $this->formatCategoryName($product?->category),
                    'total_sold'     => $totalSold,
                    'total_revenue'  => $totalRevenue,
                    'avg_weekly'     => round($totalSold / 4, 1),
                ];
            })
            ->sortByDesc('total_sold')
            ->values()
            ->take($limit)
            ->all();
    }

    // ─── Stat builders ─────────────────────────────────────────────────────────

    private function getTodayStats(Collection $orders, Collection $orderItems): array
    {
        $today      = Carbon::today();
        $todayEnd   = $today->copy()->endOfDay();
        $yesterday  = Carbon::yesterday();
        $yesterdayEnd = $yesterday->copy()->endOfDay();

        $todayOrders    = $this->filterOrders($orders, $today, $todayEnd);
        $todayItems     = $this->filterOrderItems($orderItems, $today, $todayEnd);
        $todayNoCharge  = $this->summarizeNoChargeItems($orderItems, $today, $todayEnd);
        $yesterdayOrders = $this->filterOrders($orders, $yesterday, $yesterdayEnd);

        $todaySales        = round((float) $todayOrders->sum('order_total'), 2);
        $todayOrderCount   = $todayOrders->count();
        $todayCost         = $this->getItemsCost($todayItems);
        $todayProfit       = $this->getItemsProfit($todayItems);
        $todayItemDisc     = $this->getItemDiscounts($todayItems);
        $todayOrderDisc    = $this->getOrderLevelDiscounts($todayOrders);
        $todayTotalDisc    = round($todayItemDisc + $todayOrderDisc, 2);

        $yesterdaySales      = round((float) $yesterdayOrders->sum('order_total'), 2);
        $yesterdayOrderCount = $yesterdayOrders->count();
        $yesterdayAvgOrder   = $yesterdayOrderCount > 0 ? round($yesterdaySales / $yesterdayOrderCount, 2) : 0;
        $todayAvgOrder       = $todayOrderCount > 0 ? round($todaySales / $todayOrderCount, 2) : 0;

        $salesGrowth    = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;
        $ordersGrowth   = $yesterdayOrderCount > 0 ? (($todayOrderCount - $yesterdayOrderCount) / $yesterdayOrderCount) * 100 : 0;
        $avgOrderGrowth = $yesterdayAvgOrder > 0 ? (($todayAvgOrder - $yesterdayAvgOrder) / $yesterdayAvgOrder) * 100 : 0;

        return [
            'sales'             => $todaySales,
            'income'            => $todaySales,
            'cost'              => $todayCost,
            'profit'            => $todayProfit,
            'profit_margin_pct' => $todaySales > 0 ? round(($todayProfit / $todaySales) * 100, 1) : 0,
            'orders'            => $todayOrderCount,
            'avg_order'         => $todayAvgOrder,
            'units_sold'        => (int) $todayItems->sum('quantity'),
            'free_items'        => $todayNoCharge['items'],
            'free_units'        => $todayNoCharge['units'],
            'free_orders'       => $todayNoCharge['orders'],
            'item_discounts'    => $todayItemDisc,
            'order_discounts'   => $todayOrderDisc,
            'total_discounts'   => $todayTotalDisc,
            'sales_growth'      => round($salesGrowth, 1),
            'orders_growth'     => round($ordersGrowth, 1),
            'avg_order_growth'  => round($avgOrderGrowth, 1),
            'raw_sales'         => $todaySales,
        ];
    }

    private function getTopSellingProducts(Collection $orderItems): array
    {
        $today    = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();

        return [
            'today'   => $this->summarizeTopProducts($orderItems, $today, $today->copy()->endOfDay(), 5),
            'week'    => $this->summarizeTopProducts($orderItems, $thisWeek, null, 5),
            'average' => $this->summarizeMonthTopProducts($orderItems, 5),
        ];
    }

    private function getSalesVsProfitData(Collection $orders, Collection $orderItems, Collection $customers): array
    {
        try {
            $labels           = [];
            $salesData        = [];
            $ordersData       = [];
            $profitData       = [];
            $costData         = [];
            $newCustomersData = [];
            $discountData     = [];

            for ($i = 29; $i >= 0; $i--) {
                $date     = Carbon::now()->subDays($i)->startOfDay();
                $dateEnd  = $date->copy()->endOfDay();

                $dayOrders   = $this->filterOrders($orders, $date, $dateEnd);
                $dayItems    = $this->filterOrderItems($orderItems, $date, $dateEnd);
                $salesValue  = round((float) $dayOrders->sum('order_total'), 2);

                $newCustomersValue = $customers->filter(function (Customer $customer) use ($date, $dateEnd) {
                    return $this->dateInRange($customer->created_at, $date, $dateEnd);
                })->count();

                $labels[]           = $date->format('M d');
                $salesData[]        = $salesValue;
                $ordersData[]       = $dayOrders->count();
                $costData[]         = $this->getItemsCost($dayItems);
                $profitData[]       = $this->getItemsProfit($dayItems);
                $newCustomersData[] = $newCustomersValue;
                $discountData[]     = round(
                    $this->getItemDiscounts($dayItems) + $this->getOrderLevelDiscounts($dayOrders),
                    2
                );
            }

            return [
                'labels'       => $labels,
                'sales'        => $salesData,
                'orders'       => $ordersData,
                'cost'         => $costData,
                'profit'       => $profitData,
                'new_customers'=> $newCustomersData,
                'discounts'    => $discountData,
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSalesVsProfitData: ' . $e->getMessage());
            return ['labels' => [], 'sales' => [], 'orders' => [], 'cost' => [], 'profit' => [], 'new_customers' => [], 'discounts' => []];
        }
    }

    private function getOrdersByDayData(Collection $orders): array
    {
        try {
            $currentWeek  = Carbon::now()->startOfWeek();
            $previousWeek = Carbon::now()->subWeek()->startOfWeek();
            $days         = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            $currentWeekData  = [];
            $previousWeekData = [];

            for ($i = 0; $i < 7; $i++) {
                $currentDay  = $currentWeek->copy()->addDays($i);
                $previousDay = $previousWeek->copy()->addDays($i);

                $currentWeekData[]  = $this->filterOrders($orders, $currentDay, $currentDay->copy()->endOfDay())->count();
                $previousWeekData[] = $this->filterOrders($orders, $previousDay, $previousDay->copy()->endOfDay())->count();
            }

            return [
                'labels'        => $days,
                'current_week'  => $currentWeekData,
                'previous_week' => $previousWeekData,
            ];
        } catch (\Exception $e) {
            Log::error('Error in getOrdersByDayData: ' . $e->getMessage());
            return ['labels' => [], 'current_week' => [], 'previous_week' => []];
        }
    }

    private function getMonthlyTrendsData(Collection $orders): array
    {
        try {
            $labels     = [];
            $salesData  = [];
            $ordersData = [];

            for ($i = 5; $i >= 0; $i--) {
                $date       = Carbon::now()->subMonths($i)->startOfMonth();
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd   = $date->copy()->endOfMonth();
                $monthOrders = $this->filterOrders($orders, $monthStart, $monthEnd);

                $labels[]     = $date->format('M Y');
                $salesData[]  = round((float) $monthOrders->sum('order_total'), 2);
                $ordersData[] = $monthOrders->count();
            }

            return ['labels' => $labels, 'sales' => $salesData, 'orders' => $ordersData];
        } catch (\Exception $e) {
            Log::error('Error in getMonthlyTrendsData: ' . $e->getMessage());
            return ['labels' => [], 'sales' => [], 'orders' => []];
        }
    }

    private function getCategoryBreakdownData(Collection $orderItems): array
    {
        try {
            $last30Days = Carbon::now()->subDays(30);

            $categoryData = $this->filterOrderItems($orderItems, $last30Days)
                ->groupBy(function (OrderItem $orderItem) {
                    return $orderItem->product?->category ?? 'other';
                })
                ->map(function (Collection $group, string $category) {
                    return [
                        'category' => $category,
                        'sales'    => round((float) $group->sum('total_price'), 2),
                    ];
                })
                ->sortByDesc('sales')
                ->values();

            $labels    = [];
            $salesData = [];
            $colors    = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
            ];

            foreach ($categoryData as $category) {
                $labels[]    = $this->formatCategoryName($category['category'] ?? null);
                $salesData[] = (float) $category['sales'];
            }

            if (empty($labels)) {
                return ['labels' => [], 'data' => [], 'colors' => []];
            }

            return [
                'labels' => $labels,
                'data'   => $salesData,
                'colors' => array_slice($colors, 0, count($labels)),
            ];
        } catch (\Exception $e) {
            Log::error('Error in getCategoryBreakdownData: ' . $e->getMessage());
            return ['labels' => [], 'data' => [], 'colors' => []];
        }
    }

    private function getBusinessInsights(
        Collection $orders,
        Collection $orderItems,
        Collection $products,
        Collection $inventoryMovements
    ): array {
        $last30Days          = Carbon::now()->subDays(29)->startOfDay();
        $previousPeriodStart = Carbon::now()->subDays(59)->startOfDay();
        $previousPeriodEnd   = Carbon::now()->subDays(30)->endOfDay();
        $today               = Carbon::today();
        $todayEnd            = $today->copy()->endOfDay();

        $monthOrders         = $this->filterOrders($orders, $last30Days);
        $previousPeriodOrders = $this->filterOrders($orders, $previousPeriodStart, $previousPeriodEnd);
        $monthItems          = $this->filterOrderItems($orderItems, $last30Days);
        $previousItems       = $this->filterOrderItems($orderItems, $previousPeriodStart, $previousPeriodEnd);
        $monthNoCharge       = $this->summarizeNoChargeItems($orderItems, $last30Days);

        $monthSales           = round((float) $monthOrders->sum('order_total'), 2);
        $previousPeriodSales  = round((float) $previousPeriodOrders->sum('order_total'), 2);
        $monthCost            = $this->getItemsCost($monthItems);
        $monthProfit          = $this->getItemsProfit($monthItems);
        $previousProfit       = $this->getItemsProfit($previousItems);
        $monthProfitMarginPct = $monthSales > 0 ? round(($monthProfit / $monthSales) * 100, 1) : 0;

        // Discounts
        $monthItemDisc      = $this->getItemDiscounts($monthItems);
        $monthOrderDisc     = $this->getOrderLevelDiscounts($monthOrders);
        $monthTotalDisc     = round($monthItemDisc + $monthOrderDisc, 2);
        $discountedOrderCnt = $this->countDiscountedOrders($monthOrders, $monthItems);
        $monthOrdersCount   = $monthOrders->count();
        $discountRate       = $monthOrdersCount > 0 ? round(($discountedOrderCnt / $monthOrdersCount) * 100, 1) : 0;
        $avgDiscPerOrder    = $discountedOrderCnt > 0 ? round($monthTotalDisc / $discountedOrderCnt, 2) : 0;
        // Revenue lost to discounts as a % of pre-discount potential revenue
        $potentialRevenue   = $monthSales + $monthTotalDisc;
        $discountImpactPct  = $potentialRevenue > 0 ? round(($monthTotalDisc / $potentialRevenue) * 100, 1) : 0;

        $completedOrders    = $monthOrders->whereIn('status', ['delivered', 'completed'])->count();
        $paidOrders         = $monthOrders->where('payment_status', 'paid')->count();
        $refundedOrders     = $monthOrders->where('payment_status', 'refunded')->count();
        $pendingOrders      = $monthOrders->whereIn('status', ['pending', 'preparing'])->count();
        $previousPendingOrders = $previousPeriodOrders->whereIn('status', ['pending', 'preparing'])->count();

        $activeCustomers         = $monthOrders->pluck('customer_id')->filter()->unique()->count();
        $previousActiveCustomers = $previousPeriodOrders->pluck('customer_id')->filter()->unique()->count();

        $unitsSold          = (int) $monthItems->sum('quantity');
        $activeProducts     = $products->filter(fn (Product $p) => (int) $p->stocks > 0)->count();
        $lowStockProducts   = $products->filter(fn (Product $p) => (int) $p->stocks > 0 && (int) $p->stocks < 10)->count();
        $outOfStockProducts = $products->filter(fn (Product $p) => (int) $p->stocks <= 0)->count();
        $totalProducts      = max(1, $products->count());

        $growthPercent = function (float|int $current, float|int $previous): float {
            if ($previous > 0) {
                return round((($current - $previous) / $previous) * 100, 1);
            }
            return $current > 0 ? 100.0 : 0.0;
        };

        // Inventory movement analytics
        $todayMovements = $inventoryMovements->filter(function (InventoryMovement $m) use ($today, $todayEnd) {
            return $this->dateInRange($m->created_at, $today, $todayEnd);
        })->values();

        $monthMovements = $inventoryMovements->filter(function (InventoryMovement $m) use ($last30Days) {
            return $this->dateInRange($m->created_at, $last30Days);
        })->values();

        $inventoryOutToday = (int) $todayMovements
            ->filter(fn (InventoryMovement $m) => (int) $m->after_stocks < (int) $m->before_stocks)
            ->sum('quantity');

        $inventoryInToday = (int) $todayMovements
            ->filter(fn (InventoryMovement $m) => (int) $m->after_stocks > (int) $m->before_stocks)
            ->sum('quantity');

        $topMovedProduct = $monthMovements
            ->groupBy('product_id')
            ->map(function (Collection $group) {
                return [
                    'name'  => $group->first()?->product?->name ?? __('Unknown product'),
                    'units' => (int) $group->sum('quantity'),
                ];
            })
            ->sortByDesc('units')
            ->first();

        $categorySummary = $monthItems
            ->groupBy(fn (OrderItem $i) => $i->product?->category ?? 'other')
            ->map(function (Collection $group, string $category) {
                return [
                    'category' => $category,
                    'label'    => $this->formatCategoryName($category),
                    'sales'    => round((float) $group->sum('total_price'), 2),
                ];
            })
            ->sortByDesc('sales')
            ->first();

        $topProduct = $this->summarizeMonthTopProducts($orderItems, 1)[0] ?? null;

        return [
            // Revenue & cost
            'month_sales'              => $monthSales,
            'month_sales_growth'       => $growthPercent($monthSales, $previousPeriodSales),
            'month_cost'               => $monthCost,
            'month_profit'             => $monthProfit,
            'month_profit_growth'      => $growthPercent($monthProfit, $previousProfit),
            'month_profit_margin_pct'  => $monthProfitMarginPct,

            // Discount analytics
            'month_item_discounts'     => $monthItemDisc,
            'month_order_discounts'    => $monthOrderDisc,
            'month_total_discounts'    => $monthTotalDisc,
            'discounted_orders'        => $discountedOrderCnt,
            'discount_rate'            => $discountRate,
            'avg_discount_per_order'   => $avgDiscPerOrder,
            'discount_impact_pct'      => $discountImpactPct,

            // Order metrics
            'month_orders'             => $monthOrdersCount,
            'pending_orders'           => $pendingOrders,
            'pending_orders_growth'    => $growthPercent($pendingOrders, $previousPendingOrders),
            'completion_rate'          => $monthOrdersCount > 0 ? round(($completedOrders / $monthOrdersCount) * 100, 1) : 0,
            'payment_rate'             => $monthOrdersCount > 0 ? round(($paidOrders / $monthOrdersCount) * 100, 1) : 0,
            'refund_rate'              => $monthOrdersCount > 0 ? round(($refundedOrders / $monthOrdersCount) * 100, 1) : 0,

            // Customer
            'active_customers'         => $activeCustomers,
            'active_customers_growth'  => $growthPercent($activeCustomers, $previousActiveCustomers),

            // Products & averages
            'month_units_sold'         => $unitsSold,
            'average_daily_sales'      => round($monthSales / 30, 2),
            'average_order_value'      => $monthOrdersCount > 0 ? round($monthSales / $monthOrdersCount, 2) : 0,
            'active_products'          => $activeProducts,
            'low_stock_products'       => $lowStockProducts,
            'low_stock_rate'           => round(($lowStockProducts / $totalProducts) * 100, 1),
            'out_of_stock_products'    => $outOfStockProducts,
            'out_of_stock_rate'        => round(($outOfStockProducts / $totalProducts) * 100, 1),

            // Free / no-charge
            'free_items'               => $monthNoCharge['items'],
            'free_units'               => $monthNoCharge['units'],
            'free_orders'              => $monthNoCharge['orders'],
            'free_order_rate'          => $monthOrdersCount > 0 ? round(($monthNoCharge['orders'] / $monthOrdersCount) * 100, 1) : 0,

            // Top performers
            'top_category'             => $categorySummary['label'] ?? __('No category data'),
            'top_category_sales'       => $categorySummary['sales'] ?? 0,
            'top_category_key'         => $categorySummary['category'] ?? null,
            'top_product_name'         => $topProduct['name'] ?? __('No product data'),
            'top_product_sales'        => $topProduct['total_sold'] ?? 0,

            // Inventory
            'inventory_out_today'             => $inventoryOutToday,
            'inventory_in_today'              => $inventoryInToday,
            'inventory_net_today'             => $inventoryInToday - $inventoryOutToday,
            'inventory_events_today'          => $todayMovements->count(),
            'inventory_top_product_name'      => $topMovedProduct['name'] ?? __('No product data'),
            'inventory_top_product_units'     => $topMovedProduct['units'] ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.main.dashboard');
    }
}
