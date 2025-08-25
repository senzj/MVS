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
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public $editingOrderId = null;
    public $editStatus = null;
    public $editDeliveredBy = null; // NEW

    // Batch delivery system
    public $batchDeliveryTimers = []; // Track timers for each delivery person
    public $batchDeliveryOrders = []; // Track orders in batch for each delivery person
    public $batchDeliveryDuration = 60; // 1 minute in seconds (configurable: 2-7 minutes)

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

    public function mount(): void
    {
        $this->restoreBatchState();
    }

    private function restoreBatchState(): void
    {
        // Find all orders that are currently in "preparing" status
        $preparingOrders = Order::where('status', 'preparing')
            ->whereNotNull('delivered_by')
            ->get(['id', 'delivered_by', 'updated_at']);

        foreach ($preparingOrders as $order) {
            $employeeId = $order->delivered_by;
            
            // Get the actual batch start time from cache
            $batchStartTime = Cache::get("batch_start_time_{$employeeId}");
            
            if (!$batchStartTime) {
                // If no cached start time, use the oldest preparing order's updated_at as fallback
                $oldestOrder = Order::where('delivered_by', $employeeId)
                    ->where('status', 'preparing')
                    ->orderBy('updated_at', 'asc')
                    ->first();
                    
                if ($oldestOrder) {
                    $batchStartTime = $oldestOrder->updated_at->timestamp;
                    Cache::put("batch_start_time_{$employeeId}", $batchStartTime, now()->addMinutes(10));
                } else {
                    continue;
                }
            }
            
            // Calculate how much time has passed since batch started
            $timeElapsed = now()->timestamp - $batchStartTime;
            $remainingTime = max(0, $this->batchDeliveryDuration - $timeElapsed);
            
            // If time hasn't expired, restore the batch
            if ($remainingTime > 0) {
                // Initialize arrays if not set
                if (!isset($this->batchDeliveryOrders[$employeeId])) {
                    $this->batchDeliveryOrders[$employeeId] = [];
                }
                
                // Add order to batch
                $this->batchDeliveryOrders[$employeeId][] = $order->id;
                
                // Set timer end time
                $this->batchDeliveryTimers[$employeeId] = $batchStartTime + $this->batchDeliveryDuration;
            } else {
                // Time has expired, process the batch delivery
                $this->processBatchDelivery($employeeId);
            }
        }
    }

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
        $this->editDeliveredBy = optional($order)->delivered_by ?: ''; // Convert null to empty string for form
    }

    public function saveEdit($orderId)
    {
        $order = Order::with(['orderItems'])->find($orderId);
        if (!$order) return;

        $oldStatus = $order->status;
        $newStatus = $this->editStatus;

        if (!in_array($newStatus, ['pending', 'preparing', 'in_transit', 'delivered', 'completed', 'cancelled'], true)) {
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

            // Handle batch delivery changes
            if ($oldStatus === 'preparing' && $newStatus !== 'preparing') {
                // Order is being removed from preparing status, remove from batch
                $this->removeOrderFromBatch($order->delivered_by, $order->id);
            }

            // Update delivered_by if provided (allow null = unassigned)
            // Convert empty string to null for proper database handling
            $order->delivered_by = $this->editDeliveredBy ?: null;

            $order->status = $newStatus;
            $order->save();
        });

        $this->editingOrderId = null;
    }

    // Transition: Pending -> In Transit (with batch delivery support)
    public function startDelivery($orderId): void
    {
        $order = Order::find($orderId);
        if (!$order || !$order->delivered_by) {
            return;
        }

        $employeeId = $order->delivered_by;
        
        // Check if delivery person is currently delivering (has actual in_transit orders)
        $hasActiveDeliveries = Order::where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();

        if ($hasActiveDeliveries) {
            return;
        }

        // Check if order is already in a batch
        if (isset($this->batchDeliveryOrders[$employeeId]) && 
            in_array($orderId, $this->batchDeliveryOrders[$employeeId])) {
            return;
        }

        // Check if there's an active batch timer for this delivery person
        if (!$this->isBatchDeliveryActive($employeeId)) {
            // Start new batch timer
            $this->startBatchDeliveryTimer($employeeId);
            
            // Clear any missed batch state since we're starting fresh
            Cache::forget("missed_batch_{$employeeId}");
        }

        // Add order to batch
        $this->addOrderToBatch($employeeId, $orderId);

        // Update order status to 'preparing' (new intermediate status)
        $order->status = 'preparing';
        $order->save();
    }

    // Start batch delivery timer for an employee
    private function startBatchDeliveryTimer($employeeId): void
    {
        $startTime = now()->timestamp;
        $endTime = $startTime + $this->batchDeliveryDuration;
        
        // Store start time in cache (survives page refreshes)
        Cache::put("batch_start_time_{$employeeId}", $startTime, now()->addMinutes(10));
        
        $this->batchDeliveryTimers[$employeeId] = $endTime;
        $this->batchDeliveryOrders[$employeeId] = [];
    }

    // Check if batch delivery is active for an employee
    private function isBatchDeliveryActive($employeeId): bool
    {
        if (!isset($this->batchDeliveryTimers[$employeeId])) {
            return false;
        }

        $endTime = $this->batchDeliveryTimers[$employeeId];
        return now()->timestamp < $endTime;
    }

    // Add order to batch
    private function addOrderToBatch($employeeId, $orderId): void
    {
        if (!isset($this->batchDeliveryOrders[$employeeId])) {
            $this->batchDeliveryOrders[$employeeId] = [];
        }

        if (!in_array($orderId, $this->batchDeliveryOrders[$employeeId])) {
            $this->batchDeliveryOrders[$employeeId][] = $orderId;
        }
    }

    // Remove order from batch
    private function removeOrderFromBatch($employeeId, $orderId): void
    {
        if (!$employeeId || !isset($this->batchDeliveryOrders[$employeeId])) {
            return;
        }

        $this->batchDeliveryOrders[$employeeId] = array_filter(
            $this->batchDeliveryOrders[$employeeId],
            fn($id) => $id != $orderId
        );

        // If no orders left in batch, clean up the timer
        if (empty($this->batchDeliveryOrders[$employeeId])) {
            unset($this->batchDeliveryTimers[$employeeId]);
            unset($this->batchDeliveryOrders[$employeeId]);
        }
    }

    // Get remaining batch time for an employee
    public function getRemainingBatchTime($employeeId): int
    {
        if (!$this->isBatchDeliveryActive($employeeId)) {
            return 0;
        }

        $endTime = $this->batchDeliveryTimers[$employeeId];
        return max(0, $endTime - now()->timestamp);
    }

    // Process batch delivery (move all orders to in_transit)
    public function processBatchDelivery($employeeId): void
    {
        if (!isset($this->batchDeliveryOrders[$employeeId])) {
            return;
        }

        $orderIds = $this->batchDeliveryOrders[$employeeId];
        
        if (empty($orderIds)) {
            // Clean up timer and cache
            unset($this->batchDeliveryTimers[$employeeId]);
            unset($this->batchDeliveryOrders[$employeeId]);
            Cache::forget("batch_start_time_{$employeeId}");
            return;
        }

        // Move all orders in batch to in_transit
        Order::whereIn('id', $orderIds)
            ->where('status', 'preparing')
            ->update(['status' => 'in_transit']);

        // Store cache for orders that missed this batch (for queue state)
        // This will make other pending orders for this employee show as "waiting"
        Cache::put("missed_batch_{$employeeId}", now()->timestamp, now()->addMinutes(30));

        // Clean up batch data and cache
        unset($this->batchDeliveryTimers[$employeeId]);
        unset($this->batchDeliveryOrders[$employeeId]);
        Cache::forget("batch_start_time_{$employeeId}");
    }

    // Force start batch delivery (manual trigger)
    public function forceBatchDelivery($employeeId): void
    {
        $this->processBatchDelivery($employeeId);
    }

    // Check and process expired batch timers (called by JavaScript interval)
    public function checkBatchTimers(): void
    {
        $processedEmployees = [];
        
        foreach ($this->batchDeliveryTimers as $employeeId => $endTimestamp) {
            if (now()->timestamp >= $endTimestamp) {
                $this->processBatchDelivery($employeeId);
                $processedEmployees[] = $employeeId;
            }
        }
        
        // Also check for any preparing orders that might have been missed
        $preparingOrders = Order::where('status', 'preparing')
            ->whereNotNull('delivered_by')
            ->get(['id', 'delivered_by', 'updated_at']);
            
        foreach ($preparingOrders as $order) {
            $employeeId = $order->delivered_by;
            
            // Skip if we already processed this employee
            if (in_array($employeeId, $processedEmployees)) {
                continue;
            }
            
            $timeElapsed = now()->diffInSeconds($order->updated_at);
            
            // If batch time has expired but timer wasn't caught
            if ($timeElapsed >= $this->batchDeliveryDuration) {
                $this->processBatchDelivery($employeeId);
            }
        }

        // Clean up missed batch cache when all in_transit orders are completed
        $this->cleanupMissedBatchCache();
    }

    // NEW METHOD: Clean up missed batch cache when delivery person is available again
    private function cleanupMissedBatchCache(): void
    {
        // Get all employees who have missed batch cache
        $cacheKeys = Cache::getPrefix() ? [] : []; // This is a simplified approach
        
        // Check each employee that might have missed batch cache
        $employeesWithActiveDeliveries = Order::where('status', 'in_transit')
            ->whereNotNull('delivered_by')
            ->pluck('delivered_by')
            ->unique();

        // For each employee with active deliveries, check if all are completed
        foreach ($employeesWithActiveDeliveries as $employeeId) {
            $hasActiveDeliveries = Order::where('delivered_by', $employeeId)
                ->where('status', 'in_transit')
                ->exists();
            
            $hasUnpaidDelivered = Order::where('delivered_by', $employeeId)
                ->where('status', 'delivered')
                ->where('is_paid', false)
                ->exists();

            // If no active deliveries and no unpaid delivered orders, clear the missed batch cache
            if (!$hasActiveDeliveries && !$hasUnpaidDelivered) {
                Cache::forget("missed_batch_{$employeeId}");
            }
        }
    }

    // Check if delivery person can deliver (updated for batch system)
    public function canDeliveryPersonDeliver(Order $order): bool
    {
        if (!$order->delivered_by) {
            return false; // No delivery person assigned
        }

        $employeeId = $order->delivered_by;

        // Check if the delivery person has any actual in-transit orders (not preparing)
        $hasActiveDeliveries = Order::where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();

        if ($hasActiveDeliveries) {
            return false; // Actually busy with active deliveries
        }

        return true; // Available for new batch or individual delivery
    }

    // Helper method to check delivery status for blade template (UPDATED)
    public function getDeliveryPersonStatus($orderId)
    {
        $order = Order::find($orderId);
        if (!$order || !$order->delivered_by) {
            return 'no_person'; // No delivery person assigned
        }

        $employeeId = $order->delivered_by;

        // First, check if any batch for this employee has expired and clean it up
        if (isset($this->batchDeliveryTimers[$employeeId])) {
            if (now()->timestamp >= $this->batchDeliveryTimers[$employeeId]) {
                $this->processBatchDelivery($employeeId);
            }
        }

        // Check if the delivery person has any actual in-transit orders
        $hasActiveDeliveries = Order::where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();

        // Check if there are any delivered but unpaid orders
        $hasUnpaidDelivered = Order::where('delivered_by', $employeeId)
            ->where('status', 'delivered')
            ->where('is_paid', false)
            ->exists();

        // Check if currently in a batch preparation phase
        if ($this->isBatchDeliveryActive($employeeId)) {
            // Check if this specific order is already in the batch
            if (isset($this->batchDeliveryOrders[$employeeId]) && 
                in_array($orderId, $this->batchDeliveryOrders[$employeeId])) {
                return 'preparing'; // This order is already in the batch
            } else {
                return 'batch_preparing'; // Batch is active, this order can be added
            }
        }

        // If delivery person has active work (in_transit or unpaid delivered orders)
        // then other pending orders should be in "waiting" state
        if ($hasActiveDeliveries || $hasUnpaidDelivered) {
            return 'waiting'; // Employee is busy, other orders are waiting
        }

        // UPDATED: Check if this employee just finished a batch or has completed deliveries
        // Orders are in queue/waiting if there's a recent batch dispatch and no active work
        if (Cache::has("missed_batch_{$employeeId}")) {
            return 'waiting'; // Missed the batch window, waiting for next opportunity
        }

        return 'available'; // Available for delivery - shows "Deliver" button
    }

    // Get batch info for an employee
    public function getBatchInfo($employeeId)
    {
        // First check if the batch has expired
        if (isset($this->batchDeliveryTimers[$employeeId])) {
            if (now()->timestamp >= $this->batchDeliveryTimers[$employeeId]) {
                $this->processBatchDelivery($employeeId);
                return null;
            }
        }

        if (!$this->isBatchDeliveryActive($employeeId)) {
            return null;
        }

        $remainingTime = $this->getRemainingBatchTime($employeeId);
        $orderCount = isset($this->batchDeliveryOrders[$employeeId]) ? count($this->batchDeliveryOrders[$employeeId]) : 0;

        return [
            'remaining_time' => $remainingTime,
            'order_count' => $orderCount,
            'orders' => $this->batchDeliveryOrders[$employeeId] ?? []
        ];
    }

    // Get orders that are currently in batch for an employee
    public function getActiveBatchOrders($employeeId)
    {
        if (!isset($this->batchDeliveryOrders[$employeeId])) {
            return collect();
        }

        return Order::whereIn('id', $this->batchDeliveryOrders[$employeeId])
            ->where('status', 'preparing')
            ->get();
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

            // NEW: Check if this was the last unpaid/incomplete order for this employee
            // If so, clear the missed batch cache to make them available again
            $employeeId = $order->delivered_by;
            if ($employeeId) {
                $hasRemainingWork = Order::where('delivered_by', $employeeId)
                    ->whereIn('status', ['in_transit', 'delivered'])
                    ->where(function($query) {
                        $query->where('status', 'in_transit')
                              ->orWhere(function($subQuery) {
                                  $subQuery->where('status', 'delivered')
                                           ->where('is_paid', false);
                              });
                    })
                    ->exists();

                if (!$hasRemainingWork) {
                    Cache::forget("missed_batch_{$employeeId}");
                }
            }
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

    public function cancelPrepare(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::find($orderId);
            if (!$order) return;

            // Store receipt number for flash message
            $receiptNumber = $order->receipt_number;

            // Change status from 'preparing' back to 'pending'
            $order->update(['status' => 'pending']);

            // Remove from batch delivery system if it was in one
            $employeeId = $order->delivered_by;
            if ($employeeId && isset($this->batchDeliveryOrders[$employeeId])) {
                $this->batchDeliveryOrders[$employeeId] = array_filter(
                    $this->batchDeliveryOrders[$employeeId],
                    fn($id) => $id !== $orderId
                );
                
                // If no orders left in batch, clear the timer and cache
                if (empty($this->batchDeliveryOrders[$employeeId])) {
                    unset($this->batchDeliveryTimers[$employeeId]);
                    unset($this->batchDeliveryOrders[$employeeId]);
                    Cache::forget("batch_start_time_{$employeeId}");
                    Cache::forget("missed_batch_{$employeeId}");
                }
            }
        });
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