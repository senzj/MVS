<script>
(function () {
    const TOAST_MSG  = @js(__('Please fill in all required fields.'));
    const ERROR_CLS  = ['!border-red-500', 'ring-2', 'ring-red-500/60', 'ring-offset-0'];

    /**
     * Given a dotted field key (e.g. "selectedEmployeeId", "orderItems.0.product_id"),
     * return every DOM element that should be highlighted for it.
     */
    function resolveEls(field) {
        // Exact data-field match (covers simple fields AND indexed item fields)
        const direct = Array.from(document.querySelectorAll(`[data-field="${field}"]`));
        if (direct.length) return direct;

        // Fallback: orderItems.* without a sub-key → highlight all product buttons
        if (field === 'orderItems') {
            return Array.from(document.querySelectorAll('[data-field^="orderItems."][data-field$=".product_id"]'));
        }

        return [];
    }

    function clearHighlight(el) {
        el.classList.remove('form-field-error', ...ERROR_CLS);
    }

    function applyHighlight(el) {
        el.classList.add('form-field-error', ...ERROR_CLS);

        // Auto-clear on any user interaction
        const off = () => { clearHighlight(el); el.removeEventListener('input', off); el.removeEventListener('change', off); el.removeEventListener('click', off); };
        el.addEventListener('input',  off, { once: true });
        el.addEventListener('change', off, { once: true });
        el.addEventListener('click',  off, { once: true });
    }

    function handleValidationFailed(errorFields) {
        // Clear previous highlights
        document.querySelectorAll('.form-field-error').forEach(clearHighlight);

        let highlighted = 0;

        (errorFields ?? []).forEach(field => {
            resolveEls(field).forEach(el => {
                applyHighlight(el);
                highlighted++;
            });
        });

        if (highlighted > 0) {
            // Scroll to the first highlighted element
            const first = document.querySelector('.form-field-error');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Always show the toastr (even if 0 highlights — e.g. "no product" case)
        if (window.toastr) {
            toastr.error(TOAST_MSG, '', {
                positionClass: 'toast-bottom-left',
                timeOut:       4500,
                closeButton:   true,
                progressBar:   true,
                newestOnTop:   true,
            });
        }
    }

    // Wire up after Livewire boots
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('form-validation-failed', ({ errorFields }) => {
            handleValidationFailed(errorFields);
        });
    });
})();
</script>
