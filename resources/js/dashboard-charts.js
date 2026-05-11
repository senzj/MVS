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

function initBusiestMetricToggle() {
  const root = document.getElementById('busiest-metrics-toggle');
  if (!root) return;

  const buttons = Array.from(root.querySelectorAll('[data-busiest-target]'));
  const panels = Array.from(document.querySelectorAll('[data-busiest-panel]'));
  if (!buttons.length || !panels.length) return;

  const activeClasses = ['bg-indigo-600', 'text-white', 'dark:bg-indigo-500', 'shadow-sm'];
  const inactiveClasses = ['text-zinc-700', 'dark:text-zinc-200'];

  const setActive = (target) => {
    panels.forEach((panel) => {
      panel.classList.toggle('hidden', panel.dataset.busiestPanel !== target);
    });

    buttons.forEach((button) => {
      const isActive = button.dataset.busiestTarget === target;
      button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

      if (isActive) {
        button.classList.add(...activeClasses);
        button.classList.remove(...inactiveClasses);
      } else {
        button.classList.remove(...activeClasses);
        button.classList.add(...inactiveClasses);
      }
    });

    // Force Chart.js to recalculate dimensions for the newly visible canvas.
    requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
  };

  if (!root.dataset.bound) {
    buttons.forEach((button) => {
      button.addEventListener('click', () => setActive(button.dataset.busiestTarget));
    });
    root.dataset.bound = '1';
  }

  const initial = buttons.find(button => button.getAttribute('aria-pressed') === 'true') || buttons[0];
  if (initial) setActive(initial.dataset.busiestTarget);
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
    busiestMetrics = null,
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

  // Busiest / Most Profitable
  if (busiestMetrics) {
    try {
      const byYear = busiestMetrics.by_year || { labels: [], orders: [], profit: [], summary: {} };
      const byMonth = busiestMetrics.by_month || { labels: [], orders: [], profit: [], summary: {} };
      const byWeekday = busiestMetrics.by_weekday || { labels: [], orders: [], profit: [], summary: {} };
      const byHour = busiestMetrics.by_hour || { labels: [], orders: [], profit: [], summary: {} };

      if ((byYear.labels || []).length) {
        makeChart('busiestByYear', document.getElementById('busiestByYearChart'), {
          type: 'bar',
          data: {
            labels: byYear.labels,
            datasets: [
              { label: i18n.orders || 'Orders', data: byYear.orders, backgroundColor: 'rgba(59,130,246,.85)', borderColor: '#3b82f6', yAxisID: 'y' },
              { label: i18n.estimated_profit || 'Estimated Profit (₱)', data: byYear.profit, backgroundColor: 'rgba(16,185,129,.6)', borderColor: '#10b981', yAxisID: 'y1' },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: i18n.num_orders || 'Number of Orders' } }, y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: i18n.amount_currency || 'Amount (₱)' } } } },
        });
      }

      if ((byMonth.labels || []).length) {
        makeChart('busiestByMonth', document.getElementById('busiestByMonthChart'), {
          type: 'bar',
          data: {
            labels: translateDateLabels(byMonth.labels, 'month'),
            datasets: [
              { label: i18n.orders || 'Orders', data: byMonth.orders, backgroundColor: 'rgba(99,102,241,.85)', borderColor: '#6366f1', yAxisID: 'y' },
              { label: i18n.sales || 'Sales (₱)', data: byMonth.revenue, backgroundColor: 'rgba(249,115,22,.6)', borderColor: '#f97316', yAxisID: 'y1' },
              { label: i18n.estimated_profit || 'Estimated Profit (₱)', data: byMonth.profit, backgroundColor: 'rgba(59,130,246,.6)', borderColor: '#3b82f6', yAxisID: 'y1' },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: i18n.num_orders || 'Number of Orders' } }, y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: i18n.amount_currency || 'Amount (₱)' } } } },
        });
      }

      if ((byWeekday.labels || []).length) {
        makeChart('busiestByWeekday', document.getElementById('busiestByWeekdayChart'), {
          type: 'bar',
          data: {
            labels: translateDateLabels(byWeekday.labels, 'day'),
            datasets: [
              { label: i18n.orders || 'Orders', data: byWeekday.orders, backgroundColor: 'rgba(34,197,94,.85)', borderColor: '#22c55e', xAxisID: 'x1' },
              { label: i18n.sales || 'Sales (₱)', data: byWeekday.revenue, backgroundColor: 'rgba(59,130,246,.6)', borderColor: '#3b82f6', xAxisID: 'x' },
              { label: i18n.estimated_profit || 'Estimated Profit (₱)', data: byWeekday.profit, backgroundColor: 'rgba(148,163,184,.6)', borderColor: '#94a3b8', xAxisID: 'x' },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, position: 'bottom', title: { display: true, text: i18n.amount_currency || 'Amount (₱)' }, ticks: { callback: (v)=> ('₱' + Number(v).toLocaleString()) } }, x1: { beginAtZero: true, position: 'top', grid: { drawOnChartArea: false }, title: { display: true, text: i18n.num_orders || 'Number of Orders' }, ticks: { callback: (v)=> Number(v).toLocaleString() } } } },
        });
      }

      if ((byHour.labels || []).length) {
        makeChart('busiestByHour', document.getElementById('busiestByHourChart'), {
          type: 'bar',
          data: {
            labels: byHour.labels,
            datasets: [
              { label: i18n.orders || 'Orders', data: byHour.orders, backgroundColor: 'rgba(148,163,184,.85)', borderColor: '#94a3b8', xAxisID: 'x1' },
              { label: i18n.sales || 'Sales (₱)', data: byHour.revenue, backgroundColor: 'rgba(99,102,241,.6)', borderColor: '#6366f1', xAxisID: 'x' },
              { label: i18n.estimated_profit || 'Estimated Profit (₱)', data: byHour.profit, backgroundColor: 'rgba(34,197,94,.6)', borderColor: '#22c55e', xAxisID: 'x' },
            ],
          },
          options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, position: 'bottom', title: { display: true, text: i18n.amount_currency || 'Amount (₱)' }, ticks: { callback: (v)=> ('₱' + Number(v).toLocaleString()) } }, x1: { beginAtZero: true, position: 'top', grid: { drawOnChartArea: false }, title: { display: true, text: i18n.num_orders || 'Number of Orders' }, ticks: { callback: (v)=> Number(v).toLocaleString() } } } },
        });
      }
    } catch (e) {
      // fail gracefully
      console.error('Error rendering busiest metrics charts', e);
    }
  }

  // Populate textual summaries (most/least) for each dimension
  if (busiestMetrics && busiestMetrics.by_year) {
    const s = busiestMetrics;
    const setText = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };

    const ysum = s.by_year.summary || {};
    if (ysum.most_orders) setText('year-summary-most-orders', `${i18n.most_busy || 'Most orders'}: ${ysum.most_orders.label} (${ysum.most_orders.value})`);
    if (ysum.most_profit) setText('year-summary-most-profit', `${i18n.most_profitable || 'Most profit'}: ${ysum.most_profit.label} (${ysum.most_profit.value})`);

    const msum = s.by_month.summary || {};
    if (msum.most_orders) setText('month-summary-most-orders', `${i18n.most_busy || 'Most orders'}: ${msum.most_orders.label} (${msum.most_orders.value})`);
    if (msum.most_profit) setText('month-summary-most-profit', `${i18n.most_profitable || 'Most profit'}: ${msum.most_profit.label} (${msum.most_profit.value})`);

    const wsum = s.by_weekday.summary || {};
    if (wsum.most_orders) setText('weekday-summary-most-orders', `${i18n.most_busy || 'Most orders'}: ${wsum.most_orders.label} (${wsum.most_orders.value})`);
    if (wsum.most_profit) setText('weekday-summary-most-profit', `${i18n.most_profitable || 'Most profit'}: ${wsum.most_profit.label} (${wsum.most_profit.value})`);

    const hsum = s.by_hour.summary || {};
    if (hsum.most_orders) setText('hour-summary-most-orders', `${i18n.most_busy || 'Most orders'}: ${hsum.most_orders.label} (${hsum.most_orders.value})`);
    if (hsum.most_profit) setText('hour-summary-most-profit', `${i18n.most_profitable || 'Most profit'}: ${hsum.most_profit.label} (${hsum.most_profit.value})`);
  }

  initBusiestMetricToggle();

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

  // Category Breakdown (horizontal bar)
  if (categoryBreakdownData.labels?.length) {
    makeChart('categoryBreakdown', document.getElementById('categoryBreakdownChart'), {
      type: 'bar',
      data: {
        labels: translateCategoryLabels(categoryBreakdownData.labels),
        datasets: [{
          label: i18n.sales || 'Sales (₱)',
          data: categoryBreakdownData.data,
          backgroundColor: categoryBreakdownData.colors,
          borderWidth: 1,
          borderColor: isDark ? '#3f3f46' : '#ffffff',
        }],
      },
      options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { callback: (v)=> ('₱' + Number(v).toLocaleString()) } } }, plugins: { legend: { display: false } } },
    });
  }
}

export default initDashboardCharts;
