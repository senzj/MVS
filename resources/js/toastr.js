// toastr.js (notification library)
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';  // Import the CSS too

// toastr configuration
toastr.options = {
  // Shows an "x" button to manually close the toast
  "closeButton": true,

  // Used for debugging; generally leave as false
  "debug": false,

  // Shows new toasts above older ones
  "newestOnTop": true,

  // Shows a timer/progress bar at the bottom of the toast
  "progressBar": true,

  // Sets the position on screen. Options include: 
  // "toast-top-right", "toast-bottom-right", "toast-bottom-left", "toast-top-left",
  // "toast-top-full-width", "toast-bottom-full-width", "toast-top-center", "toast-bottom-center"
  "positionClass": "toast-top-right",

  // Prevents showing duplicate toasts (same message)
  "preventDuplicates": false,

  // Callback function to run when the toast is clicked (null = no action)
  "onclick": null,

  // Time it takes to fade in the toast (in ms) (1000ms = 1 second)
  "showDuration": "500",

  // Time it takes to fade out the toast (in ms) (1000ms = 1 second)
  "hideDuration": "50000",

  // How long the toast stays visible before fading out (in ms) (1000ms = 1 second)
  "timeOut": "10000",

  // Extra time if the user hovers over the toast (in ms) (1000ms = 1 second)
  "extendedTimeOut": "8000",

  // Animation easing for showing the toast ("swing" or "linear")
  "showEasing": "swing",

  // Animation easing for hiding the toast ("swing" or "linear")
  "hideEasing": "linear",

  // jQuery method to use when showing toast ("fadeIn", "slideDown", or "show")
  "showMethod": "fadeIn",

  // jQuery method to use when hiding toast ("fadeOut", "slideUp", or "hide")
  "hideMethod": "slideUp"
};

// export toastr
export default toastr;