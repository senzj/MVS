<?php

namespace App\Livewire\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $categoryFilter = 'all';
    public $stockFilter = '';
    public $sortBy = 'id';
    public $sortDirection = 'asc';

    // Remove modal properties - Alpine.js will handle these
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
        'category' => 'required|string|max:255',
        'sold' => 'integer|min:0',
        'is_in_stock' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'Product name is required.',
        'price.required' => 'Price is required.',
        'price.numeric' => 'Price must be a valid number.',
        'price.min' => 'Price cannot be negative.',
        'stocks.required' => 'Stock quantity is required.',
        'stocks.integer' => 'Stock must be a whole number.',
        'stocks.min' => 'Stock cannot be negative.',
        'category.required' => 'Category is required.',
    ];

    public function mount()
    {
        //
    }

    // FIXED: Search and filter methods with forced pagination reset
    public function updatedSearch()
    {
        $this->resetPage();
        $this->dispatch('$refresh'); // Force component refresh
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
        $this->dispatch('$refresh'); // Force component refresh
        // Add debug to check what's happening
        Log::info('Category filter updated to: ' . $this->categoryFilter);
    }

    public function updatedStockFilter()
    {
        $this->resetPage();
        $this->dispatch('$refresh'); // Force component refresh
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->categoryFilter = 'all';
        $this->stockFilter = '';
        $this->resetPage();
        $this->dispatch('$refresh'); // Force component refresh
    }

    public function clearFilters()
    {
        $this->categoryFilter = 'all';
        $this->stockFilter = '';
        $this->resetPage();
        $this->dispatch('$refresh'); // Force component refresh
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

    // Modal methods - simplified for Alpine.js
    public function openCreateModal()
    {
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
        }
    }

    public function openArchiveModal($productId)
    {
        $this->selectedProductId = $productId;
    }

    public function openDeleteModal($productId)
    {
        $this->selectedProductId = $productId;
    }

    public function setSelectedProduct($productId)
    {
        $this->selectedProductId = $productId;
    }

    // CRUD methods - Updated to use Toastr
    public function createProduct()
    {
        $this->validate();

        Product::create([
            'name' => ucwords(trim($this->name)),
            'description' => trim($this->description),
            'price' => $this->price,
            'stocks' => $this->stocks,
            'category' => $this->category,
            'sold' => $this->sold,
            'is_in_stock' => $this->is_in_stock,
        ]);

        $this->dispatch('show-success', ['message' => 'Product created successfully!']);
        $this->dispatch('close-create-modal');
        $this->resetForm();
    }

    public function updateProduct()
    {
        $this->validate();

        $product = Product::find($this->selectedProductId);
        
        if (!$product) {
            $this->dispatch('show-error', ['message' => 'Product not found!']);
            return;
        }

        $product->update([
            'name' => ucwords(trim($this->name)),
            'description' => trim($this->description),
            'price' => $this->price,
            'stocks' => $this->stocks,
            'category' => $this->category,
            'sold' => $this->sold,
            'is_in_stock' => $this->is_in_stock,
        ]);

        $this->dispatch('show-success', ['message' => "Product \"{$this->name}\" updated successfully!"]);
        $this->dispatch('close-edit-modal');
        $this->resetForm();
    }

    public function makeAvailable($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $product->update(['is_in_stock' => true]);
            $this->dispatch('show-success', ['message' => "\"{$product->name}\" is now available for sale!"]);
        } else {
            $this->dispatch('show-error', ['message' => 'Product not found!']);
        }
    }

    public function archiveProduct()
    {
        $product = Product::find($this->selectedProductId);
        if ($product) {
            $product->update(['is_in_stock' => false]);
            $this->dispatch('show-success', ['message' => "\"{$product->name}\" has been marked as unavailable!"]);
            $this->dispatch('close-archive-modal');
            $this->selectedProductId = null;
        } else {
            $this->dispatch('show-error', ['message' => 'Product not found!']);
        }
    }

    public function deleteProduct()
    {
        $product = Product::find($this->selectedProductId);
        
        if (!$product) {
            $this->dispatch('show-error', ['message' => 'Product not found!']);
            return;
        }

        $hasOrderHistory = $product->orderItems()->count() > 0;
        
        if ($hasOrderHistory) {
            $this->dispatch('show-error', ['message' => 'Cannot permanently delete product with order history!']);
            return;
        }

        $productName = $product->name;
        $product->delete();
        
        $this->dispatch('show-success', ['message' => "Product '{$productName}' permanently deleted!"]);
        $this->dispatch('close-delete-modal');
        $this->selectedProductId = null;
    }

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

    // FIXED: Updated render method with better filter handling and debugging
    public function render()
    {
        // Start fresh query
        $query = Product::query();

        // Apply search filter
        if ($this->search && trim($this->search) !== '') {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhere('category', 'like', $searchTerm);
            });
        }

        // FIXED: Category filter with explicit check and better debugging
        if ($this->categoryFilter !== 'all' && !empty($this->categoryFilter)) {
            // Log for debugging
            Log::info('Applying category filter: ' . $this->categoryFilter);
            $query->where('category', $this->categoryFilter);
            
            // Debug: Check what products exist with this category
            $categoryProducts = Product::where('category', $this->categoryFilter)->get();
            Log::info('Products with category "' . $this->categoryFilter . '": ' . $categoryProducts->count());
        }

        // Apply stock filter
        if ($this->stockFilter && trim($this->stockFilter) !== '' && $this->stockFilter !== 'all') {
            switch ($this->stockFilter) {
                case 'in_stock':
                    $query->where('stocks', '>=', 10);
                    break;
                case 'low_stock':
                    $query->where('stocks', '<', 10)->where('stocks', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stocks', '<=', 0);
                    break;
                case 'available':
                    $query->where('is_in_stock', true);
                    break;
                case 'hidden':
                    $query->where('is_in_stock', false);
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        // Debug: Log the final query
        Log::info('Final query SQL: ' . $query->toSql());
        Log::info('Query bindings: ', $query->getBindings());

        // Get paginated results
        $products = $query->paginate(10);

        // Debug: Log the results count
        Log::info('Products found: ' . $products->total());

        // Get all products for stats (separate query to avoid filter interference)
        $allProducts = Product::all();

        // Get categories - FIXED: Make sure we get all available categories from database
        $categories = Product::getCategories();
        
        // Also get categories that actually exist in the database
        $existingCategories = Product::whereNotNull('category')
                                   ->where('category', '!=', '')
                                   ->distinct()
                                   ->pluck('category', 'category')
                                   ->toArray();
        
        // Debug: Log existing categories
        Log::info('Existing categories in database: ', $existingCategories);
        Log::info('Predefined categories: ', $categories);

        return view('livewire.product.dashboard', [
            'products' => $products,
            'allProducts' => $allProducts,
            'categories' => $categories,
            'existingCategories' => $existingCategories, // Pass this for debugging
        ]);
    }

    // Add this method to debug your data
    public function debugCategories()
    {
        $allProducts = Product::all();
        $categoryData = [];
        
        foreach ($allProducts as $product) {
            $cat = $product->category;
            if (!isset($categoryData[$cat])) {
                $categoryData[$cat] = 0;
            }
            $categoryData[$cat]++;
        }
        
        Log::info('Category distribution:', $categoryData);
        
        $this->dispatch('show-success', ['message' => 'Check logs for category debug info']);
    }
}