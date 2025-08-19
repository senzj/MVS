@section('title', 'Employee Dashboard')
<div class="container mx-auto p-3">
    
    {{-- Header with Stats --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-users mr-3"></i>Employee Management
                </h1>
                <p class="text-zinc-600 dark:text-zinc-400">Manage delivery personnel and staff</p>
            </div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i>
                Add Employee
            </button>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            {{-- Total Employees --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                        <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $allEmployees->count() }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Active Employees</div>
                    </div>
                </div>
            </div>

            {{-- Active Employees --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                        <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $allEmployees->where('status', 'active')->count() }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Active Status</div>
                    </div>
                </div>
            </div>

            {{-- Inactive Employees --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <i class="fas fa-user-times text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $allEmployees->where('status', 'inactive')->count() }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Inactive Status</div>
                    </div>
                </div>
            </div>

            {{-- Archived Employees --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-900">
                        <i class="fas fa-archive text-gray-600 dark:text-gray-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ App\Models\Employee::where('is_archived', true)->count() }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Archived</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Search and Filter Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-search mr-1"></i>Search Employees
                </label>
                <input type="text" wire:model.live="search" placeholder="Search by name or contact" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-filter mr-1"></i>Filter by Status
                </label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Active</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            {{-- Clear Filters --}}
            <div class="flex items-end">
                <button wire:click="$set('search', ''); $set('statusFilter', '')" class="w-full px-4 py-2 bg-zinc-600 text-white rounded-lg hover:bg-zinc-700 transition">
                    <i class="fas fa-times mr-1"></i>Clear Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Employees Table --}}
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
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer" wire:click="sortByField('status')">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-circle"></i>
                                Status
                                @if($sortBy === 'status')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($employees as $employee)
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $employee->status_color }}-100 text-{{ $employee->status_color }}-800 dark:bg-{{ $employee->status_color }}-900 dark:text-{{ $employee->status_color }}-200">
                                    <i class="fas fa-circle mr-1 text-xs"></i>{{ $employee->display_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($employee->is_archived)
                                        {{-- Restore button for archived employees --}}
                                        <button wire:click="restoreEmployee({{ $employee->id }})" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @else
                                        {{-- Normal edit/archive buttons for active employees --}}
                                        <button wire:click="openEditModal({{ $employee->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="openDeleteModal({{ $employee->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Archive">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl mb-3"></i>
                                    <p class="text-sm">No employees found.</p>
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
    @if($showCreateModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-plus mr-2"></i>Add New Employee
                    </h3>
                    <button wire:click="closeCreateModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="createEmployee" class="p-6 space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-user mr-1"></i>Employee Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" placeholder="Enter employee name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Contact Number --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-phone mr-1"></i>Contact Number <span class="text-red-500">*</span>
                        </label>
                        <input 
                            maxlength="11" 
                            type="tel" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                            wire:model="contact_number" 
                            placeholder="Enter contact number (e.g., 09123456789)" 
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-circle mr-1"></i>Status <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="status" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Create Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Employee Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-edit mr-2"></i>Edit Employee
                    </h3>
                    <button wire:click="closeEditModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="updateEmployee" class="p-6 space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-user mr-1"></i>Employee Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" placeholder="Enter employee name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Contact Number --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-phone mr-1"></i>Contact Number <span class="text-red-500">*</span>
                        </label>
                        <input 
                            maxlength="11" 
                            type="tel" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                            wire:model="contact_number" 
                            placeholder="Enter contact number" 
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('contact_number') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-circle mr-1"></i>Status <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="status" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900 rounded-full mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">Archive Employee</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                        Are you sure you want to archive this employee? Archived employees can be restored later and their order history will be preserved.
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="closeDeleteModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button wire:click="deleteEmployee" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            <i class="fas fa-archive mr-1"></i>Archive Employee
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>