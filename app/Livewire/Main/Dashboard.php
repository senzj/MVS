<?php

namespace App\Livewire\Main;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    private const ESTIMATED_PROFIT_MARGIN = 0.25;

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
        $orderItems = OrderItem::all();
        $orderItems->load(['order', 'product']);
        $products = Product::all();

        $this->todayStats = $this->getTodayStats($orders, $orderItems);
        $this->topSellingProducts = $this->getTopSellingProducts($orderItems);
        $this->salesVsProfitData = $this->getSalesVsProfitData($orders);
        $this->ordersByDayData = $this->getOrdersByDayData($orders);
        $this->monthlyTrendsData = $this->getMonthlyTrendsData($orders);
        $this->categoryBreakdownData = $this->getCategoryBreakdownData($orderItems);
        $this->businessInsights = $this->getBusinessInsights($orders, $orderItems, $products);

        // Dispatch data to the browser so JS can render charts after Livewire mounts
        $this->dispatch('dashboard-charts-data', data: [
            'salesVsProfitData' => $this->salesVsProfitData,
            'ordersByDayData' => $this->ordersByDayData,
            'monthlyTrendsData' => $this->monthlyTrendsData,
            'categoryBreakdownData' => $this->categoryBreakdownData,
            'businessInsights' => $this->businessInsights,
        ]);
    }

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
            return $order->status !== 'cancelled' && $this->dateInRange($order->created_at, $start, $end);
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
            'items' => $freeItems->count(),
            'units' => (int) $freeItems->sum('quantity'),
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
                $firstItem = $group->first();
                $product = $firstItem?->product;
                $totalSold = (int) $group->sum('quantity');
                $totalRevenue = round((float) $group->sum('total_price'), 2);

                return [
                    'product_id' => $firstItem?->product_id,
                    'name' => $product?->name ?? __('Unknown product'),
                    'category' => $product?->category,
                    'category_label' => $this->formatCategoryName($product?->category),
                    'total_sold' => $totalSold,
                    'total_revenue' => $totalRevenue,
                    'avg_price' => $totalSold > 0 ? round($totalRevenue / $totalSold, 2) : 0,
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
                $firstItem = $group->first();
                $product = $firstItem?->product;
                $totalSold = (int) $group->sum('quantity');
                $totalRevenue = round((float) $group->sum('total_price'), 2);

                return [
                    'product_id' => $firstItem?->product_id,
                    'name' => $product?->name ?? __('Unknown product'),
                    'category' => $product?->category,
                    'category_label' => $this->formatCategoryName($product?->category),
                    'total_sold' => $totalSold,
                    'total_revenue' => $totalRevenue,
                    'avg_weekly' => round($totalSold / 4, 1),
                ];
            })
            ->sortByDesc('total_sold')
            ->values()
            ->take($limit)
            ->all();
    }

    private function getTodayStats(Collection $orders, Collection $orderItems): array
    {
        $today = Carbon::today();
        $todayEnd = $today->copy()->endOfDay();
        $yesterday = Carbon::yesterday();
        $yesterdayEnd = $yesterday->copy()->endOfDay();

        $todayOrders = $this->filterOrders($orders, $today, $todayEnd);
        $todayItems = $this->filterOrderItems($orderItems, $today, $todayEnd);
        $todayNoCharge = $this->summarizeNoChargeItems($orderItems, $today, $todayEnd);
        $yesterdayOrders = $this->filterOrders($orders, $yesterday, $yesterdayEnd);

        $todaySales = round((float) $todayOrders->sum('order_total'), 2);
        $todayOrderCount = $todayOrders->count();
        $yesterdaySales = round((float) $yesterdayOrders->sum('order_total'), 2);
        $salesGrowth = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;
        $profit = round($todaySales * self::ESTIMATED_PROFIT_MARGIN, 2);

        return [
            'sales' => $todaySales,
            'income' => $todaySales,
            'profit' => $profit,
            'orders' => $todayOrderCount,
            'avg_order' => $todayOrderCount > 0 ? round($todaySales / $todayOrderCount, 2) : 0,
            'pending' => $todayOrders->whereIn('status', ['pending', 'preparing', 'in_transit'])->count(),
            'completed' => $todayOrders->whereIn('status', ['delivered', 'completed'])->count(),
            'paid' => $todayOrders->where('is_paid', true)->count(),
            'unpaid' => $orders->filter(fn (Order $order) => ! $order->is_paid && ! in_array($order->status, ['completed', 'cancelled'], true))->count(),
            'units_sold' => (int) $todayItems->sum('quantity'),
            'free_items' => $todayNoCharge['items'],
            'free_units' => $todayNoCharge['units'],
            'free_orders' => $todayNoCharge['orders'],
            'sales_growth' => round($salesGrowth, 1),
            'profit_margin' => self::ESTIMATED_PROFIT_MARGIN * 100,
            'raw_sales' => $todaySales,
        ];
    }

    private function getTopSellingProducts(Collection $orderItems): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();

        $topToday = $this->summarizeTopProducts($orderItems, $today, $today->copy()->endOfDay(), 5);
        $topWeek = $this->summarizeTopProducts($orderItems, $thisWeek, null, 5);
        $topAverage = $this->summarizeMonthTopProducts($orderItems, 5);

        return [
            'today' => $topToday,
            'week' => $topWeek,
            'average' => $topAverage,
        ];
    }

    private function getSalesVsProfitData(Collection $orders): array
    {
        try {
            $last30Days = Carbon::now()->subDays(29)->startOfDay();

            $labels = [];
            $salesData = [];
            $ordersData = [];
            $profitData = [];

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->startOfDay();
                $dayOrders = $this->filterOrders($orders, $date, $date->copy()->endOfDay());
                $salesValue = round((float) $dayOrders->sum('order_total'), 2);
                $ordersValue = $dayOrders->count();

                $labels[] = $date->format('M d');
                $salesData[] = $salesValue;
                $ordersData[] = $ordersValue;
                $profitData[] = round($salesValue * self::ESTIMATED_PROFIT_MARGIN, 2);
            }

            return [
                'labels' => $labels,
                'sales' => $salesData,
                'orders' => $ordersData,
                'profit' => $profitData
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSalesVsProfitData: ' . $e->getMessage());
            return [
                'labels' => [],
                'sales' => [],
                'orders' => [],
                'profit' => []
            ];
        }
    }

    private function getOrdersByDayData(Collection $orders): array
    {
        try {
            $currentWeek = Carbon::now()->startOfWeek();
            $previousWeek = Carbon::now()->subWeek()->startOfWeek();

            $currentWeekData = [];
            $previousWeekData = [];
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; // Shorter labels for charts

            for ($i = 0; $i < 7; $i++) {
                $currentDay = $currentWeek->copy()->addDays($i);
                $previousDay = $previousWeek->copy()->addDays($i);

                $currentWeekData[] = $this->filterOrders($orders, $currentDay, $currentDay->copy()->endOfDay())->count();
                $previousWeekData[] = $this->filterOrders($orders, $previousDay, $previousDay->copy()->endOfDay())->count();
            }

            return [
                'labels' => $days,
                'current_week' => $currentWeekData,
                'previous_week' => $previousWeekData
            ];
        } catch (\Exception $e) {
            Log::error('Error in getOrdersByDayData: ' . $e->getMessage());
            return [
                'labels' => [],
                'current_week' => [],
                'previous_week' => []
            ];
        }
    }

    private function getMonthlyTrendsData(Collection $orders): array
    {
        try {
            $last6Months = Carbon::now()->subMonths(5)->startOfMonth();

            $labels = [];
            $salesData = [];
            $ordersData = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i)->startOfMonth();
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();
                $monthOrders = $this->filterOrders($orders, $monthStart, $monthEnd);

                $labels[] = $date->format('M Y');
                $salesData[] = round((float) $monthOrders->sum('order_total'), 2);
                $ordersData[] = $monthOrders->count();
            }

            return [
                'labels' => $labels,
                'sales' => $salesData,
                'orders' => $ordersData
            ];
        } catch (\Exception $e) {
            Log::error('Error in getMonthlyTrendsData: ' . $e->getMessage());
            return [
                'labels' => [],
                'sales' => [],
                'orders' => []
            ];
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
                        'sales' => round((float) $group->sum('total_price'), 2),
                    ];
                })
                ->sortByDesc('sales')
                ->values();

            $labels = [];
            $salesData = [];
            $colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ];

            foreach ($categoryData as $index => $category) {
                $labels[] = $this->formatCategoryName($category['category'] ?? null);
                $salesData[] = (float) $category['sales'];
            }

            if (empty($labels)) {
                return [
                    'labels' => [],
                    'data' => [],
                    'colors' => []
                ];
            }

            return [
                'labels' => $labels,
                'data' => $salesData,
                'colors' => array_slice($colors, 0, count($labels))
            ];
        } catch (\Exception $e) {
            Log::error('Error in getCategoryBreakdownData: ' . $e->getMessage());
            return [
                'labels' => [],
                'data' => [],
                'colors' => []
            ];
        }
    }

    private function getBusinessInsights(Collection $orders, Collection $orderItems, Collection $products): array
    {
        $last30Days = Carbon::now()->subDays(29)->startOfDay();
        $monthOrders = $this->filterOrders($orders, $last30Days);
        $monthItems = $this->filterOrderItems($orderItems, $last30Days);
        $monthNoCharge = $this->summarizeNoChargeItems($orderItems, $last30Days);
        $monthSales = round((float) $monthOrders->sum('order_total'), 2);
        $monthProfit = round($monthSales * self::ESTIMATED_PROFIT_MARGIN, 2);
        $monthOrdersCount = $monthOrders->count();
        $completedOrders = $monthOrders->whereIn('status', ['delivered', 'completed'])->count();
        $paidOrders = $monthOrders->where('is_paid', true)->count();
        $unitsSold = (int) $monthItems->sum('quantity');
        $activeProducts = $products->filter(fn (Product $product) => (int) $product->stocks > 0)->count();
        $lowStockProducts = $products->filter(fn (Product $product) => (int) $product->stocks > 0 && (int) $product->stocks < 10)->count();
        $outOfStockProducts = $products->filter(fn (Product $product) => (int) $product->stocks <= 0)->count();

        $categorySummary = $monthItems
            ->groupBy(function (OrderItem $orderItem) {
                return $orderItem->product?->category ?? 'other';
            })
            ->map(function (Collection $group, string $category) {
                return [
                    'category' => $category,
                    'label' => $this->formatCategoryName($category),
                    'sales' => round((float) $group->sum('total_price'), 2),
                ];
            })
            ->sortByDesc('sales')
            ->first();

        $topProduct = $this->summarizeMonthTopProducts($orderItems, 1)[0] ?? null;

        return [
            'month_sales' => $monthSales,
            'month_profit' => $monthProfit,
            'month_orders' => $monthOrdersCount,
            'month_units_sold' => $unitsSold,
            'free_items' => $monthNoCharge['items'],
            'free_units' => $monthNoCharge['units'],
            'free_orders' => $monthNoCharge['orders'],
            'free_order_rate' => $monthOrdersCount > 0 ? round(($monthNoCharge['orders'] / $monthOrdersCount) * 100, 1) : 0,
            'average_daily_sales' => round($monthSales / 30, 2),
            'average_order_value' => $monthOrdersCount > 0 ? round($monthSales / $monthOrdersCount, 2) : 0,
            'completion_rate' => $monthOrdersCount > 0 ? round(($completedOrders / $monthOrdersCount) * 100, 1) : 0,
            'payment_rate' => $monthOrdersCount > 0 ? round(($paidOrders / $monthOrdersCount) * 100, 1) : 0,
            'active_products' => $activeProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'top_category' => $categorySummary['label'] ?? __('No category data'),
            'top_category_sales' => $categorySummary['sales'] ?? 0,
            'top_category_key' => $categorySummary['category'] ?? null,
            'top_product_name' => $topProduct['name'] ?? __('No product data'),
            'top_product_sales' => $topProduct['total_sold'] ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.main.dashboard');
    }
}
