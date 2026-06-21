@section('title', __('Record Sales'))

<div class="w-full max-w-full overflow-hidden px-2 sm:px-4">

    {{-- Header --}}
    <div class="flex items-center justify-between py-2 mb-2 gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-file-invoice text-green-500"></i>{{ __('Record Sales') }}
            </h2>
            @include('livewire.partials.clock')
        </div>
        <a href="{{ route('orders') }}" wire:navigate
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg shrink-0
                   bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600
                   text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span class="hidden sm:inline">{{ __('Back') }}</span>
        </a>
    </div>

    <form wire:submit.prevent="openSaveConfirmation">
        @include('livewire.partials.orders.layout.order', ['pageMode' => 'record'])
    </form>

    @include('livewire.partials.orders.modal.order', [
        'modalMode'   => 'confirm',
        'confirmData' => $confirmData,
    ])

    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'saveSalesRecord,createProduct,selectCustomer,selectEmployee,openProductForm,closeProductForm',
    ])

    @include('livewire.partials.form-error-handler')
</div>
