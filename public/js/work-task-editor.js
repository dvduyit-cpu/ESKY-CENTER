document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#assignTaskModal, #editTaskModal, .task-list-card, form[action*="/tasks/"]')) {
        document.body.classList.add('work-task-page');
    }

    const editTaskModal = document.getElementById('editTaskModal');
    if (editTaskModal) {
        editTaskModal.classList.add('task-assign-modal', 'task-edit-modal');
        editTaskModal.querySelector('.modal-content')?.classList.add('task-assign-content');
        const editForm = editTaskModal.querySelector('.modal-body > form');
        editForm?.classList.add('task-assign-form');
        editForm?.querySelector('input[name="assignee_ids[]"]')?.closest('.border')?.classList.add('task-assignee-list');
        editForm?.querySelector('[data-edit-task-lead]')?.closest('.col-lg-4')?.classList.add('task-lead-panel');
        const editActions = editForm?.querySelector('.col-12.text-end');
        editActions?.classList.add('task-assign-actions');
        const editTitle = editTaskModal.querySelector('.modal-title');
        if (editTitle) {
            editTitle.innerHTML = '<span class="task-assign-title-icon"><i class="bi bi-pencil-square"></i></span><span>Chỉnh sửa công việc<small>Cập nhật nội dung, người nhận và người chịu trách nhiệm</small></span>';
        }
    }

    const taskForms = [...document.querySelectorAll('form[action*="/tasks"]')]
        .filter(form => form.querySelector('textarea[name="description"]'));

    taskForms.forEach(form => {
        form.enctype = 'multipart/form-data';
        const textarea = form.querySelector('textarea[name="description"]');
        textarea.classList.add('d-none');

        const editor = document.createElement('div');
        editor.className = 'task-rich-editor';
        editor.innerHTML = `
            <div class="task-rich-toolbar" role="toolbar" aria-label="Định dạng nội dung">
                <button type="button" data-command="bold" title="In đậm"><i class="bi bi-type-bold"></i></button>
                <button type="button" data-command="italic" title="In nghiêng"><i class="bi bi-type-italic"></i></button>
                <button type="button" data-command="underline" title="Gạch chân"><i class="bi bi-type-underline"></i></button>
                <button type="button" data-command="insertUnorderedList" title="Danh sách"><i class="bi bi-list-ul"></i></button>
                <button type="button" data-command="insertOrderedList" title="Danh sách số"><i class="bi bi-list-ol"></i></button>
                <span></span>
                <button type="button" class="task-add-link" title="Thêm liên kết"><i class="bi bi-plus-lg"></i><i class="bi bi-link-45deg"></i></button>
            </div>
            <div class="task-rich-content" contenteditable="true" data-placeholder="Nhập nội dung, yêu cầu hoặc ghi chú..."></div>`;
        textarea.insertAdjacentElement('afterend', editor);
        const content = editor.querySelector('.task-rich-content');
        content.textContent = textarea.value;

        editor.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
            content.focus();
            document.execCommand(button.dataset.command, false);
        }));
        editor.querySelector('.task-add-link').addEventListener('click', () => {
            const url = window.prompt('Nhập đường dẫn (https://...)');
            if (!url) return;
            const normalized = /^https?:\/\//i.test(url) ? url : `https://${url}`;
            content.focus();
            document.execCommand('createLink', false, normalized);
        });

        const fileBox = document.createElement('div');
        fileBox.className = 'task-file-box mt-3';
        fileBox.innerHTML = `<label class="form-label mb-1"><i class="bi bi-paperclip me-1"></i>File đính kèm</label>
            <input class="form-control" type="file" name="attachments[]" multiple>
            <small class="text-muted">Tối đa 5 file, mỗi file không quá 10 MB.</small>`;
        editor.insertAdjacentElement('afterend', fileBox);

        const isEditForm = form.querySelector('input[name="_method"][value="PUT"]');
        const existingAttachmentLinks = isEditForm
            ? [...document.querySelectorAll('.content > section.card a[href*="/attachments/"]')]
            : [];
        if (existingAttachmentLinks.length) {
            const existingFiles = document.createElement('div');
            existingFiles.className = 'task-existing-files';
            existingFiles.innerHTML = '<div class="task-existing-files-title"><i class="bi bi-folder-check me-1"></i>Tệp hiện có</div>';
            existingAttachmentLinks.forEach(link => {
                const row = document.createElement('div');
                row.className = 'task-existing-file-row';
                const item = document.createElement('a');
                item.href = link.href;
                item.className = 'task-existing-file';
                item.innerHTML = `<span><i class="bi bi-file-earmark-check"></i>${escapeHtml(link.querySelector('span')?.textContent.trim() || link.textContent.trim())}</span><i class="bi bi-eye"></i>`;
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = link.href;
                deleteForm.dataset.confirm = 'Xóa file đính kèm này? File sẽ bị xóa khỏi host.';
                deleteForm.innerHTML = `<input type="hidden" name="_token" value="${escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '')}"><input type="hidden" name="_method" value="DELETE"><button class="task-delete-existing-file" title="Xóa file" aria-label="Xóa file"><i class="bi bi-trash"></i><span>Xóa</span></button>`;
                row.append(item, deleteForm);
                existingFiles.append(row);
            });
            fileBox.prepend(existingFiles);
        }

        form.addEventListener('submit', () => {
            textarea.value = content.innerHTML;
        });
    });

    document.querySelectorAll('form[action*="/comments"] textarea[name="body"], form[action*="/complete"] textarea[name="note"]').forEach(textarea => {
        const isRequired = textarea.name === 'body';
        textarea.required = false;
        textarea.classList.add('d-none');
        const editor = document.createElement('div');
        editor.className = 'task-rich-editor mb-2';
        editor.innerHTML = `
            <div class="task-rich-toolbar" role="toolbar" aria-label="Định dạng nội dung">
                <button type="button" data-command="bold" title="In đậm"><i class="bi bi-type-bold"></i></button>
                <button type="button" data-command="italic" title="In nghiêng"><i class="bi bi-type-italic"></i></button>
                <button type="button" data-command="underline" title="Gạch chân"><i class="bi bi-type-underline"></i></button>
                <button type="button" data-command="insertUnorderedList" title="Danh sách"><i class="bi bi-list-ul"></i></button>
                <button type="button" data-command="insertOrderedList" title="Danh sách số"><i class="bi bi-list-ol"></i></button>
                <span></span>
                <button type="button" class="task-add-link" title="Thêm liên kết"><i class="bi bi-plus-lg"></i><i class="bi bi-link-45deg"></i></button>
            </div>
            <div class="task-rich-content task-comment-editor" contenteditable="true" data-placeholder="${isRequired ? 'Nhập nội dung phản hồi...' : 'Nhập ghi chú kết quả...'}"></div>`;
        textarea.insertAdjacentElement('afterend', editor);
        const content = editor.querySelector('.task-rich-content');
        content.textContent = textarea.value;
        editor.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
            content.focus();
            document.execCommand(button.dataset.command, false);
        }));
        editor.querySelector('.task-add-link').addEventListener('click', () => {
            const url = window.prompt('Nhập đường dẫn (https://...)');
            if (!url) return;
            content.focus();
            document.execCommand('createLink', false, /^https?:\/\//i.test(url) ? url : `https://${url}`);
        });
        textarea.form.addEventListener('submit', event => {
            textarea.value = content.innerHTML;
            if (isRequired && !content.textContent.trim()) {
                event.preventDefault();
                content.focus();
            }
        });
    });

    const description = document.querySelector('.row.g-4 > .col-lg-8 > section:first-child .card-body > p');
    if (description && /<\/?[a-z][\s\S]*>/i.test(description.textContent)) {
        description.innerHTML = description.textContent;
        description.classList.add('task-rich-description');
    }

    document.querySelectorAll('.task-list-card .badge.rounded-pill, .col-lg-4 .badge-soft').forEach(badge => {
        if (!badge.classList.contains('text-bg-info') && !badge.classList.contains('badge-info')) return;
        const separator = badge.textContent.includes('·') ? '·' : null;
        badge.textContent = separator
            ? `${badge.textContent.split(separator)[0].trim()} · Đã nhận việc`
            : 'Đã nhận việc';
    });

    document.querySelectorAll('.col-lg-8 section .card-body > .border-top.py-3 > div').forEach(body => {
        if (/<\/?[a-z][\s\S]*>/i.test(body.textContent)) {
            body.innerHTML = body.textContent;
            body.classList.add('task-rich-description');
        }
    });
    document.querySelectorAll('.col-lg-4 .small.text-muted.mt-2').forEach(note => {
        if (/<\/?[a-z][\s\S]*>/i.test(note.textContent)) note.innerHTML = note.textContent;
    });

    document.querySelectorAll('input[type="file"][name="attachments[]"]').forEach(input => {
        const preview = document.createElement('div');
        preview.className = 'task-selected-files d-none';
        input.insertAdjacentElement('afterend', preview);

        const render = () => {
            const files = [...input.files];
            preview.classList.toggle('d-none', files.length === 0);
            preview.innerHTML = files.map((file, index) => {
                const size = file.size >= 1048576
                    ? `${(file.size / 1048576).toFixed(1)} MB`
                    : `${Math.max(1, Math.round(file.size / 1024))} KB`;
                return `<div class="task-selected-file">
                    <span><i class="bi bi-file-earmark-check"></i><span><strong>${escapeHtml(file.name)}</strong><small>${size}</small></span></span>
                    <button type="button" data-remove-file="${index}" title="Bỏ tệp" aria-label="Bỏ ${escapeHtml(file.name)}"><i class="bi bi-x-lg"></i></button>
                </div>`;
            }).join('');

            preview.querySelectorAll('[data-remove-file]').forEach(button => button.addEventListener('click', () => {
                const transfer = new DataTransfer();
                files.forEach((file, index) => {
                    if (index !== Number(button.dataset.removeFile)) transfer.items.add(file);
                });
                input.files = transfer.files;
                render();
            }));
        };
        input.addEventListener('change', render);
    });

    const assigneeGroups = new Set(
        [...document.querySelectorAll('input[name="assignee_ids[]"]')]
            .map(input => input.closest('.task-assignee-list, .border.rounded-3'))
            .filter(Boolean)
    );
    assigneeGroups.forEach(group => {
        const items = [...group.querySelectorAll('input[name="assignee_ids[]"]')];
        if (!items.length || group.querySelector('[data-select-all-assignees]')) return;

        const allLabel = document.createElement('label');
        allLabel.className = 'task-select-all-assignees';
        allLabel.innerHTML = `<input class="form-check-input" type="checkbox" data-select-all-assignees>
            <span><strong>Tất cả</strong><small>Chọn toàn bộ người nhận</small></span>`;
        group.prepend(allLabel);
        const all = allLabel.querySelector('input');

        const searchBox = document.createElement('div');
        searchBox.className = 'task-assignee-search';
        searchBox.innerHTML = '<i class="bi bi-search"></i><input type="search" placeholder="Tìm theo tên hoặc email..." aria-label="Tìm người nhận"><button type="button" class="d-none" title="Xóa tìm kiếm" aria-label="Xóa tìm kiếm"><i class="bi bi-x-lg"></i></button>';
        allLabel.insertAdjacentElement('beforebegin', searchBox);
        const searchInput = searchBox.querySelector('input');
        const clearSearch = searchBox.querySelector('button');
        const emptyResult = document.createElement('div');
        emptyResult.className = 'task-assignee-empty d-none';
        emptyResult.textContent = 'Không tìm thấy người phù hợp.';
        group.append(emptyResult);

        const filterItems = () => {
            const keyword = searchInput.value.trim().toLocaleLowerCase('vi');
            let visible = 0;
            items.forEach(item => {
                const label = item.closest('label');
                const matched = !keyword || label.textContent.toLocaleLowerCase('vi').includes(keyword);
                label.classList.toggle('d-none', !matched);
                if (matched) visible++;
            });
            clearSearch.classList.toggle('d-none', !keyword);
            emptyResult.classList.toggle('d-none', visible > 0);
        };
        searchInput.addEventListener('input', filterItems);
        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            filterItems();
            searchInput.focus();
        });

        const sync = () => {
            const selected = items.filter(item => item.checked).length;
            all.checked = selected === items.length;
            all.indeterminate = selected > 0 && selected < items.length;
        };
        all.addEventListener('change', () => {
            const shouldSelectAll = all.checked;
            items.forEach(item => { item.checked = shouldSelectAll; });
            items.forEach(item => item.dispatchEvent(new Event('change', {bubbles: true})));
            sync();
        });
        items.forEach(item => item.addEventListener('change', sync));
        sync();
    });

    document.querySelectorAll('[data-task-lead], [data-edit-task-lead]').forEach(select => {
        const panel = select.closest('.col-lg-4');
        if (!panel || panel.querySelector('.task-lead-title')) return;
        const title = document.createElement('div');
        title.className = 'task-lead-title';
        title.innerHTML = '<i class="bi bi-star-fill"></i><span><strong>Người chủ trì</strong><small>Người chịu trách nhiệm chính</small></span>';
        panel.prepend(title);
    });

    const standaloneAttachmentLink = document.querySelector('.content > section.card a[href*="/attachments/"]');
    const standaloneAttachmentSection = standaloneAttachmentLink?.closest('section');
    const taskLeftColumn = document.querySelector('.row.g-4 > .col-lg-8');
    const responseSection = taskLeftColumn?.querySelector('section:last-of-type');
    if (standaloneAttachmentSection && taskLeftColumn && responseSection) {
        taskLeftColumn.insertBefore(standaloneAttachmentSection, responseSection);
    }

    const attachmentLinks = [...document.querySelectorAll('a[href*="/attachments/"]')];
    if (attachmentLinks.length) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `<div class="modal fade" id="taskAttachmentPreview" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0">
                    <div class="modal-header"><div><h5 class="modal-title">Xem trước tệp</h5><small class="text-muted" data-preview-name></small></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body task-attachment-preview-body"></div>
                    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Đóng</button><a class="btn btn-primary" data-preview-download data-no-loading><i class="bi bi-download me-1"></i>Tải xuống</a></div>
                </div>
            </div>
        </div>`;
        const previewModal = wrapper.firstElementChild;
        document.body.append(previewModal);
        const modal = bootstrap.Modal.getOrCreateInstance(previewModal);
        const body = previewModal.querySelector('.task-attachment-preview-body');
        const name = previewModal.querySelector('[data-preview-name]');
        const download = previewModal.querySelector('[data-preview-download]');
        const previewable = /\.(pdf|png|jpe?g|gif|webp|svg|txt|csv)/i;

        attachmentLinks.forEach(link => {
            link.setAttribute('data-no-loading', '');
            link.addEventListener('click', event => {
                event.preventDefault();
                const fileName = link.textContent.replace(/\s*\([^)]*\)\s*/g, ' ').trim();
                name.textContent = fileName;
                download.href = link.href;
                body.innerHTML = previewable.test(fileName)
                    ? `<iframe sandbox title="Xem trước ${escapeHtml(fileName)}" src="${escapeHtml(link.href)}?preview=1"></iframe>`
                    : '<div class="empty-state py-5"><i class="bi bi-file-earmark-arrow-down fs-1"></i><p class="mt-3 mb-0">Định dạng này không hỗ trợ xem trực tiếp. Bạn có thể tải tệp xuống để mở.</p></div>';
                modal.show();
            });
        });
        previewModal.addEventListener('hidden.bs.modal', () => { body.innerHTML = ''; });
    }

    const commentForm = document.querySelector('form[action*="/comments"]');
    if (commentForm) {
        const cardBody = commentForm.closest('.card-body');
        const heading = cardBody?.querySelector(':scope > h5');
        if (cardBody && heading) {
            const header = document.createElement('div');
            header.className = 'task-comments-header';
            const openButton = document.createElement('button');
            openButton.type = 'button';
            openButton.className = 'btn btn-primary btn-sm';
            openButton.setAttribute('data-bs-toggle', 'modal');
            openButton.setAttribute('data-bs-target', '#taskCommentModal');
            openButton.innerHTML = '<i class="bi bi-chat-dots me-1"></i>Gửi phản hồi';
            cardBody.insertBefore(header, heading);
            header.append(heading, openButton);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = `<div class="modal fade task-comment-modal" id="taskCommentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <div><h5 class="modal-title">Gửi phản hồi</h5><small class="text-muted">Cập nhật nội dung, kết quả hoặc tài liệu liên quan</small></div>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                    </div>
                </div>
            </div>`;
            const commentModal = wrapper.firstElementChild;
            commentModal.querySelector('.modal-body').append(commentForm);
            document.body.append(commentModal);
        }
    }

    const acknowledgeForm = document.querySelector('form[action*="/acknowledge"]');
    if (acknowledgeForm) {
        const acknowledgeButton = acknowledgeForm.querySelector('button');
        if (acknowledgeButton) {
            acknowledgeButton.innerHTML = acknowledgeButton.classList.contains('btn-success')
                ? '<i class="bi bi-check-circle-fill me-1"></i>Đã nhận việc'
                : '<i class="bi bi-check2-square me-1"></i>Xác nhận nhận việc';
        }
        const pageHeader = document.querySelector('.content > .d-flex.justify-content-between.mb-4');
        const actions = pageHeader?.querySelector(':scope > .d-flex:last-child');
        if (actions) {
            acknowledgeForm.classList.remove('mb-3');
            acknowledgeForm.classList.add('task-acknowledge-top');
            actions.prepend(acknowledgeForm);
        }
    }

    const completionForm = document.querySelector('form[action*="/complete"]');
    if (completionForm) {
        const reportSection = completionForm.closest('section');
        const leftColumn = document.querySelector('.row.g-4 > .col-lg-8');
        const reportTitle = reportSection?.querySelector('h5');
        const completionButton = completionForm.querySelector(':scope > button');
        const reportEditor = completionForm.querySelector('.task-rich-editor');
        const reportToolbar = reportEditor?.querySelector('.task-rich-toolbar');
        const reportContent = reportEditor?.querySelector('.task-rich-content');
        if (reportEditor && reportToolbar && reportContent) {
            reportEditor.replaceChildren(reportToolbar, reportContent);
        }
        if (reportTitle) {
            reportTitle.innerHTML = '<i class="bi bi-clipboard2-check me-2"></i>Báo cáo kết quả thực hiện';
            const helper = document.createElement('p');
            helper.className = 'task-result-helper';
            helper.textContent = 'Ghi lại kết quả, nội dung đã xử lý hoặc thông tin cần bàn giao trước khi hoàn thành.';
            reportTitle.insertAdjacentElement('afterend', helper);
        }
        if (completionButton) {
            completionButton.classList.add('task-completion-button');
            completionButton.innerHTML = completionButton.classList.contains('btn-outline-warning')
                ? '<i class="bi bi-arrow-counterclockwise me-1"></i>Mở lại công việc'
                : '<i class="bi bi-check-circle-fill me-1"></i>Xác nhận đã hoàn thành';
        }
        if (reportSection && leftColumn) {
            reportSection.classList.add('task-result-report');
            leftColumn.append(reportSection);
        }
    }
});

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
}
