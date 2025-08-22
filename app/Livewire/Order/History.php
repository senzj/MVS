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
    }

    public function updatedMonthFilter(): void
    {
        // Reset day when month changes
        $this->dayFilter = '';
    }

    public function render()
    {
        $tz = config('app.timezone');

        // Build the query
        $query = Order::with(['customer','employee'])
            ->when($this->search, function($q) {
                $q->where(function($subQuery) {
                    $subQuery->where('receipt_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function($customerQuery) {
                            $customerQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })

            // Filter by status
            ->when($this->statusFilter, function($q) {
                $q->where('status', $this->statusFilter);
            })

            // Filter by payment status
            ->when($this->paymentFilter !== '', function($q) {
                $q->where('is_paid', $this->paymentFilter === 'paid');
            })

            // Filter by year
            ->when($this->yearFilter, function($q) {
                $q->whereYear('created_at', $this->yearFilter);
            })

            // Filter by month
            ->when($this->monthFilter, function($q) {
                $q->whereMonth('created_at', $this->monthFilter);
            })

            // Filter by day
            ->when($this->dayFilter, function($q) {
                $q->whereDay('created_at', $this->dayFilter);
            });

        // Apply sorting with proper handling for receipt_number
        if ($this->sortBy === 'receipt_number') {
            if ($this->sortDirection === 'desc') {
                $orders = $query->get()->sortByDesc(function($order) {
                    if (preg_match('/(\d+)$/', $order->receipt_number, $matches)) {
                        return (int)$matches[1];
                    }
                    return $order->receipt_number;
                });
            } else {
                $orders = $query->get()->sortBy(function($order) {
                    if (preg_match('/(\d+)$/', $order->receipt_number, $matches)) {
                        return (int)$matches[1];
                    }
                    return $order->receipt_number;
                });
            }
        } else {
            $orders = $query->orderBy($this->sortBy, $this->sortDirection)->get();
        }

        // Build grouping
        $grouped = [];
        foreach ($orders as $o) {
            $dt = $o->created_at->setTimezone($tz);
            $year = $dt->format('Y');
            $monthKey = $dt->format('Y-m');
            $dayKey = $dt->toDateString();

            $grouped[$year][$monthKey][$dayKey][] = $o;
        }

        // Sort each day's orders in ascending order (oldest first within the day)
        foreach ($grouped as $year => &$months) {
            foreach ($months as $monthKey => &$days) {
                foreach ($days as $dayKey => &$dayOrders) {
                    if ($this->sortBy === 'receipt_number') {
                        // Sort by receipt number (ascending within day)
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
                        // Sort by created_at time (ascending within day - earliest first)
                        usort($dayOrders, function($a, $b) {
                            return $a->created_at <=> $b->created_at;
                        });
                    }
                }
            }
        }

        // Sort groups by date for display (this controls the order of date groups)
        if ($this->sortBy === 'created_at') {
            if ($this->sortDirection === 'asc') {
                krsort($grouped); // Years in descending order (2025, 2024, 2023...)
                foreach ($grouped as $y => &$months) {
                    ksort($months); // Months in ascending order (Jan, Feb, Mar...)
                    foreach ($months as $m => &$days) {
                        ksort($days); // Days in ascending order (1, 2, 3...)
                    }
                }
            } else {
                ksort($grouped); // Years in ascending order (2023, 2024, 2025...)
                foreach ($grouped as $y => &$months) {
                    ksort($months); // Months in ascending order (Jan, Feb, Mar...)
                    foreach ($months as $m => &$days) {
                        ksort($days); // Days in ascending order (1, 2, 3...)
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

        // Get available months for current year OR all months if no year selected
        $availableMonths = [];
        if ($this->yearFilter) {
            // User has selected a specific year - show months for that year
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
            // No year selected - show all months that have orders
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

        // Get available days for current year/month OR all days if no filters
        $availableDays = [];
        if ($this->yearFilter && $this->monthFilter) {
            // Both year and month selected
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
            // Only month selected (any year)
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

        Log::info("fetched Dates: ", [
            'yearFilter' => $this->yearFilter,
            'monthFilter' => $this->monthFilter,
            'years' => $availableYears,
            'months' => $availableMonths,
            'days' => $availableDays,
        ]);

        return view('livewire.order.history', [
            'grouped' => $grouped,
            'tz' => $tz,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'availableDays' => $availableDays,
            'totalOrders' => $orders->count(),
        ]);
    }
}