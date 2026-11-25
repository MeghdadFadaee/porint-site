<script>
    document.addEventListener('livewire:init', () => {
        let expirationShown = false;

        Livewire.interceptRequest(({ onError }) => {
            onError(({ response, body, preventDefault }) => {
                const expired = response.status === 419;
                const emptyResponse = response.ok && body.trim() === '';

                if (!expired && !emptyResponse) return;

                preventDefault();
                if (expired && expirationShown) return;
                expirationShown ||= expired;

                const message = expired
                    ? @js(__('admin.livewire.expired'))
                    : @js(__('admin.livewire.empty_response'));

                if (window.confirm(message)) window.location.reload();
            });
        });
    }, { once: true });
</script>
