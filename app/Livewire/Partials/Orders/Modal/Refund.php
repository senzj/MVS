<?php

namespace App\Livewire\Partials\Orders\Modal;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Products\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Refund component
 * ────────────────
 * Access rules:
 *   • Opens only when order status = 'completed' AND payment_status = 'paid'
 *   • Partial refunds are supported: only items with refund_qty > 0 are processed
 *   • Each line has a "restore to stock" toggle (default ON) — staff can turn off
 *     for damaged/unsellable items that shouldn't go back to inventory
 *   • Payment status after refund:
 *     - All items fully refunded → 'refunded'
 *     - Any item still partially unreturned → stays 'paid'
 *
 * Triggering from Alpine or anywhere:
 *   window.dispatchEvent(new CustomEvent('open-refund', { detail: { orderId: 123 } }))
 */
class Refund extends Component
{
    public ?int   $orderId = null;
    public ?Order $order   = null;
    public bool   $show    = false;

    /**
     * Each line:
     * [
     *   order_item_id    => int,
     *   product_id       => int,
     *   product_name     => string,
     *   unit_price       => float,
     *   ordered          => int,   original quantity
     *   already_refunded => int,   units already returned in past refunds
     *   returnable       => int,   ordered - already_refunded
     *   refund_qty       => int,   how many to return THIS time (0 = skip)
     *   restore_stock    => bool,  whether to put units back in inventory
     * ]
     */
    public array $refundLines = [];

    public function mount(?int $orderId = null): void
    {
        if ($orderId) {
            $this->loadOrder($orderId);
        }
    }

    // ── Open / Close ───────────────────────────────────────────────

    public function openRefund(int $orderId): void
    {
        $order = Order::with(['orderItems.product'])->find($orderId);

        if (! $order) {
            return;
        }

        // Guard: only completed + paid orders
        if ($order->status !== 'completed' || $order->payment_status !== 'paid') {
            $this->dispatch('show-error', [
                'message' => __('Refunds can only be processed for completed, paid orders.'),
            ]);
            return;
        }

        // Guard: nothing left to refund
        $anyReturnable = $order->orderItems->some(
            fn ($item) => ((int) $item->quantity - (int) ($item->refunded_quantity ?? 0)) > 0
        );

        if (! $anyReturnable) {
            $this->dispatch('show-error', [
                'message' => __('All items on this order have already been fully refunded.'),
            ]);
            return;
        }

        $this->loadOrder($orderId, $order);
        $this->show = true;
    }

    public function closeRefund(): void
    {
        $this->show        = false;
        $this->refundLines = [];
        $this->order       = null;
        $this->orderId     = null;
    }

    private function loadOrder(int $orderId, ?Order $order = null): void
    {
        $this->orderId = $orderId;
        $this->order   = $order ?? Order::with(['orderItems.product'])->find($orderId);

        if (! $this->order) {
            return;
        }

        $this->refundLines = $this->order->orderItems
            ->map(function ($item) {
                $ordered         = (int) $item->quantity;
                $alreadyRefunded = (int) ($item->refunded_quantity ?? 0);
                $returnable      = max(0, $ordered - $alreadyRefunded);

                return [
                    'order_item_id'   => $item->id,
                    'product_id'      => $item->product_id,
                    'product_name'    => $item->product?->name ?? 'Product #' . $item->product_id,
                    'unit_price'      => (float) $item->unit_price,
                    'ordered'         => $ordered,
                    'already_refunded'=> $alreadyRefunded,
                    'returnable'      => $returnable,
                    'refund_qty'      => 0,
                    'restore_stock'   => true,
                ];
            })
            ->values()
            ->all();
    }

    // ── Computed ───────────────────────────────────────────────────

    public function getTotalRefundQtyProperty(): int
    {
        return (int) collect($this->refundLines)
            ->sum(fn ($l) => max(0, (int) ($l['refund_qty'] ?? 0)));
    }

    public function getRefundAmountProperty(): float
    {
        return (float) collect($this->refundLines)->sum(function ($l) {
            return max(0, (int) ($l['refund_qty'] ?? 0)) * (float) ($l['unit_price'] ?? 0);
        });
    }

    // ── Confirm ────────────────────────────────────────────────────

    public function confirmRefund(): void
    {
        // Re-check guard in case order state changed while modal was open
        $fresh = Order::query()->find($this->orderId);
        if (! $fresh || $fresh->status !== 'completed' || $fresh->payment_status !== 'paid') {
            $this->addError('refundLines', __('This order can no longer be refunded.'));
            return;
        }

        $this->validate([
            'refundLines'                   => 'required|array|min:1',
            'refundLines.*.refund_qty'      => 'required|integer|min:0',
            'refundLines.*.restore_stock'   => 'required|boolean',
        ]);

        $hasAny = collect($this->refundLines)->some(fn ($l) => (int) ($l['refund_qty'] ?? 0) > 0);
        if (! $hasAny) {
            $this->addError('refundLines', __('Enter a return quantity for at least one item.'));
            return;
        }

        // Per-line validation: can't exceed returnable
        foreach ($this->refundLines as $idx => $line) {
            $qty        = (int) ($line['refund_qty']  ?? 0);
            $returnable = (int) ($line['returnable']  ?? 0);
            if ($qty > $returnable) {
                $this->addError(
                    "refundLines.{$idx}.refund_qty",
                    __('Cannot return more than :n remaining units.', ['n' => $returnable])
                );
                return;
            }
        }

        DB::transaction(function () {
            $inventory = app(InventoryService::class);
            $allItemsFullyRefunded = true;

            foreach ($this->refundLines as $line) {
                $qty = (int) ($line['refund_qty'] ?? 0);

                // Lock row to prevent concurrent refund races
                $item = OrderItem::query()
                    ->where('id', $line['order_item_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $item) continue;

                if ($qty > 0) {
                    // Re-check returnable from live DB value
                    $liveReturnable = max(0, (int) $item->quantity - (int) $item->refunded_quantity);
                    if ($qty > $liveReturnable) {
                        throw ValidationException::withMessages([
                            'refundLines' => __('Refund quantity exceeds available units for :product.', [
                                'product' => $line['product_name'],
                            ]),
                        ]);
                    }

                    $item->refunded_quantity = (int) $item->refunded_quantity + $qty;
                    $item->save();

                    // Conditionally restore stock
                    if ((bool) ($line['restore_stock'] ?? true)) {
                        $inventory->restore(
                            (int) $line['product_id'],
                            $qty,
                            'refund',
                            $this->order,
                            __('Refund processed for order #:receipt.', ['receipt' => $this->order->receipt_number])
                        );
                    }
                }

                // Re-read from the item object (just updated above)
                if ((int) $item->refunded_quantity < (int) $item->quantity) {
                    $allItemsFullyRefunded = false;
                }
            }

            // Full refund → mark order as refunded
            // Partial refund → payment_status stays 'paid'
            if ($allItemsFullyRefunded) {
                Order::query()
                    ->where('id', $this->orderId)
                    ->update(['payment_status' => 'refunded']);
            }
        });

        $orderId = $this->orderId;
        $this->closeRefund();

        $this->dispatch('show-success', ['message' => __('Refund processed successfully.')]);
        $this->dispatch('order-refunded', orderId: $orderId);
    }

    public function render()
    {
        return view('livewire.partials.orders.modal.refund');
    }
}
