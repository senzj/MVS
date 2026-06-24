<?php

namespace App\Livewire\Product;

use App\Models\InventoryMovement;
use App\Models\ItemRestocks;
use App\Models\Product;
use App\Services\Products\InventoryService;
use App\Services\System\AuditLogsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Overview extends Component
{
    use WithFileUploads;
    use WithPagination;

    public Product $product;

    // Edit form properties — mirrors Dashboard's field set
    public $name = '';
    public $description = '';
    public $price = '';
    public $stocks = '';
    public $category = '';
    public $sold = 0;
    public $is_in_stock = true;
    public $color = '';
    public $image = null;
    public $existingImageUrl = null;
    public $removeExistingImage = false;
    public $imageVersion = 0;

    private const IMAGE_TARGET_SIZE = 800;

    public function mount(Product $product): void
    {
        $this->product = $product->loadCount('orderItems');
    }

    protected function rules(): array
    {
        $colorRules = ['nullable'];

        if (! empty($this->color)) {
            $colorRules[] = 'regex:/^#[0-9A-Fa-f]{6}$/';
            $colorRules[] = Rule::unique('products', 'color')->ignore($this->product->id);
        }

        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stocks'      => 'required|integer|min:0',
            'category'    => 'required|exists:product_categories,id',
            'sold'        => 'integer|min:0',
            'is_in_stock' => 'boolean',
            'image'       => 'nullable|image|max:10000',
            'color'       => $colorRules,
        ];
    }

    protected $messages = [
        'name.required'     => 'Product name is required.',
        'price.required'    => 'Price is required.',
        'price.numeric'     => 'Price must be a valid number.',
        'price.min'         => 'Price cannot be negative.',
        'stocks.required'   => 'Stock quantity is required.',
        'stocks.integer'    => 'Stock must be a whole number.',
        'stocks.min'        => 'Stock cannot be negative.',
        'category.required' => 'Category is required.',
        'image.image'       => 'The uploaded file must be an image.',
        'image.max'         => 'Image size must not exceed 4MB.',
        'color.regex'       => 'Please choose a valid color.',
        'color.unique'      => 'This color is already assigned to another product. Please pick a different one.',
    ];

    // ── Edit modal ──────────────────────────────────────────────────────────

    public function openEditModal(): void
    {
        $this->name = $this->product->name;
        $this->description = $this->product->description;
        $this->price = $this->product->price;
        $this->stocks = $this->product->stocks;
        $this->category = $this->product->category;
        $this->sold = $this->product->sold;
        $this->is_in_stock = $this->product->is_in_stock;
        $this->color = $this->product->color ?: $this->generateUniqueColor();
        $this->image = null;
        $this->existingImageUrl = $this->product->image_url;
        $this->removeExistingImage = false;
        $this->resetErrorBag();

        $this->dispatch('edit-product-loaded');
    }

    public function regenerateColor(): void
    {
        $this->color = $this->generateUniqueColor();
        $this->resetErrorBag('color');
    }

    public function removeColor(): void
    {
        $this->color = null;
        $this->resetErrorBag('color');
    }

    public function removeImage(): void
    {
        $this->image = null;
        $this->existingImageUrl = null;
        $this->removeExistingImage = true;
        $this->imageVersion++;
        $this->resetErrorBag('image');
    }

    public function save(AuditLogsService $audit, InventoryService $inventory): void
    {
        $this->normalizeColor();
        $this->validate();

        $oldValues = [
            'name'        => $this->product->name,
            'description' => $this->product->description,
            'price'       => $this->product->price,
            'stocks'      => $this->product->stocks,
            'category'    => $this->product->category,
            'is_in_stock' => $this->product->is_in_stock,
            'color'       => $this->product->color,
            'image_url'   => $this->product->image_url,
        ];

        $imageUrl = $this->product->image_url;

        if ($this->image) {
            if ($this->product->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->product->image_url));
            }
            $imageUrl = $this->storeSquareImage($this->image);
        } elseif ($this->removeExistingImage) {
            if ($this->product->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->product->image_url));
            }
            $imageUrl = null;
        }

        $oldStocks = (int) $this->product->stocks;
        $newStocks = (int) $this->stocks;

        try {
            DB::transaction(function () use ($audit, $inventory, $oldValues, $imageUrl, $oldStocks, $newStocks) {
                if ($newStocks > $oldStocks) {
                    $inventory->restore(
                        $this->product->id,
                        $newStocks - $oldStocks,
                        'restock',
                        $this->product,
                        __('Manual restock from product overview.')
                    );
                } elseif ($newStocks < $oldStocks) {
                    $inventory->deduct(
                        $this->product->id,
                        $oldStocks - $newStocks,
                        'manual_adjustment',
                        $this->product,
                        __('Manual stock deduction from product overview.')
                    );
                }

                $this->product->refresh();

                $this->product->update([
                    'name'        => ucwords(trim($this->name)),
                    'description' => trim($this->description),
                    'price'       => $this->price,
                    'stocks'      => $newStocks,
                    'category'    => $this->category,
                    'sold'        => $this->sold,
                    'is_in_stock' => $this->is_in_stock,
                    'color'       => $this->color,
                    'image_url'   => $imageUrl,
                ]);

                if ($oldStocks !== $newStocks) {
                    $audit->recordProductStockAdjusted(
                        Auth::user(),
                        $this->product,
                        $oldStocks,
                        $newStocks,
                        'manual',
                        request()
                    );
                }

                $audit->recordProductUpdated(Auth::user(), $this->product, $oldValues, request());
            });
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('Unable to update product.');
            $this->dispatch('show-error', ['message' => $message]);
            return;
        }

        $this->product->refresh();
        $this->dispatch('show-success', ['message' => __('Product :name updated successfully!', ['name' => $this->name])]);
        $this->dispatch('close-form-modal');
    }

    // ── Hide / Show / Delete ─────────────────────────────────────────────────

    public function makeAvailable(AuditLogsService $audit): void
    {
        $this->product->update(['is_in_stock' => true]);
        $audit->recordProductRestored(Auth::user(), $this->product, request());
        $this->dispatch('show-success', ['message' => __(':name is now available for sale!', ['name' => $this->product->name])]);
    }

    public function archiveProduct(AuditLogsService $audit): void
    {
        $this->product->update(['is_in_stock' => false]);
        $audit->recordProductArchived(Auth::user(), $this->product, request());
        $this->dispatch('show-success', ['message' => __(':name has been marked as unavailable!', ['name' => $this->product->name])]);
        $this->dispatch('close-archive-modal');
    }

    public function deleteProduct(AuditLogsService $audit): void
    {
        if ($this->product->orderItems()->count() > 0) {
            $this->dispatch('show-error', ['message' => __('Cannot permanently delete product with order history!')]);
            return;
        }

        $snapshot = [
            'name'     => $this->product->name,
            'category' => $this->product->category,
            'price'    => $this->product->price,
            'stocks'   => $this->product->stocks,
        ];

        if ($this->product->image_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $this->product->image_url));
        }

        $this->product->delete();
        $audit->recordProductDeleted(Auth::user(), $snapshot, request());

        session()->flash('success', __('Product :name permanently deleted!', ['name' => $snapshot['name']]));
        $this->redirect(route('products'), navigate: true);
    }

    // ── Color + image helpers (same logic as Dashboard) ──────────────────────

    private function normalizeColor(): void
    {
        $this->color = $this->color !== '' ? $this->color : null;
    }

    private function generateUniqueColor(): string
    {
        $attempts = 0;

        do {
            $color = $this->randomHexColor();
            $attempts++;
        } while (
            Product::where('color', $color)->where('id', '!=', $this->product->id)->exists()
            && $attempts < 50
        );

        return $color;
    }

    private function randomHexColor(): string
    {
        $hue = mt_rand(0, 359);
        [$r, $g, $b] = $this->hslToRgb($hue, 65, 55);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function hslToRgb(int $h, int $s, int $l): array
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        if ($s === 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hueToRgb($p, $q, $h + 1 / 3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1 / 3);
        }

        return [(int) round($r * 255), (int) round($g * 255), (int) round($b * 255)];
    }

    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }

    private function applyExifOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;

        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                imageflip($image, IMG_FLIP_VERTICAL);
                $image = imagerotate($image, -90, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 7:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }

    private function storeSquareImage(TemporaryUploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();
        [$width, $height, $type] = getimagesize($sourcePath);

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
            default        => imagecreatefromstring(file_get_contents($sourcePath)),
        };

        if ($type === IMAGETYPE_JPEG) {
            $source = $this->applyExifOrientation($source, $sourcePath);
            $width = imagesx($source);
            $height = imagesy($source);
        }

        $side = min($width, $height);
        $srcX = (int) (($width - $side) / 2);
        $srcY = (int) (($height - $side) / 2);

        $targetSize = min($side, self::IMAGE_TARGET_SIZE);
        $canvas = imagecreatetruecolor($targetSize, $targetSize);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetSize, $targetSize, $transparent);
        }

        imagecopyresampled(
            $canvas, $source,
            0, 0, $srcX, $srcY,
            $targetSize, $targetSize, $side, $side
        );

        $extension = match ($type) {
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF  => 'gif',
            default        => 'jpg',
        };

        $relativePath = 'products/' . uniqid('prod_', true) . '.' . $extension;
        Storage::disk('public')->makeDirectory('products');
        $fullPath = Storage::disk('public')->path($relativePath);

        match ($type) {
            IMAGETYPE_PNG  => imagepng($canvas, $fullPath),
            IMAGETYPE_WEBP => imagewebp($canvas, $fullPath, 90),
            IMAGETYPE_GIF  => imagegif($canvas, $fullPath),
            default        => imagejpeg($canvas, $fullPath, 88),
        };

        imagedestroy($source);
        imagedestroy($canvas);

        return Storage::url($relativePath);
    }

    public function render()
    {
        $restocks = ItemRestocks::query()
            ->join('product_restocks', 'product_restocks.id', '=', 'item_restocks.restock_id')
            ->leftJoin('users', 'users.id', '=', 'product_restocks.user_id')
            ->where('item_restocks.product_id', $this->product->id)
            ->select([
                'item_restocks.id',
                'item_restocks.quantity',
                'item_restocks.unit_cost',
                'item_restocks.total_cost',
                'item_restocks.unit_type',
                'item_restocks.created_at',
                'product_restocks.notes',
                'users.name as user_name',
            ])
            ->orderByDesc('item_restocks.created_at')
            ->paginate(8, ['*'], 'restocks_page');

        $movements = InventoryMovement::query()
            ->leftJoin('users', 'users.id', '=', 'inventory_movements.user_id')
            ->where('inventory_movements.product_id', $this->product->id)
            ->select(['inventory_movements.*', 'users.name as user_name'])
            ->orderByDesc('inventory_movements.created_at')
            ->paginate(8, ['*'], 'movements_page');

        $totalRestocked = ItemRestocks::where('product_id', $this->product->id)->sum('quantity');
        $totalSpent     = ItemRestocks::where('product_id', $this->product->id)->sum('total_cost');

        return view('livewire.product.overview', [
            'restocks'            => $restocks,
            'movements'           => $movements,
            'totalRestocked'      => $totalRestocked,
            'totalSpent'          => $totalSpent,
            'categories'          => Product::getCategories(),
        ]);
    }
}
