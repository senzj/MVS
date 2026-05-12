{{--
    History page order details modal.
    Uses the universal order modal in 'view' mode.
    Delete button is injected via the header slot override is not possible,
    so we layer it on top via absolute positioning within the modal trigger wrapper.
--}}
{{-- In the view mode branch inside modal/index.blade.php --}}
@include('livewire.partials.orders.modal.order', [
    'modalMode'     => 'view',
    'selectedOrder' => $selectedOrder ?? null,
    'showDelete'    => true,
])

{{-- Delete confirmation modal --}}
@include('livewire.partials.orders.modal.delete')

{{-- Loading overlay --}}
@include('livewire.partials.loading-overlay', [
    'wireTarget' => implode(', ', [
        'openOrder',
        'closeOrderDetailsModal',
        'deleteOrderConfirmed',
        'loadMore',
        'search',
        'statusFilter',
        'paymentFilter',
        'yearFilter',
    ])
])
