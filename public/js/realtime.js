document.addEventListener('DOMContentLoaded', () => {
    const widget = document.querySelector('[data-realtime-notifications]');
    if (!widget) return;

    const badge = widget.querySelector('[data-notification-count]');
    const itemsBox = widget.querySelector('[data-notification-items]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const config = {
        userId: widget.dataset.userId,
        key: widget.dataset.reverbKey,
        host: widget.dataset.reverbHost || window.location.hostname,
        port: widget.dataset.reverbPort,
        scheme: widget.dataset.reverbScheme || (window.location.protocol === 'https:' ? 'https' : 'http'),
    };
    let initialized = false;
    let notificationsEnabled = true;
    let socket = null;
    let reconnectTimer = null;
    let reconnectAttempts = 0;
    let refreshInFlight = null;

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
        count.textContent = item.names?.length
            ? item.names.join(' · ') + (item.count > item.names.length ? ` · và ${item.count - item.names.length} nội dung khác` : '')
            : item.count + ' nội dung cần chú ý';
        copy.append(title, count);
        link.appendChild(copy);
        return link;
    };

    const render = data => {
        badge.textContent = data.total > 99 ? '99+' : data.total;
        notificationsEnabled = Boolean(data.enabled);
        badge.classList.toggle('d-none', !data.enabled || data.total === 0);
        if (!data.enabled) {
            itemsBox.replaceChildren(empty('Thông báo đã tắt cho tài khoản này.'));
            initialized = true;
            socket?.close();
            return;
        }
        const nodes = [...data.reminders.map(reminderLink), ...data.items.map(summaryLink)];
        itemsBox.replaceChildren(...(nodes.length ? nodes : [empty('Không có thông báo mới.')]));
        initialized = true;
        document.dispatchEvent(new CustomEvent('esky:realtime-update', { detail: data }));
    };

    const refresh = async (message = null) => {
        if (refreshInFlight) return refreshInFlight;
        refreshInFlight = fetch('/realtime/status', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        }).then(async response => {
            if (!response.ok) return;
            const data = await response.json();
            render(data);
            if (message && data.enabled) {
                const toast = document.createElement('div');
                toast.className = 'alert alert-info alert-dismissible fade show realtime-toast shadow';
                const icon = document.createElement('i');
                icon.className = 'bi bi-bell-fill me-2';
                toast.append(icon, document.createTextNode(message));
                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn-close';
                close.dataset.bsDismiss = 'alert';
                toast.appendChild(close);
                document.body.appendChild(toast);
                setTimeout(() => window.bootstrap?.Alert.getOrCreateInstance(toast).close(), 5000);
            }
        }).catch(() => {}).finally(() => { refreshInFlight = null; });
        return refreshInFlight;
    };

    const authenticate = async (socketId, channel) => {
        const body = new URLSearchParams({ socket_id: socketId, channel_name: channel });
        const response = await fetch('/broadcasting/auth', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
            body,
        });
        if (!response.ok) throw new Error('Không thể xác thực kênh WebSocket.');
        return response.json();
    };

    const subscribe = async (socketId, channel) => {
        const auth = await authenticate(socketId, channel);
        socket?.send(JSON.stringify({ event: 'pusher:subscribe', data: { channel, auth: auth.auth, channel_data: auth.channel_data } }));
    };

    const scheduleReconnect = () => {
        if (reconnectTimer || document.hidden) return;
        const delay = Math.min(30000, 1000 * (2 ** Math.min(reconnectAttempts++, 5)));
        reconnectTimer = setTimeout(() => { reconnectTimer = null; connect(); }, delay);
    };

    const connect = () => {
        if (!notificationsEnabled || !config.key || !config.userId || socket?.readyState === WebSocket.OPEN || socket?.readyState === WebSocket.CONNECTING) return;
        const secure = config.scheme === 'https';
        const defaultPort = secure ? '443' : '80';
        const port = config.port && String(config.port) !== defaultPort ? ':' + config.port : '';
        const url = (secure ? 'wss://' : 'ws://') + config.host + port + '/app/' + encodeURIComponent(config.key) + '?protocol=7&client=esky&version=1.0&flash=false';
        socket = new WebSocket(url);
        socket.addEventListener('message', async event => {
            let packet;
            try { packet = JSON.parse(event.data); } catch (_) { return; }
            if (packet.event === 'pusher:connection_established') {
                reconnectAttempts = 0;
                const connection = typeof packet.data === 'string' ? JSON.parse(packet.data) : packet.data;
                await Promise.all([
                    subscribe(connection.socket_id, 'private-users.' + config.userId),
                    subscribe(connection.socket_id, 'private-system.notifications'),
                ]).catch(() => socket?.close());
                if (initialized) refresh();
                return;
            }
            if (packet.event === 'pusher:ping') {
                socket?.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
                return;
            }
            if (packet.event === 'notification.changed') {
                const payload = typeof packet.data === 'string' ? JSON.parse(packet.data) : packet.data;
                refresh(payload?.message || 'Có thông báo mới.');
            }
        });
        socket.addEventListener('close', scheduleReconnect);
        socket.addEventListener('error', () => socket?.close());
    };

    refresh().then(() => connect());
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) return;
        refresh();
        connect();
    });
    window.addEventListener('online', connect);
    window.addEventListener('pagehide', () => socket?.close());
});
