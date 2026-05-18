// jquery import
import $ from 'jquery';

// toastr import
import toastr from 'toastr';
import './toastr';

// Chartjs import
import Chart from 'chart.js/auto';

// global variables
window.$ = window.jQuery = $;
window.toastr = toastr;
window.Chart = Chart;

// Main dashboard page charts
import initDashboardCharts, { destroyDashboardCharts } from './dashboard-charts';

// product inventory audit charts
import initInventoryAuditChart, {
    destroyInventoryAuditChart,
    initStockDistributionChart,
    destroyStockDistributionChart,
    initMovementTypeChart,
    destroyMovementTypeChart
} from './products/inventory-chart';

import initAuditLogsCharts, { destroyAuditLogsCharts } from './logs/audit-charts';

// orders history charts — registerOrdersChartListeners handles EVERYTHING internally
import { registerOrdersChartListeners } from './orders/charts';

    function setNavigationOverlayVisible(isVisible) {
    const overlay = document.getElementById('app-navigation-overlay');
    if (!overlay) return;
    overlay.classList.toggle('hidden', !isVisible);
    overlay.classList.toggle('flex', isVisible);
}

function registerDashboardChartsListener() {
    if (window.__dashboardChartsListenerRegistered) return;
    window.__dashboardChartsListenerRegistered = true;

    window.addEventListener('dashboard-charts-data', (event) => {
        const payload = event.detail?.data || {};
        window.__dashboardChartsPayload = payload;
        initDashboardCharts(payload);
    });

    document.addEventListener('livewire:message.processed', () => {
        if (window.__dashboardChartsPayload) {
        destroyDashboardCharts();
        initDashboardCharts(window.__dashboardChartsPayload);
        }
    });

    window.addEventListener('livewire:navigating', () => {
        destroyDashboardCharts();
    });
}

function registerInventoryAuditChartListener() {
    if (window.__inventoryAuditChartListenerRegistered) return;
    window.__inventoryAuditChartListenerRegistered = true;

    const build = (payload = null) => {
        try {
            initInventoryAuditChart(payload);
        } catch (error) {
            console.error('Failed to initialize inventory audit chart', error);
        }
    };

    window.addEventListener('inventory-audit-chart-data', (event) => {
        const payload = event.detail?.data || null;
        window.__inventoryAuditChartPayload = payload;
        build(payload);
    });

    document.addEventListener('livewire:message.processed', () => {
        build(window.__inventoryAuditChartPayload || null);
    });

    window.addEventListener('livewire:navigating', () => destroyInventoryAuditChart());
    window.addEventListener('livewire:navigated', () => build(window.__inventoryAuditChartPayload || null));

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        build(window.__inventoryAuditChartPayload || null);

    } else {
        document.addEventListener(
            'DOMContentLoaded', () => build(
                window.__inventoryAuditChartPayload || null
            )
        );
    }
}

function registerStockDistributionChartListener() {
    if (window.__stockDistributionChartListenerRegistered) return;
    window.__stockDistributionChartListenerRegistered = true;

    const build = (payload = null) => {
        try {
            initStockDistributionChart(payload);
        } catch (error) {
            console.error('Failed to initialize stock distribution chart', error);
        }
    };

    window.addEventListener('stock-distribution-chart-data', (event) => {
        const payload = event.detail?.data || null;
        window.__stockDistributionChartPayload = payload;
        build(payload);
    });

    document.addEventListener('livewire:message.processed', () => {
        build(window.__stockDistributionChartPayload || null);
    });

    window.addEventListener('livewire:navigating', () => destroyStockDistributionChart());

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        build(window.__stockDistributionChartPayload || null);

    } else {
        document.addEventListener(
            'DOMContentLoaded', () => build(
                window.__stockDistributionChartPayload || null)
            );
    }
}

function registerMovementTypeChartListener() {
    if (window.__movementTypeChartListenerRegistered) return;
    window.__movementTypeChartListenerRegistered = true;

    const build = (payload = null) => {
        try {
            initMovementTypeChart(payload);

        } catch (error) {
            console.error('Failed to initialize movement type chart', error);
        }
    };

    window.addEventListener('movement-type-chart-data', (event) => {
        const payload = event.detail?.data || null;
        window.__movementTypeChartPayload = payload;
        build(payload);
    });

    document.addEventListener('livewire:message.processed', () => {
        build(window.__movementTypeChartPayload || null);
    });

    window.addEventListener('livewire:navigating', () => destroyMovementTypeChart());

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        build(window.__movementTypeChartPayload || null);

    } else {
        document.addEventListener(
            'DOMContentLoaded', () => build(
                window.__movementTypeChartPayload || null
            )
        );
    }
}

function registerNavigationOverlayListener() {
    if (window.__navigationOverlayListenerRegistered) return;
    window.__navigationOverlayListenerRegistered = true;

    window.addEventListener('livewire:navigating', () => setNavigationOverlayVisible(true));
    window.addEventListener('livewire:navigated', () => setNavigationOverlayVisible(false));
    window.addEventListener('pageshow', () => setNavigationOverlayVisible(false));
}

function registerAuditLogsChartListener() {
    if (window.__auditLogsChartListenerRegistered) return;
    window.__auditLogsChartListenerRegistered = true;

    const build = (payload = null) => {
        try {
            initAuditLogsCharts(payload);
        } catch (error) {
            console.error('Failed to initialize audit logs charts', error);
        }
    };

    window.addEventListener('audit-logs-data', (event) => {
        const payload = event.detail?.data || null;
        window.__auditLogsChartPayload = payload;
        build(payload);
    });

    document.addEventListener('livewire:message.processed', () => {
        build(window.__auditLogsChartPayload || null);
    });

    window.addEventListener('livewire:navigating', () => destroyAuditLogsCharts());

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        build(window.__auditLogsChartPayload || null);
    } else {
        document.addEventListener('DOMContentLoaded', () => build(window.__auditLogsChartPayload || null));
    }
}

function refreshAuditLogsChartsFromDom() {
    const dataScript = document.getElementById('audit-logs-chart-data');
    if (!dataScript) return;

    try {
        const payload = JSON.parse(dataScript.textContent || '{}');
        window.__auditLogsChartPayload = payload;
        initAuditLogsCharts(payload);
    } catch (error) {
        console.error('Failed to read audit logs chart data', error);
    }
}

// ─── Bootstrap ───────────────────────────────────────────────────────────────

function bootAll() {
    registerDashboardChartsListener();
    registerNavigationOverlayListener();
    registerInventoryAuditChartListener();
    registerStockDistributionChartListener();
    registerMovementTypeChartListener();
    registerAuditLogsChartListener();
    registerOrdersChartListeners();
    refreshAuditLogsChartsFromDom();
}

window.addEventListener('livewire:init', bootAll);
window.addEventListener('livewire:navigated', refreshAuditLogsChartsFromDom);

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    bootAll();
} else {
    document.addEventListener('DOMContentLoaded', bootAll);
}
