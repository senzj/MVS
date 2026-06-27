<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use App\Models\Paymentqr;
use App\Helpers\PaymentImageHelper;

new class extends Component {
    use WithFileUploads;

    public array $paymentQrs = [];

    // Add / Edit form (shared)
    public bool    $showForm          = false;
    public ?int    $editingId         = null;
    public string  $name              = '';
    public $image                     = null;
    public bool    $showCropper       = false;
    public ?string $croppedImageData  = null;
    public ?string $existingImagePath = null;

    // Delete confirm
    public bool $showDeleteConfirm = false;
    public ?int $deletingId        = null;

    public function mount(): void
    {
        $this->loadQrCodes();
    }

    private function loadQrCodes(): void
    {
        $this->paymentQrs = Paymentqr::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Paymentqr $qr) => [
                'id'        => $qr->id,
                'name'      => $qr->name,
                'image_url' => PaymentImageHelper::getPaymentImageUrl($qr->image),
                'is_active' => (bool) $qr->is_active,
            ])
            ->all();
    }

    public function startAdd(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $qr = Paymentqr::query()->find($id);
        if (! $qr) return;

        $this->resetForm();
        $this->editingId         = $qr->id;
        $this->name              = $qr->name;
        $this->existingImagePath = $qr->image;
        $this->showForm          = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'image', 'showCropper', 'croppedImageData', 'existingImagePath']);
    }

    public function updatedImage(): void
    {
        $this->validateOnly('image', [
            'image' => 'image|mimes:png,jpg,jpeg,webp|max:50240000',
        ]);
        if ($this->image) {
            $this->showCropper = true;
        }
    }

    public function resetUpload(): void
    {
        $this->reset(['image', 'croppedImageData', 'showCropper']);
    }

    public function setCroppedImage($imageData): void
    {
        $this->croppedImageData = $imageData;
        $this->showCropper      = false;
    }

    public function cancelCrop(): void
    {
        $this->showCropper = false;
        $this->reset(['image', 'croppedImageData']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('payment_qr', 'name')->ignore($this->editingId),
            ],
            'image' => $this->editingId
                ? 'nullable|image|mimes:png,jpg,jpeg,webp|max:50240000'
                : 'required|image|mimes:png,jpg,jpeg,webp|max:50240000',
        ]);

        $path           = $this->existingImagePath;
        $replacingImage = (bool) ($this->croppedImageData || $this->image);

        if ($replacingImage) {
            $newPath = $this->storeImage();

            if ($this->editingId && $this->existingImagePath) {
                Storage::disk('public')->delete($this->existingImagePath);
            }

            $path = $newPath;
        }

        if (! $path) {
            $this->addError('image', __('Please upload a QR code image.'));
            return;
        }

        if ($this->editingId) {
            Paymentqr::query()->whereKey($this->editingId)->update([
                'name'  => $this->name,
                'image' => $path,
            ]);
            session()->flash('message', __('Payment QR updated successfully.'));
        } else {
            Paymentqr::create([
                'name'      => $this->name,
                'image'     => $path,
                'is_active' => true,
            ]);
            session()->flash('message', __('Payment QR added successfully.'));
        }

        $this->cancelForm();
        $this->loadQrCodes();
    }

    private function storeImage(): string
    {
        $slug = Str::slug($this->name) ?: 'qr';

        if ($this->croppedImageData) {
            $imageData    = str_replace('data:image/png;base64,', '', $this->croppedImageData);
            $imageData    = str_replace(' ', '+', $imageData);
            $decodedImage = base64_decode($imageData);
            $filename     = $slug . '-' . now()->format('YmdHis') . '.png';
            $path         = 'image/payment/' . $filename;
            Storage::disk('public')->put($path, $decodedImage);
            return $path;
        }

        $ext      = $this->image->getClientOriginalExtension();
        $filename = $slug . '-' . now()->format('YmdHis') . '.' . $ext;

        return $this->image->storeAs('image/payment', $filename, 'public');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId        = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $qr = Paymentqr::query()->find($this->deletingId);

        if ($qr) {
            Storage::disk('public')->delete($qr->image);
            $qr->delete();
            session()->flash('message', __('Payment QR deleted.'));
        }

        $this->deletingId        = null;
        $this->showDeleteConfirm = false;
        $this->loadQrCodes();
    }

    public function cancelDelete(): void
    {
        $this->deletingId        = null;
        $this->showDeleteConfirm = false;
    }

    public function toggleActive(int $id): void
    {
        $qr = Paymentqr::query()->find($id);
        $qr?->update(['is_active' => ! $qr->is_active]);
        $this->loadQrCodes();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout>

        {{-- ── Page header ──────────────────────────────────────────── --}}
        <div class="rounded-2xl border border-blue-100 dark:border-blue-900/50
                    bg-gradient-to-br from-blue-50 via-indigo-50/60 to-white
                    dark:from-blue-950/40 dark:via-indigo-950/20 dark:to-zinc-900/0
                    p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-4">

                {{-- Icon + copy --}}
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div class="shrink-0 p-2.5 sm:p-3 rounded-xl
                                bg-blue-100 dark:bg-blue-900/50
                                ring-1 ring-blue-200 dark:ring-blue-800/60">
                        <i class="fa-solid fa-qrcode text-xl sm:text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
                            {{ __('QR Code Payment Settings') }}
                        </h2>
                        <p class="mt-1 text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                            {{ __('One QR per payment account. The cashier picks which to show at checkout.') }}
                        </p>
                    </div>
                </div>

                {{-- Add button --}}
                @unless($showForm)
                    <button wire:click="startAdd"
                            class="cursor-pointer self-start shrink-0 inline-flex items-center gap-2
                                   px-4 py-2.5 rounded-xl text-sm font-semibold
                                   bg-blue-600 hover:bg-blue-700 active:scale-95
                                   dark:bg-blue-500 dark:hover:bg-blue-600
                                   text-white shadow-sm shadow-blue-500/25
                                   transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>{{ __('Add QR') }}</span>
                    </button>
                @endunless
            </div>
        </div>

        {{-- ── Flash / Error banners ────────────────────────────────── --}}
        @if (session()->has('message'))
            <div class="flex items-center gap-3 px-4 py-3 mb-5 rounded-xl
                        bg-emerald-50 dark:bg-emerald-900/20
                        border border-emerald-200 dark:border-emerald-800/60
                        text-emerald-800 dark:text-emerald-300"
                 x-data x-init="setTimeout(() => $el.remove(), 4000)">
                <i class="fa-solid fa-circle-check text-emerald-500 dark:text-emerald-400 shrink-0"></i>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
        @endif

        @if ($errors->has('name') || $errors->has('image'))
            <div class="flex items-start gap-3 px-4 py-3 mb-5 rounded-xl
                        bg-red-50 dark:bg-red-900/20
                        border border-red-200 dark:border-red-800/60
                        text-red-800 dark:text-red-300">
                <i class="fa-solid fa-triangle-exclamation text-red-500 dark:text-red-400 shrink-0 mt-0.5"></i>
                <span class="text-sm font-medium">{{ $errors->first('name') ?: $errors->first('image') }}</span>
            </div>
        @endif

        {{-- ── Add / Edit form ──────────────────────────────────────── --}}
        @if ($showForm)
            <div class="mb-6 rounded-2xl border
                        border-zinc-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-900
                        shadow-sm overflow-hidden"
                 x-data x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Form header --}}
                <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4
                            border-b border-zinc-100 dark:border-zinc-800
                            bg-zinc-50/80 dark:bg-zinc-800/50">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/50">
                            <i class="fa-solid fa-{{ $editingId ? 'pen' : 'cloud-arrow-up' }} text-sm text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <h3 class="text-sm sm:text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $editingId ? __('Edit Payment QR') : __('Add Payment QR') }}
                        </h3>
                    </div>
                    <button type="button" wire:click="cancelForm"
                            class="cursor-pointer w-8 h-8 inline-flex items-center justify-center rounded-lg
                                   text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300
                                   hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fa-solid fa-times text-sm"></i>
                    </button>
                </div>

                {{-- Form body --}}
                <form wire:submit.prevent="save"
                      class="p-4 sm:p-6 space-y-5"
                      enctype="multipart/form-data">

                    {{-- Account name --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            {{ __('Account Name') }}
                        </label>
                        <input type="text"
                               wire:model="name"
                               placeholder="{{ __('e.g. GCash – Juan Dela Cruz') }}"
                               autocomplete="off"
                               class="w-full rounded-xl border px-3.5 py-2.5 text-sm
                                      border-zinc-300 dark:border-zinc-600
                                      bg-white dark:bg-zinc-800
                                      text-zinc-900 dark:text-zinc-100
                                      placeholder-zinc-400 dark:placeholder-zinc-500
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 dark:focus:border-blue-500
                                      transition-colors">
                        @error('name')
                            <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('Shown in cashier dropdown') }}
                        </p>
                    </div>

                    {{-- Current image (edit mode) --}}
                    @if ($editingId && $existingImagePath && !$image && !$croppedImageData)
                        <div class="flex items-center gap-4 p-1 rounded-xl
                                    bg-zinc-50 dark:bg-zinc-800/60
                                    border border-zinc-200 dark:border-zinc-700">
                            <img src="{{ PaymentImageHelper::getPaymentImageUrl($existingImagePath) }}"
                                 class="w-52 h-52 object-contain rounded-lg
                                        border border-zinc-200 dark:border-zinc-700
                                        bg-white dark:bg-zinc-900"
                                 alt="{{ __('Current QR') }}">
                            <div>
                                <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    {{ __('Current QR Image') }}
                                </p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                    {{ __('Upload below to replace it.') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Upload zone --}}
                    @if (!$showCropper && !$croppedImageData)
                        <div x-data="{
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files && files[0]) {
                                        $refs.fileInput.files = files;
                                        $refs.fileInput.dispatchEvent(new Event('input'));
                                    }
                                }
                             }"
                             x-on:dragover.prevent="dragging = true"
                             x-on:dragleave.prevent="dragging = false"
                             x-on:drop.prevent="handleDrop($event)"
                             x-on:click="$refs.fileInput.click()"
                             :class="dragging
                                ? 'border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20 scale-[1.01]'
                                : 'border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/40 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-900/10'"
                             class="relative border-2 border-dashed rounded-xl
                                    p-6 sm:p-8 text-center cursor-pointer
                                    transition-all duration-200 min-h-[140px] sm:min-h-[160px]
                                    flex flex-col items-center justify-center gap-3">

                            <input type="file" x-ref="fileInput" wire:model="image"
                                   accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden">

                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 ring-1 ring-blue-200 dark:ring-blue-800/60">
                                <i class="fa-solid fa-cloud-arrow-up text-lg text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    <span class="text-blue-600 dark:text-blue-400 underline underline-offset-2">{{ __('Click to browse') }}</span>
                                    <span class="hidden sm:inline"> {{ __('or drag & drop') }}</span>
                                </p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('PNG, JPG, JPEG, WEBP · Max 100 MB') }}</p>
                            </div>
                        </div>

                        @error('image')
                            <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1 -mt-2">
                                <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    @endif

                    {{-- Upload loading --}}
                    <div wire:loading.flex wire:target="image"
                         class="hidden items-center gap-3 px-4 py-3 rounded-xl
                                bg-blue-50 dark:bg-blue-900/20
                                border border-blue-200 dark:border-blue-800/50">
                        <i class="fa-solid fa-spinner fa-spin text-blue-600 dark:text-blue-400"></i>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Processing image') }}</span>
                    </div>

                    {{-- Cropped preview --}}
                    @if ($croppedImageData)
                        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60
                                    bg-emerald-50 dark:bg-emerald-900/10 p-4 sm:p-5">
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-crop-simple"></i>{{ __('Cropped Image Preview') }}
                            </p>
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <img src="{{ $croppedImageData }}"
                                     class="w-full max-w-40 sm:max-w-50 h-auto rounded-lg
                                            border border-white dark:border-zinc-700 shadow-md object-contain
                                            bg-white dark:bg-zinc-900">
                                <div class="flex flex-col gap-2 w-full sm:w-auto">
                                    <button type="button" wire:click="$set('showCropper', true)"
                                            class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2
                                                   rounded-lg text-sm font-medium
                                                   bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700
                                                   text-zinc-700 dark:text-zinc-300
                                                   hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                        <i class="fa-solid fa-crop text-xs"></i>{{ __('Crop Image') }}
                                    </button>
                                    <button type="button" wire:click="resetUpload"
                                            class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2
                                                   rounded-lg text-sm font-medium
                                                   text-red-600 dark:text-red-400
                                                   hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <i class="fa-solid fa-trash text-xs"></i>{{ __('Remove') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Form actions --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-1">
                        <button type="button" wire:click="cancelForm"
                                class="cursor-pointer inline-flex items-center justify-center gap-2
                                       px-5 py-2.5 rounded-xl text-sm font-semibold
                                       border border-zinc-300 dark:border-zinc-600
                                       bg-white dark:bg-zinc-800
                                       text-zinc-700 dark:text-zinc-300
                                       hover:bg-zinc-50 dark:hover:bg-zinc-700
                                       transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="cursor-pointer inline-flex items-center justify-center gap-2
                                       px-5 py-2.5 rounded-xl text-sm font-semibold
                                       bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                                       text-white shadow-sm shadow-blue-500/25
                                       disabled:opacity-60 disabled:cursor-not-allowed
                                       transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <i class="fa-solid fa-check text-xs"></i>{{ __('Save') }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin text-xs"></i>{{ __('Saving') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ── QR code grid ─────────────────────────────────────────── --}}
        @if (empty($paymentQrs) && !$showForm)
            <div class="flex flex-col items-center justify-center gap-3 py-14 sm:py-20
                        rounded-2xl border-2 border-dashed
                        border-zinc-200 dark:border-zinc-700
                        bg-zinc-50 dark:bg-zinc-800/30 text-center px-4">
                <div class="p-4 rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <i class="fa-solid fa-qrcode text-3xl text-zinc-300 dark:text-zinc-600"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('No QR codes yet') }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('Add your first payment account to get started.') }}</p>
                </div>
                <button wire:click="startAdd"
                        class="cursor-pointer mt-1 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                               bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                               text-white shadow-sm transition-all active:scale-95">
                    <i class="fa-solid fa-plus text-xs"></i>{{ __('Add QR Code') }}
                </button>
            </div>

        @elseif (!empty($paymentQrs))
            <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-2 gap-2">
                @foreach ($paymentQrs as $qr)
                    <div class="group flex flex-col rounded-2xl border transition-all duration-200
                                {{ $qr['is_active']
                                    ? 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:border-blue-200 dark:hover:border-blue-800/60 hover:shadow-md dark:hover:shadow-blue-900/10'
                                    : 'border-zinc-200 dark:border-zinc-700/50 bg-zinc-50 dark:bg-zinc-900/50 opacity-60' }}">

                        {{-- QR Image --}}
                        <div class="flex items-center justify-center
                                    bg-zinc-50 dark:bg-zinc-800/60 rounded-t-2xl
                                    border-b border-zinc-100 dark:border-zinc-800">
                            <img src="{{ $qr['image_url'] }}"
                                 alt="{{ $qr['name'] }}"
                                 class="w-64 h-64 object-contain rounded-lg
                                        bg-white dark:bg-zinc-900
                                        border border-zinc-100 dark:border-zinc-700">
                        </div>

                        {{-- Card body --}}
                        <div class="flex flex-col flex-1 p-3 sm:p-4 gap-3">

                            {{-- Name + badge --}}
                            <div class="flex items-start justify-between gap-2 min-w-0">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate leading-snug">
                                    {{ $qr['name'] }}
                                </p>
                                <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                    {{ $qr['is_active']
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400' }}">
                                    {{ $qr['is_active'] ? __('Active') : __('Disabled') }}
                                </span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1.5 mt-auto justify-between">
                                {{-- Toggle --}}
                                <button wire:click="toggleActive({{ $qr['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleActive({{ $qr['id'] }})"
                                        title="{{ $qr['is_active'] ? __('Disable') : __('Enable') }}"
                                        class="cursor-pointer flex-1 inline-flex items-center justify-center gap-1.5
                                               px-3 py-2 rounded-xl text-xs font-semibold
                                               border transition-colors
                                               {{ $qr['is_active']
                                                    ? 'border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
                                                    : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                                    <i class="fa-solid fa-{{ $qr['is_active'] ? 'toggle-on' : 'toggle-off' }} text-sm"></i>
                                    <span class="hidden xs:inline">{{ $qr['is_active'] ? __('Disable') : __('Enable') }}</span>
                                </button>

                                {{-- Edit --}}
                                <button wire:click="startEdit({{ $qr['id'] }})"
                                        title="{{ __('Edit') }}"
                                        class="cursor-pointer inline-flex items-center justify-center
                                               w-9 h-9 rounded-xl border text-xs
                                               border-blue-200 dark:border-blue-800/60
                                               text-blue-600 dark:text-blue-400
                                               hover:bg-blue-50 dark:hover:bg-blue-900/20
                                               transition-colors">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                {{-- Delete --}}
                                <button wire:click="confirmDelete({{ $qr['id'] }})"
                                        title="{{ __('Delete') }}"
                                        class="cursor-pointer inline-flex items-center justify-center
                                               w-9 h-9 rounded-xl border text-xs
                                               border-red-200 dark:border-red-800/60
                                               text-red-500 dark:text-red-400
                                               hover:bg-red-50 dark:hover:bg-red-900/20
                                               transition-colors">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── Crop modal ────────────────────────────────────────────── --}}
        @if($showCropper && $image)
            <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                        bg-black/60 dark:bg-black/75 backdrop-blur-sm p-0 sm:p-4"
                 x-data x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">

                <div class="relative w-full sm:max-w-3xl
                            bg-white dark:bg-zinc-900
                            rounded-t-2xl sm:rounded-2xl shadow-2xl
                            max-h-[92dvh] sm:max-h-[90vh]
                            flex flex-col overflow-hidden"
                     x-transition:enter="transition ease-out duration-250"
                     x-transition:enter-start="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100">

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4
                                border-b border-zinc-200 dark:border-zinc-800
                                bg-zinc-50 dark:bg-zinc-800/60 shrink-0">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-crop text-blue-500 dark:text-blue-400"></i>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Crop Image') }}</h3>
                        </div>
                        <button wire:click="cancelCrop"
                                class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-lg
                                       text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200
                                       hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>

                    {{-- Cropper canvas --}}
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6"
                         x-data="{
                            canvas: null, ctx: null, img: null, isDrawing: false,
                            startX: 0, startY: 0, currentX: 0, currentY: 0,
                            cropX: 0, cropY: 0, cropWidth: 0, cropHeight: 0,
                            initCropper() {
                                this.canvas = this.$refs.cropCanvas;
                                this.ctx    = this.canvas.getContext('2d');
                                this.img    = new Image();
                                this.img.onload = () => {
                                    const maxW = Math.min(560, window.innerWidth - 48);
                                    const maxH = Math.min(380, window.innerHeight * 0.45);
                                    let { width, height } = this.img;
                                    if (width > maxW)  { height = (height * maxW)  / width;  width  = maxW;  }
                                    if (height > maxH) { width  = (width  * maxH)  / height; height = maxH;  }
                                    this.canvas.width  = Math.round(width);
                                    this.canvas.height = Math.round(height);
                                    this.cropX = 0; this.cropY = 0;
                                    this.cropWidth = this.canvas.width; this.cropHeight = this.canvas.height;
                                    this.drawImage();
                                    this.drawCropRect();
                                };
                                this.img.src = '{{ $image->temporaryUrl() }}';
                            },
                            drawImage() {
                                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                this.ctx.drawImage(this.img, 0, 0, this.canvas.width, this.canvas.height);
                            },
                            drawCropRect() {
                                this.drawImage();
                                this.ctx.fillStyle = 'rgba(0,0,0,0.48)';
                                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                                this.ctx.clearRect(this.cropX, this.cropY, this.cropWidth, this.cropHeight);
                                this.ctx.drawImage(
                                    this.img,
                                    (this.cropX / this.canvas.width) * this.img.naturalWidth,
                                    (this.cropY / this.canvas.height) * this.img.naturalHeight,
                                    (this.cropWidth / this.canvas.width) * this.img.naturalWidth,
                                    (this.cropHeight / this.canvas.height) * this.img.naturalHeight,
                                    this.cropX, this.cropY, this.cropWidth, this.cropHeight
                                );
                                this.ctx.strokeStyle = '#3b82f6';
                                this.ctx.lineWidth = 2;
                                this.ctx.strokeRect(this.cropX, this.cropY, this.cropWidth, this.cropHeight);
                                const hs = 8;
                                this.ctx.fillStyle = '#3b82f6';
                                [[this.cropX, this.cropY],[this.cropX+this.cropWidth, this.cropY],
                                 [this.cropX, this.cropY+this.cropHeight],[this.cropX+this.cropWidth, this.cropY+this.cropHeight]]
                                    .forEach(([x,y]) => this.ctx.fillRect(x-hs/2, y-hs/2, hs, hs));
                            },
                            startCrop(e) {
                                const rect = this.canvas.getBoundingClientRect();
                                const scaleX = this.canvas.width / rect.width;
                                const scaleY = this.canvas.height / rect.height;
                                this.startX = (e.clientX - rect.left) * scaleX;
                                this.startY = (e.clientY - rect.top)  * scaleY;
                                this.isDrawing = true;
                            },
                            updateCrop(e) {
                                if (!this.isDrawing) return;
                                const rect = this.canvas.getBoundingClientRect();
                                const scaleX = this.canvas.width / rect.width;
                                const scaleY = this.canvas.height / rect.height;
                                this.currentX = Math.max(0, Math.min((e.clientX - rect.left) * scaleX, this.canvas.width));
                                this.currentY = Math.max(0, Math.min((e.clientY - rect.top)  * scaleY, this.canvas.height));
                                this.cropX = Math.min(this.startX, this.currentX);
                                this.cropY = Math.min(this.startY, this.currentY);
                                this.cropWidth  = Math.abs(this.currentX - this.startX);
                                this.cropHeight = Math.abs(this.currentY - this.startY);
                                this.drawCropRect();
                            },
                            endCrop() { this.isDrawing = false; },
                            applyCrop() {
                                if (this.cropWidth < 10 || this.cropHeight < 10) {
                                    alert('{{ __('Please select a larger area to crop.') }}');
                                    return;
                                }
                                const tmp = document.createElement('canvas');
                                const tc  = tmp.getContext('2d');
                                const sX  = this.img.naturalWidth  / this.canvas.width;
                                const sY  = this.img.naturalHeight / this.canvas.height;
                                tmp.width  = this.cropWidth  * sX;
                                tmp.height = this.cropHeight * sY;
                                tc.drawImage(this.img, this.cropX*sX, this.cropY*sY, tmp.width, tmp.height, 0, 0, tmp.width, tmp.height);
                                $wire.setCroppedImage(tmp.toDataURL('image/png'));
                            },
                            resetCrop() {
                                this.cropX = 0; this.cropY = 0;
                                this.cropWidth = this.canvas.width; this.cropHeight = this.canvas.height;
                                this.drawCropRect();
                            }
                         }"
                         x-init="$nextTick(() => initCropper())">

                        <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center mb-4">
                            {{ __('Click and drag on the image to select the crop area.') }}
                        </p>

                        {{-- Scrollable canvas wrapper --}}
                        <div class="w-full overflow-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 flex justify-center">
                            <canvas x-ref="cropCanvas"
                                    x-on:mousedown="startCrop($event)"
                                    x-on:mousemove="updateCrop($event)"
                                    x-on:mouseup="endCrop()"
                                    x-on:mouseleave="endCrop()"
                                    class="cursor-crosshair max-w-full"></canvas>
                        </div>

                        {{-- Crop buttons --}}
                        <div class="flex flex-col sm:flex-row gap-2.5 justify-center mt-4">
                            <button type="button" wire:click="cancelCrop"
                                    class="cursor-pointer inline-flex items-center justify-center gap-2
                                           px-5 py-2.5 rounded-xl text-sm font-semibold
                                           border border-red-200 dark:border-red-800/60
                                           text-red-600 dark:text-red-400
                                           hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <i class="fa-solid fa-times text-xs"></i>{{ __('Cancel') }}
                            </button>
                            <button type="button" x-on:click="resetCrop()"
                                    class="cursor-pointer inline-flex items-center justify-center gap-2
                                           px-5 py-2.5 rounded-xl text-sm font-semibold
                                           border border-zinc-300 dark:border-zinc-600
                                           text-zinc-700 dark:text-zinc-300
                                           bg-white dark:bg-zinc-800
                                           hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                <i class="fa-solid fa-expand text-xs"></i>{{ __('Reset') }}
                            </button>
                            <button type="button" x-on:click="applyCrop()"
                                    class="cursor-pointer inline-flex items-center justify-center gap-2
                                           px-5 py-2.5 rounded-xl text-sm font-semibold
                                           bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
                                           text-white shadow-sm transition-all active:scale-95">
                                <i class="fa-solid fa-check text-xs"></i>{{ __('Apply Crop') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Delete confirmation modal ─────────────────────────────── --}}
        @if($showDeleteConfirm)
            <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center
                        bg-black/60 dark:bg-black/75 backdrop-blur-sm p-0 sm:p-4"
                 wire:click.self="cancelDelete"
                 x-data x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">

                <div class="relative w-full sm:max-w-md
                            bg-white dark:bg-zinc-900
                            rounded-t-2xl sm:rounded-2xl shadow-2xl
                            overflow-hidden"
                     x-transition:enter="transition ease-out duration-250"
                     x-transition:enter-start="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100">

                    {{-- Drag handle (mobile) --}}
                    <div class="flex justify-center pt-3 pb-1 sm:hidden">
                        <div class="w-10 h-1 rounded-full bg-zinc-300 dark:bg-zinc-600"></div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        {{-- Icon + title --}}
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 p-2.5 rounded-xl bg-red-100 dark:bg-red-900/30 ring-1 ring-red-200 dark:ring-red-800/50">
                                <i class="fa-solid fa-triangle-exclamation text-red-600 dark:text-red-400 text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ __('Delete Payment QR') }}
                                </h4>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                    {{ __('This QR code will be permanently removed. This action cannot be undone.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex flex-col-reverse sm:flex-row gap-2.5">
                            <button wire:click="cancelDelete"
                                    class="cursor-pointer flex-1 inline-flex items-center justify-center px-5 py-2.5
                                           rounded-xl text-sm font-semibold
                                           border border-zinc-300 dark:border-zinc-600
                                           bg-white dark:bg-zinc-800
                                           text-zinc-700 dark:text-zinc-300
                                           hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                {{ __('Keep it') }}
                            </button>
                            <button wire:click="delete"
                                    wire:loading.attr="disabled"
                                    wire:target="delete"
                                    class="cursor-pointer flex-1 inline-flex items-center justify-center gap-2
                                           px-5 py-2.5 rounded-xl text-sm font-semibold
                                           bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600
                                           text-white shadow-sm transition-all active:scale-95
                                           disabled:opacity-60 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="delete" class="flex items-center gap-2">
                                    <i class="fa-solid fa-trash text-xs"></i>{{ __('Yes, delete') }}
                                </span>
                                <span wire:loading wire:target="delete" class="flex items-center gap-2">
                                    <i class="fa-solid fa-spinner fa-spin text-xs"></i>{{ __('Deleting…') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </x-settings.layout>
</section>
