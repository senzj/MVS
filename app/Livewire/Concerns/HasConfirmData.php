<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Carbon\Carbon;

/**
 * HasConfirmData
 * ==============
 * Provides a cached computed property for the confirm/review modal data.
 *
 * Avoids recomputing expensive Carbon::parse + isoFormat + match() blocks
 * on every Livewire render cycle (which caused modal lag).
 *
 * Add this trait to Add, Create, and Edit alongside HasOrderForm.
 *
 * The property is intentionally NOT marked #[Computed(persist: true)]
 * because the data must update when any of its sources change.
 * Using Livewire's #[Computed] with no arguments still memoizes
 * within a single request, which is all we need.
 */
trait HasConfirmData
{
    /**
     * Returns the array consumed by the universal order modal.
     * Computed once per render, not on every blade include.
     *
     * Components must expose these properties for this to work:
     *   Add:    $receiptNumber, $saleDate, $orderType, $paymentType,
     *           $paymentStatus, $status, $orderItems, $totalAmount,
     *           $customerName, $customerContact, $customerUnit, $customerAddress
     *           + selectedEmployee computed property
     *
     *   Create: $orderNumber, $orderType, $paymentType, $orderItems,
     *           $totalAmount, $customerName, $customerContact,
     *           $customerUnit, $customerAddress
     *           + selectedEmployee computed property
     *
     *   Edit:   $order (model), $order_type, $payment_type,
     *           $payment_status, $status, $orderItems, $editedTotal,
     *           + $selectedCustomer / $selectedEmployee passed via render()
     */
    #[Computed]
    public function getConfirmDataProperty(): array
    {
        $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();

        // ── Receipt number ──────────────────────────────────────────
        $receiptNumber = $this->receiptNumber          // Add
            ?? $this->orderNumber                       // Create
            ?? ($this->order->receipt_number ?? '');    // Edit

        // ── Date ────────────────────────────────────────────────────
        if (! empty($this->saleDate)) {
            // Add: user-supplied datetime-local string
            try {
                $dateLabel = Carbon::parse($this->saleDate)->locale($loc)->isoFormat('LLLL');
            } catch (\Throwable) {
                $dateLabel = $this->saleDate;
            }
        } elseif (isset($this->order)) {
            // Edit: order's created_at
            $dateLabel = $this->order->created_at->locale($loc)->isoFormat('LLLL');
        } else {
            // Create: current time at the moment of opening
            $dateLabel = now()->locale($loc)->isoFormat('LLLL');
        }

        // ── Order type ──────────────────────────────────────────────
        $orderTypeRaw = $this->orderType              // Add / Create (trait alias)
            ?? $this->order_type                      // Edit
            ?? '';

        $orderTypeLabel = $orderTypeRaw === 'deliver' ? __('Delivery') : __('Walk-In');

        // ── Payment method ──────────────────────────────────────────
        $paymentTypeRaw = $this->paymentType          // Add / Create
            ?? $this->payment_type                    // Edit
            ?? 'cash';

        $paymentLabel = match (strtolower((string) $paymentTypeRaw)) {
            'cash'  => __('Cash'),
            'gcash' => __('GCash'),
            default => ucwords(str_replace('_', ' ', (string) $paymentTypeRaw)),
        };

        // ── Payment status ──────────────────────────────────────────
        $paymentStatusRaw = $this->paymentStatus      // Add
            ?? $this->payment_status                  // Edit
            ?? 'unpaid';

        $paymentStatusLabel = match ($paymentStatusRaw) {
            'paid'     => __('Paid'),
            'refunded' => __('Refunded'),
            default    => __('Unpaid'),
        };

        // ── Order status ────────────────────────────────────────────
        $statusRaw   = $this->status ?? '';
        $statusLabel = match ($statusRaw) {
            'completed'  => __('Completed'),
            'pending'    => __('Pending'),
            'preparing'  => __('Preparing'),
            'in_transit' => __('In transit'),
            'delivered'  => __('Delivered'),
            'cancelled'  => __('Cancelled'),
            default      => ucfirst(str_replace('_', ' ', $statusRaw)),
        };

        // ── Employee / Customer ─────────────────────────────────────
        // selectedEmployee is a computed property provided by HasOrderForm
        $employee = null;
        if (isset($this->selectedEmployee)) {
            $employee = $this->selectedEmployee;
        } elseif (isset($selectedEmployee)) {
            // Edit passes it through render() as a view variable
            $employee = $selectedEmployee ?? null;
        }

        $customer = null;
        if (method_exists($this, 'getSelectedCustomerProperty')) {
            $customer = $this->selectedCustomer;
        }

        // ── Items / total ───────────────────────────────────────────
        $items = $this->orderItems ?? [];
        $total = $this->totalAmount    // Add / Create (trait computed)
            ?? $this->editedTotal      // Edit (computed)
            ?? 0;

        return [
            'receiptNumber'      => $receiptNumber,
            'reviewDateTime'     => $dateLabel,
            'orderType'          => $orderTypeLabel,
            'paymentLabel'       => $paymentLabel,
            'paymentStatusLabel' => $paymentStatusLabel,
            'statusLabel'        => $statusLabel,
            'deliveredBy'        => optional($employee)->name,
            'customerName'       => filled($this->customerName ?? null)
                ? $this->customerName
                : optional($customer)->name,
            'customerContact'    => filled($this->customerContact ?? null)
                ? $this->customerContact
                : optional($customer)->contact_number,
            'customerUnit'       => filled($this->customerUnit ?? null)
                ? $this->customerUnit
                : optional($customer)->unit,
            'customerAddress'    => filled($this->customerAddress ?? null)
                ? $this->customerAddress
                : optional($customer)->address,
            'items'              => $items,
            'totalAmount'        => (float) $total,
        ];
    }

    /**
     * Called explicitly (e.g. in openSaveConfirmation) to build and return the array.
     * Use this instead of the computed property when you want a one-time snapshot.
     */
    public function buildConfirmData(): array
    {
        return $this->getConfirmDataProperty();
    }
}
