self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = event.notification.data?.url || '/';
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
        const existing = windows.find(client => new URL(client.url).pathname === new URL(target, self.location.origin).pathname);
        if (existing) return existing.focus();
        return clients.openWindow(target);
    }));
});
