<?php

use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    use WithFileUploads;

    public $image;
    public $currentImage = '';
    public $showDeleteConfirm = false;
    public $uploadProgress = 0;
    public $showCropper = false;
    public $croppedImageData = null;

    protected $rules = [
        'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
    ];

    public function mount()
    {
        $files = Storage::disk('public')->files('image/gcash');
        $this->currentImage = collect($files)->first() ?? '';
    }

    public function updatedImage()
    {
        $this->validateOnly('image');
        if ($this->image) {
            $this->showCropper = true;
        }
    }

    public function setCroppedImage($imageData)
    {
        $this->croppedImageData = $imageData;
        $this->showCropper = false;
    }

    public function cancelCrop()
    {
        $this->showCropper = false;
        $this->reset(['image', 'croppedImageData']);
    }

    public function save()
    {
        $this->validate();

        // Remove previous file(s)
        $oldFiles = Storage::disk('public')->files('image/gcash');
        foreach ($oldFiles as $f) {
            Storage::disk('public')->delete($f);
        }

        // Handle cropped image or original
        if ($this->croppedImageData) {
            // Process base64 cropped image
            $imageData = str_replace('data:image/png;base64,', '', $this->croppedImageData);
            $imageData = str_replace(' ', '+', $imageData);
            $decodedImage = base64_decode($imageData);
            
            $filename = 'gcash-' . now()->format('YmdHis') . '.png';
            $path = 'image/gcash/' . $filename;
            
            Storage::disk('public')->put($path, $decodedImage);
            $this->currentImage = $path;
        } else {
            // Process original image
            $ext = $this->image->getClientOriginalExtension();
            $filename = 'gcash-' . now()->format('YmdHis') . '.' . $ext;
            $path = $this->image->storeAs('image/gcash', $filename, 'public');
            $this->currentImage = $path;
        }

        $this->reset(['image', 'croppedImageData']);
        session()->flash('message', 'GCash image uploaded successfully.');
    }

    public function confirmDelete()
    {
        $this->showDeleteConfirm = true;
    }

    public function delete()
    {
        if ($this->currentImage) {
            Storage::disk('public')->delete($this->currentImage);
            $this->currentImage = '';
            session()->flash('message', 'Image deleted successfully.');
        }
        $this->showDeleteConfirm = false;
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirm = false;
    }

    public function with()
    {
        return [
            'showDeleteConfirm' => $this->showDeleteConfirm,
            'currentImage' => $this->currentImage,
            'image' => $this->image,
            'uploadProgress' => $this->uploadProgress,
            'showCropper' => $this->showCropper,
            'croppedImageData' => $this->croppedImageData,
        ];
    }
}; ?>

