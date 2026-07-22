document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    const desktopQuery = window.matchMedia('(min-width: 992px)');
    const desktopStorageKey = 'esky.sidebar.hidden';
    const groupStorageKey = 'esky.sidebar.groups';
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'sidebar-backdrop';
    backdrop.setAttribute('aria-label', 'Đóng menu');
    document.body.appendChild(backdrop);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'sidebar-close';
    closeButton.innerHTML = '<i class="bi bi-x-lg"></i>';
    closeButton.setAttribute('aria-label', 'Đóng menu');
    sidebar.querySelector('.brand')?.appendChild(closeButton);

    let groupState = {};
    try { groupState = JSON.parse(localStorage.getItem(groupStorageKey) || '{}'); } catch (_) { groupState = {}; }

    const labels = Array.from(sidebar.querySelectorAll('.sidebar-label'));
    labels.forEach((label, index) => {
        const menu = label.nextElementSibling;
        if (!menu?.classList.contains('sidebar-nav')) return;

        const groupId = 'group-' + index;
        const hasActiveItem = Boolean(menu.querySelector('.nav-link.active'));
        const collapsed = hasActiveItem ? false : groupState[groupId] === true;
        label.classList.add('sidebar-group-toggle');
        label.dataset.sidebarGroup = groupId;
        label.setAttribute('role', 'button');
        label.setAttribute('tabindex', '0');
        label.setAttribute('aria-expanded', String(!collapsed));
        label.innerHTML = '<span>' + label.textContent.trim() + '</span><i class="bi bi-chevron-down sidebar-group-chevron"></i>';
        label.classList.toggle('is-collapsed', collapsed);
        menu.classList.toggle('is-collapsed', collapsed);

        const toggleGroup = () => {
            const willCollapse = !label.classList.contains('is-collapsed');
            label.classList.toggle('is-collapsed', willCollapse);
            menu.classList.toggle('is-collapsed', willCollapse);
            label.setAttribute('aria-expanded', String(!willCollapse));
            groupState[groupId] = willCollapse;
            localStorage.setItem(groupStorageKey, JSON.stringify(groupState));
        };
        label.addEventListener('click', toggleGroup);
        label.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggleGroup(); }
        });
    });

    const syncMobile = () => {
        const open = sidebar.classList.contains('open');
        backdrop.classList.toggle('show', open && !desktopQuery.matches);
        document.body.classList.toggle('sidebar-is-open', open && !desktopQuery.matches);
    };
    const closeMobile = () => { sidebar.classList.remove('open'); syncMobile(); };
    const syncDesktopButton = () => {
        const hidden = document.body.classList.contains('sidebar-desktop-hidden');
        toggleButtons.forEach(button => {
            button.setAttribute('aria-expanded', String(!hidden));
            const icon = button.querySelector('i');
            if (icon && desktopQuery.matches) icon.className = hidden ? 'bi bi-layout-sidebar-inset fs-5' : 'bi bi-list fs-5';
            else if (icon) icon.className = 'bi bi-list fs-5';
        });
    };

    if (desktopQuery.matches && localStorage.getItem(desktopStorageKey) === '1') {
        document.body.classList.add('sidebar-desktop-hidden');
    }
    syncDesktopButton();

    toggleButtons.forEach(button => button.addEventListener('click', () => {
        if (desktopQuery.matches) {
            sidebar.classList.remove('open');
            document.body.classList.toggle('sidebar-desktop-hidden');
            localStorage.setItem(desktopStorageKey, document.body.classList.contains('sidebar-desktop-hidden') ? '1' : '0');
            syncDesktopButton();
        } else {
            window.setTimeout(syncMobile, 0);
        }
    }));

    closeButton.addEventListener('click', closeMobile);
    backdrop.addEventListener('click', closeMobile);
    sidebar.querySelectorAll('a.nav-link').forEach(link => link.addEventListener('click', () => {
        if (!desktopQuery.matches) closeMobile();
    }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMobile(); });
    window.addEventListener('resize', () => {
        if (desktopQuery.matches) closeMobile(); else document.body.classList.remove('sidebar-desktop-hidden');
        syncDesktopButton();
        syncMobile();
    });
});