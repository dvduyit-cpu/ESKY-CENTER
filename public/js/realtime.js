document.addEventListener('DOMContentLoaded', () => {
    const widget = document.querySelector('[data-realtime-notifications]');
    if (!widget) return;
    const badge = widget.querySelector('[data-notification-count]');
    const itemsBox = widget.querySelector('[data-notification-items]');
    let lastServerTime = null;
    let initialized = false;

    const empty = message => {
        const box = document.createElement('div');
        box.className = 'empty-state py-4';
        box.innerHTML = '<i class="bi bi-bell-slash"></i>';
        const text = document.createElement('div');
        text.className = 'small mt-2';
        text.textContent = message;
        box.appendChild(text);
        return box;
    };

    const reminderLink = item => {
        const link = document.createElement('a');
        link.className = 'reminder-item text-decoration-none text-dark';
        link.href = item.url;
        const icon = document.createElement('i');
        icon.className = item.overdue ? 'bi bi-exclamation-circle-fill text-danger' : 'bi bi-bell-fill';
        const copy = document.createElement('span');
        const title = document.createElement('strong');
        title.textContent = item.title;
        const time = document.createElement('small');
        time.textContent = (item.overdue ? 'Đã quá hạn · ' : '') + item.time;
        copy.append(title, time);
        link.append(icon, copy);
        return link;
    };

    const summaryLink = item => {
        const link = document.createElement('a');
        link.className = 'reminder-item text-decoration-none text-dark';
        link.href = item.url;
        link.innerHTML = '<i class="bi bi-info-circle-fill"></i>';
        const copy = document.createElement('span');
        const title = document.createElement('strong');
        title.textContent = item.label;
        const count = document.createElement('small');
        count.textContent = item.count + ' nội dung cần chú ý';
        copy.append(title, count);
        link.appendChild(copy);
        return link;
    };

    const render = data => {
        badge.textContent = data.total > 99 ? '99+' : data.total;
        badge.classList.toggle('d-none', !data.enabled || data.total === 0);
        if (!data.enabled) {
            itemsBox.replaceChildren(empty('Thông báo đã tắt cho tài khoản này.'));
            initialized = true;
            lastServerTime = data.server_time;
            return;
        }
        const nodes = [...data.reminders.map(reminderLink), ...data.items.map(summaryLink)];
        itemsBox.replaceChildren(...(nodes.length ? nodes : [empty('Không có thông báo mới.')]));
        if (initialized && data.changed) {
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show realtime-toast shadow';
            toast.innerHTML = '<i class="bi bi-bell-fill me-2"></i>Có thông báo mới.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(toast);
            setTimeout(() => window.bootstrap?.Alert.getOrCreateInstance(toast).close(), 5000);
        }
        initialized = true;
        lastServerTime = data.server_time;
        document.dispatchEvent(new CustomEvent('esky:realtime-update', { detail: data }));
    };

    const poll = async () => {
        try {
            const url = new URL('/realtime/status', window.location.origin);
            if (lastServerTime) url.searchParams.set('since', lastServerTime);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            if (response.ok) render(await response.json());
        } catch (_) {}
    };

    poll();
    setInterval(poll, 15000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
});
