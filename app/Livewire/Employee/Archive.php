<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;

class Archive extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $sortBy = 'updated_at';
    public $sortDirection = 'desc';

    // Selected employee for operations
    public $selectedEmployeeId = null;

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

    // Restore archived employee
    public function restoreEmployee($employeeId)
    {
        $employee = Employee::find($employeeId);
        
        if ($employee && $employee->is_archived) {
            $employee->update(['is_archived' => false]);
            $this->dispatch('show-success', ['message' => 'Employee restored successfully!']);
        } else {
            $this->dispatch('show-error', ['message' => 'Employee not found or not archived!']);
        }
    }

    // Set selected employee for permanent deletion
    public function setSelectedEmployee($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
    }

    // Permanently delete employee
    public function permanentlyDeleteEmployee()
    {
        $employee = Employee::find($this->selectedEmployeeId);
        
        if (!$employee) {
            $this->dispatch('show-error', ['message' => 'Employee not found!']);
            return;
        }

        // Check if employee has any orders
        $hasOrders = $employee->orders()->count() > 0;
        
        if ($hasOrders) {
            $this->dispatch('show-error', ['message' => 'Cannot permanently delete employee with order history!']);
            return;
        }

        // Permanently delete
        $employeeName = $employee->name;
        $employee->delete();
        
        $this->dispatch('show-success', ['message' => "Employee '{$employeeName}' permanently deleted!"]);
        $this->selectedEmployeeId = null;
    }

    // Get archived employees
    public function getArchivedEmployeesProperty()
    {
        $query = Employee::where('is_archived', true);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    // Get stats
    public function getStatsProperty()
    {
        return [
            'total_archived' => Employee::where('is_archived', true)->count(),
            'can_be_deleted' => Employee::where('is_archived', true)
                ->whereDoesntHave('orders')
                ->count(),
            'has_orders' => Employee::where('is_archived', true)
                ->whereHas('orders')
                ->count(),
        ];
    }

    public function render()
    {
        return view('livewire.employee.archive', [
            'archivedEmployees' => $this->archivedEmployees,
            'stats' => $this->stats,
        ]);
    }
}
