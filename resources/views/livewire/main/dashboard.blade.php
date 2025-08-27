@section('title', 'Dashboard')
<div class="container mx-auto p-1">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                <i class="fas fa-chart-bar mr-2"></i>Dashboard
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Welcome back! Here's what's happening today.</p>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today's Sales -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Today's Sales</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($todayStats['sales'] ?? 0, 2) }}</p>
                    @if(isset($todayStats['sales_growth']) && $todayStats['sales_growth'] != 0)
                        <p class="text-sm {{ $todayStats['sales_growth'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $todayStats['sales_growth'] > 0 ? '+' : '' }}{{ number_format($todayStats['sales_growth'], 1) }}% from yesterday
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
                    <i class="fas fa-dollar-sign w-6 h-6 text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Total Orders Today -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Orders Today</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['orders'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Avg: ₱{{ number_format($todayStats['avg_order'] ?? 0, 2) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                    <i class="fas fa-shopping-cart w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Pending Orders</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['pending'] ?? 0 }}</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Needs attention</p>
                </div>
                <div class="p-3 bg-amber-100 dark:bg-amber-900/20 rounded-full">
                    <i class="fas fa-clock w-6 h-6 text-amber-600 dark:text-amber-400"></i>
                </div>
            </div>
        </div>

        <!-- Top Product Today -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Top Product Today</p>
                    @if(isset($topSellingProducts['today']) && $topSellingProducts['today'])
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ Str::limit($topSellingProducts['today']->name, 20) }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['today']->total_sold }} sold</p>
                    @else
                        <p class="text-lg font-medium text-zinc-500 dark:text-zinc-400">No sales yet</p>
                    @endif
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
                    <i class="fas fa-star w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Selling Products Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Today's Top Product -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-trophy mr-2 text-yellow-500"></i>Today's Best Seller
            </h3>
            @if(isset($topSellingProducts['today']) && $topSellingProducts['today'])
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-trophy text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $topSellingProducts['today']->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['today']->total_sold }} units sold</p>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-chart-line text-4xl text-zinc-300 dark:text-zinc-600 mb-3"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">No sales recorded today</p>
                </div>
            @endif
        </div>

        <!-- This Week's Top Product -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>This Week's Top Seller
            </h3>
            @if(isset($topSellingProducts['week']) && $topSellingProducts['week'])
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $topSellingProducts['week']->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['week']->total_sold }} units this week</p>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-chart-line text-4xl text-zinc-300 dark:text-zinc-600 mb-3"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">No sales recorded this week</p>
                </div>
            @endif
        </div>

        <!-- Average Top Performers -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-medal mr-2 text-purple-500"></i>Top Sellers (4-week avg)
            </h3>
            @if(isset($topSellingProducts['average']) && $topSellingProducts['average']->count() > 0)
                <div class="space-y-3">
                    @foreach($topSellingProducts['average']->take(3) as $index => $product)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 bg-zinc-100 dark:bg-zinc-700 rounded-full flex items-center justify-center text-xs font-medium">{{ $index + 1 }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ Str::limit($product->name, 15) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 block">{{ $product->avg_weekly ?? 0 }}/week</span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $product->total_sold ?? 0 }} total</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-chart-line text-4xl text-zinc-300 dark:text-zinc-600 mb-3"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">No data available</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Sales vs Profit Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-chart-line mr-2 text-blue-500"></i>Sales vs Profit (Last 30 Days)
            </h3>
            <div class="h-80">
                <canvas id="salesVsProfitChart"></canvas>
            </div>
        </div>

        <!-- Orders by Day Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-calendar-alt mr-2 text-green-500"></i>Orders by Day (Current vs Previous Week)
            </h3>
            <div class="h-80">
                <canvas id="ordersByDayChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Trends -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-chart-area mr-2 text-purple-500"></i>Monthly Trends (Last 6 Months)
            </h3>
            <div class="h-80">
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-chart-pie mr-2 text-orange-500"></i>Sales by Category (Last 30 Days)
            </h3>
            <div class="h-80">
                <canvas id="categoryBreakdownChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded. Please include Chart.js library.');
                return;
            }

            // Chart configuration for dark mode
            Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#a1a1aa' : '#71717a';
            Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#3f3f46' : '#e4e4e7';

            // Check if data exists before creating charts
            const salesVsProfitData = @json($salesVsProfitData) || {labels: [], sales: [], profit: [], orders: []};
            const ordersByDayData = @json($ordersByDayData) || {labels: [], current_week: [], previous_week: []};
            const monthlyTrendsData = @json($monthlyTrendsData) || {labels: [], sales: [], orders: []};
            const categoryBreakdownData = @json($categoryBreakdownData) || {labels: [], data: [], colors: []};

            // Sales vs Profit Chart
            const salesVsProfitCtx = document.getElementById('salesVsProfitChart');
            if (salesVsProfitCtx && salesVsProfitData.labels.length > 0) {
                new Chart(salesVsProfitCtx, {
                    type: 'line',
                    data: {
                        labels: salesVsProfitData.labels,
                        datasets: [{
                            label: 'Sales (₱)',
                            data: salesVsProfitData.sales,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Estimated Profit (₱)',
                            data: salesVsProfitData.profit,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Orders',
                            data: salesVsProfitData.orders,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Amount (₱)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Number of Orders'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    }
                });
            }

            // Orders by Day Chart
            const ordersByDayCtx = document.getElementById('ordersByDayChart');
            if (ordersByDayCtx && ordersByDayData.labels.length > 0) {
                new Chart(ordersByDayCtx, {
                    type: 'bar',
                    data: {
                        labels: ordersByDayData.labels,
                        datasets: [{
                            label: 'Current Week',
                            data: ordersByDayData.current_week,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: '#3b82f6',
                            borderWidth: 1
                        }, {
                            label: 'Previous Week',
                            data: ordersByDayData.previous_week,
                            backgroundColor: 'rgba(156, 163, 175, 0.8)',
                            borderColor: '#9ca3af',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Orders'
                                }
                            }
                        }
                    }
                });
            }

            // Monthly Trends Chart
            const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart');
            if (monthlyTrendsCtx && monthlyTrendsData.labels.length > 0) {
                new Chart(monthlyTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyTrendsData.labels,
                        datasets: [{
                            label: 'Monthly Sales (₱)',
                            data: monthlyTrendsData.sales,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Monthly Orders',
                            data: monthlyTrendsData.orders,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Sales Amount (₱)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Number of Orders'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    }
                });
            }

            // Category Breakdown Chart
            const categoryBreakdownCtx = document.getElementById('categoryBreakdownChart');
            if (categoryBreakdownCtx && categoryBreakdownData.labels.length > 0) {
                new Chart(categoryBreakdownCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryBreakdownData.labels,
                        datasets: [{
                            data: categoryBreakdownData.data,
                            backgroundColor: categoryBreakdownData.colors,
                            borderWidth: 2,
                            borderColor: document.documentElement.classList.contains('dark') ? '#3f3f46' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }
        });
    </script>

</div>