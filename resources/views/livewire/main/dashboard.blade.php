@section('title', __('Dashboard'))
<div class="container p-1 mx-auto">

    {{-- Header --}}
    <div class="flex flex-col gap-3 mb-6 md:flex-row md:items-center md:justify-between">
        <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 fas fa-chart-bar"></i>{{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __("Welcome back! Here's what's happening today.") }}</p>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400"
             x-data="{
                locale: '{{ app()->getLocale() }}',
                serverTs: {{ now()->timestamp }} * 1000,
                clientStart: Date.now(),
                nowMs: {{ now()->timestamp }} * 1000,
                get intlLocale() { return this.locale === 'zh' ? 'zh-CN' : this.locale; },
                tick() { const elapsed = Date.now() - this.clientStart; this.nowMs = this.serverTs + elapsed; },
                start() { this.tick(); setInterval(() => this.tick(), 1000); },
                get formattedDate() {
                  return new Intl.DateTimeFormat(this.intlLocale, {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                  }).format(this.nowMs);
                },
                get formattedTime() {
                  return new Intl.DateTimeFormat(this.intlLocale, {
                    hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
                  }).format(this.nowMs);
                }
             }"
             x-init="start()">
            <span x-text="formattedDate"></span>
            <span class="mx-1">•</span>
            <span x-text="formattedTime"></span>
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    <div class="grid grid-cols-1 gap-3 mb-8 md:grid-cols-2 xl:grid-cols-4">

        {{-- Today's Revenue --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Today\'s Revenue') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($todayStats['income'] ?? 0, 2) }}</p>
                    @if(isset($todayStats['sales_growth']) && $todayStats['sales_growth'] != 0)
                        <p class="text-sm {{ $todayStats['sales_growth'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $todayStats['sales_growth'] > 0 ? '+' : '' }}{{ number_format($todayStats['sales_growth'], 1) }}% from yesterday
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 rounded-2xl dark:bg-green-900/20">
                    <i class="text-green-600 fas fa-dollar-sign dark:text-green-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>

        {{-- Estimated Profit --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Estimated Profit') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($todayStats['profit'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($todayStats['profit_margin'] ?? 0, 1) }}% {{ __('margin') }}</p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-2xl dark:bg-emerald-900/20">
                    <i class="text-emerald-600 fas fa-coins dark:text-emerald-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>

        {{-- Orders Today --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Orders Today') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['orders'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg') }}: ₱{{ number_format($todayStats['avg_order'] ?? 0, 2) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-2xl dark:bg-blue-900/20">
                    <i class="text-blue-600 fas fa-shopping-cart dark:text-blue-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>

        {{-- Today's Best Seller --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Today\'s Best Seller') }}</p>
                    @php $todayBestSeller = $topSellingProducts['today'][0] ?? null; @endphp
                    @if($todayBestSeller)
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ Str::limit($todayBestSeller['name'] ?? '', 20) }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($todayBestSeller['total_sold'] ?? 0) }} {{ __('units sold') }}</p>
                    @else
                        <p class="text-lg font-medium text-zinc-500 dark:text-zinc-400">{{ __('No sales yet') }}</p>
                    @endif
                </div>
                <div class="p-3 bg-purple-100 rounded-2xl dark:bg-purple-900/20">
                    <i class="text-purple-600 fas fa-star dark:text-purple-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Operational Snapshot --}}
    <div class="grid grid-cols-1 gap-3 mb-8 md:grid-cols-2 xl:grid-cols-4">

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Pending Orders') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['pending'] ?? 0 }}</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">{{ __('Needs attention') }}</p>
                </div>
                <i class="fas fa-clock text-amber-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Completed Orders') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['completed'] ?? 0 }}</p>
                    <p class="text-sm text-green-600 dark:text-green-400">{{ __('Ready / done') }}</p>
                </div>
                <i class="fas fa-circle-check text-green-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Paid Orders') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['paid'] ?? 0 }}</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Cash-in secured') }}</p>
                </div>
                <i class="fas fa-wallet text-emerald-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Unpaid Orders') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['unpaid'] ?? 0 }}</p>
                    <p class="text-sm text-rose-600 dark:text-rose-400">{{ __('Open balance') }}</p>
                </div>
                <i class="fas fa-receipt text-rose-500 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- No Charge Activity --}}
    <div class="grid grid-cols-1 gap-3 mb-8 md:grid-cols-2 xl:grid-cols-4">
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Free Items Today') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['free_items'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Line items given away') }}</p>
                </div>
                <i class="fas fa-gift text-emerald-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Free Units Today') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['free_units'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Units moved with zero charge') }}</p>
                </div>
                <i class="fas fa-box-open text-blue-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Orders with Free Items Today') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['free_orders'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Orders containing no-charge items') }}</p>
                </div>
                <i class="fas fa-bag-shopping text-purple-500 text-xl"></i>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Free Order Share') }}</p>
            <div class="flex items-end justify-between mt-2">
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['free_order_rate'] ?? 0, 1) }}%</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last 30 days') }}</p>
                </div>
                <i class="fas fa-percentage text-amber-500 text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Top Selling Products Section --}}
    <div class="grid grid-cols-1 gap-3 mb-8 xl:grid-cols-3">

        {{-- Today's Best Sellers --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-yellow-500 fas fa-trophy"></i>{{ __('Today\'s Best Seller') }}
            </h3>
            @if(!empty($topSellingProducts['today']))
                <div class="space-y-3">
                    @foreach($topSellingProducts['today'] as $index => $product)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50/80 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <div class="flex items-center space-x-3 min-w-0">
                                <span class="flex items-center justify-center w-7 h-7 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">{{ $index + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ Str::limit($product['name'] ?? '', 18) }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($product['category_label'] ?? __('Uncategorized')) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($product['total_sold'] ?? 0) }} {{ __('sold') }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">₱{{ number_format($product['total_revenue'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <i class="mb-3 text-4xl fas fa-chart-line text-zinc-300 dark:text-zinc-600"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('No sales recorded today') }}</p>
                </div>
            @endif
        </div>

        {{-- This Week's Best Sellers --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-blue-500 fas fa-chart-bar"></i>{{ __('This Week\'s Top Seller') }}
            </h3>

            @if(!empty($topSellingProducts['week']))
                <div class="space-y-3">
                    @foreach($topSellingProducts['week'] as $index => $product)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50/80 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <div class="flex items-center space-x-3 min-w-0">
                                <span class="flex items-center justify-center w-7 h-7 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ $index + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ Str::limit($product['name'] ?? '', 18) }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($product['category_label'] ?? __('Uncategorized')) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($product['total_sold'] ?? 0) }} {{ __('units sold') }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">₱{{ number_format($product['total_revenue'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <i class="mb-3 text-4xl fas fa-chart-line text-zinc-300 dark:text-zinc-600"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('No sales recorded this week') }}</p>
                </div>
            @endif

        </div>

        {{-- Average Top Performers --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-purple-500 fas fa-medal"></i>{{ __("This Month's Top Sellers") }}
            </h3>

            @if(!empty($topSellingProducts['average']))
                <div class="space-y-3">
                    @foreach($topSellingProducts['average'] as $index => $product)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50/80 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <div class="flex items-center space-x-3">
                                <span class="flex items-center justify-center w-6 h-6 text-xs font-medium rounded-full bg-zinc-100 dark:bg-zinc-700">{{ $index + 1 }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ Str::limit($product['name'] ?? '', 15) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($product['avg_weekly'] ?? 0, 1) }}/{{ __('week') }}</span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ number_format($product['total_sold'] ?? 0) }} {{ __('total') }} • ₱{{ number_format($product['total_revenue'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <i class="mb-3 text-4xl fas fa-chart-line text-zinc-300 dark:text-zinc-600"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('No data available') }}</p>
                </div>
            @endif

        </div>
    </div>

    {{-- Business Insights --}}
    <div class="grid grid-cols-1 gap-3 mb-8 xl:grid-cols-2">
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-emerald-500 fas fa-coins"></i>{{ __('Money & Growth Snapshot') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/10">
                    <p class="text-xs uppercase text-emerald-700 dark:text-emerald-300">{{ __('Monthly Revenue') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($businessInsights['month_sales'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last 30 days') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/10">
                    <p class="text-xs uppercase text-amber-700 dark:text-amber-300">{{ __('Estimated Profit') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($businessInsights['month_profit'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Based on a 25% margin') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10">
                    <p class="text-xs uppercase text-blue-700 dark:text-blue-300">{{ __('Average Order Value') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($businessInsights['average_order_value'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Per completed order') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-violet-50 dark:bg-violet-900/10">
                    <p class="text-xs uppercase text-violet-700 dark:text-violet-300">{{ __('Average Daily Sales') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($businessInsights['average_daily_sales'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('30-day run rate') }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-orange-500 fas fa-box-open"></i>{{ __('Product Health & Operations') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Products Sold') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['month_units_sold'] ?? 0) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Units moved in the last 30 days') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Active Products') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['active_products'] ?? 0) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Currently in stock') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Low Stock Items') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['low_stock_products'] ?? 0) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Need restocking soon') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Order Completion Rate') }}
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['completion_rate'] ?? 0, 1) }}%</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Delivered and completed orders') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 mb-8 xl:grid-cols-3">
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 xl:col-span-2">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-indigo-500 fas fa-lightbulb"></i>{{ __('Insights') }}
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Best Category') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Highest sales by category in the last 30 days') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __($businessInsights['top_category'] ?? __('No data')) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">₱{{ number_format($businessInsights['top_category_sales'] ?? 0, 2) }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Best Product This Month') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Most units sold in the last 30 days') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ Str::limit($businessInsights['top_product_name'] ?? 'No data', 26) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($businessInsights['top_product_sales'] ?? 0) }} {{ __('units') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Payment Rate') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Paid orders share over the last 30 days') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['payment_rate'] ?? 0, 1) }}%</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Cash flow health') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-red-500 fas fa-triangle-exclamation"></i>{{ __('Stock Watch') }}
            </h3>
            <div class="space-y-4">
                <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/10">
                    <p class="text-xs uppercase text-red-700 dark:text-red-300">{{ __('Low Stock') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['low_stock_products'] ?? 0) }}</p>
                </div>
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Out of Stock') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($businessInsights['out_of_stock_products'] ?? 0) }}</p>
                </div>
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10">
                    <p class="text-xs uppercase text-blue-700 dark:text-blue-300">{{ __('Units Sold') }}</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($todayStats['units_sold'] ?? 0) }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 gap-3 mb-8 lg:grid-cols-2">

        {{-- Sales vs Profit Chart --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-blue-500 fas fa-chart-line"></i>{{ __('Sales & Profit (Last 30 Days)') }}
            </h3>
            <div class="h-80" wire:ignore>
                <canvas id="salesVsProfitChart"></canvas>
            </div>
        </div>

        {{-- Orders by Day Chart --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-green-500 fas fa-calendar-alt"></i>{{ __('Orders by Day (Current vs Previous Week)') }}
            </h3>
            <div class="h-80" wire:ignore>
                <canvas id="ordersByDayChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Statistical Charts --}}
    <div class="grid grid-cols-1 gap-3 mb-8 lg:grid-cols-2">

        {{-- Monthly Trends --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-purple-500 fas fa-chart-area"></i>{{ __('Monthly Trends (Last 6 Months)') }}
            </h3>
            <div class="h-80" wire:ignore>
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>

        {{-- Category Breakdown --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-orange-500 fas fa-chart-pie"></i>{{ __('Sales by Category (Last 30 Days)') }}
            </h3>
            <div class="h-80" wire:ignore>
                <canvas id="categoryBreakdownChart"></canvas>
            </div>
        </div>
    </div>

{{-- Add chart labels for JS --}}
<script>
  window.__dashboardI18n = {
      sales:                 "{{ __('Sales (₱)') }}",
      estimated_profit:      "{{ __('Estimated Profit (₱)') }}",
      orders:                "{{ __('Orders') }}",
      current_week:          "{{ __('Current Week') }}",
      previous_week:         "{{ __('Previous Week') }}",
      monthly_sales:         "{{ __('Monthly Sales (₱)') }}",
      monthly_orders:        "{{ __('Monthly Orders') }}",
      amount_currency:       "{{ __('Amount (₱)') }}",
      num_orders:            "{{ __('Number of Orders') }}",
      sales_amount_currency: "{{ __('Sales Amount (₱)') }}",
  };

  // Pass current locale to JavaScript
  window.__appLocale = "{{ app()->getLocale() }}";

  // Known category translations (fallback if backend didn't localize)
  window.__categoryMap = {
      "Meat & Poultry": "{{ __('Meat & Poultry') }}",
      "Vegetables": "{{ __('Vegetables') }}",
      "Fruits": "{{ __('Fruits') }}",
      "Dairy": "{{ __('Dairy') }}",
      "Eggs": "{{ __('Eggs') }}",
      "Seafood": "{{ __('Seafood') }}",
      "Beverages": "{{ __('Beverages') }}",
      "Snacks": "{{ __('Snacks') }}",
      "Condiments & Spices": "{{ __('Condiments & Spices') }}",
      "Grains & Cereals": "{{ __('Grains & Cereals') }}",
      "Frozen Goods": "{{ __('Frozen Goods') }}",
      "Bakery Goods": "{{ __('Bakery Goods') }}",
      "Gas": "{{ __('Gas') }}",
      "Other": "{{ __('Other') }}",
  };
</script>
