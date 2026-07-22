document.addEventListener('DOMContentLoaded', () => {
    const topbar = document.querySelector('.topbar');
    if (!topbar) return;

    const userMenu = topbar.querySelector(':scope > .dropdown');
    const widget = document.createElement('div');
    widget.className = 'dropdown realtime-widget ms-auto me-2';
    widget.innerHTML = `<button class="btn realtime-bell" type="button" data-bs-toggle="dropdown" aria-label="Thông báo cập nhật"><i class="bi bi-bell-fill"></i><span class="realtime-badge d-none">0</span></button><div class="dropdown-menu dropdown-menu-end realtime-menu"><div class="px-3 py-2 fw-bold border-bottom">Cập nhật gần thời gian thực</div><div class="p-2 border-bottom" data-notification-permission><button class="btn btn-sm btn-primary w-100" type="button"><i class="bi bi-phone-vibrate"></i>Bật thông báo điện thoại</button><div class="small text-muted mt-1 px-1">Cần HTTPS và quyền thông báo của trình duyệt.</div></div><div data-realtime-items><div class="p-3 small text-muted">Đang kiểm tra...</div></div><div class="px-3 py-2 border-top small text-muted">Tự cập nhật mỗi 45 giây</div></div>`;
    topbar.insertBefore(widget, userMenu || null);

    const badge = widget.querySelector('.realtime-badge');
    const itemsBox = widget.querySelector('[data-realtime-items]');
    const permissionBox = widget.querySelector('[data-notification-permission]');
    const endpoint = '/realtime/status';
    let lastServerTime = null;
    let initialized = false;
    let serviceWorkerRegistration = null;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/service-worker.js').then(registration => { serviceWorkerRegistration = registration; }).catch(() => {});
    }
    const syncPermission = () => {
        if (!('Notification' in window) || !window.isSecureContext) {
            permissionBox.querySelector('button').disabled = true;
            permissionBox.querySelector('.small').textContent = 'Thiết bị cần truy cập website bằng HTTPS để bật thông báo.';
            return;
        }
        if (Notification.permission === 'granted') permissionBox.classList.add('d-none');
        else if (Notification.permission === 'denied') {
            permissionBox.querySelector('button').disabled = true;
            permissionBox.querySelector('.small').textContent = 'Thông báo đang bị chặn. Hãy mở cài đặt trình duyệt để cấp lại quyền.';
        }
    };
    permissionBox.querySelector('button').addEventListener('click', async event => {
        event.stopPropagation();
        if (!('Notification' in window) || !window.isSecureContext) return syncPermission();
        await Notification.requestPermission();
        syncPermission();
    });
    syncPermission();

    const showToast = () => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-info alert-dismissible fade show realtime-toast shadow';
        alert.dataset.autoDismiss = '5000';
        alert.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Dữ liệu trung tâm vừa có cập nhật mới.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alert);
        window.setTimeout(() => window.bootstrap?.Alert.getOrCreateInstance(alert).close(), 5000);
    };

    const showDeviceNotification = async data => {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        const changedItems = data.items.filter(item => item.count > 0);
        const target = changedItems[0]?.url || '/';
        const body = changedItems.slice(0, 3).map(item => `${item.label}: ${item.count}`).join(' · ') || 'E-SKY CENTER vừa có dữ liệu mới.';
        if (serviceWorkerRegistration) {
            await serviceWorkerRegistration.showNotification('E-SKY CENTER', { body, tag: 'esky-realtime', renotify: true, data: { url: target } });
        } else {
            const notification = new Notification('E-SKY CENTER', { body, tag: 'esky-realtime' });
            notification.onclick = () => { window.focus(); window.location.href = target; notification.close(); };
        }
    };

    const render = data => {
        badge.textContent = data.total > 99 ? '99+' : data.total;
        badge.classList.toggle('d-none', data.total === 0);
        const available = data.items.filter(item => item.count > 0);
        itemsBox.replaceChildren(...(available.length ? available.map(item => {
            const link = document.createElement('a');
            link.className = 'dropdown-item d-flex justify-content-between align-items-center gap-3';
            link.href = item.url;
            link.innerHTML = `<span>${item.label}</span><span class="badge rounded-pill text-bg-primary">${item.count}</span>`;
            return link;
        }) : [Object.assign(document.createElement('div'), { className: 'p-3 small text-muted', textContent: 'Không có việc cần chú ý.' })]));
        if (initialized && data.changed) { showToast(); showDeviceNotification(data); }
        initialized = true;
        lastServerTime = data.server_time;
        document.dispatchEvent(new CustomEvent('esky:realtime-update', { detail: data }));
    };

    const poll = async () => {
        try {
            const url = new URL(endpoint, window.location.origin);
            if (lastServerTime) url.searchParams.set('since', lastServerTime);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (response.ok) render(await response.json());
        } catch (_) {}
    };

    poll();
    window.setInterval(poll, 45000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
});
