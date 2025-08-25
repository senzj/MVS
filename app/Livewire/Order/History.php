<?php

namespace App\Livewire\Order;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class History extends Component
{
    public bool $showOrderModal = false;
    public $selectedOrder = null;

    // Search and Filter properties
    public string $search = '';
    public string $statusFilter = '';
    public string $paymentFilter = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public string $yearFilter = '';
    public string $monthFilter = '';
    public string $dayFilter = '';

    // Pagination properties
    public int $perPage = 35; // Orders per load
    public int $page = 1;
    public bool $hasMorePages = true;
    public bool $isLoading = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'paymentFilter' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'yearFilter' => ['except' => ''],
        'monthFilter' => ['except' => ''],
        'dayFilter' => ['except' => ''],
    ];

    public function openOrder(int $orderId): void
    {
        $this->selectedOrder = Order::with(['customer','employee','staff','orderItems.product'])
            ->find($orderId);

        if($this->selectedOrder) {
            $this->showOrderModal = true;
            $this->dispatch('history-open');
        }
    }

    public function closeOrder(): void
    {
        $this->showOrderModal = false;
        $this->selectedOrder = null;
        $this->dispatch('history-close');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->paymentFilter = '';
        $this->yearFilter = '';
        $this->monthFilter = '';
        $this->dayFilter = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPagination();
    }

    public function resetPagination(): void
    {
        $this->page = 1;
        $this->hasMorePages = true;
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages || $this->isLoading) {
            return;
        }

        $this->isLoading = true;
        $this->page++;
        
        // Re-render the component
        $this->dispatch('orders-loaded');
        $this->isLoading = false;
    }

    // Renamed method to avoid conflict with property
    public function changeSortBy($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function updatedYearFilter(): void
    {
        // Reset month and day when year changes
        $this->monthFilter = '';
        $this->dayFilter = '';
        $this->resetPagination();
    }

    public function updatedMonthFilter(): void
    {
        // Reset day when month changes
        $this->dayFilter = '';
        $this->resetPagination();
    }

    public function updatedSearch(): void
    {
        $this->resetPagination();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPagination();
    }

    public function updatedPaymentFilter(): void
    {
        $this->resetPagination();
    }

    public function updatedSortBy(): void
    {
        $this->resetPagination();
    }

    public function updatedSortDirection(): void
    {
        $this->resetPagination();
    }

    public function render()
    {
        $tz = config('app.timezone') ?? 'UTC';

        // Build the base query
        $baseQuery = Order::with(['customer','employee'])
            ->when($this->search, function($q) {
                $q->where(function($subQuery) {
                    $subQuery->where('receipt_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function($customerQuery) {
                            $customerQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->paymentFilter !== '', function($q) {
                $q->where('is_paid', $this->paymentFilter === 'paid');
            })
            ->when($this->yearFilter, function($q) {
                $q->whereYear('created_at', $this->yearFilter);
            })
            ->when($this->monthFilter, function($q) {
                $q->whereMonth('created_at', $this->monthFilter);
            })
            ->when($this->dayFilter, function($q) {
                $q->whereDay('created_at', $this->dayFilter);
            });

        // Get total count for pagination
        $totalOrders = $baseQuery->count();
        
        // Calculate pagination
        $totalToLoad = $this->page * $this->perPage;
        $this->hasMorePages = $totalToLoad < $totalOrders;

        // Apply sorting and pagination
        if ($this->sortBy === 'receipt_number') {
            // For receipt number sorting, we need to get all and sort manually
            $allOrders = $baseQuery->get();
            if ($this->sortDirection === 'desc') {
                $sortedOrders = $allOrders->sortByDesc(function($order) {
                    if (preg_match('/(\d+)$/', $order->receipt_number, $matches)) {
                        return (int)$matches[1];
                    }
                    return $order->receipt_number;
                });
            } else {
                $sortedOrders = $allOrders->sortBy(function($order) {
                    if (preg_match('/(\d+)$/', $order->receipt_number, $matches)) {
                        return (int)$matches[1];
                    }
                    return $order->receipt_number;
                });
            }
            $orders = $sortedOrders->take($totalToLoad);
        } else {
            $orders = $baseQuery
                ->orderBy($this->sortBy, $this->sortDirection)
                ->take($totalToLoad)
                ->get();
        }

        // Build grouping with complete months
        $grouped = [];
        $monthsWithOrders = [];
        
        // First pass: collect all months that have orders
        foreach ($orders as $o) {
            $dt = $o->created_at->setTimezone($tz);
            $year = $dt->format('Y');
            $monthKey = $dt->format('Y-m');
            $monthsWithOrders[$year][$monthKey] = true;
        }

        // Second pass: for each month with orders, get ALL orders for that month
        foreach ($monthsWithOrders as $year => $months) {
            foreach (array_keys($months) as $monthKey) {
                [$yearStr, $monthStr] = explode('-', $monthKey);
                
                $monthOrders = Order::with(['customer','employee'])
                    ->whereYear('created_at', $yearStr)
                    ->whereMonth('created_at', $monthStr)
                    ->when($this->search, function($q) {
                        $q->where(function($subQuery) {
                            $subQuery->where('receipt_number', 'like', '%' . $this->search . '%')
                                ->orWhereHas('customer', function($customerQuery) {
                                    $customerQuery->where('name', 'like', '%' . $this->search . '%');
                                });
                        });
                    })
                    ->when($this->statusFilter, function($q) {
                        $q->where('status', $this->statusFilter);
                    })
                    ->when($this->paymentFilter !== '', function($q) {
                        $q->where('is_paid', $this->paymentFilter === 'paid');
                    })
                    ->when($this->dayFilter, function($q) {
                        $q->whereDay('created_at', $this->dayFilter);
                    })
                    ->orderBy('created_at', 'asc') // Always sort within month by time
                    ->get();

                foreach ($monthOrders as $o) {
                    $dt = $o->created_at->setTimezone($tz);
                    $dayKey = $dt->toDateString();
                    $grouped[$year][$monthKey][$dayKey][] = $o;
                }
            }
        }

        // Sort each day's orders properly
        foreach ($grouped as $year => &$months) {
            foreach ($months as $monthKey => &$days) {
                foreach ($days as $dayKey => &$dayOrders) {
                    if ($this->sortBy === 'receipt_number') {
                        usort($dayOrders, function($a, $b) {
                            $aNum = 0;
                            $bNum = 0;
                            if (preg_match('/(\d+)$/', $a->receipt_number, $matches)) {
                                $aNum = (int)$matches[1];
                            }
                            if (preg_match('/(\d+)$/', $b->receipt_number, $matches)) {
                                $bNum = (int)$matches[1];
                            }
                            return $aNum <=> $bNum;
                        });
                    } else {
                        usort($dayOrders, function($a, $b) {
                            return $a->created_at <=> $b->created_at;
                        });
                    }
                }
            }
        }

        // Sort groups by date for display
        if ($this->sortBy === 'created_at') {
            if ($this->sortDirection === 'desc') {
                krsort($grouped);
                foreach ($grouped as $y => &$months) {
                    krsort($months);
                    foreach ($months as $m => &$days) {
                        krsort($days);
                    }
                }
            } else {
                ksort($grouped);
                foreach ($grouped as $y => &$months) {
                    ksort($months);
                    foreach ($months as $m => &$days) {
                        ksort($days);
                    }
                }
            }
        }

        // Get available years for filter dropdown
        $availableYears = Order::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Get available months
        $availableMonths = [];
        if ($this->yearFilter) {
            $monthsCollection = Order::whereYear('created_at', $this->yearFilter)
                ->get()
                ->map(function($order) {
                    return (int)$order->created_at->format('n');
                })
                ->unique()
                ->sort()
                ->values();
            
            foreach ($monthsCollection as $monthNumber) {
                $availableMonths[] = [
                    'value' => $monthNumber,
                    'label' => Carbon::create()->month($monthNumber)->format('F')
                ];
            }
        } else {
            $monthsCollection = Order::get()
                ->map(function($order) {
                    return (int)$order->created_at->format('n');
                })
                ->unique()
                ->sort()
                ->values();
            
            foreach ($monthsCollection as $monthNumber) {
                $availableMonths[] = [
                    'value' => $monthNumber,
                    'label' => Carbon::create()->month($monthNumber)->format('F')
                ];
            }
        }

        // Get available days
        $availableDays = [];
        if ($this->yearFilter && $this->monthFilter) {
            $availableDays = Order::whereYear('created_at', $this->yearFilter)
                ->whereMonth('created_at', $this->monthFilter)
                ->get()
                ->map(function($order) {
                    return (int)$order->created_at->format('j');
                })
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        } elseif ($this->monthFilter) {
            $availableDays = Order::whereMonth('created_at', $this->monthFilter)
                ->get()
                ->map(function($order) {
                    return (int)$order->created_at->format('j');
                })
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return view('livewire.order.history', [
            'grouped' => $grouped,
            'tz' => $tz,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'availableDays' => $availableDays,
            'totalOrders' => $totalOrders,
            'loadedOrders' => count($orders),
            'hasMorePages' => $this->hasMorePages,
            'isLoading' => $this->isLoading,
        ]);
    }
}