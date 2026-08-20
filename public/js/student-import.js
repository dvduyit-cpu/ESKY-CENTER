document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-student-import-form]');
    const modalElement = document.getElementById('studentImportModal');
    if (!form || !modalElement) return;

    const picker = form.querySelector('[data-student-import-picker]');
    const fileInput = form.querySelector('input[type="file"]');
    const progressPanel = form.querySelector('[data-student-import-progress]');
    const progressRoot = progressPanel.querySelector('[role="progressbar"]');
    const progressBar = form.querySelector('[data-student-import-bar]');
    const status = form.querySelector('[data-student-import-status]');
    const count = form.querySelector('[data-student-import-count]');
    const log = form.querySelector('[data-student-import-log]');
    const errorBox = form.querySelector('[data-student-import-error]');
    const finishButton = form.querySelector('[data-student-import-finish]');
    const validateButton = form.querySelector('[data-student-import-validate]');
    const actionElements = [...form.querySelectorAll('[data-student-import-action]')];
    const closeButtons = [...form.querySelectorAll('[data-student-import-close]')];
    const summary = {
        created: form.querySelector('[data-student-import-created]'),
        updated: form.querySelector('[data-student-import-updated]'),
        skipped: form.querySelector('[data-student-import-skipped]'),
        failed: form.querySelector('[data-student-import-failed]'),
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
                element.hasAttribute('data-student-import-submit') && !validated
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
        count.textContent = `${processed}/${total} dong`;
    };

    const resetProgress = () => {
        processed = 0;
        total = 0;
        setProgress(0, 0);
        resetSummary();
        log.replaceChildren();
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
        status.textContent = 'Dang doc file Excel...';
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
            skipped: 'bi bi-exclamation-circle-fill',
            failed: 'bi bi-x-circle-fill',
        };

        item.className = `student-import-live-item is-${event.status}`;
        icon.className = icons[event.status] || icons.failed;
        message.textContent = event.message || `Da xu ly dong ${event.row}.`;
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

    const showError = (message, phase = 'import') => {
        busy = false;
        validated = false;
        status.textContent = phase === 'validate'
            ? 'Kiem tra file chua hoan tat'
            : 'Nhap du lieu chua hoan tat';
        errorBox.textContent = message || (
            phase === 'validate'
                ? 'Khong the kiem tra file Excel.'
                : 'Khong the nhap file Excel.'
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
            status.textContent = event.message || 'Dang doc va kiem tra file Excel...';
            return;
        }

        if (event.type === 'start') {
            total = Number(event.total || 0);
            status.textContent = `Dang nhap 0/${total} dong...`;
            setProgress(0, total);
            return;
        }

        if (event.type === 'row') {
            setProgress(event.processed, event.total);
            updateSummary(event);
            status.textContent = `Dang nhap ${event.processed}/${event.total} dong...`;
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
                ? `Da nhap xong, co ${result.failed} dong loi.`
                : 'Da nhap hoc vien hoan tat.';
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
            return validation || payload.message || `May chu tra ve loi ${response.status}.`;
        } catch (_) {
            return `May chu tra ve loi ${response.status}.`;
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
        startWorkingState('Dang kiem tra file Excel...', true);
        window.dispatchEvent(new Event('page-loading:hide'));

        const body = new FormData(form);
        body.set('duplicate_action', 'overwrite');

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
            renderMessages(result.warnings || [], 'skipped');
            renderMessages(result.errors || [], 'failed');
            if (!log.children.length && payload.ok) {
                appendLog({
                    status: 'created',
                    message: 'Khong phat hien loi. Ban co the bam nut nhap hoc vien.',
                });
            }

            status.textContent = payload.message || (
                validated
                    ? 'File hop le. Ban co the bam Nhap hoc vien.'
                    : 'Da kiem tra xong nhung con dong loi.'
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
            showError('Hay bam "Kiem tra file" va xu ly het loi truoc khi nhap.', 'validate');
            return;
        }

        const submitter = event.submitter;
        startWorkingState('Dang tai va nhap du lieu...', false);
        window.dispatchEvent(new Event('page-loading:hide'));

        const body = new FormData(form);
        body.set('duplicate_action', submitter?.value === 'overwrite' ? 'overwrite' : 'skip');

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
            if (!response.body) throw new Error('Trinh duyet khong ho tro nhan tien trinh nhap du lieu.');

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
                throw new Error('Ket noi nhap du lieu ket thuc truoc khi hoan tat.');
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
