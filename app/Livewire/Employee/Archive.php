<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\System\AuditLogsService;
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
    public function restoreEmployee(int $employeeId, AuditLogsService $audit): void
    {
        $employee = Employee::find($employeeId);

        if ($employee && $employee->is_archived) {
            $employee->update(['is_archived' => false]);
            $audit->recordEmployeeRestored(Auth::user(), $employee, request());
            $this->dispatch('show-success', ['message' => __('Employee restored successfully!')]);
        } else {
            $this->dispatch('show-error', ['message' => __('Employee not found or not archived!')]);
        }
    }

    // Set selected employee for permanent deletion
    public function setSelectedEmployee($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
    }

    // Permanently delete employee
    public function permanentlyDeleteEmployee(AuditLogsService $audit): void
    {
        $employee = Employee::find($this->selectedEmployeeId);

        if (! $employee) {
            $this->dispatch('show-error', ['message' => __('Employee not found!')]);
            return;
        }

        if ($employee->orders()->count() > 0) {
            $this->dispatch('show-error', ['message' => __('Cannot permanently delete employee with order history!')]);
            return;
        }

        $snapshot = [
            'name'           => $employee->name,
            'contact_number' => $employee->contact_number,
            'status'         => $employee->status,
        ];

        $employee->delete();

        $audit->recordEmployeeDeleted(Auth::user(), $snapshot, request());

        $this->dispatch('show-success', ['message' => __('Employee :name permanently deleted!', ['name' => $snapshot['name']])]);
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
        if ($this->sortBy === 'orders_delivered') {
            $query->withCount(['orders' => function ($q) {
                $q->whereIn('status', ['delivered', 'completed']);
            }])->orderBy('orders_count', $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

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
