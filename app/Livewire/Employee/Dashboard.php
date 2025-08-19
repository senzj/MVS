<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;

class Dashboard extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $statusFilter = '';
    public $sortBy = 'id';
    public $sortDirection = 'asc';

    // Modal properties
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $selectedEmployeeId = null;

    // Form properties
    public $name = '';
    public $contact_number = '';
    public $status = 'active';

    protected $rules = [
        'name' => 'required|string|max:255',
        'contact_number' => 'required|string|digits:11|regex:/^09[0-9]{9}$/',
        'status' => 'required|in:active,inactive',
    ];

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

    public function updatedStatusFilter()
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

    public function openEditModal($employeeId)
    {
        $employee = Employee::find($employeeId);
        if ($employee) {
            $this->selectedEmployeeId = $employee->id;
            $this->name = $employee->name;
            $this->contact_number = $employee->contact_number;
            $this->status = $employee->status;
            $this->showEditModal = true;
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function openDeleteModal($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedEmployeeId = null;
    }

    // CRUD methods
    public function createEmployee()
    {
        $this->validate();

        Employee::create([
            'name' => ucwords(trim($this->name)),
            'contact_number' => $this->contact_number,
            'status' => $this->status,
            'is_archived' => false,
        ]);

        session()->flash('success', 'Employee created successfully!');
        $this->closeCreateModal();
    }

    public function updateEmployee()
    {
        $this->validate();

        $employee = Employee::find($this->selectedEmployeeId);
        
        $employee->update([
            'name' => ucwords(trim($this->name)),
            'contact_number' => $this->contact_number,
            'status' => $this->status,
        ]);

        session()->flash('success', 'Employee updated successfully!');
        $this->closeEditModal();
    }

    public function deleteEmployee()
    {
        $employee = Employee::find($this->selectedEmployeeId);
        if (!$employee) {
            session()->flash('error', 'Employee not found!');
            return;
        }

        // Check if employee has ongoing orders
        $ongoingOrders = $employee->orders()->whereIn('status', ['pending', 'in_progress', 'out_for_delivery'])->count();
        if ($ongoingOrders > 0) {
            session()->flash('error', 'Cannot archive employee with ongoing orders!');
            $this->closeDeleteModal();
            return;
        }

        // Archive the employee instead of deleting
        $employee->update(['is_archived' => true]);
        session()->flash('success', 'Employee archived successfully!');
        $this->closeDeleteModal();
    }

    // Add method to restore archived employee
    public function restoreEmployee($employeeId)
    {
        $employee = Employee::find($employeeId);
        if ($employee && $employee->is_archived) {
            $employee->update(['is_archived' => false]);
            session()->flash('success', 'Employee restored successfully!');
        }
    }

    // Helper methods
    public function resetForm()
    {
        $this->name = '';
        $this->contact_number = '';
        $this->status = 'active';
        $this->selectedEmployeeId = null;
        $this->resetErrorBag();
    }

    public function getEmployeesProperty()
    {
        $query = Employee::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            if ($this->statusFilter === 'archived') {
                $query->where('is_archived', true);
            } else {
                $query->where('status', $this->statusFilter)->where('is_archived', false);
            }
        } else {
            // By default, only show non-archived employees
            $query->where('is_archived', false);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    // Get all employees for stats (excluding archived)
    public function getAllEmployeesProperty()
    {
        return Employee::where('is_archived', false)->get();
    }

    public function render()
    {
        return view('livewire.employee.dashboard', [
            'employees' => $this->employees,
            'allEmployees' => $this->allEmployees,
        ]);
    }
}
