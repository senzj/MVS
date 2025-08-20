<?php

namespace App\Livewire\Order;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product; // <-- add
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;   // <-- add
use Livewire\Component;

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
        'delivered_by' => null, // Changed from delivery_id
        'payment_type' => 'cash',
        'status' => 'pending',
        'is_paid' => false,
        'receipt_number' => null,
    ];

    protected array $rules = [
        'newOrder.customer_id'   => 'nullable|integer|exists:customers,id',
        'newOrder.delivered_by'  => 'nullable|integer|exists:employees,id', // Updated field name
        'newOrder.payment_type'  => 'required|in:cash,gcash',
        'newOrder.status'        => 'required|in:pending,delivered',
        'newOrder.is_paid'       => 'boolean',
        'newOrder.receipt_number'=> 'nullable|string|max:255|unique:orders,receipt_number',
    ];

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrder = Order::with(['customer', 'employee', 'staff', 'orderItems.product'])
            ->find($orderId);
        
        Log::info('details:', $this->selectedOrder->toArray());

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
        if ($order) {
            $order->is_paid = !$order->is_paid;
            $order->save();
            
            session()->flash('success', 'Payment status updated successfully!');
        }
    }

    public function markFinished($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        // set editing to false
        $this->editingOrderId = null;

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
        $order = Order::with(['orderItems'])->find($orderId);
        if (!$order) return;

        $oldStatus = $order->status;
        $newStatus = $this->editStatus;

        if (!in_array($newStatus, ['pending', 'delivered', 'completed', 'cancelled'], true)) {
            return;
        }

        if ($oldStatus === $newStatus) {
            $this->editingOrderId = null;
            return;
        }

        DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            // Moving into cancelled: rollback inventory
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->rollbackInventory($order);
            }

            // Moving out of cancelled: re-apply inventory
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                $this->applyInventory($order);
            }

            $order->status = $newStatus;
            $order->save();
        });

        $this->editingOrderId = null;
    }

    private function applyInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
            if (!$product) {
                continue;
            }

            $qty = (int) $item->quantity;

            // Deduct stocks, increase sold
            $product->stocks = max(0, (int) $product->stocks - $qty);
            $product->sold = max(0, (int) ($product->sold ?? 0) + $qty);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

    private function rollbackInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
            if (!$product) {
                continue;
            }

            $qty = (int) $item->quantity;

            // Return stocks, reduce sold
            $product->stocks = (int) $product->stocks + $qty;
            $product->sold = max(0, (int) ($product->sold ?? 0) - $qty);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

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
        return collect($this->newOrder ?? [])
            ->map(fn($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();
    }

    protected function resetNewOrder(): void
    {
        $this->newOrder = [
            'customer_id' => null,
            'delivered_by' => null, // Updated field name
            'payment_type' => 'cash',
            'status' => 'pending',
            'is_paid' => false,
            'receipt_number' => null,
        ];
    }

    public function createOrder(): void
    {
        $this->newOrder = $this->sanitizeNewOrder();
        $validated = $this->validate();
        $data = $validated['newOrder'];

        // Set defaults and required fields
        $data['payment_type'] = $data['payment_type'] ?? 'cash';
        $data['status'] = $data['status'] ?? 'pending';
        $data['is_paid'] = (bool) ($data['is_paid'] ?? false);
        $data['created_by'] = Auth::id(); // Set the user who created the order
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

        // Eager load both employee and staff relationships
        $orders = Order::with(['customer', 'employee', 'staff'])
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
