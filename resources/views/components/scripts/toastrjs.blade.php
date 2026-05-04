<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Wait a bit for toastr to be fully loaded
        setTimeout(function() {
            if (typeof toastr !== 'undefined') {

                @if(session('success'))
                    console.log('Showing success:', @json(session('success')));
                    toastr.success(@json(session('success')));
                @endif

                @if(session('error'))
                    console.log('Showing error:', @json(session('error')));
                    toastr.error(@json(session('error')));
                @endif

                @if(session('warning'))
                    toastr.warning(@json(session('warning')));
                @endif

                @if(session('info'))
                    toastr.info(@json(session('info')));
                @endif

                @if(session('message'))
                    toastr.info(@json(session('message')));
                @endif

                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        toastr.error('{{ addslashes($error) }}');
                    @endforeach
                @endif
            } else {
                console.error('Toastr is still not defined after timeout!');
            }
        }, 100);
    });

    // Also listen for Livewire events
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-success', (data) => {
            if (typeof toastr !== 'undefined') {
                toastr.success(data[0].message);
            }
        });

        Livewire.on('show-error', (data) => {
            if (typeof toastr !== 'undefined') {
                toastr.error(data[0].message);
            }
        });

        Livewire.on('show-warning', (data) => {
            if (typeof toastr !== 'undefined') {
                toastr.warning(data[0].message);
            }
        });

        Livewire.on('show-info', (data) => {
            if (typeof toastr !== 'undefined') {
                toastr.info(data[0].message);
            }
        });
    });
</script>
