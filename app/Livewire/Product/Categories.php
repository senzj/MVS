<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Models\ProductCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Categories extends Component
{
    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    public bool $showProductsModal = false;

    public ?int $selectedCategoryId = null;
    public string $selectedCategoryName = '';

    public array $selectedCategoryProducts = [];

    public string $name = '';
    public string $description = '';

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')->ignore($this->selectedCategoryId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('Category name is required.'),
            'name.unique' => __('This category name is already in use.'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetPage(): void
    {
        // No pagination is used on this screen.
    }

    public function sortByField(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
    }

    public function openEditModal(int $categoryId): void
    {
        $category = ProductCategories::find($categoryId);

        if (! $category) {
            $this->dispatch('show-error', ['message' => __('Category not found!')]);

            return;
        }

        $this->selectedCategoryId = $category->id;
        $this->selectedCategoryName = $category->name;
        $this->name = $category->name;
        $this->description = $category->description ?? '';

        $this->dispatch('edit-category-loaded');
    }

    public function openDeleteModal(int $categoryId): void
    {
        $category = ProductCategories::find($categoryId);

        if (! $category) {
            $this->dispatch('show-error', ['message' => __('Category not found!')]);

            return;
        }

        $this->selectedCategoryId = $category->id;
        $this->selectedCategoryName = $category->name;

        $this->dispatch('delete-category-loaded');
    }

    public function openProductsModal(int $categoryId): void
    {
        $category = ProductCategories::find($categoryId);

        if (! $category) {
            $this->dispatch('show-error', ['message' => __('Category not found!')]);

            return;
        }

        $this->selectedCategoryId = $category->id;
        $this->selectedCategoryName = $category->name;
        $this->selectedCategoryProducts = Product::query()
            ->where('category_id', $category->id)
            ->orderBy('name')
            ->get(['id', 'name', 'stocks', 'price'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stocks' => (int) $product->stocks,
                'price' => (float) $product->price,
            ])
            ->all();

        $this->dispatch('products-category-loaded');
    }

    public function createCategory(): void
    {
        $this->validate();

        ProductCategories::create([
            'name' => ucwords(trim($this->name)),
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
        ]);

        $this->dispatch('close-create-modal');
        $this->dispatch('show-success', ['message' => __('Category created successfully!')]);
        $this->resetForm();
    }

    public function updateCategory(): void
    {
        $this->validate();

        $category = ProductCategories::find($this->selectedCategoryId);

        if (! $category) {
            $this->dispatch('show-error', ['message' => __('Category not found!')]);

            return;
        }

        $newName = ucwords(trim($this->name));

        DB::transaction(function () use ($category, $newName): void {
            $category->update([
                'name' => $newName,
                'description' => trim($this->description) !== '' ? trim($this->description) : null,
            ]);
        });

        $this->dispatch('close-edit-modal');
        $this->dispatch('show-success', ['message' => __('Category updated successfully!')]);
        $this->resetForm();
    }

    public function deleteCategory(): void
    {
        $category = ProductCategories::find($this->selectedCategoryId);

        if (! $category) {
            $this->dispatch('show-error', ['message' => __('Category not found!')]);

            return;
        }

        if ($category->products()->exists()) {
            $this->dispatch('show-error', ['message' => __('Cannot delete a category that still has products assigned to it!')]);

            return;
        }

        $category->delete();

        $this->dispatch('close-delete-modal');
        $this->dispatch('show-success', ['message' => __('Category deleted successfully!')]);
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->showProductsModal = false;
        $this->selectedCategoryId = null;
        $this->selectedCategoryName = '';
        $this->selectedCategoryProducts = [];
        $this->name = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $productStats = Product::query()
            ->selectRaw('category_id, COUNT(*) as products_count, COALESCE(SUM(stocks), 0) as total_stocks, COALESCE(SUM(stocks * price), 0) as inventory_value')
            ->groupBy('category_id')
            ->get()
            ->keyBy(fn ($row) => $row->category_id ?: '__uncategorized__');

        $categoryQuery = ProductCategories::query()
            ->withCount('products')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%' . trim($this->search) . '%';
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            });

        $categories = $categoryQuery->get()->map(function (ProductCategories $category) use ($productStats) {
            $stats = $productStats[(string) $category->id] ?? $productStats[$category->id] ?? null;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'products_count' => (int) ($category->products_count ?? 0),
                'total_stocks' => (int) ($stats?->total_stocks ?? 0),
                'inventory_value' => (float) ($stats?->inventory_value ?? 0),
                'updated_at' => $category->updated_at,
            ];
        });

        $sortedCategories = $this->sortBy === 'products_count'
            ? $categories->sortBy(fn ($item) => $item['products_count'], SORT_REGULAR, $this->sortDirection === 'desc')
            : ($this->sortBy === 'inventory_value'
                ? $categories->sortBy(fn ($item) => $item['inventory_value'], SORT_REGULAR, $this->sortDirection === 'desc')
                : $categories->sortBy(fn ($item) => $item[$this->sortBy] ?? '', SORT_NATURAL | SORT_FLAG_CASE, $this->sortDirection === 'desc'));

        $sortedCategories = $sortedCategories->values();

        $uncategorizedProducts = (int) ($productStats['__uncategorized__']->products_count ?? 0);
        $totalProducts = Product::count();
        $totalCategories = ProductCategories::count();

        $kpi = [
            'total_categories' => $totalCategories,
            'active_categories' => $categories->where('products_count', '>', 0)->count(),
            'empty_categories' => $categories->where('products_count', 0)->count(),
            'uncategorized_products' => $uncategorizedProducts,
            'total_inventory_value' => round($categories->sum('inventory_value'), 2),
            'total_products' => $totalProducts,
        ];

        $categoryChart = $categories
            ->sortByDesc('products_count')
            ->take(6)
            ->values()
            ->map(fn ($item) => [
                'label' => $item['name'],
                'value' => $item['products_count'],
            ])
            ->all();

        $valueChart = $categories
            ->sortByDesc('inventory_value')
            ->take(6)
            ->values()
            ->map(fn ($item) => [
                'label' => $item['name'],
                'value' => round($item['inventory_value'], 2),
            ])
            ->all();

        return view('livewire.product.categories', [
            'categories' => $sortedCategories,
            'kpi' => $kpi,
            'categoryChart' => $categoryChart,
            'valueChart' => $valueChart,
        ]);
    }
}
