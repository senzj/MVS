/* global Chart */
const state = (window.__dashboardCharts ??= {});

function destroyByCanvas(canvas) {
  // Chart.js v3/v4
  const existing = Chart.getChart?.(canvas);
  if (existing) {
    try { existing.destroy(); } catch {}
  }
}

function makeChart(key, canvas, config) {
  if (!canvas) return;

  // Destroy by key (stored reference)
  if (state[key]) {
    try { state[key].destroy(); } catch {}
    delete state[key];
  }

  // Destroy any chart already attached to this canvas
  destroyByCanvas(canvas);

  state[key] = new Chart(canvas, config);
}

export function destroyDashboardCharts() {
  Object.values(state).forEach(c => { try { c.destroy(); } catch {} });
  Object.keys(state).forEach(k => delete state[k]);
}

export function initDashboardCharts(payload) {
  if (!window.Chart) {
    console.error('Chart.js not available');
    return;
  }
  const isDark = document.documentElement.classList.contains('dark');
  Chart.defaults.color = isDark ? '#a1a1aa' : '#71717a';
  Chart.defaults.borderColor = isDark ? '#3f3f46' : '#e4e4e7';

  const {
    salesVsProfitData = { labels: [], sales: [], profit: [], orders: [] },
    ordersByDayData = { labels: [], current_week: [], previous_week: [] },
    monthlyTrendsData = { labels: [], sales: [], orders: [] },
    categoryBreakdownData = { labels: [], data: [], colors: [] },
  } = payload || {};

  // Sales vs Profit
  if (salesVsProfitData.labels?.length) {
    makeChart('salesVsProfit', document.getElementById('salesVsProfitChart'), {
      type: 'line',
      data: {
        labels: salesVsProfitData.labels,
        datasets: [
          { label: 'Sales (₱)', data: salesVsProfitData.sales, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.1)', tension: 0.4, yAxisID: 'y' },
          { label: 'Estimated Profit (₱)', data: salesVsProfitData.profit, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', tension: 0.4, yAxisID: 'y' },
          { label: 'Orders', data: salesVsProfitData.orders, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', tension: 0.4, yAxisID: 'y1' },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          y: { type: 'linear', position: 'left', title: { display: true, text: 'Amount (₱)' } },
          y1:{ type: 'linear', position: 'right', title: { display: true, text: 'Number of Orders' }, grid: { drawOnChartArea: false } },
        },
      },
    });
  }

  // Orders by Day
  if (ordersByDayData.labels?.length) {
    makeChart('ordersByDay', document.getElementById('ordersByDayChart'), {
      type: 'bar',
      data: {
        labels: ordersByDayData.labels,
        datasets: [
          { label: 'Current Week', data: ordersByDayData.current_week, backgroundColor: 'rgba(59,130,246,.8)', borderColor: '#3b82f6', borderWidth: 1 },
          { label: 'Previous Week', data: ordersByDayData.previous_week, backgroundColor: 'rgba(156,163,175,.8)', borderColor: '#9ca3af', borderWidth: 1 },
        ],
      },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Orders' } } } },
    });
  }

  // Monthly Trends
  if (monthlyTrendsData.labels?.length) {
    makeChart('monthlyTrends', document.getElementById('monthlyTrendsChart'), {
      type: 'line',
      data: {
        labels: monthlyTrendsData.labels,
        datasets: [
          { label: 'Monthly Sales (₱)', data: monthlyTrendsData.sales, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.1)', tension: 0.4, yAxisID: 'y' },
          { label: 'Monthly Orders', data: monthlyTrendsData.orders, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', tension: 0.4, yAxisID: 'y1' },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          y: { type: 'linear', position: 'left', title: { display: true, text: 'Sales Amount (₱)' } },
          y1:{ type: 'linear', position: 'right', title: { display: true, text: 'Number of Orders' }, grid: { drawOnChartArea: false } },
        },
      },
    });
  }

  // Category Breakdown
  if (categoryBreakdownData.labels?.length) {
    makeChart('categoryBreakdown', document.getElementById('categoryBreakdownChart'), {
      type: 'doughnut',
      data: {
        labels: categoryBreakdownData.labels,
        datasets: [{
          data: categoryBreakdownData.data,
          backgroundColor: categoryBreakdownData.colors,
          borderWidth: 2,
          borderColor: isDark ? '#3f3f46' : '#ffffff',
        }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } },
    });
  }
}

export default initDashboardCharts;