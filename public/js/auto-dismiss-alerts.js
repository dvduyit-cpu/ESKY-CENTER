document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((alertElement) => {
        const delay = Number.parseInt(alertElement.dataset.autoDismiss, 10) || 5000;

        window.setTimeout(() => {
            if (!alertElement.isConnected) return;

            if (window.bootstrap?.Alert) {
                window.bootstrap.Alert.getOrCreateInstance(alertElement).close();
                return;
            }

            alertElement.style.transition = 'opacity .3s ease';
            alertElement.style.opacity = '0';
            window.setTimeout(() => alertElement.remove(), 300);
        }, delay);
    });
});
