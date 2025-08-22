<?php

namespace App\Livewire\Order;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $editingOrderId = null;
    public $editStatus = null;
    public $editDeliveredBy = null; // NEW

    // Order Details Modal
    public $showOrderDetailsModal = false;
    public $selectedOrder = null;

    // Create Order modal state
    public bool $showCreateModal = false;

    // Delete Order modal state
    public bool $showDeleteModal = false;
    public ?int $deleteOrderId = null;
    public ?string $deleteReceipt = null;

    // New order form
    public array $newOrder = [
        'customer_id' => null,
        'delivered_by' => null,
        'payment_type' => 'cash',
        'status' => 'pending',
        'is_paid' => false,
        'receipt_number' => null,
    ];

    protected array $rules = [
        'newOrder.customer_id'   => 'nullable|integer|exists:customers,id',
        'newOrder.delivered_by'  => 'nullable|integer|exists:employees,id',
        'newOrder.payment_type'  => 'required|in:cash,gcash',
        'newOrder.status'        => 'required|in:pending,delivered',
        'newOrder.is_paid'       => 'boolean',
        'newOrder.receipt_number'=> 'nullable|string|max:255|unique:orders,receipt_number',
    ];

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrder = Order::with(['customer', 'employee', 'staff', 'orderItems.product'])
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
        if ($order) {
            $order->is_paid = !$order->is_paid;
            $order->save();

            $this->dispatch('show-success', ['message' => "\"{$order->receipt_number}\" has been marked as paid!"]);
        }
    }

    public function editOrder($orderId)
    {
        $this->editingOrderId = $orderId;
        $order = Order::find($orderId);
        $this->editStatus = optional($order)->status;
        $this->editDeliveredBy = optional($order)->delivered_by; // NEW
    }

    public function saveEdit($orderId)
    {
        $order = Order::with(['orderItems'])->find($orderId);
        if (!$order) return;

        $oldStatus = $order->status;
        $newStatus = $this->editStatus;

        if (!in_array($newStatus, ['pending', 'in_transit', 'delivered', 'completed', 'cancelled'], true)) {
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

            // Update delivered_by if provided (allow null = unassigned)
            $order->delivered_by = $this->editDeliveredBy;

            $order->status = $newStatus;
            $order->save();
        });

        $this->editingOrderId = null;
    }

    // Transition: Pending -> In Transit
    public function startDelivery($orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        // session flash message
        $this->dispatch('show-info', ['message' => "Delivering order with \"{$order->receipt_number}\" !"]);

        // mark order as in transit
        if ($order->status === 'pending') {
            $order->status = 'in_transit';
            $order->save();
        }
    }

    // Transition: In Transit -> Delivered
    public function markDelivered($orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        // session flash message
        $this->dispatch('show-info', ['message' => "\"{$order->receipt_number}\" has been marked as delivered!"]);

        // mark order as delivered
        if ($order->status === 'in_transit') {
            $order->status = 'delivered';
            $order->save();
        }
    }

    public function markFinished($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) return;

        // set editing to false
        $this->editingOrderId = null;

        // session flash message
        $this->dispatch('show-success', ['message' => "\"{$order->receipt_number}\" has been marked as finished!"]);

        // mark order status complete
        if ($order->is_paid && $order->status === 'delivered') {
            $order->status = 'completed';
            $order->save();
        }
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

    public function confirmDelete(int $orderId): void
    {
        $order = Order::select('id', 'receipt_number')->find($orderId);
        if (!$order) return;

        $this->deleteOrderId = $order->id;
        $this->deleteReceipt = $order->receipt_number;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteOrderId = null;
        $this->deleteReceipt = null;
    }

    public function deleteOrderConfirmed(): void
    {
        if (!$this->deleteOrderId) {
            session()->flash('error', 'Order not found.');
            return;
        }
            
        DB::transaction(function () {
            $order = Order::with('orderItems')->find($this->deleteOrderId);
            if (!$order) return;

            // Store receipt number for flash message before deletion
            $receiptNumber = $order->receipt_number;

            // Only rollback inventory if order status is not 'cancelled' 
            // (cancelled orders already have their inventory rolled back)
            if ($order->status !== 'cancelled') {
                $this->rollbackInventory($order);
            }

            // Delete items first to avoid FK issues if cascade is not set
            $order->orderItems()->delete();
            $order->delete();

            // flash message
            session()->flash('success', 'Order "' . $receiptNumber . '" deleted successfully and inventory restored.');
        });

        $this->closeDeleteModal();
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
        $orders = Order::with(['customer','employee','staff'])
            ->where(function ($outer) {
                // Today's orders (regardless of status)
                $outer->where(function ($q) {
                        $q->where('created_at', '>=', DB::raw('CURRENT_DATE'))
                        ->where('created_at', '<', DB::raw('DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)'));
                    })
                    // Or older orders that are not completed/cancelled
                    ->orWhere(function ($q) {
                        $q->where('created_at', '<', DB::raw('CURRENT_DATE'))
                        ->whereNotIn('status', ['completed', 'cancelled']);
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        // Filter for ongoing vs completed based on status only
        $ongoing = $orders->whereNotIn('status', ['completed', 'cancelled']);
        $completed = $orders->whereIn('status', ['completed', 'cancelled']);

        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.order.dashboard', [
            'today' => now()->toFormattedDateString(),
            'ongoing' => $ongoing,
            'completed' => $completed,
            'ongoingCount' => $ongoing->count(),
            'completedCount' => $completed->count(),
            'customers' => $customers,
            'employees' => $employees,
        ]);
    }
}
