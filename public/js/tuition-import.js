document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-tuition-import-form]');
    const modalElement = document.getElementById('tuitionImportModal');
    if (!form || !modalElement) return;

    const picker = form.querySelector('[data-tuition-import-picker]');
    const fileInput = form.querySelector('input[type="file"]');
    const progressPanel = form.querySelector('[data-tuition-import-progress]');
    const progressRoot = progressPanel.querySelector('[role="progressbar"]');
    const progressBar = form.querySelector('[data-tuition-import-bar]');
    const status = form.querySelector('[data-tuition-import-status]');
    const count = form.querySelector('[data-tuition-import-count]');
    const log = form.querySelector('[data-tuition-import-log]');
    const errorBox = form.querySelector('[data-tuition-import-error]');
    const previewPanel = form.querySelector('[data-tuition-import-preview]');
    const previewBody = form.querySelector('[data-tuition-import-preview-body]');
    const previewNote = form.querySelector('[data-tuition-import-preview-note]');
    const finishButton = form.querySelector('[data-tuition-import-finish]');
    const validateButton = form.querySelector('[data-tuition-import-validate]');
    const actionElements = [...form.querySelectorAll('[data-tuition-import-action]')];
    const closeButtons = [...form.querySelectorAll('[data-tuition-import-close]')];
    const summary = {
        created: form.querySelector('[data-tuition-import-created]'),
        updated: form.querySelector('[data-tuition-import-updated]'),
        failed: form.querySelector('[data-tuition-import-failed]'),
    };

    let busy = false;
    let finished = false;
    let processed = 0;
    let total = 0;
    let validated = false;
    let reloadOnClose = false;

    const setControlState = (element, disabled) => {
        if ('disabled' in element) element.disabled = disabled;
        element.classList.toggle('disabled', disabled);
        element.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    };

    const refreshActions = () => {
        actionElements.forEach(element => {
            const disabled = busy || (
                element.hasAttribute('data-tuition-import-submit') && !validated
            );
            setControlState(element, disabled);
        });

        closeButtons.forEach(button => {
            if ('disabled' in button) button.disabled = busy;
        });
    };

    const resetSummary = () => {
        Object.values(summary).forEach(element => {
            element.textContent = '0';
        });
    };

    const setProgress = (current, maximum) => {
        processed = Number(current || 0);
        total = Number(maximum || total || 0);
        const percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
        progressBar.style.width = `${percent}%`;
        progressRoot.setAttribute('aria-valuenow', String(percent));
        count.textContent = `${processed}/${total} dòng`;
    };

    const resetProgress = () => {
        processed = 0;
        total = 0;
        setProgress(0, 0);
        resetSummary();
        log.replaceChildren();
        previewBody?.replaceChildren();
        previewPanel?.classList.add('d-none');
        if (previewNote) previewNote.textContent = '';
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
        status.textContent = 'Đang đọc file Excel...';
    };

    const resetModalState = () => {
        busy = false;
        finished = false;
        validated = false;
        reloadOnClose = false;
        picker.classList.remove('d-none');
        progressPanel.classList.add('d-none');
        finishButton.classList.add('d-none');
        actionElements.forEach(element => element.classList.remove('d-none'));
        resetProgress();
        refreshActions();
    };

    const updateSummary = event => {
        Object.keys(summary).forEach(key => {
            if (event[key] !== undefined) summary[key].textContent = String(event[key]);
        });
    };

    const appendLog = event => {
        const item = document.createElement('div');
        const icon = document.createElement('i');
        const message = document.createElement('span');
        const icons = {
            created: 'bi bi-check-circle-fill',
            updated: 'bi bi-arrow-repeat',
            failed: 'bi bi-x-circle-fill',
        };

        item.className = `student-import-live-item is-${event.status}`;
        icon.className = icons[event.status] || icons.failed;
        message.textContent = event.message || `Đã xử lý dòng ${event.row}.`;
        item.append(icon, message);
        log.append(item);

        while (log.children.length > 200) {
            log.firstElementChild.remove();
        }

        log.scrollTop = log.scrollHeight;
    };

    const renderMessages = (items, statusName) => {
        items.forEach(message => appendLog({
            status: statusName,
            message,
        }));
    };

    const formatMoney = value => `${Number(value || 0).toLocaleString('vi-VN')}đ`;

    const renderPreview = (rows, successCount) => {
        if (!previewPanel || !previewBody) return;

        previewBody.replaceChildren();
        if (!Array.isArray(rows) || rows.length === 0) {
            previewPanel.classList.add('d-none');
            if (previewNote) previewNote.textContent = '';
            return;
        }

        const paymentMethodLabels = {
            cash: 'Tiền mặt',
            transfer: 'Chuyển khoản',
            card: 'Thẻ',
            other: 'Khác',
        };

        rows.forEach(row => {
            const element = document.createElement('tr');
            const createCell = () => document.createElement('td');
            const createStrong = text => {
                const strong = document.createElement('strong');
                strong.textContent = text || '';
                return strong;
            };
            const createMuted = text => {
                const div = document.createElement('div');
                div.className = 'small text-muted';
                div.textContent = text || '';
                return div;
            };

            const studentCell = createCell();
            studentCell.append(createStrong(row.student_name || ''), createMuted(row.date_of_birth || ''));

            const chargeCell = createCell();
            chargeCell.append(createStrong(row.class_code || ''), createMuted(row.charge_code || ''));

            const receiptCell = createCell();
            receiptCell.append(createStrong(row.receipt_code || 'Bổ sung sau'), createMuted(row.paid_at || ''));

            const amountCell = createCell();
            amountCell.append(createStrong(formatMoney(row.tuition_amount)));
            amountCell.append(createMuted(
                Number(row.book_amount || 0) > 0
                    ? `Sách ${formatMoney(row.book_amount)}`
                    : (row.ratio_label || 'Giữ nguyên')
            ));

            const methodCell = createCell();
            methodCell.append(
                createStrong(paymentMethodLabels[row.payment_method] || row.payment_method || ''),
                createMuted(`Còn lại ${formatMoney(row.remaining || 0)}`)
            );

            const actionCell = createCell();
            const badge = document.createElement('span');
            badge.className = 'badge rounded-pill text-bg-light border';
            badge.textContent = row.action || '';
            actionCell.appendChild(badge);

            element.append(studentCell, chargeCell, receiptCell, amountCell, methodCell, actionCell);
            previewBody.appendChild(element);
        });

        if (previewNote) {
            previewNote.textContent = successCount > rows.length
                ? `Đang xem trước ${rows.length}/${successCount} dòng hợp lệ đầu tiên.`
                : `Đang xem trước ${rows.length} dòng hợp lệ sắp được nhập.`;
        }
        previewPanel.classList.remove('d-none');
    };

    const showError = (message, phase = 'import') => {
        busy = false;
        validated = false;
        status.textContent = phase === 'validate'
            ? 'Kiểm tra file chưa hoàn tất'
            : 'Nhập dữ liệu chưa hoàn tất';
        errorBox.textContent = message || (
            phase === 'validate'
                ? 'Không thể kiểm tra file Excel.'
                : 'Không thể nhập file Excel.'
        );
        errorBox.classList.remove('d-none');
        progressPanel.classList.remove('d-none');
        progressBar.classList.remove('progress-bar-animated', 'bg-success', 'bg-warning');
        progressBar.classList.add('bg-danger');

        if (phase === 'import' && processed > 0) {
            finished = true;
            reloadOnClose = true;
            actionElements.forEach(element => element.classList.add('d-none'));
            finishButton.classList.remove('d-none');
        } else {
            picker.classList.remove('d-none');
        }

        refreshActions();
    };

    const handleEvent = event => {
        if (event.type === 'preparing') {
            status.textContent = event.message || 'Đang đọc và kiểm tra file Excel...';
            return;
        }

        if (event.type === 'start') {
            total = Number(event.total || 0);
            status.textContent = `Đang import 0/${total} dòng...`;
            setProgress(0, total);
            return;
        }

        if (event.type === 'row') {
            setProgress(event.processed, event.total);
            updateSummary(event);
            status.textContent = `Đang import ${event.processed}/${event.total} dòng...`;
            appendLog(event);
            return;
        }

        if (event.type === 'complete') {
            busy = false;
            finished = true;
            validated = false;
            reloadOnClose = true;
            const result = event.result || {};
            const finalTotal = Number(result.total || total || 0);

            setProgress(finalTotal, finalTotal);
            updateSummary(result);
            status.textContent = result.failed > 0
                ? `Đã import xong, còn ${result.failed} dòng lỗi.`
                : 'Đã cập nhật học phí hoàn tất.';
            progressBar.classList.remove('progress-bar-animated', 'bg-danger');
            progressBar.classList.add(result.failed > 0 ? 'bg-warning' : 'bg-success');
            actionElements.forEach(element => element.classList.add('d-none'));
            finishButton.classList.remove('d-none');
            refreshActions();
            return;
        }

        if (event.type === 'error') {
            showError(event.message, 'import');
        }
    };

    const responseError = async response => {
        try {
            const payload = await response.json();
            const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            return validation || payload.message || `Máy chủ trả về lỗi ${response.status}.`;
        } catch (_) {
            return `Máy chủ trả về lỗi ${response.status}.`;
        }
    };

    const requireFile = () => {
        if (fileInput.files?.length) return true;
        fileInput.reportValidity();
        return false;
    };

    const startWorkingState = (message, keepPickerVisible = true) => {
        busy = true;
        finished = false;
        reloadOnClose = false;
        progressPanel.classList.remove('d-none');
        finishButton.classList.add('d-none');
        actionElements.forEach(element => element.classList.remove('d-none'));
        errorBox.classList.add('d-none');
        resetProgress();
        status.textContent = message;
        picker.classList.toggle('d-none', !keepPickerVisible);
        refreshActions();
    };

    validateButton?.addEventListener('click', async () => {
        if (busy || !requireFile()) return;

        validated = false;
        startWorkingState('Đang kiểm tra file Excel...', true);
        window.dispatchEvent(new Event('page-loading:hide'));

        const body = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Import-Validate': 'preview',
                },
            });

            if (!response.ok) throw new Error(await responseError(response));

            const payload = await response.json();
            const result = payload.result || {};
            const finalTotal = Number(result.total || 0);

            busy = false;
            validated = Boolean(payload.ok);
            setProgress(finalTotal, finalTotal);
            updateSummary(result);
            log.replaceChildren();
            renderMessages(result.errors || [], 'failed');
            renderPreview(result.preview || [], Number(result.success || 0));
            if (!log.children.length && payload.ok) {
                appendLog({
                    status: 'created',
                    message: 'Không phát hiện lỗi. Bạn có thể bấm Cập nhật học phí.',
                });
            }

            status.textContent = payload.message || (
                validated
                    ? 'File hợp lệ. Bạn có thể bấm Cập nhật học phí.'
                    : 'Đã kiểm tra xong nhưng còn dòng lỗi.'
            );
            progressBar.classList.remove('progress-bar-animated', 'bg-danger');
            progressBar.classList.add(validated ? 'bg-success' : 'bg-warning');
            picker.classList.remove('d-none');
            refreshActions();
        } catch (error) {
            showError(error.message, 'validate');
        }
    });

    fileInput?.addEventListener('change', () => {
        if (busy) return;
        validated = false;
        finished = false;
        picker.classList.remove('d-none');
        progressPanel.classList.add('d-none');
        finishButton.classList.add('d-none');
        actionElements.forEach(element => element.classList.remove('d-none'));
        resetProgress();
        refreshActions();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !requireFile()) return;

        if (!validated) {
            progressPanel.classList.remove('d-none');
            showError('Hãy bấm "Kiểm tra file" và xử lý hết lỗi trước khi import.', 'validate');
            return;
        }

        startWorkingState('Đang tải và import dữ liệu...', false);
        window.dispatchEvent(new Event('page-loading:hide'));

        const body = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Import-Progress': 'stream',
                },
            });
            if (!response.ok) throw new Error(await responseError(response));
            if (!response.body) throw new Error('Trình duyệt không hỗ trợ nhận tiến trình import dữ liệu.');

            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let buffer = '';
            let completed = false;

            while (true) {
                const {value, done} = await reader.read();
                buffer += decoder.decode(value || new Uint8Array(), {stream: !done});
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (!line.trim()) continue;
                    const progressEvent = JSON.parse(line);
                    handleEvent(progressEvent);
                    if (progressEvent.type === 'complete' || progressEvent.type === 'error') {
                        completed = true;
                    }
                }

                if (done) break;
            }

            if (buffer.trim()) {
                const progressEvent = JSON.parse(buffer);
                handleEvent(progressEvent);
                if (progressEvent.type === 'complete' || progressEvent.type === 'error') {
                    completed = true;
                }
            }

            if (!completed && !finished) {
                throw new Error('Kết nối import dữ liệu kết thúc trước khi hoàn tất.');
            }
        } catch (error) {
            showError(error.message, 'import');
        }
    });

    modalElement.addEventListener('hide.bs.modal', event => {
        if (busy) event.preventDefault();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        if (reloadOnClose) {
            window.location.reload();
            return;
        }

        form.reset();
        resetModalState();
    });

    finishButton.addEventListener('click', () => window.location.reload());

    resetModalState();
});
