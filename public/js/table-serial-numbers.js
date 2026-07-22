(function () {
    'use strict';

    function pageSizeForPath(pathname) {
        if (/\/imports\/records\/?$/.test(pathname)) return 50;
        if (/\/payments\/?$/.test(pathname)) return 25;
        if (/\/kpis\/\d+\/?$/.test(pathname)) return 25;
        return 20;
    }

    function paginationInfo(table) {
        const container = table.closest('.card, .tab-pane') || table.parentElement;
        const footer = container ? container.querySelector('.card-footer, nav[role="navigation"], .pagination') : null;

        if (!footer) return { page: 1, size: 0 };

        const links = Array.from(footer.querySelectorAll('a[href]'));
        let pageParameter = 'page';

        for (const link of links) {
            const parameters = new URL(link.href, window.location.href).searchParams;
            const matched = Array.from(parameters.keys()).find((key) => key === 'page' || key.endsWith('_page'));
            if (matched) {
                pageParameter = matched;
                break;
            }
        }

        const page = Math.max(1, Number(new URLSearchParams(window.location.search).get(pageParameter)) || 1);
        return { page: page, size: pageSizeForPath(window.location.pathname) };
    }

    function addSerialNumbers(table) {
        if (table.dataset.serialNumbers === 'off' || table.classList.contains('permission-table')) return;

        const headerRow = table.querySelector('thead tr');
        const body = table.tBodies[0];
        if (!headerRow || !body) return;

        const hasSerialColumn = Array.from(headerRow.cells).some((cell) => cell.textContent.trim().toUpperCase() === 'STT');
        if (hasSerialColumn) return;

        const heading = document.createElement('th');
        heading.scope = 'col';
        heading.className = 'table-serial-column text-center';
        heading.textContent = 'STT';
        headerRow.insertBefore(heading, headerRow.firstElementChild);

        const pagination = paginationInfo(table);
        let rowNumber = (pagination.page - 1) * pagination.size;

        Array.from(body.rows).forEach((row) => {
            const isEmptyState = row.cells.length === 1 && row.cells[0].hasAttribute('colspan');
            if (isEmptyState) {
                const colspan = Number(row.cells[0].getAttribute('colspan')) || headerRow.cells.length - 1;
                row.cells[0].setAttribute('colspan', String(colspan + 1));
                return;
            }

            rowNumber += 1;
            const cell = document.createElement('td');
            cell.className = 'table-serial-column text-center text-muted fw-semibold';
            cell.textContent = String(rowNumber);
            row.insertBefore(cell, row.firstElementChild);
        });
    }

    function initialize() {
        document.querySelectorAll('table.table-modern').forEach(addSerialNumbers);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
