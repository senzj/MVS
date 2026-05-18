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

        console.debug('orders:initPaymentStatusChart payload', !!payload, data && data.labels ? data.labels.length : 0);

        destroyPaymentStatusChart();

        if (!data?.labels?.length || !data?.datasets) {
            console.debug('orders:initPaymentStatusChart aborted - missing labels/datasets');
            return;
        }

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
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const ordersWord = (window.__ordersI18n && window.__ordersI18n.orders) || 'orders';
                            return ' ' + value + ' ' + ordersWord;
                        }
                    }
                }
            },
        },
        });
    } catch (err) { console.error('initPaymentStatusChart', err); }
}

export function initPaymentMethodsChart(payload = null) {
    try {
        if (payload) __lastPaymentMethodsPayload = payload;
        const data = payload ?? __lastPaymentMethodsPayload;

        console.debug('orders:initPaymentMethodsChart payload', !!payload, data && data.labels ? data.labels.length : 0);

        destroyPaymentMethodsChart();

        if (!data?.labels?.length || !data?.datasets) {
            console.debug('orders:initPaymentMethodsChart aborted - missing labels/datasets');
            return;
        }

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
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const ordersWord = (window.__ordersI18n && window.__ordersI18n.orders) || 'orders';
                                    return ' ' + value + ' ' + ordersWord;
                            }
                        }
                    }
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

    // Helper to safely read JSON payload from DOM script tags
    const readDOMChartData = (id) => {
        try {
            const el = document.getElementById(id);
            if (!el) {
                console.debug('orders:readDOMChartData - element not found', id);
                return null;
            }
            const txt = el.textContent || el.innerText || '';
            if (!txt) return null;
            return JSON.parse(txt);
        } catch (err) {
            // swallow parse errors
            console.debug('orders:readDOMChartData parse error', id, err);
            return null;
        }
    };

    // Attempt initial init from DOM payloads (useful on first load)
    try {
        const statusPayload = readDOMChartData('payment-status-chart-data') || __lastPaymentStatusPayload;
        if (statusPayload) initPaymentStatusChart(statusPayload);

        const methodsPayload = readDOMChartData('payment-methods-chart-data') || __lastPaymentMethodsPayload;
        if (methodsPayload) initPaymentMethodsChart(methodsPayload);
    } catch (err) {
        console.error('orders charts initial init', err);
    }

    // After Livewire re-renders, re-init from last known payload
        document.addEventListener('livewire:message.processed', () => {
            try {
                // On Livewire update, try to read DOM script tags; if not available yet, retry shortly.
                const tryInit = () => {
                    try {
                        if (document.getElementById('paymentStatusChart')) {
                            const p = readDOMChartData('payment-status-chart-data') || __lastPaymentStatusPayload;
                            if (p) initPaymentStatusChart(p);
                        }

                        if (document.getElementById('paymentMethodsChart')) {
                            const p = readDOMChartData('payment-methods-chart-data') || __lastPaymentMethodsPayload;
                            if (p) initPaymentMethodsChart(p);
                        }
                        return true;
                    } catch (err) {
                        return false;
                    }
                };

                if (!tryInit()) {
                    // DOM might not be fully updated yet; retry a couple times
                    setTimeout(() => { tryInit(); }, 50);
                    setTimeout(() => { tryInit(); }, 150);
                }
            } catch (err) {
                console.error('orders charts reinit', err);
            }
        });

    window.addEventListener('livewire:navigating', () => {
        destroyPaymentStatusChart();
        destroyPaymentMethodsChart();
        window.__ordersChartsReady = false;
    });
}

export default {
    initPaymentStatusChart,
    destroyPaymentStatusChart,
    initPaymentMethodsChart,
    destroyPaymentMethodsChart,
    registerOrdersChartListeners,
};
