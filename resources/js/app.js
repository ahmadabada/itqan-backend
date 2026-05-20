import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

// Bridge: Livewire components dispatch a generic `notify` event
// ({ type: 'error'|'success'|'warning'|'info', message }). Flux's <flux:toast />
// listens for `toast-show` with { text, variant: 'danger'|'success'|... }.
// Without this bridge, notify events are silently swallowed.
document.addEventListener('livewire:init', () => {
    Livewire.on('notify', (event) => {
        const payload = Array.isArray(event) ? event[0] : event;
        const variantMap = {
            error: 'danger',
            success: 'success',
            warning: 'warning',
            info: 'info',
        };
        const variant = variantMap[payload?.type] ?? 'info';
        document.dispatchEvent(new CustomEvent('toast-show', {
            detail: { text: payload?.message ?? '', variant },
        }));
    });
});
