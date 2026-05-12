let __paymentStatusChart = null;
let __paymentMethodsChart = null;
let __lastPaymentStatusPayload = null;
let __lastPaymentMethodsPayload = null;
let __listenersRegistered = false;

export function destroyPaymentStatusChart() {
    try {
        if (__paymentStatusChart) {
            __paymentStatusChart.destroy();
            __paymentStatusChart = null;
        }
    } catch (e) { console.error(e); }
}

export function destroyPaymentMethodsChart() {
    try {
        if (__paymentMethodsChart) {
            __paymentMethodsChart.destroy();
            __paymentMethodsChart = null;
        }
    } catch (e) { console.error(e); }
}

export function initPaymentStatusChart(payload = null) {
    try {
        if (payload) __lastPaymentStatusPayload = payload;
        const data = payload ?? __lastPaymentStatusPayload;

        destroyPaymentStatusChart();

        if (!data?.labels?.length || !data?.datasets) return;

        const canvas = document.getElementById('paymentStatusChart');
        if (!canvas) return;

        __paymentStatusChart = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: data.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
            },
        },
        });
    } catch (err) { console.error('initPaymentStatusChart', err); }
}

export function initPaymentMethodsChart(payload = null) {
    try {
        if (payload) __lastPaymentMethodsPayload = payload;
        const data = payload ?? __lastPaymentMethodsPayload;

        destroyPaymentMethodsChart();

        if (!data?.labels?.length || !data?.datasets) return;

        const canvas = document.getElementById('paymentMethodsChart');
        if (!canvas) return;

        __paymentMethodsChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: data.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    } catch (err) { console.error('initPaymentMethodsChart', err); }
}

/**
 * Call once from app.js. Registers all event listeners needed for
 * the orders history charts, including Livewire re-render recovery.
 */
export function registerOrdersChartListeners() {
    if (__listenersRegistered) return;
    __listenersRegistered = true;

    window.addEventListener('payment-status-chart-data', (e) => {
        initPaymentStatusChart(e.detail?.data ?? null);
    });

    window.addEventListener('payment-methods-chart-data', (e) => {
        initPaymentMethodsChart(e.detail?.data ?? null);
    });

    // Signal to any pending inline scripts that listeners are now attached
    window.__ordersChartsReady = true;
    window.dispatchEvent(new CustomEvent('orders-charts-ready'));

    // After Livewire re-renders, re-init from last known payload
    document.addEventListener('livewire:message.processed', () => {
        if (document.getElementById('paymentStatusChart')) {
            // Prefer window.__pending* (freshest render data) over cached payload
            const p = window.__pendingPaymentStatusChartData ?? __lastPaymentStatusPayload;
            if (p) initPaymentStatusChart(p);
        }
        if (document.getElementById('paymentMethodsChart')) {
            const p = window.__pendingPaymentMethodsChartData ?? __lastPaymentMethodsPayload;
            if (p) initPaymentMethodsChart(p);
        }
    });

    window.addEventListener('livewire:navigating', () => {
        destroyPaymentStatusChart();
        destroyPaymentMethodsChart();
        window.__ordersChartsReady = false;
        __listenersRegistered = false;
    });
}

export default {
    initPaymentStatusChart,
    destroyPaymentStatusChart,
    initPaymentMethodsChart,
    destroyPaymentMethodsChart,
    registerOrdersChartListeners,
};