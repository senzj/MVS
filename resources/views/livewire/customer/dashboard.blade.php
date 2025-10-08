@section('title', 'Customer Dashboard')

<div class="container" 
     x-data="{ 
        showCreateModal: false, 
        showEditModal: false, 
        showDeleteModal: false,
        
        openCreateModal() {
            this.showCreateModal = true;
            $wire.resetForm();
        },
        closeCreateModal() {
            this.showCreateModal = false;
            $wire.resetForm();
        },
        openEditModal(customerId) {
            this.showEditModal = true;
            $wire.loadCustomerForEdit(customerId);
        },
        closeEditModal() {
            this.showEditModal = false;
            $wire.resetForm();
        },
        openDeleteModal(customerId) {
            this.showDeleteModal = true;
            $wire.setSelectedCustomer(customerId);
        },
        closeDeleteModal() {
            this.showDeleteModal = false;
            $wire.setSelectedCustomer(null);
        }
     }"
     @close-create-modal.window="closeCreateModal()"
     @close-edit-modal.window="closeEditModal()"
     @close-delete-modal.window="closeDeleteModal()"
     >

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-users mr-2"></i>{{ __('Customer Management') }}
                </h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Manage your customer database and contact information') }}</p>
            </div>
            <button @click="openCreateModal()" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-user-plus"></i>
                {{ __('Add Customer') }}
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $customers->total() }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Customers') }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        @php
                            $totalCustomers = $allCustomers->count();
                            $totalOrders = $allCustomers->sum(function($customer) { 
                                return $customer->orders()->count(); 
                            });
                            $avgOrdersPerCustomer = $totalCustomers > 0 ? round($totalOrders / $totalCustomers, 1) : 0;
                        @endphp
                        {{ $avgOrdersPerCustomer }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Average Orders per Customer') }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                    <i class="fas fa-user-plus text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $allCustomers->where('created_at', '>=', now()->startOfMonth())->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('New Customers this Month') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-search mr-1"></i>{{ __('Search Customer') }}
                </label>
                {{-- FIXED: Changed from wire:model.live.debounce.300ms to wire:model.live --}}
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="{{ __('Search by name, address, unit, or contact number') }}" 
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            {{-- Search Results Info --}}
            @if($search)
                <div class="mt-9 text-sm text-zinc-600 dark:text-zinc-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('Showing results for') }}: <strong>"{{ $search }}"</strong>
                </div>
            @endif
        </div>
        
        
    </div>

    {{-- Customers Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                {{-- Table Header --}}
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        {{-- Customer ID --}}
                        <th wire:click="sortByField('id')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-hashtag"></i>
                                {{ __('ID') }}
                                @if($sortBy === 'id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Customer Name --}}
                        <th wire:click="sortByField('name')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-user"></i>
                                {{ __('Customer Name') }}
                                @if($sortBy === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Unit & Address --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ __('Unit & Address') }}
                        </th>

                        {{-- Contact Number --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-phone mr-1"></i>{{ __('Contact Number') }}
                        </th>

                        {{-- Orders Count --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-shopping-bag mr-1"></i>{{ __('Orders Count') }}
                        </th>

                        {{-- Created At --}}
                        <th wire:click="sortByField('created_at')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-calendar"></i>
                                {{ __('Created At') }}
                                @if($sortBy === 'created_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>

                        {{-- Actions --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>{{ __('Actions') }}
                        </th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            {{-- Customer ID --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">#{{ $customer->id }}</div>
                            </td>

                            {{-- Customer Name --}}
                            <td class="px-6 py-4 text-left">
                                <div class="flex items-center">
                                    
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                            <span class="text-white text-xs font-medium">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->name }}</div>
                                    </div>

                                </div>
                            </td>

                            {{-- Unit & Address --}}
                            <td class="px-6 py-4 text-left">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    @if($customer->unit || $customer->address)
                                        @if($customer->unit)
                                            <i class="fas fa-home mr-1 text-zinc-400"></i>
                                            {{ $customer->unit }}@if($customer->address),@endif
                                        @endif
                                        @if($customer->address)
                                            {{ $customer->address }}
                                        @endif
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">No address provided</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Contact Number --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    @if ($customer->contact_number)
                                        <i class="fas fa-mobile-alt mr-1 text-zinc-400"></i>{{ $customer->contact_number }}
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">No contact number</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Orders Count --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customer->orders()->count() > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                    <i class="fas fa-shopping-bag mr-1"></i>{{ $customer->orders()->count() }}
                                </span>
                            </td>

                            {{-- Joined Date --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-calendar mr-1 text-zinc-400"></i>{{ $customer->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $customer->created_at->format('h:i A') }}
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEditModal({{ $customer->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Edit Customer">
                                        <i class="fas fa-edit text-lg"></i>
                                        <span class="text-xs">{{ __('Edit') }}</span>
                                    </button>
                                    
                                    <button @click="openDeleteModal({{ $customer->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete Customer">
                                        <i class="fas fa-trash text-lg"></i>
                                        <span class="text-xs">{{ __('Delete') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    @if($search)
                                        <p class="text-sm">{{ __('No customers found.') }} "{{ $search }}".</p>
                                        <button wire:click="$set('search', '')" class="cursor-pointer bg-blue-600 p-2 text-white rounded hover:text-gray-200 hover:bg-blue-900 text-sm mt-2">
                                            <i class="fas fa-times mr-1"></i>{{ __('Clear search') }}
                                        </button>
                                    @else
                                        <p class="text-sm">{{ __('No customers found.') }}</p>
                                        <p class="text-xs mt-1">{{ __('Add your first customer to get started.') }}</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- Create Customer Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full" @click.away="closeCreateModal()" x-transition.scale>
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-user-plus mr-2"></i>{{ __('Create New Customer') }}
                </h3>
                <button @click="closeCreateModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="createCustomer" class="p-6 space-y-4">

                @csrf
                {{-- Customer Name --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-user mr-1"></i>{{ __('Customer Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="{{ __('Enter customer name') }}" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Unit & Address --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>{{ __('Unit & Address') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="unit" placeholder="e.g. Unit 123" class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <input type="text" wire:model="address" placeholder="e.g. 123 Sesame Street" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @error('unit') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    @error('address') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
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
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeCreateModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-user-plus mr-1"></i>{{ __('Create Customer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Customer Modal --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full" @click.away="closeEditModal()" x-transition.scale>
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-user-edit mr-2"></i>{{ __('Edit Customer') }}
                </h3>
                <button @click="closeEditModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="updateCustomer" class="p-6 space-y-4">
                {{-- Customer Name --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-user mr-1"></i>{{ __('Customer Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="{{ __('Enter customer name') }}" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Unit & Address --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>{{ __('Unit & Address') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="unit" placeholder="e.g. Unit 123" class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <input type="text" wire:model="address" placeholder="e.g. 123 Sesame Street" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @error('unit') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    @error('address') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
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
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeEditModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>{{ __('Update Customer') }}
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
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">{{ __('Delete Customer') }}</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                    {{ __('Are you sure you want to delete this customer? This action cannot be undone and will permanently remove all customer data.') }}
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="closeDeleteModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button wire:click="deleteCustomer()" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i>{{ __('Confirm Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
