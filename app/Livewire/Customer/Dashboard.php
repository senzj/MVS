<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\System\AuditLogsService;
use App\Models\Customer;

class Dashboard extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $sortBy = 'id';
    public $sortDirection = 'asc';

    // Remove modal properties - Alpine.js will handle these
    public $selectedCustomerId = null;

    // Form properties
    public $name = '';
    public $unit = '';
    public $address = '';
    public $contact_number = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'unit' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'contact_number' => 'nullable|string|max:11|regex:/^[0-9]{11}$/',
    ];

    // error messages
    protected $messages = [
        'contact_number.max' => 'Contact number must not exceed 11 digits.',
        'contact_number.regex' => 'Contact number must be exactly 11 digits.',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'id'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount()
    {
        //
    }

    // Search method - FIXED: This will trigger when search updates
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Clear search method
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    // Sort method
    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // Modal methods - simplified for Alpine.js
    public function loadCustomerForEdit($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->name = $customer->name;
            $this->contact_number = $customer->contact_number;
            $this->unit = $customer->unit;
            $this->address = $customer->address;
        }
    }

    public function setSelectedCustomer($customerId)
    {
        $this->selectedCustomerId = $customerId;
    }

    // CRUD methods
    public function createCustomer(AuditLogsService $audit): void
    {
        $this->validate();

        $customer = Customer::create([
            'name'           => ucwords(trim($this->name)),
            'unit'           => ucwords($this->unit),
            'address'        => ucwords($this->address),
            'contact_number' => trim($this->contact_number) !== '' ? trim($this->contact_number) : null,
        ]);

        $audit->recordCustomerCreated(Auth::user(), $customer, request());

        $this->dispatch('close-create-modal');
        $this->dispatch('show-success', ['message' => __('Customer created successfully!')]);
        $this->resetForm();
    }

    public function updateCustomer(AuditLogsService $audit): void
    {
        $this->validate();

        $customer = Customer::find($this->selectedCustomerId);

        if ($customer) {
            // Snapshot before update
            $oldValues = [
                'name'           => $customer->name,
                'unit'           => $customer->unit,
                'address'        => $customer->address,
                'contact_number' => $customer->contact_number,
            ];

            $customer->update([
                'name'           => ucwords(trim($this->name)),
                'unit'           => ucwords($this->unit),
                'address'        => ucwords($this->address),
                'contact_number' => trim($this->contact_number) !== '' ? trim($this->contact_number) : null,
            ]);

            $audit->recordCustomerUpdated(Auth::user(), $customer, $oldValues, request());

            $this->dispatch('close-edit-modal');
            $this->dispatch('show-success', ['message' => __('Customer updated successfully!')]);
            $this->resetForm();
        } else {
            $this->dispatch('show-error', ['message' => __('Customer not found!')]);
        }
    }

    public function deleteCustomer(AuditLogsService $audit): void
    {
        $customer = Customer::find($this->selectedCustomerId);

        if (! $customer) {
            $this->dispatch('show-error', ['message' => __('Customer not found!')]);
            return;
        }

        if ($customer->orders()->count() > 0) {
            $this->dispatch('show-error', ['message' => __('Cannot delete customer with existing orders!')]);
            return;
        }

        // Snapshot before delete
        $snapshot = [
            'name'           => $customer->name,
            'unit'           => $customer->unit,
            'address'        => $customer->address,
            'contact_number' => $customer->contact_number,
        ];

        $this->dispatch('close-delete-modal');
        $customer->delete();

        $audit->recordCustomerDeleted(Auth::user(), $snapshot, request());

        $this->dispatch('show-success', ['message' => __('Customer :name deleted successfully!', ['name' => $snapshot['name']])]);
        $this->selectedCustomerId = null;
    }

    // Helper methods
    public function resetForm()
    {
        $this->name = '';
        $this->unit = '';
        $this->address = '';
        $this->contact_number = '';
        $this->selectedCustomerId = null;
        $this->resetErrorBag();
    }

    // FIXED: Search query - removed the computed property and made it direct
    public function render()
    {
        $query = Customer::query()->withCount('orders');

        // Apply search filter
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('unit', 'like', $searchTerm)
                  ->orWhere('address', 'like', $searchTerm)
                  ->orWhere('contact_number', 'like', $searchTerm);
            });
        }

        // Apply sorting
        if ($this->sortBy === 'orders_count') {
            $query->orderBy('orders_count', $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $customers = $query->paginate(10);

        // Get all customers for stats
        $allCustomers = Customer::all();

        // Count customers with multiple orders in current month
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();

        $repeatedCustomersThisMonth = $allCustomers->filter(function ($customer) use ($thisMonthStart, $thisMonthEnd) {
            $ordersThisMonth = $customer->orders()
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
                ->count();
            return $ordersThisMonth > 1;
        })->count();

        return view('livewire.customer.dashboard', [
            'customers' => $customers,
            'allCustomers' => $allCustomers,
            'repeatedCustomersThisMonth' => $repeatedCustomersThisMonth,
        ]);
    }
}
