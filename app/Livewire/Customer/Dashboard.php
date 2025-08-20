<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
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
        'contact_number' => 'required|string|max:11|regex:/^[0-9]{11}$/',
    ];

    // error messages
    protected $messages = [
        'contact_number.required' => 'Contact number is required.',
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
    public function createCustomer()
    {
        $this->validate();

        Customer::create([
            'name' => ucwords(trim($this->name)),
            'unit' => ucwords($this->unit),
            'address' => ucwords($this->address),
            'contact_number' => $this->contact_number,
        ]);

        // close modal
        $this->dispatch('close-create-modal');

        $this->dispatch('show-success', ['message' => 'Customer created successfully!']);
        $this->resetForm();
    }

    public function updateCustomer()
    {
        $this->validate();

        $customer = Customer::find($this->selectedCustomerId);
        
        if ($customer) {
            $customer->update([
                'name' => ucwords(trim($this->name)),
                'unit' => ucwords($this->unit),
                'address' => ucwords($this->address),
                'contact_number' => $this->contact_number,
            ]);

            // close modal
            $this->dispatch('close-edit-modal');

            $this->dispatch('show-success', ['message' => 'Customer updated successfully!']);
            $this->resetForm();
        } else {
            $this->dispatch('show-error', ['message' => 'Customer not found!']);
        }
    }

    public function deleteCustomer()
    {
        $customer = Customer::find($this->selectedCustomerId);
        if (!$customer) {
            $this->dispatch('show-error', ['message' => 'Customer not found!']);
            return;
        }

        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            $this->dispatch('show-error', ['message' => 'Cannot delete customer with existing orders!']);
            return;
        }

        // close modal
        $this->dispatch('close-delete-modal');

        $customerName = $customer->name;
        $customer->delete();
        
        $this->dispatch('show-success', ['message' => "Customer '{$customerName}' deleted successfully!"]);
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
        $query = Customer::query();

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
        $query->orderBy($this->sortBy, $this->sortDirection);

        $customers = $query->paginate(10);

        // Get all customers for stats
        $allCustomers = Customer::all();

        return view('livewire.customer.dashboard', [
            'customers' => $customers,
            'allCustomers' => $allCustomers,
        ]);
    }
}
