@section('title', 'Employee Archive')

<div class="container mx-auto p-3" x-data="{
    // Modal states
    showDeleteModal: false,
    
    // Modal methods
    openDeleteModal(employeeId) {
        this.showDeleteModal = true;
        $wire.setSelectedEmployee(employeeId);
    },
    
    closeDeleteModal() {
        this.showDeleteModal = false;
        $wire.setSelectedEmployee(null);
    }
}">
    
    {{-- Header with Navigation --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="flex items-right  gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-archive mr-3"></i>Employee Archive
                    </h1>
                </div>
                <p class="text-zinc-600 dark:text-zinc-400">Manage archived employees - restore or permanently delete</p>
            </div>
            <a href="{{ route('employees') }}" class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <i class="fas fa-arrow-left"></i>
                Back to Employees
            </a>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Total Archived --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-900">
                        <i class="fas fa-archive text-gray-600 dark:text-gray-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['total_archived'] }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Archived</div>
                    </div>
                </div>
            </div>

            {{-- Can be Deleted --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                        <i class="fas fa-trash text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['can_be_deleted'] }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Can be Deleted</div>
                    </div>
                </div>
            </div>

            {{-- Has Orders --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <i class="fas fa-shield-alt text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['has_orders'] }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Protected (Has Orders)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-search mr-1"></i>Search Archived Employees
                </label>
                <input type="text" wire:model.live="search" placeholder="Search by name or contact" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Clear Search --}}
            <div class="flex items-end">
                <button wire:click="$set('search', '')" class="w-full px-4 py-2 bg-zinc-600 text-white rounded-lg hover:bg-zinc-700 transition">
                    <i class="fas fa-times mr-1"></i>Clear Search
                </button>
            </div>
        </div>
    </div>

    {{-- Archived Employees Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('id')">
                            <div class="flex items-center gap-1">
                                <i class="fas fa-hashtag"></i>
                                ID
                                @if($sortBy === 'id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('name')">
                            <div class="flex items-center gap-1">
                                <i class="fas fa-user"></i>
                                Name
                                @if($sortBy === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-phone mr-1"></i>Contact
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('updated_at')">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-clock"></i>
                                Archived Date
                                @if($sortBy === 'updated_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-clipboard-list mr-1"></i>Orders
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($archivedEmployees as $employee)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                #{{ $employee->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-user mr-2 text-zinc-400"></i>{{ $employee->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-phone mr-1 text-zinc-400"></i>{{ $employee->contact_number ?: 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-calendar mr-1 text-zinc-400"></i>{{ $employee->updated_at->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $employee->updated_at->format('h:i A') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $orderCount = $employee->orders()->count();
                                @endphp
                                
                                @if($orderCount > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        <i class="fas fa-shield-alt mr-1 text-xs"></i>{{ $orderCount }} Order{{ $orderCount > 1 ? 's' : '' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <i class="fas fa-check mr-1 text-xs"></i>No Orders
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Restore button --}}
                                    <button wire:click="restoreEmployee({{ $employee->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Restore Employee">
                                        <i class="fas fa-undo text-lg"></i>
                                        <span class="text-xs">Restore</span>
                                    </button>
                                    
                                    {{-- Delete permanently button (only if no orders) --}}
                                    @if($employee->orders()->count() === 0)
                                        <button @click="openDeleteModal({{ $employee->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete Permanently">
                                            <i class="fas fa-trash text-lg"></i>
                                            <span class="text-xs">Delete</span>
                                        </button>
                                    @else
                                        <div class="cursor-not-allowed inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-zinc-400 dark:text-zinc-600" title="Cannot delete - has order history">
                                            <i class="fas fa-shield-alt text-lg"></i>
                                            <span class="text-xs">Protected</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-archive text-4xl mb-3"></i>
                                    @if($search)
                                        <p class="text-sm">No archived employees found matching "{{ $search }}".</p>
                                        <button wire:click="$set('search', '')" class="text-blue-600 hover:text-blue-800 text-sm mt-2">
                                            <i class="fas fa-times mr-1"></i>Clear search
                                        </button>
                                    @else
                                        <p class="text-sm">No archived employees found.</p>
                                        <p class="text-xs text-zinc-400 mt-1">Employees will appear here when they are archived.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($archivedEmployees->hasPages())
            <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $archivedEmployees->links() }}
            </div>
        @endif
    </div>

    {{-- Permanent Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50" x-transition>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full" @click.away="closeDeleteModal()" x-transition.scale>
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">Permanently Delete Employee</h3>
                <div class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6 space-y-2">
                    <p><strong class="text-red-600 dark:text-red-400">⚠️ WARNING:</strong> This action cannot be undone!</p>
                    <p>This will permanently remove the employee from the database.</p>
                    <p class="text-xs">Only employees without order history can be permanently deleted.</p>
                </div>
                <div class="flex justify-center gap-3">
                    <button @click="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button wire:click="permanentlyDeleteEmployee" @click="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>