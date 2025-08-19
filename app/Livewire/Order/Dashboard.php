<?php

namespace App\Livewire\Order;

use Livewire\Component;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $editingOrderId = null;
    public $editStatus = null;

    // Order Details Modal
    public $showOrderDetailsModal = false;
    public $selectedOrder = null;

    // Create Order modal state
    public bool $showCreateModal = false;

    // New order form
    public array $newOrder = [
        'customer_id' => null,
        'delivery_id' => null,
        'payment_type' => 'cash',
        'status' => 'pending', // pending | delivered
        'is_paid' => false,
        'receipt_number' => null,
    ];

    protected array $rules = [
        'newOrder.customer_id'   => 'nullable|integer|exists:customers,id',
        'newOrder.delivery_id'   => 'nullable|integer|exists:employees,id',
        'newOrder.payment_type'  => 'required|in:cash,gcash',
        'newOrder.status'        => 'required|in:pending,delivered',
        'newOrder.is_paid'       => 'boolean',
        'newOrder.receipt_number'=> 'nullable|string|max:255|unique:orders,receipt_number',
    ];

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrder = Order::with(['customer', 'employee', 'orderItems.product'])
            ->find($orderId);
        
        if ($this->selectedOrder) {
            $this->showOrderDetailsModal = true;
        }
    }

    public function closeOrderDetailsModal()
    {
        $this->showOrderDetailsModal = false;
        $this->selectedOrder = null;
    }

    public function togglePaid($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $order->is_paid = !$order->is_paid;
        $order->save();
    }

    public function markFinished($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        if ($order->is_paid) {
            $order->status = 'completed';
            $order->save();
        }
    }

    public function editOrder($orderId)
    {
        $this->editingOrderId = $orderId;
        $this->editStatus = optional(Order::find($orderId))->status;
    }

    public function saveEdit($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $order->status = $this->editStatus;
        $order->save();
        $this->editingOrderId = null;
    }

    // Modal controls
    public function openCreateModal(): void
    {
        $this->resetNewOrder();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    protected function sanitizeNewOrder(): array
    {
        // Convert empty strings to null to avoid saving blanks
        return collect($this->newOrder ?? [])
            ->map(fn($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();
    }

    protected function resetNewOrder(): void
    {
        // Ensure predictable defaults
        $this->newOrder = [
            'customer_id' => null,
            'delivery_id' => null,
            'payment_type' => 'cash',
            'status' => 'pending',
            'is_paid' => false,
            'receipt_number' => null,
        ];
    }

    public function createOrder(): void
    {
        // Sanitize and validate
        $this->newOrder = $this->sanitizeNewOrder();
        $validated = $this->validate();
        $data = $validated['newOrder'];

        // Fallback defaults
        $data['payment_type'] = $data['payment_type'] ?? 'cash';
        $data['status'] = $data['status'] ?? 'pending';
        $data['is_paid'] = (bool) ($data['is_paid'] ?? false);
        $data['user_id'] = Auth::id();
        $data['receipt_number'] = $data['receipt_number'] ?: $this->generateReceiptNumber();

        Order::create($data);

        $this->closeCreateModal();
        $this->resetNewOrder();
    }

    protected function generateReceiptNumber(): string
    {
        return 'R'.now()->format('YmdHis').random_int(100, 999);
    }

    public function render()
    {
        $today = Carbon::today();

        $orders = Order::with('customer')
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        $ongoing = $orders->whereIn('status', ['pending', 'delivered']);
        $completed = $orders->whereIn('status', ['completed', 'cancelled']);

        // Dropdown sources for modal
        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.order.dashboard', [
            'today' => $today->toFormattedDateString(),
            'ongoing' => $ongoing,
            'completed' => $completed,
            'ongoingCount' => $ongoing->count(),
            'completedCount' => $completed->count(),
            'customers' => $customers,
            'employees' => $employees,
        ]);
    }
}
