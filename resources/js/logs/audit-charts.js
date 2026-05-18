/* global Chart */
const state = (window.__auditLogsCharts ??= {});

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

export function destroyAuditLogsCharts() {
  Object.values(state).forEach((chart) => { try { chart.destroy(); } catch {} });
  Object.keys(state).forEach((key) => delete state[key]);
}

export default function initAuditLogsCharts(payload) {
  if (!window.Chart) return;

  const isDark = document.documentElement.classList.contains('dark');
  Chart.defaults.color = isDark ? '#a1a1aa' : '#71717a';
  Chart.defaults.borderColor = isDark ? '#3f3f46' : '#e4e4e7';

  const data = payload || {};

  const actionLabels = data.action_labels || [];
  const actionsByDay = data.actions_by_day || [];
  const authTrendLabels = data.auth_trend_labels || [];
  const authLoginsByWeek = data.auth_logins_by_week || [];
  const authLogoutsByWeek = data.auth_logouts_by_week || [];
  const authFailedByWeek = data.auth_failed_by_week || [];
  const activityHourLabels = data.activity_hour_labels || [];
  const activityByHour = data.activity_by_hour || [];
  const deviceTypeLabels = data.device_type_labels || Object.keys(data.device_types || {});
  // deviceTypeLabels are keys like 'desktop', 'mobile', 'tablet', 'bot'.
  const deviceTypes = deviceTypeLabels.map((label) => data.device_types?.[label] ?? data.device_types?.[label.toLowerCase()] ?? 0);
  // Localized display labels using window.__auditLogsI18n (e.g. device_desktop)
  const displayDeviceLabels = deviceTypeLabels.map((key) => {
    return (window.__auditLogsI18n?.['device_' + key] ) || (window.__auditLogsI18n?.['device_' + key.toLowerCase()]) || key;
  });
  const actionBreakdownLabels = data.action_breakdown_labels || [];
  const actionBreakdownValues = data.action_breakdown_values || [];

  makeChart('actionsByDay', document.getElementById('auditActionsChart'), {
    type: 'line',
    data: {
      labels: actionLabels,
      datasets: [{
        label: (window.__auditLogsI18n?.actions) || 'Actions',
        data: actionsByDay,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.12)',
        fill: true,
        tension: 0.35,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
    },
  });

  makeChart('authTrends', document.getElementById('auditAuthTrendChart'), {
    type: 'line',
    data: {
      labels: authTrendLabels,
      datasets: [
        {
          label: (window.__auditLogsI18n?.logins) || 'Logins',
          data: authLoginsByWeek,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.12)',
          fill: true,
          tension: 0.35,
        },
        {
          label: (window.__auditLogsI18n?.logouts) || 'Logouts',
          data: authLogoutsByWeek,
          borderColor: '#f97316',
          backgroundColor: 'rgba(249, 115, 22, 0.12)',
          fill: true,
          tension: 0.35,
        },
        {
          label: (window.__auditLogsI18n?.failed_logins) || 'Failed Logins',
          data: authFailedByWeek,
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239, 68, 68, 0.12)',
          fill: true,
          tension: 0.35,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
      scales: { y: { beginAtZero: true } },
    },
  });

  makeChart('activityHours', document.getElementById('auditLoginHourChart'), {
    type: 'bar',
    data: {
      labels: activityHourLabels,
      datasets: [{
        label: (window.__auditLogsI18n?.activity) || 'Activity',
        data: activityByHour,
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderRadius: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } },
    },
  });

      makeChart('deviceTypes', document.getElementById('auditDeviceChart'), {
    type: 'doughnut',
      data: {
      labels: displayDeviceLabels,
      datasets: [{
        data: deviceTypes,
        backgroundColor: ['#3b82f6', '#8b5cf6', '#f97316', '#10b981', '#ef4444'],
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    },
  });

  makeChart('actionBreakdown', document.getElementById('auditActionBreakdownChart'), {
    type: 'doughnut',
    data: {
      labels: actionBreakdownLabels,
      datasets: [{
        data: actionBreakdownValues,
        backgroundColor: ['#10b981', '#f97316', '#ef4444', '#8b5cf6', '#0ea5e9', '#eab308', '#6366f1'],
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
    },
  });
}
