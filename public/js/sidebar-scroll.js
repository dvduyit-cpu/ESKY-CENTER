document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar-menu-scroll');
    if (!sidebar) return;

    const storageKey = 'esky-center.sidebar-scroll';
    const savedPosition = Number.parseInt(sessionStorage.getItem(storageKey), 10);

    if (Number.isFinite(savedPosition)) {
        sidebar.scrollTop = savedPosition;
    } else {
        const activeLink = sidebar.querySelector('.nav-link.active');
        if (activeLink) sidebar.scrollTop = Math.max(0, activeLink.getBoundingClientRect().top - sidebar.getBoundingClientRect().top + sidebar.scrollTop - sidebar.clientHeight / 2);
    }

    let frame;
    const rememberPosition = () => sessionStorage.setItem(storageKey, String(sidebar.scrollTop));
    sidebar.addEventListener('scroll', () => {
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(rememberPosition);
    }, { passive: true });

    sidebar.querySelectorAll('a.nav-link').forEach((link) => {
        link.addEventListener('click', rememberPosition);
    });
    window.addEventListener('pagehide', rememberPosition);
});
