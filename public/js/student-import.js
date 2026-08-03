document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-student-import-form]');
    const modalElement = document.getElementById('studentImportModal');
    if (!form || !modalElement) return;

    const picker = form.querySelector('[data-student-import-picker]');
    const progressPanel = form.querySelector('[data-student-import-progress]');
    const progressRoot = progressPanel.querySelector('[role="progressbar"]');
    const progressBar = form.querySelector('[data-student-import-bar]');
    const status = form.querySelector('[data-student-import-status]');
    const count = form.querySelector('[data-student-import-count]');
    const log = form.querySelector('[data-student-import-log]');
    const errorBox = form.querySelector('[data-student-import-error]');
    const finishButton = form.querySelector('[data-student-import-finish]');
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

    const setActionsDisabled = disabled => {
        actionElements.forEach(element => {
            if ('disabled' in element) element.disabled = disabled;
            element.classList.toggle('disabled', disabled);
            element.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        });
        closeButtons.forEach(button => { button.disabled = disabled; });
    };

    const setProgress = (current, maximum) => {
        processed = Number(current || 0);
        total = Number(maximum || total || 0);
        const percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
        progressBar.style.width = `${percent}%`;
        progressRoot.setAttribute('aria-valuenow', String(percent));
        count.textContent = `${processed}/${total} dòng`;
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
        message.textContent = event.message || `Đã xử lý dòng ${event.row}.`;
        item.append(icon, message);
        log.append(item);
        while (log.children.length > 200) log.firstElementChild.remove();
        log.scrollTop = log.scrollHeight;
    };

    const showError = message => {
        busy = false;
        status.textContent = 'Nhập dữ liệu chưa hoàn tất';
        errorBox.textContent = message || 'Không thể nhập file Excel.';
        errorBox.classList.remove('d-none');
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        closeButtons.forEach(button => { button.disabled = false; });

        if (processed > 0) {
            finished = true;
            actionElements.forEach(element => element.classList.add('d-none'));
            finishButton.classList.remove('d-none');
        } else {
            picker.classList.remove('d-none');
            setActionsDisabled(false);
        }
    };

    const handleEvent = event => {
        if (event.type === 'preparing') {
            status.textContent = event.message || 'Đang đọc và kiểm tra file Excel...';
            return;
        }
        if (event.type === 'start') {
            total = Number(event.total || 0);
            status.textContent = `Đang nhập 0/${total} dòng...`;
            setProgress(0, total);
            return;
        }
        if (event.type === 'row') {
            setProgress(event.processed, event.total);
            updateSummary(event);
            status.textContent = `Đang nhập ${event.processed}/${event.total} dòng...`;
            appendLog(event);
            return;
        }
        if (event.type === 'complete') {
            busy = false;
            finished = true;
            const result = event.result || {};
            setProgress(result.total || total, result.total || total);
            updateSummary(result);
            status.textContent = result.failed > 0
                ? `Đã nhập xong, có ${result.failed} dòng lỗi.`
                : 'Đã nhập học viên hoàn tất.';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add(result.failed > 0 ? 'bg-warning' : 'bg-success');
            actionElements.forEach(element => element.classList.add('d-none'));
            closeButtons.forEach(button => { button.disabled = false; });
            finishButton.classList.remove('d-none');
            return;
        }
        if (event.type === 'error') showError(event.message);
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

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;

        const submitter = event.submitter;
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files?.length) {
            fileInput.reportValidity();
            return;
        }

        busy = true;
        finished = false;
        processed = 0;
        total = 0;
        setActionsDisabled(true);
        picker.classList.add('d-none');
        progressPanel.classList.remove('d-none');
        finishButton.classList.add('d-none');
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
        log.replaceChildren();
        Object.values(summary).forEach(element => { element.textContent = '0'; });
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
        setProgress(0, 0);
        status.textContent = 'Đang tải và đọc file Excel...';
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
            if (!response.body) throw new Error('Trình duyệt không hỗ trợ nhận tiến trình nhập dữ liệu.');

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
                    if (progressEvent.type === 'complete' || progressEvent.type === 'error') completed = true;
                }
                if (done) break;
            }
            if (buffer.trim()) {
                const progressEvent = JSON.parse(buffer);
                handleEvent(progressEvent);
                if (progressEvent.type === 'complete' || progressEvent.type === 'error') completed = true;
            }
            if (!completed && !finished) throw new Error('Kết nối nhập dữ liệu kết thúc trước khi hoàn tất.');
        } catch (error) {
            showError(error.message);
        }
    });

    modalElement.addEventListener('hide.bs.modal', event => {
        if (busy) event.preventDefault();
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        if (finished || processed > 0) {
            window.location.reload();
            return;
        }
        form.reset();
        picker.classList.remove('d-none');
        progressPanel.classList.add('d-none');
        finishButton.classList.add('d-none');
        actionElements.forEach(element => element.classList.remove('d-none'));
        setActionsDisabled(false);
    });
    finishButton.addEventListener('click', () => window.location.reload());
});
