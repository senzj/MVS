@section('title', 'Employee Dashboard')

<div class="container" x-data="{
    // Modal states
    showCreateModal: false,
    showEditModal: false,
    showDeleteModal: false,
    
    // Modal methods
    openCreateModal() {
        this.showCreateModal = true;
        $wire.resetForm();
    },
    
    closeCreateModal() {
        this.showCreateModal = false;
        $wire.resetForm();
    },
    
    openEditModal(employeeId) {
        this.showEditModal = true;
        $wire.loadEmployeeForEdit(employeeId);
    },
    
    closeEditModal() {
        this.showEditModal = false;
        $wire.resetForm();
    },
    
    openDeleteModal(employeeId) {
        this.showDeleteModal = true;
        $wire.setSelectedEmployee(employeeId);
    },
    
    closeDeleteModal() {
        this.showDeleteModal = false;
        $wire.setSelectedEmployee(null);
    }
}"
@close-create-modal.window="closeCreateModal()"
@close-edit-modal.window="closeEditModal()"
@close-delete-modal.window="closeDeleteModal()"
>

    {{-- Header with Stats --}}
    <div class="mb-3">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-users mr-3"></i>{{ __('Employee Management') }}
                </h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Manage delivery personnel and staff') }}</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('employees.archived') }}">
                    <button class="cursor-pointer inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-archive"></i>
                        {{ __('View Archive') }} ({{ App\Models\Employee::where('is_archived', true)->count() }})
                    </button>
                </a>

                <button @click="openCreateModal()" class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus"></i>
                    {{ __('Create Employee') }}
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $totalEmployees  = $allEmployees->count();
                $activeCount     = $allEmployees->where('status', 'active')->count();
                $inactiveCount   = $allEmployees->where('status', 'inactive')->count();
                $archivedCount   = \App\Models\Employee::where('is_archived', true)->count();
                $activePct       = $totalEmployees ? round(($activeCount / $totalEmployees) * 100) : 0;
                $inactivePct     = $totalEmployees ? round(($inactiveCount / $totalEmployees) * 100) : 0;
                $archivedPct     = $totalEmployees ? round(($archivedCount / $totalEmployees) * 100) : 0;

                $deliveredStatuses = ['delivered', 'completed'];
                $yesterdayDelivered = \App\Models\Order::whereIn('status', $deliveredStatuses)
                    ->whereDate('updated_at', \Carbon\Carbon::yesterday())->count();
                $last7Delivered = \App\Models\Order::whereIn('status', $deliveredStatuses)
                    ->where('updated_at', '>=', now()->subDays(7))->count();
                $last30Delivered = \App\Models\Order::whereIn('status', $deliveredStatuses)
                    ->where('updated_at', '>=', now()->subDays(30))->count();
                $avgPerActive7 = $activeCount ? round($last7Delivered / $activeCount, 1) : 0.0;

                $topWeek = \App\Models\Order::selectRaw('delivered_by, COUNT(*) as c')
                    ->whereIn('status', $deliveredStatuses)
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->whereNotNull('delivered_by')
                    ->groupBy('delivered_by')->orderByDesc('c')->first();

                $topMonth = \App\Models\Order::selectRaw('delivered_by, COUNT(*) as c')
                    ->whereIn('status', $deliveredStatuses)
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->whereNotNull('delivered_by')
                    ->groupBy('delivered_by')->orderByDesc('c')->first();

                $topWeekEmp  = $topWeek? \App\Models\Employee::find($topWeek->delivered_by) : null;
                $topMonthEmp = $topMonth? \App\Models\Employee::find($topMonth->delivered_by) : null;
            @endphp

            {{-- Employees (combined) --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ number_format($totalEmployees) }}
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Employees') }}</div>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-300">
                            <i class="fas fa-circle text-green-500 mr-1"></i>{{ __('Active') }}
                        </span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activeCount }} ({{ $activePct }}%)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-300">
                            <i class="fas fa-circle text-yellow-500 mr-1"></i>{{ __('Inactive') }}
                        </span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $inactiveCount }} ({{ $inactivePct }}%)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-300">
                            <i class="fas fa-circle text-zinc-400 mr-1"></i>{{ __('Archived') }}
                        </span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $archivedCount }} ({{ $archivedPct }}%)</span>
                    </div>
                    {{-- stacked progress --}}
                    <div class="mt-3 h-2 w-full bg-zinc-200 dark:bg-zinc-700 rounded overflow-hidden flex">
                        <div class="bg-green-500" style="width: {{ $activePct }}%"></div>
                        <div class="bg-yellow-500" style="width: {{ $inactivePct }}%"></div>
                        <div class="bg-zinc-400" style="width: {{ $archivedPct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Performance --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 dark:bg-emerald-900">
                        <i class="fas fa-chart-line text-emerald-600 dark:text-emerald-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Employee Performance') }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Delivered Orders') }}</div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($yesterdayDelivered) }}</div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Yesterday') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($last7Delivered) }}</div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Last 7 Days') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($last30Delivered) }}</div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Last 30 Days') }}</div>
                    </div>
                </div>
                <div class="mt-3 text-center text-xs text-zinc-600 dark:text-zinc-400">
                    {{ __('Average of') }} <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $avgPerActive7 }}</span> {{ __('orders') }}
                </div>
            </div>

            {{-- Top Performers --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                        <i class="fas fa-trophy text-indigo-600 dark:text-indigo-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Top Performers') }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('By delivered count') }}</div>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-zinc-700 dark:text-zinc-300">
                            <i class="fas fa-award text-amber-500 mr-1"></i>{{ __('Top Performer (Week)') }}:
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $topWeekEmp? $topWeekEmp->name : __('N/A') }}
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $topWeek->c ?? 0 }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-zinc-700 dark:text-zinc-300">
                            <i class="fas fa-medal text-indigo-500 mr-1"></i>{{ __('Top Performer (Month)') }}:
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $topMonthEmp? $topMonthEmp->name : __('N/A') }}
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $topMonth->c ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filter Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <label class="flex text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-search mr-1"></i>{{ __('Search Employees') }}
                </label>
                <input type="text" wire:model.live="search" placeholder="{{ __('Search by name or contact number') }}" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="flex text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-filter mr-1"></i>{{ __('Filter by status') }}
                </label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="">{{ __('All') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Employees Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        {{-- employee ID --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('id')">
                            <div class="flex items-center gap-1">
                                {{ __('Employee ID') }}
                                @if($sortBy === 'id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>

                        {{-- employee full name --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('name')">
                            <div class="flex items-center gap-1">
                                {{ __('Employee Name') }}
                                @if($sortBy === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>

                        {{-- employee contact --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            {{ __('Contact Number') }}
                        </th>

                        {{-- employee orders delivered --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            {{ __('Orders Delivered') }}
                        </th>

                        {{-- employee status --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('status')">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Status') }}
                                @if($sortBy === 'status')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>{{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">

                            {{-- employee ID --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ __('Employee ID') }}: {{ $employee->id }}
                            </td>

                            {{-- employee name --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-user mr-2 text-zinc-400"></i>{{ $employee->name }}
                                </div>
                            </td>

                            {{-- employee contact --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-phone mr-1 text-zinc-400"></i>{{ $employee->contact_number ?: 'N/A' }}
                                </div>
                            </td>

                            {{-- orders delivered --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-box mr-1 text-zinc-400"></i>{{ $employee->orders_delivered ?: 0 }}
                                </div>
                            </td>

                            {{-- employee status --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $employee->status_color }}-100 text-{{ $employee->status_color }}-800 dark:bg-{{ $employee->status_color }}-900 dark:text-{{ $employee->status_color }}-200">
                                    @php $status = $employee->status @endphp
                                    <i class="fas fa-circle mr-1 text-xs"></i>{{ __(ucwords($status)) }}
                                </span>
                            </td>

                            {{-- action buttons --}}
                            <td class="px-3 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Normal edit/archive buttons for active employees --}}
                                    <button @click="openEditModal({{ $employee->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit">
                                        <i class="fas fa-edit text-lg"></i>
                                        <span class="text-xs">{{ __('Edit') }}</span>
                                    </button>
                                    <button @click="openDeleteModal({{ $employee->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Archive">
                                        <i class="fas fa-archive text-lg"></i>
                                        <span class="text-xs">{{ __('Archive') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl mb-3"></i>
                                    <p class="text-sm">{{ __('No employees found.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
            {{ $employees->links() }}
        </div>
    </div>

    {{-- Create Employee Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full" @click.away="closeCreateModal()" x-transition.scale>
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-users mr-2"></i>{{ __('Create Employee') }}
                </h3>
                <button @click="closeCreateModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="createEmployee" class="p-6 space-y-4">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-user mr-1"></i>{{ __('Employee Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="{{ __('Enter employee name') }}" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Contact Number --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-phone mr-1"></i>{{ __('Contact Number') }} <span class="text-red-500">*</span>
                    </label>
                    <input maxlength="11" type="tel" inputmode="numeric" pattern="[0-9]*" 
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                        wire:model="contact_number" 
                        placeholder="{{ __('Enter contact number (e.g., 09123456789)') }}" 
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-circle mr-1"></i>{{ __('Status') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeCreateModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>{{ __('Create Employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Employee Modal --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full" @click.away="closeEditModal()" x-transition.scale>
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-edit mr-2"></i>{{ __('Edit Employee') }}
                </h3>
                <button @click="closeEditModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="updateEmployee" class="p-6 space-y-4">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-user mr-1"></i>{{ __('Employee Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="{{ __('Enter employee name') }}" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Contact Number --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-phone mr-1"></i>{{ __('Contact Number') }} <span class="text-red-500">*</span>
                    </label>
                    <input maxlength="11" type="tel" inputmode="numeric" pattern="[0-9]*" 
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                        wire:model="contact_number" 
                        placeholder="{{ __('Enter contact number (e.g., 09123456789)') }}" 
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-circle mr-1"></i>Status <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeEditModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>{{ __('Update Employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full" @click.away="closeDeleteModal()" x-transition.scale>
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">{{ __('Archive Employee') }}</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                    {{ __('Are you sure you want to archive this employee? Archived employees can be restored later and their order history will be preserved.') }}
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button wire:click="deleteEmployee" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-archive mr-1"></i>{{ __('Confirm Archive') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>