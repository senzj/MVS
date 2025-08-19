<?php

namespace App\Livewire\Product;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;

class Dashboard extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $categoryFilter = '';
    public $stockFilter = '';
    public $sortBy = 'id';
    public $sortDirection = 'asc';

    // Modal properties
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $selectedProductId = null;

    // Form properties (matching your database schema)
    public $name = '';
    public $description = '';
    public $price = '';
    public $stocks = '';
    public $category = '';
    public $sold = 0;
    public $is_in_stock = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'stocks' => 'required|integer|min:0',
        'category' => 'required|string',
        'sold' => 'integer|min:0',
        'is_in_stock' => 'boolean',
    ];

    public function mount()
    {
        //
    }

    // Search and filter methods
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedStockFilter()
    {
        $this->resetPage();
    }

    // Fix the sortBy method
    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // Modal methods
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $this->selectedProductId = $product->id;
            $this->name = $product->name;
            $this->description = $product->description;
            $this->price = $product->price;
            $this->stocks = $product->stocks;
            $this->category = $product->category;
            $this->sold = $product->sold;
            $this->is_in_stock = $product->is_in_stock;
            $this->showEditModal = true;
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function openDeleteModal($productId)
    {
        $this->selectedProductId = $productId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedProductId = null;
    }

    // CRUD methods
    public function createProduct()
    {
        $this->validate();

        Product::create([
            'name' => ucwords($this->name),
            'description' => $this->description,
            'price' => $this->price,
            'stocks' => $this->stocks,
            'category' => $this->category,
            'sold' => $this->sold,
            'is_in_stock' => $this->is_in_stock,
        ]);

        session()->flash('success', 'Product created successfully!');
        $this->closeCreateModal();
    }

    public function updateProduct()
    {
        $this->validate();

        $product = Product::find($this->selectedProductId);
        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stocks' => $this->stocks,
            'category' => $this->category,
            'sold' => $this->sold,
            'is_in_stock' => $this->is_in_stock,
        ]);

        session()->flash('success', 'Product updated successfully!');
        $this->closeEditModal();
    }

    public function deleteProduct()
    {
        $product = Product::find($this->selectedProductId);
        if (!$product) {
            session()->flash('error', 'Product not found!');
            return;
        }
        $product->is_in_stock = false;
        $product->save();

        session()->flash('success', 'Product archived successfully!');
        $this->closeDeleteModal();
    }

    // Helper methods
    public function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->stocks = '';
        $this->category = '';
        $this->sold = 0;
        $this->is_in_stock = true;
        $this->selectedProductId = null;
        $this->resetErrorBag();
    }

    public function getProductsProperty()
    {
        $query = Product::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%');
            });
        }

        // Apply category filter
        if (!empty($this->categoryFilter)) {
            $query->where('category', $this->categoryFilter);
        }

        // Apply stock filter
        if (!empty($this->stockFilter)) {
            switch ($this->stockFilter) {
                case 'in_stock':
                    $query->where('stocks', '>=', 10);
                    break;
                case 'low_stock':
                    $query->where('stocks', '<', 10);
                    break;
                case 'out_of_stock':
                    $query->where('stocks', '<=', 0);
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    // Get all products for stats (not paginated)
    public function getAllProductsProperty()
    {
        return Product::all();
    }

    public function render()
    {
        return view('livewire.product.dashboard', [
            'products' => $this->products,
            'allProducts' => $this->allProducts,
            'categories' => Product::getCategories(),
        ]);
    }
}
