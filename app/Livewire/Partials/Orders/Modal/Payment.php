<?php
namespace App\Livewire\Partials\Orders\Modal;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class Payment extends Component
{
    use WithFileUploads;

    public bool $show   = false;
    public ?int $orderId = null;
    public ?Order $order = null;

    public ?float $amountReceived = null;
    public $proofOfPayment        = null;

    protected $listeners = [
        'openPaymentModal' => 'open',
    ];

    protected $rules = [
        'amountReceived' => 'required|numeric|min:0',
        'proofOfPayment' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
    ];

    public function open(int $orderId): void
    {
        $this->resetValidation();
        $this->proofOfPayment = null;

        $this->orderId = $orderId;
        $this->order   = Order::with('customer')->find($orderId);

        if (! $this->order) {
            $this->show = false;
            return;
        }

        $this->amountReceived = $this->order->amount_received ?? $this->order->order_total;
        $this->show = true;
    }

    // Called by x-on:click="$wire.close()" in the blade
    public function close(): void
    {
        $this->show    = false;
        $this->orderId = null;
        $this->order   = null;
        $this->amountReceived = null;
        $this->proofOfPayment = null;
        $this->resetValidation();
    }

    public function updatedProofOfPayment(): void
    {
        $this->validateOnly('proofOfPayment');
    }

    public function confirmPayment(): void
    {
        if (! $this->order) return;

        $this->validate();

        DB::transaction(function () {
            $proofPath = $this->order->proof_of_payment;

            if ($this->order->payment_type === 'gcash' && $this->proofOfPayment) {
                $ext       = strtolower($this->proofOfPayment->getClientOriginalExtension() ?: 'png');
                $dir       = 'order/' . ($this->order->receipt_number ?? uniqid());
                $proofPath = $this->proofOfPayment->storeAs(
                    $dir,
                    ($this->order->receipt_number ?? uniqid()) . '.' . $ext,
                    'public'
                );
            }

            $this->order->payment_status  = 'paid';
            $this->order->amount_received = $this->amountReceived ?? $this->order->order_total;
            $this->order->change_amount   = max(0, ($this->amountReceived ?? 0) - ($this->order->order_total ?? 0));

            if ($proofPath) {
                $this->order->proof_of_payment = $proofPath;
            }

            $this->order->save();
        });

        $receipt = $this->order->receipt_number;

        $this->close();

        $this->dispatch('show-success', message: __('Payment confirmed for order ":receipt"!', [
            'receipt' => $receipt,
        ]));

        $this->dispatch('orderPaymentConfirmed');
    }

    public function render()
    {
        return view('livewire.partials.orders.modal.payment');
    }
}
