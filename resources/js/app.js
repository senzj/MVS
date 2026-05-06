// jquery import
import $ from 'jquery';

// toastr import
import toastr from 'toastr';
import './toastr';

// Chartjs import
import Chart from 'chart.js/auto';
// import './Chart';

// global variables
window.$ = window.jQuery = $;
window.toastr = toastr; // Add this line to make toastr globally available
window.Chart = Chart; // Add this line to make Chart.js globally available

import initDashboardCharts, { destroyDashboardCharts } from './dashboard-charts';

function setNavigationOverlayVisible(isVisible) {
  const overlay = document.getElementById('app-navigation-overlay');

  if (!overlay) {
    return;
  }

  overlay.classList.toggle('hidden', !isVisible);
  overlay.classList.toggle('flex', isVisible);
}

function registerDashboardChartsListener() {
  if (window.__dashboardChartsListenerRegistered) return;
  window.__dashboardChartsListenerRegistered = true;

  window.addEventListener('dashboard-charts-data', (event) => {
    const payload = event.detail?.data || {};
    window.__dashboardChartsPayload = payload; // remember last data
    initDashboardCharts(payload);
  });

  // Rebuild charts after any Livewire DOM update (captures language changes)
  document.addEventListener('livewire:message.processed', () => {
    if (window.__dashboardChartsPayload) {
      destroyDashboardCharts();
      initDashboardCharts(window.__dashboardChartsPayload);
    }
  });

  // Optional: cleanup when navigating away
  window.addEventListener('livewire:navigating', () => {
    destroyDashboardCharts();
  });
}

function registerNavigationOverlayListener() {
  if (window.__navigationOverlayListenerRegistered) return;
  window.__navigationOverlayListenerRegistered = true;

  window.addEventListener('livewire:navigating', () => {
    setNavigationOverlayVisible(true);
  });

  window.addEventListener('livewire:navigated', () => {
    setNavigationOverlayVisible(false);
  });

  window.addEventListener('pageshow', () => {
    setNavigationOverlayVisible(false);
  });
}

// Ensure listener is registered when Livewire and DOM are ready
window.addEventListener('livewire:init', () => {
  registerDashboardChartsListener();
  registerNavigationOverlayListener();
});
if (document.readyState === 'complete' || document.readyState === 'interactive') {
  registerDashboardChartsListener();
  registerNavigationOverlayListener();
} else {
  document.addEventListener('DOMContentLoaded', registerDashboardChartsListener);
  document.addEventListener('DOMContentLoaded', registerNavigationOverlayListener);
}
