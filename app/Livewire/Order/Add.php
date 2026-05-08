<?php
/**
 * Add.php  (Record Sale)
 * ======================
 * Key changes from original:
 *  1. Uses HasOrderForm trait — removes ~200 lines of duplicated methods.
 *  2. openSaveConfirmation() dispatches 'customer-validation-clear' on success.
 *  3. updatedOrderItems() delegates to trait's handleUpdatedOrderItem().
 *  4. Removed duplicate loadData / selectEmployee / selectProduct / etc.
 */

namespace App\Livewire\Order;

use App\Livewire\Concerns\HasOrderForm;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Add extends Component
{
    use HasOrderForm;

    // ── Form state ─────────────────────────────────────────────────
    public string  $receiptNumber        = '';
    public string  $saleDate             = '';
    public string  $orderType            = 'walk_in';
    public string  $paymentType          = 'cash';
    public bool    $isPaid               = true;
    public string  $status               = 'completed';
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

    // Product form
    public bool        $showProductForm    = false;
    public ?int        $productTargetIndex = null;
    public string      $productName        = '';
    public string      $productDescription = '';
    public string      $productCategory    = 'other';
    public string|int  $productStocks      = 1;
    public string|float $productPrice      = 0;

    protected $rules = [
        'receiptNumber'              => 'required|string|max:255|unique:orders,receipt_number',
        'saleDate'                   => 'required|date',
        'orderType'                  => 'required|in:deliver,walk_in',
        'paymentType'                => 'required|in:cash,gcash',
        'isPaid'                     => 'boolean',
        'status'                     => 'required|in:pending,preparing,in_transit,delivered,completed,cancelled',
        'selectedEmployeeId'         => 'nullable|exists:employees,id',
        'selectedCustomerId'         => 'nullable|exists:customers,id',
        'customerName'               => 'nullable|string|max:255',
        'customerUnit'               => 'nullable|string|max:255',
        'customerAddress'            => 'nullable|string|max:255',
        'customerContact'            => 'nullable|string|max:20',
        'orderItems'                 => 'required|array|min:1',
        'orderItems.*.product_id'    => 'required|exists:products,id',
        'orderItems.*.quantity'      => 'required|integer|min:1',
        'orderItems.*.price'         => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->receiptNumber = $this->generateReceiptNumber();
        $this->saleDate      = now()->format('Y-m-d\TH:i');
        $this->orderType     = config('storeconfig.default_order_type', 'walk_in');
        $this->addOrderItem();
    }

    // ── Livewire lifecycle ─────────────────────────────────────────

    public function updatedOrderItems($value, $key): void
    {
        $this->handleUpdatedOrderItem($value, $key);
    }

    public function updatedOrderType($value): void
    {
        if ($value === 'walk_in') {
            $this->selectedEmployeeId    = null;
            $this->selectedCustomerId    = null;
            $this->isCreatingNewCustomer = false;
            $this->customerName          = '';
            $this->customerUnit          = '';
            $this->customerAddress       = '';
            $this->customerContact       = '';
            $this->resetErrorBag(['selectedEmployeeId', 'selectedCustomerId']);
            $this->dispatch('customer-validation-clear');
        }
    }

    // ── Modal ──────────────────────────────────────────────────────

    public function openSaveConfirmation(): void
    {
        if (! $this->validateSubmissionRequirements()) {
            $this->showConfirmModal = false;
            return;
        }

        $this->dispatch('customer-validation-clear');
        $this->showConfirmModal = true;
    }

    public function closeSaveConfirmation(): void
    {
        $this->showConfirmModal = false;
    }

    public function saveSalesRecord(): void
    {
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
                $rules['customerName']     = 'required|string|max:255';
                $rules['customerContact']  = 'nullable|string|max:20';
                $rules['customerAddress']  = 'required|string|max:255';
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

            // If any customer field failed, dispatch the scroll-to event
            $customerFields = ['selectedCustomerId','customerName','customerAddress'];
            if (array_intersect($customerFields, $this->errorFields)) {
                $this->dispatch('customer-validation-error');
            }

            return false;
        }

        return true;
    }

    // ── Receipt number ─────────────────────────────────────────────

    public function generateReceiptNumber(): string
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
        if (! $this->validateSubmissionRequirements()) {
            return;
        }

        $hasItems = collect($this->orderItems)->some(fn ($i) => ! empty($i['product_id']));
        if (! $hasItems) {
            $fields = collect(array_keys($this->orderItems))
                ->map(fn ($i) => "orderItems.{$i}.product_id")
                ->values()->all();
            $this->dispatch('form-validation-failed', errorFields: $fields);
            return;
        }

        DB::transaction(function () {
            $customerId = $this->persistCustomer();

            $order = Order::create([
                'customer_id'    => $customerId,
                'created_by'     => Auth::id(),
                'delivered_by'   => $this->orderType === 'deliver' ? $this->selectedEmployeeId : null,
                'order_total'    => $this->totalAmount,
                'order_type'     => $this->orderType,
                'payment_type'   => $this->paymentType,
                'status'         => $this->status,
                'is_paid'        => $this->isPaid,
                'receipt_number' => $this->receiptNumber,
            ]);

            $saleDate = Carbon::parse($this->saleDate);
            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            foreach ($this->orderItems as $item) {
                if (! ($item['product_id'] ?? null)) continue;

                $qty       = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($item['price'] ?? 0));

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => (int) $item['product_id'],
                    'quantity'    => $qty,
                    'unit_price'  => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);
            }
        });

        $this->resetFormAfterSave();
        $this->dispatch('show-success', ['message' => __('Sales record created successfully!')]);
    }

    private function persistCustomer(): ?int
    {
        if ($this->orderType !== 'deliver') {
            return null;
        }

        if ($this->isCreatingNewCustomer) {
            $customer = Customer::create([
                'name'           => ucwords(trim($this->customerName)),
                'unit'           => ucwords(trim($this->customerUnit)),
                'address'        => ucwords(trim($this->customerAddress)),
                'contact_number' => trim($this->customerContact) ?: null,
            ]);
            return $customer->id;
        }

        if ($this->selectedCustomerId) {
            $customer = Customer::query()->whereKey($this->selectedCustomerId)->first();
            $customer?->update([
                'name'           => ucwords(trim($this->customerName)),
                'unit'           => ucwords(trim($this->customerUnit)),
                'address'        => ucwords(trim($this->customerAddress)),
                'contact_number' => trim($this->customerContact) ?: null,
            ]);
        }

        return $this->selectedCustomerId;
    }

    protected function resetFormAfterSave(): void
    {
        $this->receiptNumber        = $this->generateReceiptNumber();
        $this->saleDate             = now()->format('Y-m-d\TH:i');
        $this->orderType            = config('storeconfig.default_order_type', 'walk_in');
        $this->paymentType          = 'cash';
        $this->isPaid               = true;
        $this->status               = 'completed';
        $this->selectedEmployeeId   = null;
        $this->selectedCustomerId   = null;
        $this->isCreatingNewCustomer = false;
        $this->customerName         = '';
        $this->customerUnit         = '';
        $this->customerAddress      = '';
        $this->customerContact      = '';
        $this->customerSearch       = '';
        $this->employeeSearch       = '';
        $this->productSearch        = '';
        $this->orderItems           = [];
        $this->showConfirmModal     = false;
        $this->errorFields          = [];

        $this->resetProductForm();
        $this->addOrderItem();
        $this->resetErrorBag();
        $this->dispatch('customer-validation-clear');
    }

    public function render()
    {
        return view('livewire.order.add');
    }
}
