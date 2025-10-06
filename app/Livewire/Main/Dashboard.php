<?php

namespace App\Livewire\Main;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
    public $todayStats;
    public $topSellingProducts;
    public $salesVsProfitData;
    public $ordersByDayData;
    public $monthlyTrendsData;
    public $categoryBreakdownData;

    public function mount()
    {
        $this->todayStats = $this->getTodayStats();
        $this->topSellingProducts = $this->getTopSellingProducts();
        $this->salesVsProfitData = $this->getSalesVsProfitData();
        $this->ordersByDayData = $this->getOrdersByDayData();
        $this->monthlyTrendsData = $this->getMonthlyTrendsData();
        $this->categoryBreakdownData = $this->getCategoryBreakdownData();

        // Dispatch data to the browser so JS can render charts after Livewire mounts
        $this->dispatch('dashboard-charts-data', data: [
            'salesVsProfitData' => $this->salesVsProfitData,
            'ordersByDayData' => $this->ordersByDayData,
            'monthlyTrendsData' => $this->monthlyTrendsData,
            'categoryBreakdownData' => $this->categoryBreakdownData,
        ]);
    }

    private function getTodayStats()
    {
        $today = Carbon::today();
        
        $todaySales = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->sum('order_total');

        $totalOrdersToday = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $avgOrderValue = $totalOrdersToday > 0 ? $todaySales / $totalOrdersToday : 0;

        $pendingOrders = Order::whereDate('created_at', $today)
            ->whereIn('status', ['pending', 'preparing', 'in_transit'])
            ->count();

        // Compare with yesterday
        $yesterday = Carbon::yesterday();
        $yesterdaySales = Order::whereDate('created_at', $yesterday)
            ->where('status', '!=', 'cancelled')
            ->sum('order_total');

        $salesGrowth = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;

        return [
            'sales' => floatval($todaySales), // Keep raw for calculations
            'orders' => intval($totalOrdersToday),
            'avg_order' => floatval($avgOrderValue),
            'pending' => intval($pendingOrders),
            'sales_growth' => round($salesGrowth, 1),
            'raw_sales' => floatval($todaySales) // Keep this for backward compatibility
        ];
    }

    private function getTopSellingProducts()
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        
        // Top selling product today
        $topToday = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.created_at', $today)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->first();

        // Top selling product this week
        $topWeek = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $thisWeek)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->first();

        // Top 5 products by average weekly sales
        $topAvg = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', Carbon::now()->subWeeks(4))
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('ROUND(SUM(order_items.quantity) / 4, 1) as avg_weekly')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return [
            'today' => $topToday,
            'week' => $topWeek,
            'average' => $topAvg
        ];
    }

    private function getSalesVsProfitData()
    {
        try {
            $last30Days = Carbon::now()->subDays(29); // 30 days including today
            
            $dailyData = Order::where('created_at', '>=', $last30Days)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('DATE(created_at) as date, SUM(order_total) as sales, COUNT(*) as orders')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $labels = [];
            $salesData = [];
            $ordersData = [];
            $profitData = [];

            // Fill in missing days with zero values
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $dayData = $dailyData->firstWhere('date', $date);
                
                $labels[] = Carbon::parse($date)->format('M d');
                $salesValue = $dayData ? floatval($dayData->sales) : 0;
                $ordersValue = $dayData ? intval($dayData->orders) : 0;
                
                $salesData[] = $salesValue;
                $ordersData[] = $ordersValue;
                // Assuming 25% profit margin
                $profitData[] = round($salesValue * 0.25, 2);
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

    private function getOrdersByDayData()
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

                $currentWeekData[] = Order::whereDate('created_at', $currentDay)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                $previousWeekData[] = Order::whereDate('created_at', $previousDay)
                    ->where('status', '!=', 'cancelled')
                    ->count();
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

    private function getMonthlyTrendsData()
    {
        try {
            $last6Months = Carbon::now()->subMonths(5)->startOfMonth(); // 6 months including current
            
            $monthlyData = Order::where('created_at', '>=', $last6Months)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(order_total) as sales, COUNT(*) as orders')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            $labels = [];
            $salesData = [];
            $ordersData = [];

            // Fill in the last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthData = $monthlyData->where('year', $date->year)->where('month', $date->month)->first();
                
                $labels[] = $date->format('M Y');
                $salesData[] = $monthData ? floatval($monthData->sales) : 0;
                $ordersData[] = $monthData ? intval($monthData->orders) : 0;
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

    private function getCategoryBreakdownData()
    {
        try {
            $last30Days = Carbon::now()->subDays(30);
            
            $categoryData = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.created_at', '>=', $last30Days)
                ->where('orders.status', '!=', 'cancelled')
                ->selectRaw('products.category, SUM(order_items.total_price) as sales')
                ->whereNotNull('products.category') // Ensure category exists
                ->groupBy('products.category')
                ->orderBy('sales', 'desc')
                ->get();

            $labels = [];
            $salesData = [];
            $colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ];

            foreach ($categoryData as $index => $category) {
                // Check if Product model has getCategories method, otherwise use raw category
                if (method_exists(Product::class, 'getCategories')) {
                    $categoryNames = Product::getCategories();
                    $labels[] = $categoryNames[$category->category] ?? ucfirst(str_replace('_', ' ', $category->category));
                } else {
                    $labels[] = ucfirst(str_replace('_', ' ', $category->category));
                }
                $salesData[] = floatval($category->sales);
            }

            // If no data, provide empty arrays
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

    public function render()
    {
        return view('livewire.main.dashboard');
    }
}