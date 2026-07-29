document.addEventListener('DOMContentLoaded', () => {
    const closeAll = except => document.querySelectorAll('.search-select.open').forEach(widget => {
        if (widget !== except) widget.classList.remove('open');
    });

    document.querySelectorAll('select[data-searchable-select]').forEach(select => {
        const isRequired = select.required;
        select.required = false;
        let originalOptions = [...select.options].map(option => ({
            value: option.value,
            text: option.text,
            disabled: option.disabled,
        }));
        const placeholder = originalOptions.find(option => option.value === '')?.text || 'Chọn dữ liệu';
        const widget = document.createElement('div');
        widget.className = 'search-select';
        widget.innerHTML = `
            <button type="button" class="search-select-trigger" aria-haspopup="listbox" aria-expanded="false">
                <span></span><i class="bi bi-chevron-down"></i>
            </button>
            <div class="search-select-menu">
                <div class="search-select-search"><i class="bi bi-search"></i><input type="search" autocomplete="off"></div>
                <div class="search-select-options" role="listbox"></div>
                <div class="search-select-empty">Không tìm thấy kết quả phù hợp</div>
            </div>`;
        select.parentNode.insertBefore(widget, select);
        widget.appendChild(select);
        select.classList.add('search-select-native');

        const trigger = widget.querySelector('.search-select-trigger');
        const triggerText = trigger.querySelector('span');
        const menu = widget.querySelector('.search-select-menu');
        const search = widget.querySelector('input');
        const optionsBox = widget.querySelector('.search-select-options');
        const empty = widget.querySelector('.search-select-empty');
        search.placeholder = select.dataset.searchPlaceholder || 'Nhập để tìm kiếm...';

        const updateTrigger = () => {
            const selected = originalOptions.find(option => option.value === select.value);
            triggerText.textContent = selected?.value ? selected.text : placeholder;
            trigger.classList.toggle('has-value', Boolean(selected?.value));
            trigger.classList.remove('is-invalid');
        };

        const render = () => {
            const keyword = search.value.trim().toLocaleLowerCase('vi');
            const matches = originalOptions.filter(option =>
                !option.disabled
                && (!isRequired || option.value)
                && (!keyword || option.text.toLocaleLowerCase('vi').includes(keyword))
            );
            optionsBox.replaceChildren(...matches.map(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'search-select-option';
                button.dataset.value = option.value;
                button.textContent = option.text;
                button.setAttribute('role', 'option');
                button.classList.toggle('selected', option.value === select.value);
                button.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateTrigger();
                    widget.classList.remove('open');
                    trigger.setAttribute('aria-expanded', 'false');
                });
                return button;
            }));
            empty.classList.toggle('show', matches.length === 0);
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const opening = !widget.classList.contains('open');
            closeAll(widget);
            widget.classList.toggle('open', opening);
            trigger.setAttribute('aria-expanded', String(opening));
            if (opening) { search.value = ''; render(); setTimeout(() => search.focus(), 0); }
        });
        menu.addEventListener('click', event => event.stopPropagation());
        search.addEventListener('input', render);
        search.addEventListener('keydown', event => {
            if (event.key === 'Escape') { widget.classList.remove('open'); trigger.focus(); }
            if (event.key === 'Enter') { event.preventDefault(); optionsBox.querySelector('.search-select-option')?.click(); }
        });
        select.addEventListener('searchable-select:refresh', () => {
            originalOptions = [...select.options].map(option => ({ value: option.value, text: option.text, disabled: option.disabled }));
            updateTrigger();
            render();
        });
        select.form?.addEventListener('submit', event => {
            if (isRequired && !select.value) {
                event.preventDefault();
                trigger.classList.add('is-invalid');
                trigger.focus();
            }
        });
        updateTrigger();
        render();
    });

    document.addEventListener('click', () => closeAll());
});