<section class="w-full" x-data="{ uploadProgress: 0, showImagePreview: false }">
    @include('partials.settings-heading')

    <x-settings.layout>

        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8 border border-blue-100">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fa-brands fa-paypal text-2xl text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-1">GCash Payment Settings</h2>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Upload your GCash QR code or payment reference image. This will be displayed to customers when they choose GCash as their payment method.
                    </p>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session()->has('message'))
            <div class="flex items-center gap-3 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 mb-6">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-circle-check text-green-500"></i>
                </div>
                <span class="font-medium">{{ session('message') }}</span>
            </div>
        @endif

        @if ($errors->has('image'))
            <div class="flex items-center gap-3 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 mb-6">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                </div>
                <span class="font-medium">{{ $errors->first('image') }}</span>
            </div>
        @endif

        {{-- Main Content Card --}}
        <div class="overflow-hidden">
            {{-- Current Image Section --}}
            @if ($currentImage)
                <div class="p-6 border border-gray-200 bg-white rounded-xl shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center justify-center gap-2 mb-4">
                        <i class="fa-solid fa-image text-blue-500"></i>
                        Current GCash Image
                    </h3>

                    {{-- Centered Image --}}
                    <div class="flex justify-center mb-4">
                        <div class="relative group">
                            <img src="{{ asset('storage/' . $currentImage) }}"
                                alt="Current GCash Image"
                                class="w-full max-w-xs h-auto rounded-lg shadow-md border-2 border-gray-100 object-contain bg-gray-50 cursor-pointer"
                                x-on:click="showImagePreview = true">

                            <!-- Quick Actions Overlay -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-200 rounded-lg flex items-center justify-center">
                                <div class="flex gap-2">
                                    <button x-on:click="showImagePreview = true"
                                            class="p-2 bg-white/90 hover:bg-white text-gray-700 rounded-lg shadow-sm">
                                        <i class="fa-solid fa-expand text-sm"></i>
                                    </button>
                                    <button wire:click="confirmDelete"
                                            class="p-2 bg-red-500/90 hover:bg-red-600 text-white rounded-lg shadow-sm">
                                        <i class="fa-solid fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status + Info + Buttons --}}
                    <div class="flex flex-col items-center gap-3">
                        {{-- Active Status Badge --}}
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fa-solid fa-circle-check"></i>
                            Active
                        </span>

                        {{-- Image Info --}}
                        <div class="text-sm text-gray-600 flex items-center gap-2">
                            <i class="fa-solid fa-file text-blue-500"></i>
                            <span class="font-medium">Format:</span>
                            <span class="uppercase">{{ pathinfo($currentImage, PATHINFO_EXTENSION) }}</span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2">
                            <button wire:click="confirmDelete"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-sm font-medium transition-colors">
                                <i class="fa-solid fa-trash"></i>
                                Remove Image
                            </button>
                        </div>
                    </div>
                </div>
            @endif


            {{-- Upload Section --}}
            <div class="p-6 mt-5 border border-gray-200 bg-white rounded-xl shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-blue-500"></i>
                    {{ $currentImage ? 'Replace Image' : 'Upload GCash Image' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-6" 
                    x-on:livewire-upload-progress="uploadProgress = $event.detail.progress">

                    <!-- Enhanced Drag & Drop Zone -->
                    @if (!$showCropper && !$croppedImageData)
                        <div x-data="{
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if(files && files[0]) {
                                        $refs.fileInput.files = files;
                                        $refs.fileInput.dispatchEvent(new Event('input'));
                                    }
                                }
                            }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop.prevent="handleDrop($event)"
                            class="relative border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200 min-h-[200px] flex flex-col items-center justify-center"
                            :class="dragging ? 'border-blue-400 bg-blue-50 scale-[1.02]' : 'border-gray-300 bg-gray-50 hover:border-blue-300 hover:bg-blue-25'">

                            <input type="file"
                                x-ref="fileInput"
                                wire:model="image"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                class="hidden">

                            <div x-on:click="$refs.fileInput.click()" class="space-y-4">
                                <div class="p-4 bg-blue-100 rounded-full w-fit mx-auto">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-blue-600"></i>
                                </div>
                                
                                <div class="space-y-2">
                                    <p class="text-lg font-medium text-gray-700">
                                        <span class="text-blue-600 underline">Click to browse</span> or drag & drop your image
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Supports PNG, JPG, JPEG, WEBP • Maximum 2MB
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-center gap-4 text-xs text-gray-400 pt-2">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-shield-check"></i>
                                        <span>Secure Upload</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-crop text-blue-500"></i>
                                        <span>Auto Crop</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-zap"></i>
                                        <span>Instant Preview</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Loading State -->
                    <div wire:loading.flex wire:target="image" class="items-center justify-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center gap-2 text-blue-700">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <span class="font-medium">Processing your image...</span>
                        </div>
                    </div>

                    <!-- Cropped Image Preview -->
                    @if ($croppedImageData)
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-crop text-green-500"></i>
                                Cropped Preview
                            </h4>
                            
                            <div class="flex flex-col md:flex-row gap-6">
                                <div class="flex-shrink-0">
                                    <img src="{{ $croppedImageData }}"
                                        alt="Cropped Preview"
                                        class="w-full max-w-xs h-auto rounded-lg border-2 border-white shadow-md object-contain bg-white">
                                </div>
                                
                                <div class="flex-1 space-y-4">
                                    <div class="flex items-center gap-2 text-green-600 text-sm">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span class="font-medium">Image cropped successfully! Ready to upload.</span>
                                    </div>
                                    
                                    <button type="button"
                                            wire:click="$set('showCropper', true)"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-sm font-medium transition-colors">
                                        <i class="fa-solid fa-crop"></i>
                                        Crop Again
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Upload Progress Bar -->
                    <div x-show="uploadProgress > 0" class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium text-gray-700 flex items-center gap-2">
                                <i class="fa-solid fa-upload text-blue-500"></i>
                                Uploading...
                            </span>
                            <span class="font-bold text-blue-600" x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-300 ease-out" 
                                :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-4">
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save,image"
                                :disabled="!($wire.image || $wire.croppedImageData)"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                {{ $currentImage ? 'Replace Image' : 'Upload Image' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                Uploading...
                            </span>
                        </button>

                        @if (!empty($image) || !empty($croppedImageData))
                            <button type="button"
                                    wire:click="$set('image', null); $set('croppedImageData', null)"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                                <i class="fa-solid fa-rotate-left"></i>
                                Reset
                            </button>
                        @endif
                    </div>
                </form>

                <!-- Help Section -->
                @if (!$currentImage && !$image && !$croppedImageData)
                    <div class="mt-8 p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <h4 class="text-sm font-semibold text-amber-800 mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-lightbulb"></i>
                            Tips for best results
                        </h4>
                        <ul class="text-sm text-amber-700 space-y-1">
                            <li>• Use a clear, high-resolution image of your GCash QR code</li>
                            <li>• Ensure the QR code is easily scannable</li>
                            <li>• Include your GCash name/number for reference</li>
                            <li>• Use the crop tool to focus on the important parts</li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <!-- Image Cropper Modal -->
        @if($showCropper && $image)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                    <!-- Cropper Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-crop text-blue-500"></i>
                            Crop Your Image
                        </h3>
                        <button wire:click="cancelCrop"
                                class="p-2 hover:bg-gray-200 rounded-lg transition-colors">
                            <i class="fa-solid fa-times text-gray-400"></i>
                        </button>
                    </div>
                    
                    <!-- Cropper Content -->
                    <div class="p-6" 
                         x-data="{
                            canvas: null,
                            ctx: null,
                            img: null,
                            isDrawing: false,
                            startX: 0,
                            startY: 0,
                            currentX: 0,
                            currentY: 0,
                            cropX: 0,
                            cropY: 0,
                            cropWidth: 0,
                            cropHeight: 0,
                            
                            initCropper() {
                                this.canvas = this.$refs.cropCanvas;
                                this.ctx = this.canvas.getContext('2d');
                                this.img = new Image();
                                
                                this.img.onload = () => {
                                    const maxWidth = 600;
                                    const maxHeight = 400;
                                    let { width, height } = this.img;
                                    
                                    if (width > maxWidth) {
                                        height = (height * maxWidth) / width;
                                        width = maxWidth;
                                    }
                                    if (height > maxHeight) {
                                        width = (width * maxHeight) / height;
                                        height = maxHeight;
                                    }
                                    
                                    this.canvas.width = width;
                                    this.canvas.height = height;
                                    this.drawImage();
                                    
                                    // Set initial crop to full image
                                    this.cropX = 0;
                                    this.cropY = 0;
                                    this.cropWidth = width;
                                    this.cropHeight = height;
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
                                
                                // Draw overlay
                                this.ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                                
                                // Clear crop area
                                this.ctx.clearRect(this.cropX, this.cropY, this.cropWidth, this.cropHeight);
                                this.ctx.drawImage(this.img, 0, 0, this.canvas.width, this.canvas.height);
                                
                                // Draw crop border
                                this.ctx.strokeStyle = '#3b82f6';
                                this.ctx.lineWidth = 2;
                                this.ctx.strokeRect(this.cropX, this.cropY, this.cropWidth, this.cropHeight);
                                
                                // Draw corner handles
                                const handleSize = 8;
                                this.ctx.fillStyle = '#3b82f6';
                                this.ctx.fillRect(this.cropX - handleSize/2, this.cropY - handleSize/2, handleSize, handleSize);
                                this.ctx.fillRect(this.cropX + this.cropWidth - handleSize/2, this.cropY - handleSize/2, handleSize, handleSize);
                                this.ctx.fillRect(this.cropX - handleSize/2, this.cropY + this.cropHeight - handleSize/2, handleSize, handleSize);
                                this.ctx.fillRect(this.cropX + this.cropWidth - handleSize/2, this.cropY + this.cropHeight - handleSize/2, handleSize, handleSize);
                            },
                            
                            startCrop(e) {
                                const rect = this.canvas.getBoundingClientRect();
                                this.startX = e.clientX - rect.left;
                                this.startY = e.clientY - rect.top;
                                this.isDrawing = true;
                            },
                            
                            updateCrop(e) {
                                if (!this.isDrawing) return;
                                
                                const rect = this.canvas.getBoundingClientRect();
                                this.currentX = e.clientX - rect.left;
                                this.currentY = e.clientY - rect.top;
                                
                                this.cropX = Math.min(this.startX, this.currentX);
                                this.cropY = Math.min(this.startY, this.currentY);
                                this.cropWidth = Math.abs(this.currentX - this.startX);
                                this.cropHeight = Math.abs(this.currentY - this.startY);
                                
                                this.drawCropRect();
                            },
                            
                            endCrop() {
                                this.isDrawing = false;
                            },
                            
                            applyCrop() {
                                if (this.cropWidth < 10 || this.cropHeight < 10) {
                                    alert('Please select a larger area to crop');
                                    return;
                                }
                                
                                const tempCanvas = document.createElement('canvas');
                                const tempCtx = tempCanvas.getContext('2d');
                                
                                // Calculate scale factors
                                const scaleX = this.img.naturalWidth / this.canvas.width;
                                const scaleY = this.img.naturalHeight / this.canvas.height;
                                
                                const actualCropX = this.cropX * scaleX;
                                const actualCropY = this.cropY * scaleY;
                                const actualCropWidth = this.cropWidth * scaleX;
                                const actualCropHeight = this.cropHeight * scaleY;
                                
                                tempCanvas.width = actualCropWidth;
                                tempCanvas.height = actualCropHeight;
                                
                                tempCtx.drawImage(
                                    this.img,
                                    actualCropX, actualCropY, actualCropWidth, actualCropHeight,
                                    0, 0, actualCropWidth, actualCropHeight
                                );
                                
                                const croppedDataUrl = tempCanvas.toDataURL('image/png');
                                $wire.setCroppedImage(croppedDataUrl);
                            },
                            
                            resetCrop() {
                                this.cropX = 0;
                                this.cropY = 0;
                                this.cropWidth = this.canvas.width;
                                this.cropHeight = this.canvas.height;
                                this.drawCropRect();
                            }
                         }"
                         x-init="$nextTick(() => initCropper())">
                        
                        <div class="space-y-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-4">
                                    Click and drag to select the area you want to keep. Focus on your QR code and important details.
                                </p>
                                
                                <div class="inline-block border-2 border-gray-200 rounded-lg overflow-hidden bg-white">
                                    <canvas x-ref="cropCanvas"
                                            x-on:mousedown="startCrop($event)"
                                            x-on:mousemove="updateCrop($event)"
                                            x-on:mouseup="endCrop()"
                                            x-on:mouseleave="endCrop()"
                                            class="cursor-crosshair">
                                    </canvas>
                                </div>
                            </div>
                            
                            <!-- Crop Controls -->
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <button type="button"
                                        x-on:click="applyCrop()"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                                    <i class="fa-solid fa-check"></i>
                                    Apply Crop
                                </button>
                                
                                <button type="button"
                                        x-on:click="resetCrop()"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                                    <i class="fa-solid fa-expand"></i>
                                    Reset Selection
                                </button>
                                
                                <button type="button"
                                        wire:click="cancelCrop"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium rounded-lg transition-colors">
                                    <i class="fa-solid fa-times"></i>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Image Preview Modal -->
        <div x-show="showImagePreview" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-on:keydown.escape="showImagePreview = false"
            style="display: none;">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="showImagePreview = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl max-h-[90vh] overflow-auto">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">GCash Image Preview</h3>
                    <button x-on:click="showImagePreview = false"
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fa-solid fa-times text-gray-400"></i>
                    </button>
                </div>
                <div class="p-4">
                    @if ($currentImage)
                        <img src="{{ asset('storage/' . $currentImage) }}"
                            alt="GCash Image Preview"
                            class="w-full h-auto rounded-lg">
                    @endif
                </div>
            </div>
        </div>

        <!-- Enhanced Delete Confirmation Modal -->
        @if($showDeleteConfirm)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="cancelDelete"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Delete GCash Image</h4>
                        </div>
                        
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Are you sure you want to delete the current GCash image? This action cannot be undone and customers won't be able to see your payment details.
                        </p>
                        
                        <div class="flex gap-3">
                            <button wire:click="cancelDelete"
                                    class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button wire:click="delete"
                                    class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </x-settings.layout>
</section>