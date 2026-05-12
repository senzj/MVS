/* global Chart */

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
    } catch {}
    inventoryAuditChart = null;
  }

  const canvas = document.getElementById('inventoryAuditLineChart');
  const existing = canvas ? Chart.getChart?.(canvas) : null;
  if (existing) {
    try {
      existing.destroy();
    } catch {}
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

  const isDark = document.documentElement.classList.contains('dark');
  const tickColor = isDark ? '#a1a1aa' : '#71717a';
  const gridColor = isDark ? 'rgba(161,161,170,0.15)' : 'rgba(63,63,70,0.08)';

  destroyInventoryAuditChart();

  inventoryAuditChart = new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Additions',
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
          label: 'Deductions',
          data: removed,
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239,68,68,0.1)',
          pointRadius: 2,
          pointHoverRadius: 4,
          borderWidth: 2,
          fill: true,
          tension: 0.35,
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
          ticks: { color: tickColor, maxTicksLimit: 8 },
          grid: { color: gridColor },
        },
        y: {
          beginAtZero: true,
          ticks: { color: tickColor, precision: 0 },
          grid: { color: gridColor },
        },
      },
    },
  });
}
