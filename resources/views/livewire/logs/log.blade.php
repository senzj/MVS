@section('title', __('System Logs'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-5">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-shield-halved text-blue-500"></i>
                {{ __('System Audit Logs') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Monitor user actions, logins, devices, sessions, and account activity in one place.') }}
            </p>
        </div>
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm mb-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                    {{ __('Action') }}
                </label>
                <select wire:model.live="actionFilter"
                        class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    @foreach ($actionOptions as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                    {{ __('From') }}
                </label>
                <input type="date" wire:model.live="dateFrom"
                       class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                    {{ __('To') }}
                </label>
                <input type="date" wire:model.live="dateTo"
                       class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>
            <div class="flex items-end gap-2">
                <button type="button" wire:click="clearFilters"
                        class="w-full px-3 py-2.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-semibold transition">
                    {{ __('Clear') }}
                </button>
                <button type="button" wire:click="$refresh"
                        class="w-full px-3 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── Metric Cards ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-7 mb-5">
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($metrics['actions'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Logins') }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($metrics['logins'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Logouts') }}</p>
            <p class="mt-2 text-2xl font-bold text-orange-600">{{ number_format($metrics['logouts'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Failed Logins') }}</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">{{ number_format($metrics['failed_logins'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm col-span-2 sm:col-span-1">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Security Events') }}</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($metrics['security_events'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ number_format($metrics['unique_users'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm col-span-2 sm:col-span-1 xl:col-span-1">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Devices') }}</p>
            <p class="mt-2 text-2xl font-bold text-indigo-600">{{ number_format($metrics['unique_devices'] ?? 0) }}</p>
        </div>
    </div>

    {{-- ── Charts ───────────────────────────────────────────────────────────── --}}

    {{-- Activity Volume Chart --}}
    <div class="grid grid-cols-1 mb-5">
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Activity Volume') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('Daily system activity volume for the last 30 days') }}</p>
                </div>
            </div>
            <div class="h-48" wire:ignore>
                <canvas id="auditActionsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Authentication Trends and Device Types Charts --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2 mb-5">
        {{-- Authentication Trends Chart --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Authentication Trends') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('Logins, logouts, and failed logins by week') }}</p>
                </div>
            </div>
            <div class="h-48" wire:ignore>
                <canvas id="auditAuthTrendChart"></canvas>
            </div>
        </div>

        {{-- Action Breakdown Chart --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Action Breakdown') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('Share of key log actions in the selected period') }}</p>
                </div>
            </div>
            <div class="h-48" wire:ignore>
                <canvas id="auditActionBreakdownChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Login Hours and Action Breakdown Charts --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2 mb-5">
        {{-- Login Hours Chart --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Activity Hours') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('All logged activity by hour of day') }}</p>
                </div>
            </div>
            <div class="h-48" wire:ignore>
                <canvas id="auditLoginHourChart"></canvas>
            </div>
        </div>

        {{-- Device Types Chart --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Device Types') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('Browsers currently active in remembered devices') }}</p>
                </div>
            </div>
            <div class="h-48" wire:ignore>
                <canvas id="auditDeviceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Logs Table / Cards ───────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">

        {{-- Table header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-700">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Audit Log Entries') }}</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    {{ __('Showing :from–:to of :total entries', [
                        'from'  => $logs->firstItem() ?? 0,
                        'to'    => $logs->lastItem()  ?? 0,
                        'total' => $logs->total(),
                    ]) }}
                </p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500">
                <i class="fas fa-circle-info"></i>
                {{ __('Most recent first') }}
            </span>
        </div>

        <div class="">
            @if (count($logs) !== 0)

                {{-- ── Desktop table (md+) ─────────────────────────────────────── --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-700/40 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                                <th class="text-center px-4 py-3">{{ __('User') }}</th>
                                <th class="text-center px-4 py-3">{{ __('Action') }}</th>
                                <th class="text-center px-4 py-3">{{ __('Device') }}</th>
                                <th class="text-center px-4 py-3">{{ __('IP Address') }}</th>
                                <th class="text-center px-4 py-3">{{ __('Date & Time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($logs as $log)
                                @php
                                    $parsedAt = $log['created_at'] ? \Carbon\Carbon::parse($log['created_at']) : null;
                                @endphp
                                <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-700/30 transition-colors">
                                    {{-- User --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">
                                                    {{ substr($log['user_name'] ?? '?', 0, 1) }}
                                                </span>
                                            </div>
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate max-w-[120px]">
                                                {{ $log['user_name'] ?? __('System') }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Action badge --}}
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->actionBadgeClass($log['action']) }}">
                                            {{ $log['action_label'] ?? $log['action'] }}
                                        </span>
                                    </td>

                                    {{-- Device type --}}
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $deviceType = $this->deviceTypeLabel($log['user_agent'] ?? null);
                                        @endphp

                                        <div class="flex items-center justify-center gap-1.5 text-zinc-700 dark:text-zinc-200">
                                            @if ($deviceType === 'Mobile')
                                                <i class="fas fa-mobile-alt"></i>
                                            @elseif ($deviceType === 'Tablet')
                                                <i class="fas fa-tablet-alt"></i>
                                            @elseif ($deviceType === 'Bot')
                                                <i class="fas fa-robot"></i>
                                            @else
                                                <i class="fas fa-desktop"></i>
                                            @endif

                                            <div class="text-left">
                                                <div class="font-medium text-xs text-zinc-700 dark:text-zinc-200">
                                                    {{ $log['browser'] ?? __('Unknown') }}
                                                </div>

                                                <div class="text-xs text-zinc-400">
                                                    {{ $log['platform'] ?? __('Unknown') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- IP --}}
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-mono text-xs text-zinc-600 dark:text-zinc-300">
                                            {{ $log['ip_address'] ?? __('N/A') }}
                                        </span>
                                    </td>

                                    {{-- Date/time --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if ($parsedAt)
                                            <span class="text-zinc-700 dark:text-zinc-200 text-xs">{{ $parsedAt->translatedFormat('M d, Y') }}</span>
                                                <span class="block text-zinc-400 dark:text-zinc-500 text-xs">{{ $parsedAt->translatedFormat('h:i:s A') }}</span>
                                        @else
                                            <span class="text-zinc-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ── Mobile / Tablet cards (< md) ───────────────────────────── --}}
                <div class="md:hidden divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach ($logs as $log)
                        @php
                            $parsedAt = $log['created_at'] ? \Carbon\Carbon::parse($log['created_at']) : null;
                            $deviceType = $this->deviceTypeLabel($log['user_agent'] ?? null);
                        @endphp
                        <div class="px-4 py-3 hover:bg-zinc-50/60 dark:hover:bg-zinc-700/20 transition-colors">

                            {{-- Row 1: avatar + user + badge --}}
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">
                                            {{ substr($log['user_name'] ?? '?', 0, 1) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                            {{ $log['user_name'] ?? __('System') }}
                                        </p>
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                            {{ $parsedAt ? $parsedAt->translatedFormat('M d, Y · h:i A') : '—' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Action badge (right-aligned) --}}
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold flex-shrink-0 {{ $this->actionBadgeClass($log['action']) }}">
                                    {{ $log['action_label'] ?? $log['action'] }}
                                </span>
                            </div>

                            {{-- Row 2: IP + device --}}
                            <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400 pl-10">
                                <span class="inline-flex items-center gap-1">
                                    <i class="fas fa-globe w-3 text-center"></i>
                                    <span class="font-mono">{{ $log['ip_address'] ?? __('N/A') }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    @if ($deviceType === 'Mobile')
                                        <i class="fas fa-mobile-screen-button w-3 text-center"></i>
                                    @elseif ($deviceType === 'Tablet')
                                        <i class="fas fa-tablet-screen-button w-3 text-center"></i>
                                    @elseif ($deviceType === 'Bot')
                                        <i class="fas fa-robot w-3 text-center"></i>
                                    @else
                                        <i class="fas fa-desktop w-3 text-center"></i>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 font-medium text-zinc-700 dark:text-zinc-200">
                                    {{ $log['browser'] ?? __('Unknown') }} · {{ $log['platform'] ?? __('Unknown') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else

                {{-- Empty state --}}
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <div class="w-14 h-14 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-4">
                        <i class="fas fa-file-circle-xmark text-2xl text-zinc-400 dark:text-zinc-500"></i>
                    </div>
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('No logs found') }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('Try adjusting the filters above.') }}</p>
                </div>

            @endif
        </div>

        {{-- ── Pagination ──────────────────────────────────────────────────────── --}}
        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script id="audit-logs-chart-data" type="application/json">@json($metrics)</script>
        <script>
            // Localized labels for charts (used by resources/js/logs/audit-charts.js)
            window.__auditLogsI18n = {
                actions: '{{ __("Actions") }}',
                logins: '{{ __("Logins") }}',
                logouts: '{{ __("Logouts") }}',
                failed_logins: '{{ __("Failed Logins") }}',
                activity: '{{ __("Activity") }}',
                device_types: '{{ __("Device Types") }}',
                device_desktop: '{{ __("Desktop") }}',
                device_mobile: '{{ __("Mobile") }}',
                device_tablet: '{{ __("Tablet") }}',
                device_bot: '{{ __("Bot") }}'
            };
        </script>
        <script>
            const dispatchAuditLogsChartData = () => {
                const payload = JSON.parse(
                    document.getElementById('audit-logs-chart-data')?.textContent || '{}'
                );
                window.__auditLogsChartData = payload;
                window.dispatchEvent(new CustomEvent('audit-logs-data', { detail: { data: payload } }));
            };

            document.addEventListener('livewire:init', dispatchAuditLogsChartData);
            document.addEventListener('DOMContentLoaded', dispatchAuditLogsChartData, { once: true });
            window.addEventListener('livewire:navigated', dispatchAuditLogsChartData);
        </script>
    @endpush
</div>
