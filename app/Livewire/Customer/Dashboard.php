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

    // Modal properties
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
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
        'contact_number.digits' => 'Contact number must be exactly 11 digits.',
        'contact_number.regex' => 'Contact number must start with 09 and be 11 digits long.',
    ];

    public function mount()
    {
        //
    }

    // Search method
    public function updatedSearch()
    {
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

    // Modal methods
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal($customerId)
    {
        // clear form
        $this->resetForm();

        // populate form with existing customer data
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->name = $customer->name;
            $this->contact_number = $customer->contact_number;
            $this->unit = $customer->unit;
            $this->address = $customer->address;
            $this->showEditModal = true;
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function openDeleteModal($customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedCustomerId = null;
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

        session()->flash('success', 'Customer created successfully!');
        $this->closeCreateModal();
    }

    public function updateCustomer()
    {
        $this->validate();

        $customer = Customer::find($this->selectedCustomerId);

        $customer->update([
            'name' => ucwords(trim($this->name)),
            'unit' => ucwords($this->unit),
            'address' => ucwords($this->address),
            'contact_number' => $this->contact_number,
        ]);

        session()->flash('success', 'Customer updated successfully!');
        $this->closeEditModal();
    }

    public function deleteCustomer()
    {
        $customer = Customer::find($this->selectedCustomerId);
        if (!$customer) {
            session()->flash('error', 'Customer not found!');
            return;
        }

        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            session()->flash('error', 'Cannot delete customer with existing orders!');
            $this->closeDeleteModal();
            return;
        }

        $customer->delete();
        session()->flash('success', 'Customer deleted successfully!');
        $this->closeDeleteModal();
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

    // search
    public function getCustomersProperty()
    {
        $query = Customer::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('unit', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    // Get all customers for stats
    public function getAllCustomersProperty()
    {
        return Customer::all();
    }

    public function getAverageOrdersPerCustomerProperty()
    {
        $totalCustomers = $this->allCustomers->count();
        
        if ($totalCustomers == 0) {
            return 0;
        }
        
        $totalOrders = $this->allCustomers->sum(function($customer) {
            return $customer->orders()->count();
        });
        
        return round($totalOrders / $totalCustomers, 1);
    }

    public function render()
    {
        return view('livewire.customer.dashboard', [
            'customers' => $this->customers,
            'allCustomers' => $this->allCustomers,
        ]);
    }
}
