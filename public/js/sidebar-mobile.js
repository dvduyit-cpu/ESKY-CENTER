document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

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

    const sync = () => {
        const open = sidebar.classList.contains('open');
        backdrop.classList.toggle('show', open);
        document.body.classList.toggle('sidebar-is-open', open);
    };
    const close = () => { sidebar.classList.remove('open'); sync(); };

    closeButton.addEventListener('click', close);
    backdrop.addEventListener('click', close);
    sidebar.querySelectorAll('a.nav-link').forEach(link => link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches) close();
    }));
    document.querySelectorAll('[data-sidebar-toggle]').forEach(button => button.addEventListener('click', () => window.setTimeout(sync, 0)));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    window.addEventListener('resize', () => { if (!window.matchMedia('(max-width: 991.98px)').matches) close(); else sync(); });
});
