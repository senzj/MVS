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