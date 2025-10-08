@section('title', 'Dashboard')
<div class="container p-1 mx-auto">

    {{-- Header --}}
    <div class="flex flex-col gap-3 mb-6 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 fas fa-chart-bar"></i>{{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Welcome back! Here\'s what\'s happening today.') }}</p>
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
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">

        {{-- Today's Sales --}}
        <div class="p-6 mt-2 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Today\'s Sales') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($todayStats['sales'] ?? 0, 2) }}</p>
                    @if(isset($todayStats['sales_growth']) && $todayStats['sales_growth'] != 0)
                        <p class="text-sm {{ $todayStats['sales_growth'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $todayStats['sales_growth'] > 0 ? '+' : '' }}{{ number_format($todayStats['sales_growth'], 1) }}% {{ __('from yesterday') }}
                        </p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 rounded-2xl dark:bg-green-900/20">
                    <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>

        {{-- Total Orders Today --}}
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

        {{-- Pending Orders --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Pending Orders') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $todayStats['pending'] ?? 0 }}</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">{{ __('Pending') }}</p>
                </div>
                <div class="p-3 rounded-2xl bg-amber-100 dark:bg-amber-900/20">
                    <i class="fas fa-clock text-amber-600 dark:text-amber-400 text-xl pt-0.5"></i>
                </div>
            </div>
        </div>

        {{-- Top Product Today --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Top Product Today') }}</p>
                    @if(isset($topSellingProducts['today']) && $topSellingProducts['today'])
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ Str::limit($topSellingProducts['today']->name, 20) }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['today']->total_sold }} {{ __('sold') }}</p>
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

    {{-- Top Selling Products Section --}}
    <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-3">

        {{-- Today's Top Product --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-yellow-500 fas fa-trophy"></i>{{ __('Today\'s Best Seller') }}
            </h3>
            @if(isset($topSellingProducts['today']) && $topSellingProducts['today'])
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full dark:bg-green-900/20">
                            <i class="text-green-600 fas fa-trophy dark:text-green-400"></i>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $topSellingProducts['today']->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['today']->total_sold }} {{ __('units sold') }}</p>
                    </div>
                </div>
            @else
                <div class="py-8 text-center">
                    <i class="mb-3 text-4xl fas fa-chart-line text-zinc-300 dark:text-zinc-600"></i>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('No sales recorded today') }}</p>
                </div>
            @endif
        </div>

        {{-- This Week's Top Product --}}
        <div class="p-6 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="mr-2 text-blue-500 fas fa-chart-bar"></i>{{ __('This Week\'s Top Seller') }}
            </h3>

            @if(isset($topSellingProducts['week']) && $topSellingProducts['week'])
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full dark:bg-blue-900/20">
                            <i class="text-blue-600 fas fa-boxes dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $topSellingProducts['week']->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $topSellingProducts['week']->total_sold }} {{ __('units this week') }}</p>
                    </div>
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
                <i class="mr-2 text-purple-500 fas fa-medal"></i>{{ __('This month\'s Top Sellers') }}
            </h3>

            @if(isset($topSellingProducts['average']) && $topSellingProducts['average']->count() > 0)
                <div class="space-y-3">
                    @foreach($topSellingProducts['average']->take(3) as $index => $product)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="flex items-center justify-center w-6 h-6 text-xs font-medium rounded-full bg-zinc-100 dark:bg-zinc-700">{{ $index + 1 }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ Str::limit($product->name, 15) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $product->avg_weekly ?? 0 }}/{{ __('week') }}</span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $product->total_sold ?? 0 }} {{ __('total') }}</span>
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

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-2">

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
    <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-2">

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
    </div> {{-- end Statistical Charts --}}

<!-- Add chart i18n strings for JS (uses JSON translations via __()) -->
// ...existing code...

<!-- Add chart i18n strings for JS (uses JSON translations via __()) -->
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
