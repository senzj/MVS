<?php
/**
 * Create.php  (Create Order)
 * ==========================
 * Key changes:
 *  1. Uses HasOrderForm trait.
 *  2. Walk-in flow merged: openSaveConfirmation() sets $modalMode = 'walkin'
 *     and opens the universal modal — no separate payment modal needed.
 *     The modal handles both review + cash/gcash input in one step.
 *  3. Customer validation dispatches 'customer-validation-clear' on success.
 *  4. updatedOrderItems() delegates to trait.
 *  5. Removed PaymentImageHelper, payment modal, and duplicate methods.
 */

namespace App\Livewire\Order;

use App\Helpers\PaymentImageHelper;
use App\Livewire\Concerns\HasOrderForm;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Create extends Component
{
    use HasOrderForm;

    // ── Form state ─────────────────────────────────────────────────
    public string  $orderNumber          = '';
    public string  $orderType            = 'walk_in';
    public string  $paymentType          = 'cash';
    public ?int    $selectedEmployeeId   = null;
    public ?int    $selectedCustomerId   = null;
    public bool    $isCreatingNewCustomer = false;
    public string  $customerName         = '';
    public string  $customerUnit         = '';
    public string  $customerAddress      = '';
    public string  $customerContact      = '';
    public string  $customerSearch       = '';
    public string  $employeeSearch       = '';
    public string  $productSearch        = '';
    public array   $orderItems           = [];
    public array   $errorFields          = [];
    public bool    $showConfirmModal     = false;

    /**
     * Modal mode: 'confirm' (delivery) | 'walkin' (walk-in with payment step)
     * Passed to the universal modal partial.
     */
    public string  $modalMode            = 'confirm';

    // Walk-in payment
    public ?float  $amountReceived       = null;
    public float   $changeAmount         = 0;
    public bool    $processingPayment    = false;
    public ?string $currentImage         = null;

    // Product form
    public bool        $showProductForm    = false;
    public ?int        $productTargetIndex = null;
    public string      $productName        = '';
    public string      $productDescription = '';
    public string      $productCategory    = 'other';
    public string|int  $productStocks      = 1;
    public string|float $productPrice      = 0;

    protected $rules = [
        'orderType'                  => 'required|in:deliver,walk_in',
        'paymentType'                => 'required|in:cash,gcash',
        'selectedEmployeeId'         => 'nullable|exists:employees,id',
        'selectedCustomerId'         => 'nullable|exists:customers,id',
        'customerName'               => 'nullable|string|max:255',
        'customerUnit'               => 'nullable|string|max:255',
        'customerAddress'            => 'nullable|string|max:255',
        'customerContact'            => 'nullable|string|max:20',
        'orderItems'                 => 'required|array|min:1',
        'orderItems.*.product_id'    => 'required|exists:products,id',
        'orderItems.*.quantity'      => 'required|integer|min:1',
        'orderItems.*.price'         => 'nullable|numeric|min:0',
        'orderItems.*.is_free'       => 'nullable|boolean',
    ];

    public function mount(): void
    {
        $this->orderType   = config('storeconfig.default_order_type', 'walk_in');
        $this->paymentType = config('storeconfig.default_payment_type', 'cash');
        $this->orderNumber = $this->generateOrderNumber();
        $this->addOrderItem();
    }

    // ── Lifecycle ──────────────────────────────────────────────────

    public function updatedOrderItems($value, $key): void
    {
        $this->handleUpdatedOrderItem($value, $key);
    }

    public function updatedOrderType(): void
    {
        if ($this->orderType === 'walk_in') {
            $this->selectedEmployeeId    = null;
            $this->selectedCustomerId    = null;
            $this->isCreatingNewCustomer = false;
            $this->customerName          = '';
            $this->customerUnit          = '';
            $this->customerAddress       = '';
            $this->customerContact       = '';
            $this->dispatch('customer-validation-clear');
        }
        $this->resetErrorBag();
    }

    public function updatedAmountReceived(): void
    {
        if ($this->amountReceived === '' || $this->amountReceived === null) {
            $this->changeAmount = 0;
            $this->resetErrorBag(['amountReceived']);
            return;
        }

        $received           = is_numeric($this->amountReceived) ? (float) $this->amountReceived : 0;
        $this->changeAmount = max(0, $received - $this->totalAmount);

        if ($received >= $this->totalAmount) {
            $this->resetErrorBag(['amountReceived']);
        }
    }

    // ── Modal ──────────────────────────────────────────────────────

    /**
     * Walk-in: open the universal modal in 'walkin' mode (review + payment combined).
     * Delivery: open in 'confirm' mode (review only, payment not needed upfront).
     */
    public function openSaveConfirmation(): void
    {
        if (! $this->validateSubmissionRequirements()) {
            $this->showConfirmModal = false;
            return;
        }

        $this->dispatch('customer-validation-clear');

        if ($this->orderType === 'walk_in') {
            $this->modalMode      = 'walkin';
            $this->amountReceived = null;
            $this->changeAmount   = 0;
            $this->currentImage   = PaymentImageHelper::getPaymentImageUrl();
        } else {
            $this->modalMode = 'confirm';
        }

        $this->showConfirmModal = true;
    }

    public function closeSaveConfirmation(): void
    {
        $this->showConfirmModal   = false;
        $this->processingPayment  = false;
        $this->amountReceived     = null;
        $this->changeAmount       = 0;
    }

    /**
     * Called by the modal's "Confirm & Save" button (delivery mode).
     */
    public function saveSalesRecord(): void
    {
        $this->showConfirmModal = false;
        $this->createOrder();
    }

    /**
     * Called by the modal's "Complete Order" button (walk-in mode).
     */
    public function processPayment(): void
    {
        $this->processingPayment = true;

        if ($this->paymentType === 'cash') {
            $this->validate([
                'amountReceived' => [
                    'required',
                    'numeric',
                    'min:' . $this->totalAmount,
                ],
            ], [
                'amountReceived.required' => __('Please enter the amount received.'),
                'amountReceived.numeric'  => __('Amount must be a valid number.'),
                'amountReceived.min'      => __('Amount received must be at least ₱') . number_format($this->totalAmount, 2),
            ]);
        }

        $this->showConfirmModal = false;
        $this->createOrder();
    }

    // ── Validation ─────────────────────────────────────────────────

    protected function getSubmissionRules(): array
    {
        $rules = $this->rules;

        if ($this->orderType === 'deliver') {
            $rules['selectedEmployeeId'] = 'required|exists:employees,id';

            if ($this->isCreatingNewCustomer) {
                $rules['customerName']       = 'required|string|max:255';
                $rules['customerContact']    = 'nullable|string|max:20';
                $rules['customerAddress']    = 'required|string|max:255';
                $rules['selectedCustomerId'] = 'nullable';
            } else {
                $rules['selectedCustomerId'] = 'required|exists:customers,id';
            }
        }

        return $rules;
    }

    protected function validateSubmissionRequirements(): bool
    {
        try {
            $this->validate($this->getSubmissionRules());
        } catch (ValidationException $e) {
            $this->errorFields = array_keys($e->errors());
            $this->dispatch('form-validation-failed', errorFields: $this->errorFields);

            $customerFields = ['selectedCustomerId','customerName','customerAddress'];
            if (array_intersect($customerFields, $this->errorFields)) {
                $this->dispatch('customer-validation-error');
            }

            return false;
        }

        return true;
    }

    // ── Order number ───────────────────────────────────────────────

    public function generateOrderNumber(): string
    {
        $datePart = now()->format('ymd');
        $prefix   = "OR{$datePart}";

        $last = Order::query()
            ->where('receipt_number', 'like', "{$prefix}%")
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $next = $last
            ? ((int) substr($last, strlen($prefix)) + 1)
            : 1;

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // ── Save ───────────────────────────────────────────────────────

    public function createOrder(): void
    {
        // Pre-validate stock levels before transaction
        foreach ($this->orderItems as $i => $item) {
            if (! ($item['product_id'] ?? null)) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.product_id"]);
                return;
            }
            $product = Product::find($item['product_id']);
            if (! $product || ! $product->is_in_stock || $product->stocks <= 0) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.product_id"]);
                return;
            }
            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty < 1 || $qty > (int) $product->stocks) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.quantity"]);
                return;
            }
        }

        DB::transaction(function () {
            $customerId = $this->persistCustomer();
            $isWalkIn   = $this->orderType === 'walk_in';

            $order = Order::create([
                'customer_id'     => $customerId,
                'created_by'      => Auth::id(),
                'delivered_by'    => $isWalkIn ? null : $this->selectedEmployeeId,
                'order_total'     => $this->totalAmount,
                'order_type'      => $this->orderType,
                'payment_type'    => $this->paymentType,
                'status'          => $isWalkIn ? 'completed' : 'pending',
                'is_paid'         => $isWalkIn,
                'receipt_number'  => $this->orderNumber,
                'amount_received' => $isWalkIn ? $this->amountReceived : null,
                'change_amount'   => $isWalkIn ? $this->changeAmount   : null,
            ]);

            foreach ($this->orderItems as $item) {
                if (! ($item['product_id'] ?? null)) continue;

                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                $qty = (int) $item['quantity'];

                if (! $product || $product->stocks < $qty) {
                    throw ValidationException::withMessages([
                        'orderItems' => "Insufficient stock for product ID {$item['product_id']}.",
                    ]);
                }

                $isFree    = (bool) ($item['is_free'] ?? false);
                $unitPrice = $isFree ? 0 : max(0, (float) ($item['price'] ?? 0));

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $qty,
                    'unit_price'  => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);

                $product->stocks    = max(0, (int) $product->stocks - $qty);
                $product->sold      = (int) ($product->sold ?? 0) + $qty;
                $product->is_in_stock = $product->stocks > 0;
                $product->save();
            }
        });

        session()->flash('success', __('Order created successfully!'));
        $this->redirect(route('orders'));
    }

    private function persistCustomer(): ?int
    {
        if ($this->orderType !== 'deliver') {
            return null;
        }

        if ($this->isCreatingNewCustomer) {
            $c = Customer::create([
                'name'           => ucwords(trim($this->customerName)),
                'unit'           => ucwords(trim($this->customerUnit)),
                'address'        => ucwords(trim($this->customerAddress)),
                'contact_number' => trim($this->customerContact) ?: null,
                'created_by'     => Auth::id(),
            ]);
            return $c->id;
        }

        if ($this->selectedCustomerId) {
            Customer::query()->whereKey($this->selectedCustomerId)->first()?->update([
                'name'           => ucwords(trim($this->customerName)),
                'unit'           => ucwords(trim($this->customerUnit)),
                'address'        => ucwords(trim($this->customerAddress)),
                'contact_number' => trim($this->customerContact) ?: null,
            ]);
        }

        return $this->selectedCustomerId;
    }

    public function render()
    {
        return view('livewire.order.create');
    }
}
