<?php
namespace App\Livewire\Order;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Products\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{ // NEW

    // Search and filters
    public string $search = '';
    public string $paymentFilter = 'all';
    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'paymentFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
    ];

    // Batch delivery system
    public $batchDeliveryTimers = []; // Track timers for each delivery person
    public $batchDeliveryOrders = []; // Track orders in batch for each delivery person
    public $batchDeliveryDuration = 30; // 1 minute in seconds (configurable: 2-7 minutes)

    // Order Details Modal
    public $showOrderDetailsModal = false;
    public ?int $selectedOrderId = null;

    // Order Payment Modal
    protected $listeners = [
        'orderPaymentConfirmed' => '$refresh',
    ];


    // Create Order modal state
    public bool $showCreateModal = false;

    // Delete Order modal state
    public bool $showDeleteModal = false;
    public ?int $deleteOrderId = null;
    public ?string $deleteReceipt = null;

    // Cancel Order modal state
    public bool $showCancelModal = false;
    public ?int $cancelOrderId = null;
    public ?string $cancelReceipt = null;

    // New order form
    public array $newOrder = [
        'customer_id' => null,
        'delivered_by' => null,
        'payment_type' => 'cash',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'receipt_number' => null,
    ];

    protected array $rules = [
        'newOrder.customer_id'   => 'nullable|integer|exists:customers,id',
        'newOrder.delivered_by'  => 'nullable|integer|exists:employees,id',
        'newOrder.payment_type'  => 'required|in:cash,gcash',
        'newOrder.status'        => 'required|in:pending,delivered',
        'newOrder.payment_status'=> 'required|in:unpaid,paid,refunded',
        'newOrder.receipt_number'=> 'nullable|string|max:255|unique:orders,receipt_number',
    ];

    public function mount(): void
    {
        $this->autoProcessExpiredPreparing(); // NEW: process any expired batches first
        $this->restoreBatchState();
    }

    /**
     * Server-side sweep: promote any expired preparing batches to in_transit
     */
    private function autoProcessExpiredPreparing(): void
    {
        $groups = Order::select('delivered_by', DB::raw('MIN(updated_at) as min_updated'))
            ->where('status', 'preparing')
            ->whereNotNull('delivered_by')
            ->groupBy('delivered_by')
            ->get();

        foreach ($groups as $g) {
            $employeeId = $g->delivered_by;
            if (!$employeeId) {
                continue;
            }
            $batchStart = Cache::get("batch_start_time_{$employeeId}") ?? Carbon::parse($g->min_updated)->timestamp;
            $elapsed = now()->timestamp - $batchStart;
            if ($elapsed >= $this->batchDeliveryDuration) {
                // Fetch ids directly (works even if component arrays are empty)
                $orderIds = Order::where('delivered_by', $employeeId)
                    ->where('status', 'preparing')
                    ->pluck('id')
                    ->all();
                if (!empty($orderIds)) {
                    $this->processBatchDelivery($employeeId, $orderIds);
                } else {
                    Cache::forget("batch_start_time_{$employeeId}");
                }
            }
        }
    }

    private function restoreBatchState(): void
    {
        // Rebuild active (non-expired) batches from DB
        $preparing = Order::where('status', 'preparing')
            ->whereNotNull('delivered_by')
            ->get(['id','delivered_by','updated_at'])
            ->groupBy('delivered_by');

        foreach ($preparing as $employeeId => $orders) {
            // Derive (or cache) batch start time
            $batchStartTime = Cache::get("batch_start_time_{$employeeId}");
            if (!$batchStartTime) {
                $batchStartTime = $orders->min('updated_at')->timestamp;
                Cache::put("batch_start_time_{$employeeId}", $batchStartTime, now()->addMinutes(10));
            }

            $timeElapsed   = now()->timestamp - $batchStartTime;
            $remainingTime = max(0, $this->batchDeliveryDuration - $timeElapsed);

            if ($remainingTime <= 0) {
                // Already expired — promote now (pass explicit ids so method works statelessly)
                $orderIds = $orders->pluck('id')->all();
                $this->processBatchDelivery($employeeId, $orderIds);
                continue;
            }

            // Still active — rebuild arrays
            $this->batchDeliveryOrders[$employeeId] = $orders->pluck('id')->all();
            $this->batchDeliveryTimers[$employeeId] = $batchStartTime + $this->batchDeliveryDuration;
        }
    }

    public function viewOrderDetails(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->showOrderDetailsModal = true;
    }

    public function closeOrderDetailsModal(): void
    {
        $this->showOrderDetailsModal = false;
        $this->selectedOrderId = null;
    }

    public function updatedSearch(): void
    {
        // Livewire re-renders automatically; this keeps URL state in sync.
    }

    public function updatedPaymentFilter(): void
    {
        // Livewire re-renders automatically; this keeps URL state in sync.
    }

    public function updatedStatusFilter(): void
    {
        // Livewire re-renders automatically; this keeps URL state in sync.
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->paymentFilter = 'all';
        $this->statusFilter = 'all';
    }

    public function togglePaid($orderId)
    {
        // Dispatch to the Payment
        $this->dispatch('openPaymentModal', orderId: $orderId);
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
    public function getRemainingBatchTime($employeeId = null): int
    {
        if (!$employeeId) {
            return 0;
        }

        if (!$this->isBatchDeliveryActive($employeeId)) {
            return 0;
        }

        $endTime = $this->batchDeliveryTimers[$employeeId];
        return max(0, $endTime - now()->timestamp);
    }

    // Process batch delivery (move all orders to in_transit)
    public function processBatchDelivery($employeeId = null, $orderIds = null): void
    {
        if (!$employeeId) {
            return;
        }

        // Ensure we have order IDs even if component state was lost
        if ($orderIds === null) {
            if (isset($this->batchDeliveryOrders[$employeeId]) && !empty($this->batchDeliveryOrders[$employeeId])) {
                $orderIds = $this->batchDeliveryOrders[$employeeId];
            } else {
                $orderIds = Order::where('delivered_by', $employeeId)
                    ->where('status', 'preparing')
                    ->pluck('id')
                    ->all();
            }
        }

        if (empty($orderIds)) {
            // Cleanup any stale cache
            Cache::forget("batch_start_time_{$employeeId}");
            unset($this->batchDeliveryTimers[$employeeId], $this->batchDeliveryOrders[$employeeId]);
            return;
        }

        $updatedCount = Order::whereIn('id', $orderIds)
            ->where('status', 'preparing')
            ->update([
                'status' => 'in_transit',
                'updated_at' => now(), // reflect transition moment
            ]);

        // if ($updatedCount > 0) {
        //     Log::info("Dashboard processed batch delivery for employee {$employeeId}: {$updatedCount} orders moved to in_transit", [
        //         'employee_id' => $employeeId,
        //         'order_ids'   => $orderIds,
        //         'source'      => 'livewire_dashboard',
        //     ]);
        // }

        // Cleanup
        unset($this->batchDeliveryTimers[$employeeId], $this->batchDeliveryOrders[$employeeId]);
        Cache::forget("batch_start_time_{$employeeId}");
    }

    // Force start batch delivery (manual trigger)
    public function forceBatchDelivery($employeeId = null): void
    {
        if (!$employeeId) {
            return;
        }

        $this->processBatchDelivery($employeeId);
    }

    // Check and process expired batch timers (simplified)
    public function checkBatchTimers(): void
    {
        foreach ($this->batchDeliveryTimers as $employeeId => $endTimestamp) {
            if (now()->timestamp >= $endTimestamp) {
                $this->processBatchDelivery($employeeId);
            }
        }

        // Also check for any preparing orders that might have been missed
        $preparingOrders = Order::where('status', 'preparing')
            ->whereNotNull('delivered_by')
            ->get(['id', 'delivered_by', 'updated_at']);

        foreach ($preparingOrders as $order) {
            $employeeId = $order->delivered_by;

            // Check if batch start time exists in cache
            $batchStartTime = Cache::get("batch_start_time_{$employeeId}");

            if ($batchStartTime) {
                $timeElapsed = now()->timestamp - $batchStartTime;
            } else {
                // Fallback to order updated_at if no cache (might have been processed by scheduled command)
                $timeElapsed = now()->diffInSeconds($order->updated_at);
            }

            // If batch time has expired but timer wasn't caught
            if ($timeElapsed >= $this->batchDeliveryDuration) {
                $this->processBatchDelivery($employeeId);
            }
        }
    }

    // Check if delivery person can deliver (simplified)
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

    // Helper method to check delivery status for blade template (SIMPLIFIED)
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
            ->whereIn('payment_status', ['unpaid', 'refunded'])
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

        return 'available'; // Available for delivery - shows "Deliver" button
    }

    // Get batch info for an employee
    public function getBatchInfo($employeeId = null)
    {
        if (!$employeeId) {
            return null;
        }

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
    public function getActiveBatchOrders($employeeId = null)
    {
        if (!$employeeId) {
            return collect();
        }

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
        $this->dispatch('show-info', ['message' => __('Order ":receipt" has been marked as delivered!', ['receipt' => $order->receipt_number])]);

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

        // session flash message
        $this->dispatch('show-success', ['message' => __('Order ":receipt" has been marked as finished!', ['receipt' => $order->receipt_number])]);

        $isWalkInPaid = $order->order_type === 'walk_in' && $order->payment_status === 'paid';
        $isDeliveredPaid = $order->payment_status === 'paid' && $order->status === 'delivered';

        // mark order status complete if it's a walk-in paid order or if it's already delivered and paid
        if ($isDeliveredPaid || $isWalkInPaid) {
            $order->status = 'completed';
            $order->save();

            // Check if this was the last unpaid/incomplete order for this employee
            // If so, clear the missed batch cache to make them available again
            $employeeId = $order->delivered_by;
            if ($employeeId) {
                $hasRemainingWork = Order::where('delivered_by', $employeeId)
                    ->whereIn('status', ['in_transit', 'delivered'])
                    ->where(function($query) {
                        $query->where('status', 'in_transit')
                              ->orWhere(function($subQuery) {
                                  $subQuery->where('status', 'delivered')
                                           ->whereIn('payment_status', ['unpaid', 'refunded']);
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
        $inventory = app(InventoryService::class);

        foreach ($order->orderItems as $item) {
            $qty = (int) $item->quantity;

            $inventory->deduct(
                (int) $item->product_id,
                $qty,
                'order_created',
                $order,
                __('Order #:receipt created.', ['receipt' => $order->receipt_number])
            );
        }
    }

    private function rollbackInventory(Order $order): void
    {
        $inventory = app(InventoryService::class);

        foreach ($order->orderItems as $item) {
            $qty = (int) $item->quantity;

            $inventory->restore(
                (int) $item->product_id,
                $qty,
                'manual_adjustment',
                $order,
                __('Order #:receipt deleted.', ['receipt' => $order->receipt_number])
            );
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

    public function openCancel(int $orderId): void
    {
        $order = Order::select('id', 'receipt_number', 'status')->find($orderId);
        if (! $order) return;

        // Only open when order is cancellable
        if (in_array($order->status, ['cancelled', 'completed', 'preparing'], true)) {
            $this->dispatch('show-error', ['message' => __('This order cannot be cancelled.')]);
            return;
        }

        $this->cancelOrderId = $order->id;
        $this->cancelReceipt = $order->receipt_number;
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelOrderId = null;
        $this->cancelReceipt = null;
    }

    public function confirmCancel(): void
    {
        if (! $this->cancelOrderId) return;

        $this->cancelOrder($this->cancelOrderId);
        $this->closeCancelModal();
    }

    /**
     * Cancel an order: restore inventory and mark as cancelled.
     */
    public function cancelOrder(int $orderId): void
    {
        $order = Order::with('orderItems')->find($orderId);
        if (! $order) return;

        DB::transaction(function () use ($order) {
            // Restore inventory for this order and mark cancelled
            $this->rollbackInventory($order);
            $order->status = 'cancelled';
            $order->save();
        });

        $this->dispatch('show-success', ['message' => __('Order #:receipt has been cancelled.', ['receipt' => $order->receipt_number])]);
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
            session()->flash('error', __('Order not found.'));
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
            session()->flash('success', __('Order #:receipt deleted.', ['receipt' => $receiptNumber]));
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
            'payment_status' => 'unpaid',
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
        $data['payment_status'] = $data['payment_status'] ?? 'unpaid';
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

    /**
     * Called by wire:poll — runs server-side sweep on a timer.
     * Add wire:poll.30s="pollBatchTimers" to your root div.
     */
    public function pollBatchTimers(): void
    {
        $this->autoProcessExpiredPreparing();
        $this->checkBatchTimers();
    }

    public function render()
    {
        // load selectedOrder fresh here — NOT stored in public state
        $selectedOrder = $this->selectedOrderId
            ? Order::with(['customer', 'employee', 'staff', 'orderItems.product'])
                ->find($this->selectedOrderId)
            : null;

        $ordersQuery = Order::with(['customer', 'employee', 'staff'])
            ->where(function ($outer) {
                $outer->where(function ($q) {
                        $q->where('created_at', '>=', DB::raw('CURRENT_DATE'))
                          ->where('created_at', '<', DB::raw('DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)'));
                    })
                    ->orWhere(function ($q) {
                        $q->where('created_at', '<', DB::raw('CURRENT_DATE'))
                          ->whereNotIn('status', ['completed', 'cancelled']);
                    });
            })
            ->when($this->search !== '', function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';

                $query->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('receipt_number', 'like', $searchTerm)
                        ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                            $customerQuery->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('employee', function ($employeeQuery) use ($searchTerm) {
                            $employeeQuery->where('name', 'like', $searchTerm);
                        });
                });
            })
            ->when($this->paymentFilter === 'paid', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->when($this->paymentFilter === 'unpaid', function ($query) {
                $query->where('payment_status', 'unpaid');
            })
            ->when($this->paymentFilter === 'refunded', function ($query) {
                $query->where('payment_status', 'refunded');
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderByDesc('created_at');

        $orders = $ordersQuery->get();

        // Filter for ongoing vs completed using payment state too.
        // Completed orders that are still unpaid stay in the ongoing bucket.
        $ongoing = $orders->filter(function ($order) {
            return ! in_array($order->status, ['completed', 'cancelled'], true)
                || ($order->status === 'completed' && $order->payment_status === 'unpaid');
        });

        $completed = $orders->filter(function ($order) {
            return ($order->status === 'completed' && $order->payment_status !== 'unpaid')
                || $order->status === 'cancelled';
        });

        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);

        // Build order status counts for KPI (total counts across matching scope)
        $counts = Order::select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $orderStatusCounts = [
            'pending' => (int) ($counts['pending'] ?? 0),
            'preparing' => (int) ($counts['preparing'] ?? 0),
            'in_transit' => (int) ($counts['in_transit'] ?? 0),
            'delivered' => (int) ($counts['delivered'] ?? 0),
            'completed_cancelled' => (int) (($counts['completed'] ?? 0) + ($counts['cancelled'] ?? 0)),
        ];

        return view('livewire.order.dashboard', [
            'today' => now()->toFormattedDateString(),
            'ongoing' => $ongoing,
            'completed' => $completed,
            'ongoingCount' => $ongoing->count(),
            'completedCount' => $completed->count(),
            'customers' => $customers,
            'selectedOrder'  => $selectedOrder,
            'orderStatusCounts' => $orderStatusCounts,
        ]);
    }
}
