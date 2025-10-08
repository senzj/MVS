/* global Chart */
const state = (window.__dashboardCharts ??= {});

function destroyByCanvas(canvas) {
  const existing = Chart.getChart?.(canvas);
  if (existing) {
    try { existing.destroy(); } catch {}
  }
}

function makeChart(key, canvas, config) {
  if (!canvas) return;

  if (state[key]) {
    try { state[key].destroy(); } catch {}
    delete state[key];
  }

  destroyByCanvas(canvas);
  state[key] = new Chart(canvas, config);
}

// Helper function to get locale for Intl
function getIntlLocale() {
    const locale = window.__appLocale || 'en';
    return locale === 'zh' ? 'zh-CN' : locale;
}

// Helper function to translate date labels using Intl
function translateDateLabels(labels, type = 'day') {
  if (!labels) return [];
  const locale = getIntlLocale();

  return labels.map(label => {
    // Try to parse the label as a date
    let date;

    if (type === 'day') {
      // For day labels like "Mon", "Tue" - create a date for that day
      // Use Dec 30, 2024 which is a Monday, so days align correctly
      const dayMap = { Mon: 0, Tue: 1, Wed: 2, Thu: 3, Fri: 4, Sat: 5, Sun: 6 };
      if (dayMap.hasOwnProperty(label)) {
        date = new Date(2024, 11, 30 + dayMap[label]); // Dec 30, 2024 is Monday
        return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date);
      }
    } else if (type === 'month') {
      // For month labels like "Jan", "Feb" or "Sept 09", "Sep 11"
      const monthMap = { Jan: 0, Feb: 1, Mar: 2, Apr: 3, May: 4, Jun: 5, Jul: 6, Aug: 7, Sep: 8, Sept: 8, Oct: 9, Nov: 10, Dec: 11 };

      // Try to parse as "Month DD" format (e.g., "Sept 09", "Sep 11")
      const parts = label.split(' ');
      if (parts.length === 2 && monthMap.hasOwnProperty(parts[0])) {
        const day = parseInt(parts[1], 10);
        date = new Date(2024, monthMap[parts[0]], day);
        return new Intl.DateTimeFormat(locale, { month: 'short', day: 'numeric' }).format(date);
      }

      // Parse as just month abbreviation (e.g., "Jan", "Feb")
      if (monthMap.hasOwnProperty(label)) {
        date = new Date(2024, monthMap[label], 1);
        return new Intl.DateTimeFormat(locale, { month: 'short' }).format(date);
      }
    }

    return label; // Return original if can't translate
  });
}

// Helper function to translate category labels
function translateCategoryLabels(labels) {
  if (!labels) return [];
  return labels.map(label => window.__categoryMap?.[label] || label);
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

  const i18n = window.__dashboardI18n || {};

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
        labels: translateDateLabels(salesVsProfitData.labels, 'month'), // Translate month labels
        datasets: [
          { label: i18n.sales || 'Sales (₱)', data: salesVsProfitData.sales, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.1)', tension: 0.4, yAxisID: 'y' },
          { label: i18n.estimated_profit || 'Estimated Profit (₱)', data: salesVsProfitData.profit, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', tension: 0.4, yAxisID: 'y' },
          { label: i18n.orders || 'Orders', data: salesVsProfitData.orders, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', tension: 0.4, yAxisID: 'y1' },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          y: { type: 'linear', position: 'left', title: { display: true, text: i18n.amount_currency || 'Amount (₱)' } },
          y1:{ type: 'linear', position: 'right', title: { display: true, text: i18n.num_orders || 'Number of Orders' }, grid: { drawOnChartArea: false } },
        },
      },
    });
  }

  // Orders by Day
  if (ordersByDayData.labels?.length) {
    makeChart('ordersByDay', document.getElementById('ordersByDayChart'), {
      type: 'bar',
      data: {
        labels: translateDateLabels(ordersByDayData.labels, 'day'),
        datasets: [
          { label: i18n.current_week || 'Current Week', data: ordersByDayData.current_week, backgroundColor: 'rgba(59,130,246,.8)', borderColor: '#3b82f6', borderWidth: 1 },
          { label: i18n.previous_week || 'Previous Week', data: ordersByDayData.previous_week, backgroundColor: 'rgba(156,163,175,.8)', borderColor: '#9ca3af', borderWidth: 1 },
        ],
      },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: i18n.num_orders || 'Number of Orders' } } } },
    });
  }

  // Monthly Trends
  if (monthlyTrendsData.labels?.length) {
    makeChart('monthlyTrends', document.getElementById('monthlyTrendsChart'), {
      type: 'line',
      data: {
        labels: translateDateLabels(monthlyTrendsData.labels, 'month'),
        datasets: [
          { label: i18n.monthly_sales || 'Monthly Sales (₱)', data: monthlyTrendsData.sales, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.1)', tension: 0.4, yAxisID: 'y' },
          { label: i18n.monthly_orders || 'Monthly Orders', data: monthlyTrendsData.orders, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', tension: 0.4, yAxisID: 'y1' },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          y: { type: 'linear', position: 'left', title: { display: true, text: i18n.sales_amount_currency || 'Sales Amount (₱)' } },
          y1:{ type: 'linear', position: 'right', title: { display: true, text: i18n.num_orders || 'Number of Orders' }, grid: { drawOnChartArea: false } },
        },
      },
    });
  }

  // Category Breakdown
  if (categoryBreakdownData.labels?.length) {
    makeChart('categoryBreakdown', document.getElementById('categoryBreakdownChart'), {
      type: 'doughnut',
      data: {
        labels: translateCategoryLabels(categoryBreakdownData.labels),
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
