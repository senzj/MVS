<?php

namespace App\Livewire\Logs;

use App\Models\User;
use App\Services\System\AuditLogsService;
use App\Models\AuditLogs;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Log extends Component
{
    use WithPagination;

    public string $actionFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';

    public array $actionOptions = [
        'all'                => 'All Actions',
        'auth.login'         => 'Logins',
        'auth.logout'        => 'Logouts',
        'auth.failed_login'  => 'Failed Logins',
        'account.created'    => 'Account Created',
        'account.deleted'    => 'Account Deleted',
        'session.revoked'    => 'Session Revoked',
        'device.removed'     => 'Device Removed',
    ];

    public array $metrics    = [];
    public array $recentLogs = [];

    public function mount(AuditLogsService $auditLogsService): void
    {
        $this->loadData($auditLogsService);
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->loadData(app(AuditLogsService::class));
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->loadData(app(AuditLogsService::class));
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
        $this->loadData(app(AuditLogsService::class));
    }

    protected function loadData(AuditLogsService $auditLogsService): void
    {
        $from = $this->dateFrom !== '' ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $to   = $this->dateTo   !== '' ? Carbon::parse($this->dateTo)->endOfDay()   : null;

        $this->metrics = $auditLogsService->dashboardMetrics($from, $to);
    }

    public function clearFilters(): void
    {
        $this->actionFilter = 'all';
        $this->dateFrom     = '';
        $this->dateTo       = '';
        $this->resetPage();
        $this->loadData(app(AuditLogsService::class));
    }

    public function actionLabel(string $action, array $oldValues = [], array $newValues = []): string
    {
        // Explicit static map
        $staticMap = [
            // Auth
            'auth.login'         => fn () => __('Logged in'),
            'auth.logout'        => fn () => __('Logged out'),
            'auth.failed_login'  => fn () => __('Failed login attempt'),

            // Accounts
            'account.created'    => fn () => __('Created account: :name', ['name' => $newValues['name'] ?? $newValues['username'] ?? __('account')]),
            'account.deleted'    => fn () => __('Deleted account: :name', ['name' => $oldValues['name'] ?? $oldValues['username'] ?? __('account')]),

            // Sessions / devices
            'session.revoked'    => fn () => __('Revoked session for :name', ['name' => $this->resolveSubjectName($oldValues)]),
            'device.removed'     => fn () => __('Removed device for :name',  ['name' => $this->resolveSubjectName($oldValues)]),

            // Orders
            'order.created'           => fn () => __('Created order :receipt', ['receipt' => $newValues['receipt_number'] ?? '']),
            'order.updated'           => fn () => __('Updated order :receipt', ['receipt' => $newValues['receipt_number'] ?? '']),
            'order.deleted'           => fn () => __('Deleted order :receipt', ['receipt' => $oldValues['receipt_number'] ?? '']),
            'order.cancelled'         => fn () => __('Cancelled order :receipt', ['receipt' => $newValues['receipt_number'] ?? '']),
            'order.payment_confirmed' => fn () => __('Confirmed payment for order :receipt', ['receipt' => $newValues['receipt_number'] ?? '']),
            'order.refunded'          => fn () => __('Refunded ₱:amount on order :receipt', [
                'amount'  => number_format($newValues['refund_amount'] ?? 0, 2),
                'receipt' => $newValues['receipt_number'] ?? '',
            ]),
            'order.backdated' => fn () => __('Added backdated sales record :receipt (sale date: :date)', [
                'receipt' => $newValues['receipt_number'] ?? '',
                'date'    => $newValues['sale_date']      ?? '',
            ]),

            // Customers
            'customer.created' => fn () => __('Created customer :name', ['name' => $newValues['name'] ?? '']),
            'customer.updated' => fn () => __('Updated customer :name', ['name' => $newValues['name'] ?? '']),
            'customer.deleted' => fn () => __('Deleted customer :name', ['name' => $oldValues['name'] ?? '']),

            // Employees
            'employee.created'  => fn () => __('Created employee :name', ['name' => $newValues['name'] ?? '']),
            'employee.updated'  => fn () => __('Updated employee :name', ['name' => $newValues['name'] ?? '']),
            'employee.archived' => fn () => __('Archived employee :name', ['name' => $oldValues['name'] ?? '']),
            'employee.restored' => fn () => __('Restored employee :name', ['name' => $newValues['name'] ?? '']),
            'employee.deleted'  => fn () => __('Permanently deleted employee :name', ['name' => $oldValues['name'] ?? '']),

            // Products
            'product.created'        => fn () => __('Created product :name', ['name' => $newValues['name'] ?? '']),
            'product.updated'        => fn () => __('Updated product :name', ['name' => $newValues['name'] ?? '']),
            'product.archived'       => fn () => __('Archived product :name', ['name' => $oldValues['name'] ?? '']),
            'product.restored'       => fn () => __('Restored product :name', ['name' => $newValues['name'] ?? '']),
            'product.deleted'        => fn () => __('Permanently deleted product :name', ['name' => $oldValues['name'] ?? '']),
            'product.stock_adjusted' => fn () => __(
                ':name — :direction :amount unit(s) (:reason)',
                [
                    'name'      => $newValues['name']      ?? '',
                    'direction' => ucfirst($newValues['direction'] ?? 'adjusted'),
                    'amount'    => $newValues['amount']    ?? 0,
                    'reason'    => $newValues['reason']    ?? 'manual',
                ]
            ),
        ];

        if (isset($staticMap[$action])) {
            return ($staticMap[$action])();
        }

        // Livewire actions
        if (str_starts_with($action, 'livewire.')) {
            return $this->livewireActionLabel($action, $oldValues, $newValues);
        }

        // Generic HTTP fallback
        if (str_starts_with($action, 'http.')) {
            $parts = explode('.', $action, 3); // ['http', 'POST', 'route.name']
            $method = strtoupper($parts[1] ?? '');
            $route  = $parts[2] ?? '';
            return "{$method} {$route}";
        }

        return __(ucwords(str_replace('.', ' ', $action)));
    }

    protected function livewireActionLabel(string $action, array $oldValues = [], array $newValues = []): string
    {
        // action format: livewire.{component-memo-name}.{method}
        // e.g. livewire.partials.orders.modal.payment.confirm-payment
        //   or livewire.logs.users.revoke-session

        // Strip the leading "livewire." and split off the last segment as the method
        $stripped = substr($action, strlen('livewire.'));
        $lastDot  = strrpos($stripped, '.');

        if ($lastDot === false) {
            return $stripped;
        }

        $componentPath = substr($stripped, 0, $lastDot);   // e.g. "logs.users"
        $method        = substr($stripped, $lastDot + 1);   // e.g. "revoke-session"

        // ── Explicit component+method map ─────────────────────────────────────
        // Key format: "component.path::methodName" (method is lowercased, hyphens/underscores normalised)
        $livewireMap = [
            // Orders – dashboard
            'order.dashboard::startdelivery'          => __('Started delivery'),
            'order.dashboard::markdelivered'          => __('Marked order as delivered'),
            'order.dashboard::markfinished'           => __('Marked order as completed'),
            'order.dashboard::cancelorder'            => __('Cancelled order'),
            'order.dashboard::deleteorderconfirmed'   => __('Deleted order'),
            'order.dashboard::cancelprepare'          => __('Cancelled order preparation'),
            'order.dashboard::togglepaid'             => __('Opened payment modal'),
            'order.dashboard::vieworderdetails'       => __('Viewed order details'),
            'order.dashboard::clearfilters'           => __('Cleared order filters'),

            // Orders – create / add / edit
            'order.create::createorder'               => __('Created new order'),
            'order.create::processpayment'            => __('Processed payment for new order'),
            'order.add::createsalesrecord'            => __('Saved sales record (add)'),
            'order.edit::save'                        => __('Saved order edits'),
            'order.edit::cancel'                      => __('Cancelled order from edit'),

            // Orders – history
            'order.history::deleteorderconfirmed'     => __('Deleted order from history'),

            // Payments modal
            'partials.orders.modal.payment::confirmpayment' => __('Confirmed payment'),

            // Refund modal
            'partials.orders.modal.refund::confirmrefund'   => __('Processed refund'),

            // Products
            'product.dashboard::createproduct'        => __('Created product'),
            'product.dashboard::updateproduct'        => __('Updated product'),
            'product.dashboard::archiveproduct'       => __('Archived product'),
            'product.dashboard::deleteproduct'        => __('Deleted product'),
            'product.dashboard::makeavailable'        => __('Made product available'),

            // Customers
            'customer.dashboard::createcustomer'      => __('Created customer'),
            'customer.dashboard::updatecustomer'      => __('Updated customer'),
            'customer.dashboard::deletecustomer'      => __('Deleted customer'),

            // Employees
            'employee.dashboard::createemployee'      => __('Created employee'),
            'employee.dashboard::updateemployee'      => __('Updated employee'),
            'employee.dashboard::deleteemployee'      => __('Archived employee'),
            'employee.dashboard::restoreemployee'     => __('Restored employee'),
            'employee.archive::restoreemployee'       => __('Restored archived employee'),
            'employee.archive::permanentlydeletemployee' => __('Permanently deleted employee'),

            // Users / sessions / devices
            'logs.users::createaccount'               => __('Created user account'),
            'logs.users::deleteaccount'               => __('Deleted user account'),
            'logs.users::removedevice'                => __('Removed remembered device'),
            'logs.users::revokesession'               => __('Revoked session'),

            // Language switcher (whatever component holds it)
            'main.language-switcher::selectlanguage'  => __('Changed language'),
            'partials.language-switcher::selectlanguage' => __('Changed language'),
        ];

        $lookupKey = $componentPath . '::' . $this->normaliseMethod($method);

        if (isset($livewireMap[$lookupKey])) {
            return $livewireMap[$lookupKey];
        }

        // ── Heuristic fallback for unmapped methods ───────────────────────────
        $normalised = $this->normaliseMethod($method);

        // Read-only / navigation calls – not very interesting, but label them cleanly
        $readOnlyPrefixes = ['view', 'open', 'close', 'load', 'get', 'set', 'show', 'render',
                            'updated', 'toggle', 'clear', 'reset', 'check', 'poll'];

        foreach ($readOnlyPrefixes as $prefix) {
            if (str_starts_with($normalised, $prefix)) {
                $what = ucfirst(trim(str_replace(
                    ['view', 'open', 'close', 'load', 'get', 'set', 'show', 'render',
                    'updated', 'toggle', 'clear', 'reset', 'check', 'poll'],
                    '',
                    $normalised
                )));
                return $what ? ucfirst($prefix) . ' ' . $what : ucfirst($prefix);
            }
        }

        // Last resort: turn component path + method into readable words
        $componentLabel = ucwords(str_replace(['.', '-', '_'], ' ', $componentPath));
        $methodLabel    = ucwords(str_replace(['-', '_'], ' ', $method));

        return "{$componentLabel} – {$methodLabel}";
    }

    /**
     * Lowercase and strip hyphens/underscores so lookups are consistent
     * regardless of whether Livewire serialised the name with kebab-case or camelCase.
     */
    protected function normaliseMethod(string $method): string
    {
        return strtolower(str_replace(['-', '_'], '', $method));
    }

    public function browserLabel(?string $userAgent = null): string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/') => 'Chrome',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'safari/') => 'Safari',
            default => __('Unknown'),
        };
    }

    public function platformLabel(?string $userAgent = null): string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'windows') => __('Windows'),
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => __('macOS'),
            str_contains($ua, 'android') => __('Android'),
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => __('iOS'),
            str_contains($ua, 'linux') => __('Linux'),
            default => __('Unknown'),
        };
    }

    public function deviceTypeLabel(?string $userAgent = null): string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'ipad'), str_contains($ua, 'tablet') => __('Tablet'),
            str_contains($ua, 'mobi'), str_contains($ua, 'android'), str_contains($ua, 'iphone') => __('Mobile'),
            str_contains($ua, 'bot'), str_contains($ua, 'crawl'), str_contains($ua, 'spider') => __('Bot'),
            default => __('Desktop'),
        };
    }

    /** Badge colour class for a given action string. */
    public function actionBadgeClass(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'auth.login')    => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
            str_starts_with($action, 'auth.logout')   => 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-300',
            str_starts_with($action, 'auth.failed')   => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
            str_starts_with($action, 'account.')      => 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300',
            str_starts_with($action, 'session.')      => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300',
            str_starts_with($action, 'device.')       => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
            default                                   => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
        };
    }

    protected function resolveSubjectName(array $values): string
    {
        $userId = $values['user_id'] ?? null;

        if ($userId) {
            $name = User::query()->whereKey($userId)->value('name');

            if ($name) {
                return $name;
            }
        }

        return $values['name'] ?? $values['username'] ?? $values['browser'] ?? $values['platform'] ?? __('item');
    }

    public function render()
    {
        $from = $this->dateFrom !== '' ? Carbon::parse($this->dateFrom)->startOfDay() : null;
        $to   = $this->dateTo   !== '' ? Carbon::parse($this->dateTo)->endOfDay()     : null;

        $logs = AuditLogs::with('user')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('created_at', '<=', $to))
            ->when(
                $this->actionFilter !== 'all',
                fn ($q) => $q->where('action', $this->actionFilter)
            )
            ->latest()
            ->paginate(25);

        // Map to primitives exactly like before, but on the paginated collection
        $mappedLogs = $logs->through(fn ($log) => [
            'user_name'    => $log->user?->name ?? null,
            'action'       => $log->action,
            'action_label' => $this->actionLabel($log->action, $log->old_values ?? [], $log->new_values ?? []),
            'ip_address'   => $log->ip_address ?? null,
            'user_agent'   => $log->user_agent ?? null,
            'browser'      => $this->browserLabel($log->user_agent ?? null),
            'platform'     => $this->platformLabel($log->user_agent ?? null),
            'created_at'   => $log->created_at?->toIso8601String(),
        ]);

        return view('livewire.logs.log', [
            'logs' => $mappedLogs,
        ]);
    }
}
