<?php
namespace App\Livewire\Order;

use App\Livewire\Concerns\HasConfirmData;
use App\Livewire\Concerns\HasOrderForm;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Serives\System\AuditLogsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Add extends Component
{
    use HasOrderForm, HasConfirmData, WithFileUploads;

    // ── Form state ─────────────────────────────────────────────────
    public string  $receiptNumber         = '';
    public string  $saleDate              = '';
    public string  $orderType             = 'walk_in';
    public string  $paymentType           = 'cash';

    /**
     * payment_status: 'unpaid' | 'paid' | 'refunded'
     * Replaces the old boolean $isPaid.
     */
    public string  $paymentStatus         = 'paid';

    public string  $status                = 'completed';
    public ?int    $selectedEmployeeId    = null;
    public ?int    $selectedCustomerId    = null;
    public bool    $isCreatingNewCustomer = false;
    public string  $customerName          = '';
    public string  $customerUnit          = '';
    public string  $customerAddress       = '';
    public string  $customerContact       = '';
    public string  $customerSearch        = '';
    public string  $employeeSearch        = '';
    public string  $productSearch         = '';
    public array   $orderItems            = [];
    public array   $errorFields           = [];
    public bool    $showConfirmModal      = false;

    public array $confirmData = [];

    // Proof of payment
    public $proofOfPayment = null;

    // Product form
    public bool         $showProductForm    = false;
    public ?int         $productTargetIndex = null;
    public string       $productName        = '';
    public string       $productDescription = '';
    public string       $productCategory    = 'other';
    public string|int   $productStocks      = 1;
    public string|float $productPrice       = 0;
    public string       $defaultPaymentType  = 'cash';

    protected $rules = [
        'receiptNumber'              => 'required|string|max:255|unique:orders,receipt_number',
        'saleDate'                   => 'required|date',
        'orderType'                  => 'required|in:deliver,walk_in',
        'paymentType'                => 'required|string',
        'paymentStatus'              => 'required|in:unpaid,paid,refunded',
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
        'orderItems.*.discount'      => 'nullable|numeric|min:0',
        'proofOfPayment'             => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
    ];

    public function mount(): void
    {
        $this->receiptNumber = $this->generateReceiptNumber();
        $this->saleDate      = now()->format('Y-m-d\TH:i');
        $this->orderType     = config('storeconfig.default_order_type', 'walk_in');
        $this->addOrderItem();
    }

    // ── Lifecycle ──────────────────────────────────────────────────

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
            $this->customerName = $this->customerUnit = $this->customerAddress = $this->customerContact = '';
            $this->resetErrorBag(['selectedEmployeeId', 'selectedCustomerId']);
            $this->dispatch('customer-validation-clear');
        }
    }

    public function updatedPaymentType(): void
    {
        // Clear proof when switching away from GCash
        if ($this->paymentType !== 'gcash') {
            $this->proofOfPayment = null;
        }
    }

    public function updatedProofOfPayment(): void
    {
        $this->validateOnly('proofOfPayment', [
            'proofOfPayment' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
        ]);
    }

    public function removeProof(): void
    {
        $this->proofOfPayment = null;
        $this->resetErrorBag(['proofOfPayment']);
    }

    // ── Modal ──────────────────────────────────────────────────────

    public function openSaveConfirmation(): void
    {
        if (! $this->validateSubmissionRequirements()) {
            $this->showConfirmModal = false;
            return;
        }

        $this->dispatch('customer-validation-clear');
        $this->confirmData = $this->buildConfirmData();
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
        // Validate paymentType against configured types
        $other = config('storeconfig.other_payment_types', []);
        $other = is_array($other) ? $other : array_filter(array_map('trim', explode(',', (string) $other)));
        $allowed = array_unique(array_merge(['cash'], array_values($other)));
        $rules['paymentType'] = 'required|in:' . implode(',', $allowed);

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

            $customerFields = ['selectedCustomerId', 'customerName', 'customerAddress'];
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
            ->latest('id')
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
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

        // Store proof image
        $proofPath = null;
        if ($this->paymentType === 'gcash' && $this->proofOfPayment) {
            $ext       = strtolower($this->proofOfPayment->getClientOriginalExtension() ?: 'png');
            $dir       = 'order/' . $this->receiptNumber;
            $proofPath = $this->proofOfPayment->storeAs($dir, $this->receiptNumber . '.' . $ext, 'public');

            // Auto-upgrade to paid if proof uploaded
            if ($this->paymentStatus === 'unpaid') {
                $this->paymentStatus = 'paid';
            }
        }

        DB::transaction(function () use ($proofPath) {
            $customerId = $this->persistCustomer();

            $order = Order::create([
                'customer_id'      => $customerId,
                'created_by'       => Auth::id(),
                'delivered_by'     => $this->orderType === 'deliver' ? $this->selectedEmployeeId : null,
                'order_total'      => $this->totalAmount,
                'order_type'       => $this->orderType,
                'payment_type'     => $this->paymentType,
                'payment_status'   => $this->paymentStatus,
                'status'           => $this->status,
                'receipt_number'   => $this->receiptNumber,
                'proof_of_payment' => $proofPath,
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
                $discount  = min(max(0, (float) ($item['discount'] ?? 0)), $qty * $unitPrice);
                $lineTotal = max(0, ($qty * $unitPrice) - $discount);

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => (int) $item['product_id'],
                    'quantity'    => $qty,
                    'unit_price'  => $unitPrice,
                    'discount_amount' => $discount,
                    'total_price' => $lineTotal,
                ]);
            }

            // ── Audit ──────────────────────────────────────────────────────
            // Use 'order.backdated' to distinguish manual sales records from live orders
            app(AuditLogsService::class)->record(
                'order.backdated',
                Auth::user(),
                $order,
                [],
                [
                    'receipt_number' => $order->receipt_number,
                    'order_type'     => $order->order_type,
                    'order_total'    => $order->order_total,
                    'payment_type'   => $order->payment_type,
                    'payment_status' => $order->payment_status,
                    'status'         => $order->status,
                    'sale_date'      => $this->saleDate,
                ],
                request()
            );
        });

        $this->resetFormAfterSave();
        $this->dispatch('show-success', ['message' => __('Sales record created successfully!')]);
    }

    private function persistCustomer(): ?int
    {
        if ($this->orderType !== 'deliver') return null;

        if ($this->isCreatingNewCustomer) {
            $c = Customer::create([
                'name'           => ucwords(trim($this->customerName)),
                'unit'           => ucwords(trim($this->customerUnit)),
                'address'        => ucwords(trim($this->customerAddress)),
                'contact_number' => trim($this->customerContact) ?: null,
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

    protected function resetFormAfterSave(): void
    {
        $this->receiptNumber         = $this->generateReceiptNumber();
        $this->saleDate              = now()->format('Y-m-d\TH:i');
        $this->orderType             = config('storeconfig.default_order_type', 'walk_in');
        $this->paymentType           = 'cash';
        $this->paymentStatus         = 'paid';
        $this->status                = 'completed';
        $this->selectedEmployeeId    = null;
        $this->selectedCustomerId    = null;
        $this->isCreatingNewCustomer = false;
        $this->customerName          = '';
        $this->customerUnit          = '';
        $this->customerAddress       = '';
        $this->customerContact       = '';
        $this->customerSearch        = '';
        $this->employeeSearch        = '';
        $this->productSearch         = '';
        $this->orderItems            = [];
        $this->showConfirmModal      = false;
        $this->errorFields           = [];
        $this->proofOfPayment        = null;

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
