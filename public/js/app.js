document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(button => button.addEventListener('click', () => sidebar?.classList.toggle('open')));

    document.querySelectorAll('[data-period-type]').forEach(select => {
        const update = () => {
            const scope = select.closest('form') || document;
            scope.querySelectorAll('[data-period-month]').forEach(item => item.classList.toggle('d-none', select.value !== 'month'));
            scope.querySelectorAll('[data-period-quarter]').forEach(item => item.classList.toggle('d-none', select.value !== 'quarter'));
        };
        select.addEventListener('change', update);
        update();
    });

    document.querySelectorAll('[data-bulk-form]').forEach(form => {
        const name = form.dataset.bulkForm;
        let boxes = [...document.querySelectorAll(`[data-bulk-item="${name}"]`)];
        if (boxes.length === 0) {
            const base = form.action.replace(/\/$/, '') + '/';
            document.querySelectorAll('form').forEach(item => {
                const method = item.querySelector('input[name="_method"]')?.value;
                if (item !== form && method === 'DELETE' && item.action.startsWith(base) && !item.action.endsWith('/force')) {
                    const id = item.action.slice(base.length).split('/')[0];
                    if (/^\d+$/.test(id)) {
                        const box = document.createElement('input');
                        box.type = 'checkbox'; box.name = 'ids[]'; box.value = id;
                        box.setAttribute('form', form.id); box.className = 'form-check-input me-2';
                        box.dataset.bulkItem = name;
                        item.closest('tr, .card')?.querySelector('td, .card-body')?.prepend(box);
                        boxes.push(box);
                    }
                }
            });
        }
        const all = document.querySelector(`[data-bulk-all="${name}"]`);
        const button = form.querySelector('[data-bulk-submit]');
        const update = () => {
            const count = boxes.filter(box => box.checked).length;
            if (button) {
                button.disabled = count === 0;
                button.querySelector('[data-bulk-count]')?.replaceChildren(String(count));
            }
            if (all) {
                all.checked = count > 0 && count === boxes.length;
                all.indeterminate = count > 0 && count < boxes.length;
            }
        };
        all?.addEventListener('change', () => { boxes.forEach(box => box.checked = all.checked); update(); });
        boxes.forEach(box => box.addEventListener('change', update));
        update();
    });

    const modalElement = document.getElementById('appConfirmModal');
    if (!modalElement || !window.bootstrap) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const messageElement = modalElement.querySelector('[data-confirm-message]');
    const acceptButton = modalElement.querySelector('[data-confirm-accept]');
    let pendingAction = null;

    const ask = (message, action, danger = false) => {
        window.dispatchEvent(new Event('page-loading:hide'));
        messageElement.textContent = message || 'Bạn có chắc chắn muốn tiếp tục thao tác này?';
        acceptButton.classList.toggle('btn-danger', danger);
        acceptButton.classList.toggle('btn-primary', !danger);
        acceptButton.disabled = false;
        pendingAction = action;
        modal.show();
    };

    acceptButton.addEventListener('click', () => {
        const action = pendingAction;
        pendingAction = null;
        acceptButton.disabled = true;
        modal.hide();
        if (action) window.setTimeout(() => {
            acceptButton.disabled = false;
            action();
        }, 120);
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        pendingAction = null;
        acceptButton.disabled = false;
        window.dispatchEvent(new Event('page-loading:hide'));
    });

    const formMessage = (form, submitter) => {
        const explicit = submitter?.dataset.confirm || form.dataset.confirm || form.dataset.bulkConfirm;
        if (explicit) return { message: explicit, danger: /xóa|huỷ|hủy|vĩnh viễn/i.test(explicit) };
        const method = form.querySelector('input[name="_method"]')?.value?.toUpperCase() || form.method.toUpperCase();
        const action = form.action.toLowerCase();
        if (method === 'DELETE') return { message: 'Bạn có chắc chắn muốn xóa dữ liệu này? Thao tác có thể không hoàn tác được.', danger: true };
        if (/confirm-receipt/.test(action)) return { message: 'Xác nhận bổ sung số phiếu thu này?', danger: false };
        if (/\/pay$|\/paid$/.test(action)) return { message: 'Xác nhận thông tin và thực hiện thanh toán?', danger: false };
        if (/\/approve$/.test(action)) return { message: 'Xác nhận duyệt nội dung này?', danger: false };
        if (/\/cancel$/.test(action)) return { message: 'Xác nhận hủy nội dung này?', danger: true };
        if (/\/toggle$/.test(action)) return { message: 'Xác nhận thay đổi trạng thái?', danger: false };
        if (/\/restore$/.test(action)) return { message: 'Xác nhận khôi phục dữ liệu này?', danger: false };
        if (/\/convert$/.test(action)) return { message: 'Xác nhận chuyển đổi hồ sơ này?', danger: false };
        return null;
    };

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === 'true') {
            if (form?.dataset) delete form.dataset.confirmed;
            return;
        }
        const config = formMessage(form, event.submitter);
        if (!config) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const submitter = event.submitter;
        ask(config.message, () => {
            form.dataset.confirmed = 'true';
            if (submitter && !submitter.disabled) {
                form.requestSubmit(submitter);
                return;
            }

            let submitterValue = null;
            if (submitter?.name) {
                submitterValue = document.createElement('input');
                submitterValue.type = 'hidden';
                submitterValue.name = submitter.name;
                submitterValue.value = submitter.value;
                form.append(submitterValue);
            }
            form.requestSubmit();
            window.setTimeout(() => submitterValue?.remove(), 0);
        }, config.danger);
    }, true);

    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-confirm]');
        if (!link) return;
        event.preventDefault();
        ask(link.dataset.confirm, () => { window.location.href = link.href; }, /xóa|huỷ|hủy/i.test(link.dataset.confirm));
    });
});
