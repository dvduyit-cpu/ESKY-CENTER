document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-permission-table]').forEach(form => {
        const rows = [...form.querySelectorAll('[data-permission-row]')];
        const global = form.querySelector('[data-permission-all]');

        const updateRow = row => {
            const items = [...row.querySelectorAll('.permission-item')];
            const all = row.querySelector('[data-permission-row-all]');
            const checked = items.filter(item => item.checked).length;
            if (all) {
                all.checked = items.length > 0 && checked === items.length;
                all.indeterminate = checked > 0 && checked < items.length;
            }
        };

        const updateGlobal = () => {
            const items = [...form.querySelectorAll('.permission-item')];
            const overrides = [...form.querySelectorAll('[data-permission-override]')];
            const relevant = [...items, ...overrides];
            const checked = relevant.filter(item => item.checked).length;
            if (global) {
                global.checked = relevant.length > 0 && checked === relevant.length;
                global.indeterminate = checked > 0 && checked < relevant.length;
            }
            rows.forEach(updateRow);
        };

        global?.addEventListener('change', () => {
            form.querySelectorAll('.permission-item,[data-permission-override]').forEach(item => item.checked = global.checked);
            updateGlobal();
        });

        rows.forEach(row => {
            const rowAll = row.querySelector('[data-permission-row-all]');
            const override = row.querySelector('[data-permission-override]');
            const items = [...row.querySelectorAll('.permission-item')];

            rowAll?.addEventListener('change', () => {
                items.forEach(item => item.checked = rowAll.checked);
                if (override) override.checked = rowAll.checked;
                updateGlobal();
            });
            items.forEach(item => item.addEventListener('change', () => {
                if (override && item.checked) override.checked = true;
                updateGlobal();
            }));
            override?.addEventListener('change', () => {
                if (!override.checked) items.forEach(item => item.checked = false);
                updateGlobal();
            });
        });

        updateGlobal();
    });
});
