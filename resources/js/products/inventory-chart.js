let inventoryAuditChart = null;

function getPayloadFromDom() {
    const dataEl = document.getElementById('inventory-audit-chart-data');
    if (!dataEl) return null;

    try {
        return JSON.parse(dataEl.textContent || '{}');
    } catch (error) {
        console.error('Failed to parse inventory chart payload', error);
        return null;
    }
}

export function destroyInventoryAuditChart() {
    if (inventoryAuditChart) {
        try {
            inventoryAuditChart.destroy();
        } catch {
            console.warn('Failed to destroy existing inventory audit chart');
        }

        inventoryAuditChart = null;
    }

    const canvas = document.getElementById('inventoryAuditLineChart');
    const existing = canvas ? Chart.getChart?.(canvas) : null;
    if (existing) {
        try {
            existing.destroy();
        } catch {
            console.warn('Failed to destroy existing inventory audit chart');
        }
    }
}

export default function initInventoryAuditChart(payload = null) {
    if (!window.Chart) return;

    const canvas = document.getElementById('inventoryAuditLineChart');
    if (!canvas) {
        destroyInventoryAuditChart();
        return;
    }

    const chartPayload = payload || getPayloadFromDom();
    if (!chartPayload) return;

    const labels = chartPayload.labels || [];
    const added = chartPayload.added || [];
    const removed = chartPayload.removed || [];
    const net = chartPayload.net || [];

    const isDark = document.documentElement.classList.contains('dark');
    const tickColor = isDark ? '#a1a1aa' : '#71717a';
    const gridColor = isDark ? 'rgba(161,161,170,0.15)' : 'rgba(63,63,70,0.08)';

    destroyInventoryAuditChart();

    const seriesLabels = chartPayload.series || {};

    inventoryAuditChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: seriesLabels.added || 'Additions',
                    data: added,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.15)',
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: seriesLabels.removed || 'Deductions',
                    data: removed,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: seriesLabels.net || 'Net Movement',
                    data: net,
                    borderColor: '#3b82f6',
                    backgroundColor: 'transparent',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.35,
                    borderDash: [5, 5],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: tickColor,
                        boxWidth: 12,
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: tickColor,
                        maxTicksLimit: 8
                    },
                    grid: {
                        color: gridColor
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: tickColor,
                        precision: 0
                    },
                    grid: {
                        color: gridColor
                    },
                },
            },
        },
    });
}

let stockDistributionChart = null;

export function destroyStockDistributionChart() {
    if (stockDistributionChart) {
        try {
            stockDistributionChart.destroy();
        } catch {
            console.warn('Failed to destroy existing stock distribution chart');
        }

        stockDistributionChart = null;
    }

    const canvas = document.getElementById('stockDistributionChart');
    const existing = canvas ? Chart.getChart?.(canvas) : null;

    if (existing) {
        try {
            existing.destroy();
        } catch {
            console.warn('Failed to destroy existing stock distribution chart');
        }
    }
}

export function initStockDistributionChart(payload = null) {
    if (!window.Chart) return;

    const canvas = document.getElementById('stockDistributionChart');
    if (!canvas) {
            destroyStockDistributionChart();
            return;
    }

    const dataEl = document.getElementById('stock-distribution-chart-data');
    const chartPayload = payload || (dataEl ? JSON.parse(dataEl.textContent || '{}') : null);
    if (!chartPayload?.labels) return;

    const colors = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
        '#ec4899', '#06b6d4', '#6366f1', '#14b8a6', '#f97316',
        '#e11d48', '#22c55e', '#2563eb', '#db2777', '#0891b2',
        '#4f46e5', '#d946ef', '#0ea5e9', '#be185d', '#0f766e',
        '#8b5cf6', '#f43f5e', '#06b6d4', '#a855f7', '#ec4899',
    ];

    const isDark = document.documentElement.classList.contains('dark');
    const tickColor = isDark ? '#a1a1aa' : '#71717a';

    destroyStockDistributionChart();

    stockDistributionChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: chartPayload.labels || [],
            datasets: [{
                data: chartPayload.data || [],
                backgroundColor: colors.slice(0, (chartPayload.labels || []).length),
                borderColor: isDark ? '#27272a' : '#fff',
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: tickColor,
                        boxWidth: 12,
                        padding: 15,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value + ' units';
                        }
                    }
                }
            },
        },
    });
}

let movementTypeChart = null;

export function destroyMovementTypeChart() {
    if (movementTypeChart) {
        try {
            movementTypeChart.destroy();
        } catch {
            console.warn('Failed to destroy existing movement type chart');
        }

        movementTypeChart = null;
    }

    const canvas = document.getElementById('movementTypeChart');
    const existing = canvas ? Chart.getChart?.(canvas) : null;
    if (existing) {
        try {
            existing.destroy();
        } catch {
            console.warn('Failed to destroy existing movement type chart');
        }
    }
}

export function initMovementTypeChart(payload = null) {
    if (!window.Chart) return;

    const canvas = document.getElementById('movementTypeChart');
    if (!canvas) {
        destroyMovementTypeChart();
        return;
    }

    const dataEl = document.getElementById('movement-type-chart-data');
    const chartPayload = payload || (dataEl ? JSON.parse(dataEl.textContent || '{}') : null);
    if (!chartPayload?.labels) return;

    const typeColors = {
        'Order Created': '#ef4444',
        'Order Updated': '#f97316',
        'Order Cancelled': '#10b981',
        'Refund': '#3b82f6',
        'Manual Adjustment': '#8b5cf6',
        'Restock': '#ec4899',
        'Transfer': '#06b6d4',
        '': '#6b7280',
    };

    const colors = chartPayload.labels.map(label => typeColors[label] || '#6b7280');

    const isDark = document.documentElement.classList.contains('dark');
    const tickColor = isDark ? '#a1a1aa' : '#71717a';

    destroyMovementTypeChart();

    movementTypeChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: chartPayload.labels || [],
            datasets: [{
                data: chartPayload.data || [],
                backgroundColor: colors,
                borderColor: isDark ? '#27272a' : '#fff',
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: tickColor,
                        boxWidth: 12,
                        padding: 15,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value + ' movements';
                        }
                    }
                }
            },
        },
    });
}
