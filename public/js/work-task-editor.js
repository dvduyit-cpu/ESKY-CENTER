document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#personalPlanModal, #assignTaskModal, #editTaskModal, .task-list-card, form[action*="/tasks/"]')) {
        document.body.classList.add('work-task-page');
    }

    const editTaskModal = document.getElementById('editTaskModal');
    if (editTaskModal) {
        editTaskModal.classList.add('task-assign-modal', 'task-edit-modal');
        editTaskModal.querySelector('.modal-content')?.classList.add('task-assign-content');
        const editForm = editTaskModal.querySelector('.modal-body > form');
        editForm?.classList.add('task-assign-form');
        if (editForm?.hasAttribute('data-open-on-error')) {
            copyValidationAlert(editTaskModal);
        }
        editForm?.querySelector('input[name="assignee_ids[]"]')?.closest('.border')?.classList.add('task-assignee-list');
        editForm?.querySelector('[data-edit-task-lead]')?.closest('.col-lg-4')?.classList.add('task-lead-panel');
        const editActions = editForm?.querySelector('.col-12.text-end');
        editActions?.classList.add('task-assign-actions');
        const editTitle = editTaskModal.querySelector('.modal-title');
        if (editTitle) {
            editTitle.innerHTML = '<span class="task-assign-title-icon"><i class="bi bi-pencil-square"></i></span><span>Chỉnh sửa công việc<small>Cập nhật nội dung, người nhận và người chịu trách nhiệm</small></span>';
        }
    }
    document.querySelectorAll('#assignTaskModal form[data-open-on-error], #personalPlanModal form[data-open-on-error]')
        .forEach(form => copyValidationAlert(form.closest('.modal')));

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
        content.innerHTML = sanitizeEditorHtml(textarea.value);

        editor.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
            content.focus();
            document.execCommand(button.dataset.command, false);
        }));
        editor.querySelector('.task-add-link').addEventListener('click', () => insertEditorLink(content));

        const fileBox = form.querySelector('input[name="attachments[]"]')?.closest('.col-12, .task-file-box');
        const isEditForm = form.querySelector('input[name="_method"][value="PUT"]');
        const existingAttachmentLinks = isEditForm
            ? [...document.querySelectorAll('.content > section.card a[href*="/attachments/"]')]
            : [];
        if (fileBox && existingAttachmentLinks.length) {
            const existingFiles = document.createElement('div');
            existingFiles.className = 'task-existing-files';
            existingFiles.innerHTML = '<div class="task-existing-files-title"><i class="bi bi-folder-check me-1"></i>Tệp hiện có</div>';
            existingAttachmentLinks.forEach((link, index) => {
                const row = document.createElement('div');
                row.className = 'task-existing-file-row';
                const item = document.createElement('a');
                item.href = link.href;
                item.className = 'task-existing-file';
                item.dataset.attachmentName = link.dataset.attachmentName || link.querySelector('span')?.textContent.trim() || link.textContent.trim();
                item.innerHTML = `<span><i class="bi bi-file-earmark-check"></i>${escapeHtml(link.querySelector('span')?.textContent.trim() || link.textContent.trim())}</span><i class="bi bi-eye"></i>`;
                const deleteForm = document.createElement('form');
                deleteForm.id = `task-delete-attachment-${index}`;
                deleteForm.className = 'd-none';
                deleteForm.method = 'POST';
                deleteForm.action = link.href;
                deleteForm.dataset.confirm = 'Xóa file đính kèm này? File sẽ bị xóa khỏi host.';
                deleteForm.innerHTML = `<input type="hidden" name="_token" value="${escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '')}"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_task_form" value="edit-task">`;
                document.body.append(deleteForm);
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'task-delete-existing-file';
                deleteButton.title = 'Xóa file';
                deleteButton.setAttribute('aria-label', 'Xóa file');
                deleteButton.innerHTML = '<i class="bi bi-trash"></i><span>Xóa</span>';
                deleteButton.addEventListener('click', async () => {
                    if (
                        deleteButton.dataset.submitting === 'true'
                        || !window.confirm(deleteForm.dataset.confirm)
                    ) return;

                    deleteButton.dataset.submitting = 'true';
                    deleteButton.disabled = true;
                    const originalHtml = deleteButton.innerHTML;
                    deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Đang xóa</span>';

                    try {
                        const response = await fetch(deleteForm.action, {
                            method: 'POST',
                            body: new FormData(deleteForm),
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json().catch(() => null);
                        if (!response.ok) {
                            const validationMessage = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            throw new Error(validationMessage || payload?.message || 'Không thể xóa file đính kèm.');
                        }

                        const sourceSection = link.closest('section');
                        link.remove();
                        if (sourceSection && !sourceSection.querySelector('a[href*="/attachments/"]')) {
                            sourceSection.remove();
                        }
                        row.remove();
                        deleteForm.remove();
                        if (!existingFiles.querySelector('.task-existing-file-row')) {
                            existingFiles.remove();
                        }

                        const notice = document.createElement('div');
                        notice.className = 'alert alert-success py-2 mb-2';
                        notice.textContent = payload?.message || 'Đã xóa file đính kèm.';
                        fileBox.prepend(notice);
                        window.setTimeout(() => notice.remove(), 3000);
                    } catch (error) {
                        window.alert(error.message || 'Không thể xóa file đính kèm.');
                        deleteButton.dataset.submitting = 'false';
                        deleteButton.disabled = false;
                        deleteButton.innerHTML = originalHtml;
                    }
                });
                row.append(item, deleteButton);
                existingFiles.append(row);
            });
            fileBox.prepend(existingFiles);
        }

        form.addEventListener('submit', () => {
            textarea.value = normalizedEditorHtml(content);
        });
    });

    document.querySelectorAll('form[action*="/comments"] textarea[name="body"], form[action*="/complete"] textarea[name="note"]').forEach(textarea => {
        const isRequired = textarea.name === 'body';
        const mentionUsersSelect = isRequired
            ? textarea.form.querySelector('[data-task-mention-users]')
            : null;
        const mentionUsers = mentionUsersSelect
            ? [...mentionUsersSelect.options]
                .map(option => ({
                    id: option.value.trim(),
                    name: option.textContent.trim(),
                }))
                .filter(user => user.id && user.name)
            : [];
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
        content.innerHTML = sanitizeEditorHtml(textarea.value, {
            preserveMentionIds: Boolean(mentionUsersSelect),
        });
        content.querySelectorAll('mark[data-mention-id]').forEach(mark => {
            mark.contentEditable = 'false';
            mark.spellcheck = false;
        });
        editor.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
            content.focus();
            document.execCommand(button.dataset.command, false);
        }));
        editor.querySelector('.task-add-link').addEventListener('click', () => insertEditorLink(content));

        if (mentionUsersSelect && mentionUsers.length) {
            const toolbar = editor.querySelector('.task-rich-toolbar');
            const mentionButton = document.createElement('button');
            mentionButton.type = 'button';
            mentionButton.className = 'task-add-mention';
            mentionButton.title = 'Nhắc tên người tham gia';
            mentionButton.setAttribute('aria-label', 'Nhắc tên người tham gia');
            mentionButton.setAttribute('aria-expanded', 'false');
            mentionButton.innerHTML = '<strong aria-hidden="true">@</strong>';
            toolbar.insertBefore(mentionButton, toolbar.querySelector('span'));

            const mentionMenu = document.createElement('div');
            mentionMenu.className = 'task-mention-menu d-none';
            mentionMenu.innerHTML = `
                <label class="task-mention-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" autocomplete="off" placeholder="Tìm người cần nhắc..." aria-label="Tìm người cần nhắc">
                </label>
                <div class="task-mention-options" role="listbox" aria-label="Người có thể nhắc"></div>
                <div class="task-mention-empty d-none">Không tìm thấy người phù hợp.</div>`;
            toolbar.insertAdjacentElement('afterend', mentionMenu);

            const mentionSearch = mentionMenu.querySelector('input');
            const mentionOptions = mentionMenu.querySelector('.task-mention-options');
            const mentionEmpty = mentionMenu.querySelector('.task-mention-empty');
            const allowedMentionIds = new Set(mentionUsers.map(user => user.id));
            let savedMentionRange = null;

            const normalizeSearchText = value => value
                .toLocaleLowerCase('vi')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd');

            const rememberMentionRange = () => {
                const selection = window.getSelection();
                if (!selection?.rangeCount) return;

                const range = selection.getRangeAt(0);
                if (content.contains(range.startContainer) && content.contains(range.endContainer)) {
                    savedMentionRange = range.cloneRange();
                }
            };

            const closeMentionMenu = () => {
                mentionMenu.classList.add('d-none');
                mentionButton.setAttribute('aria-expanded', 'false');
            };

            const insertMention = user => {
                let range = savedMentionRange?.startContainer?.isConnected
                    && content.contains(savedMentionRange.startContainer)
                    && content.contains(savedMentionRange.endContainer)
                    ? savedMentionRange.cloneRange()
                    : null;
                if (!range) {
                    range = document.createRange();
                    range.selectNodeContents(content);
                    range.collapse(false);
                }

                range.deleteContents();
                const mark = document.createElement('mark');
                mark.dataset.mentionId = user.id;
                mark.textContent = `@${user.name}`;
                mark.contentEditable = 'false';
                mark.spellcheck = false;
                const spacer = document.createTextNode(' ');
                range.insertNode(mark);
                mark.after(spacer);

                content.focus();
                range.setStartAfter(spacer);
                range.collapse(true);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                savedMentionRange = range.cloneRange();
                closeMentionMenu();
            };

            const renderMentionOptions = () => {
                const query = normalizeSearchText(mentionSearch.value.trim());
                const matches = mentionUsers
                    .filter(user => normalizeSearchText(user.name).includes(query))
                    .slice(0, 20);

                mentionOptions.replaceChildren();
                matches.forEach(user => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'task-mention-option';
                    option.setAttribute('role', 'option');
                    option.innerHTML = '<strong>@</strong>';
                    option.append(document.createTextNode(user.name));
                    option.addEventListener('mousedown', event => event.preventDefault());
                    option.addEventListener('click', () => insertMention(user));
                    mentionOptions.append(option);
                });
                mentionEmpty.classList.toggle('d-none', matches.length > 0);
            };

            ['focus', 'keyup', 'mouseup', 'input'].forEach(eventName => {
                content.addEventListener(eventName, rememberMentionRange);
            });
            mentionButton.addEventListener('mousedown', event => {
                event.preventDefault();
                rememberMentionRange();
            });
            mentionButton.addEventListener('click', () => {
                if (!mentionMenu.classList.contains('d-none')) {
                    closeMentionMenu();
                    return;
                }

                mentionSearch.value = '';
                renderMentionOptions();
                mentionMenu.classList.remove('d-none');
                mentionButton.setAttribute('aria-expanded', 'true');
                mentionSearch.focus();
            });
            mentionSearch.addEventListener('input', renderMentionOptions);
            mentionSearch.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeMentionMenu();
                    content.focus();
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    mentionOptions.querySelector('.task-mention-option')?.focus();
                    return;
                }
                if (event.key === 'Enter') {
                    event.preventDefault();
                    mentionOptions.querySelector('.task-mention-option')?.click();
                }
            });
            document.addEventListener('mousedown', event => {
                if (
                    !mentionMenu.classList.contains('d-none')
                    && !mentionMenu.contains(event.target)
                    && !mentionButton.contains(event.target)
                ) {
                    closeMentionMenu();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !mentionMenu.classList.contains('d-none')) {
                    closeMentionMenu();
                }
            });

            textarea.form.addEventListener('submit', () => {
                textarea.form.querySelectorAll('[data-task-mentioned-user-input]').forEach(input => input.remove());
                const mentionedIds = new Set(
                    [...content.querySelectorAll('mark[data-mention-id]')]
                        .map(mark => mark.dataset.mentionId?.trim())
                        .filter(id => id && allowedMentionIds.has(id))
                );
                mentionedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'mentioned_user_ids[]';
                    input.value = id;
                    input.dataset.taskMentionedUserInput = '';
                    textarea.form.append(input);
                });
            });
        }

        textarea.form.addEventListener('submit', event => {
            textarea.value = normalizedEditorHtml(content, {
                preserveMentionIds: Boolean(mentionUsersSelect),
            });
            if (isRequired && !content.textContent.trim()) {
                event.preventDefault();
                content.focus();
            }
        });
    });

    document.querySelectorAll('[data-task-rich-display]').forEach(element => {
        if (!/<\/?[a-z][\s\S]*>|&(?:[a-z][a-z0-9]+|#\d+|#x[0-9a-f]+);/i.test(element.textContent)) return;
        element.innerHTML = sanitizeEditorHtml(element.textContent);
        element.classList.add('task-rich-description');
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
        const previewable = /\.(pdf|png|jpe?g|gif|webp|txt|csv)$/i;
        let previewTimer;
        let previewController;
        let previewObjectUrl;
        let previewGeneration = 0;
        let returnModal;

        const releasePreview = () => {
            clearTimeout(previewTimer);
            previewController?.abort();
            previewController = undefined;
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = undefined;
            }
        };

        const showPreviewModal = sourceModal => {
            if (sourceModal && sourceModal !== previewModal) {
                returnModal = sourceModal;
                sourceModal.addEventListener('hidden.bs.modal', () => modal.show(), {once: true});
                bootstrap.Modal.getOrCreateInstance(sourceModal).hide();
                return;
            }
            returnModal = undefined;
            modal.show();
        };

        attachmentLinks.forEach(link => {
            link.setAttribute('data-no-loading', '');
            link.addEventListener('click', event => {
                event.preventDefault();
                const generation = ++previewGeneration;
                releasePreview();
                const fileName = link.dataset.attachmentName || link.textContent.replace(/\s*\([^)]*\)\s*/g, ' ').trim();
                name.textContent = fileName;
                download.href = link.href;
                if (previewable.test(fileName)) {
                    body.innerHTML = '<div class="empty-state py-5" data-preview-status><span class="spinner-border text-primary" aria-hidden="true"></span><p class="mt-3 mb-0">Đang mở bản xem trước...</p></div>';
                    const previewUrl = new URL(link.href, window.location.href);
                    previewUrl.searchParams.set('preview', '1');
                    const showFailure = () => {
                        if (generation !== previewGeneration) return;
                        releasePreview();
                        body.innerHTML = '<div class="empty-state py-5"><i class="bi bi-exclamation-circle fs-1 text-warning"></i><p class="mt-3 mb-0">Không thể mở bản xem trước. Bạn vẫn có thể tải tệp xuống để xem.</p></div>';
                    };

                    previewController = new AbortController();
                    previewTimer = window.setTimeout(showFailure, 10000);
                    fetch(previewUrl.toString(), {
                        credentials: 'same-origin',
                        signal: previewController.signal,
                    })
                        .then(response => {
                            if (!response.ok) throw new Error(`HTTP ${response.status}`);
                            return response.blob();
                        })
                        .then(blob => {
                            if (generation !== previewGeneration) return;
                            clearTimeout(previewTimer);
                            previewController = undefined;
                            previewObjectUrl = URL.createObjectURL(blob);

                            const frame = document.createElement('iframe');
                            frame.className = 'd-none';
                            frame.setAttribute('sandbox', 'allow-same-origin allow-scripts');
                            frame.title = `Xem trước ${fileName}`;
                            frame.addEventListener('load', () => {
                                if (generation !== previewGeneration) return;
                                clearTimeout(previewTimer);
                                body.querySelector('[data-preview-status]')?.remove();
                                frame.classList.remove('d-none');
                            }, {once: true});
                            frame.addEventListener('error', showFailure, {once: true});
                            frame.src = previewObjectUrl;
                            body.append(frame);
                            previewTimer = window.setTimeout(showFailure, 5000);
                        })
                        .catch(error => {
                            if (generation === previewGeneration && error.name !== 'AbortError') {
                                showFailure();
                            }
                        });
                } else {
                    body.innerHTML = '<div class="empty-state py-5"><i class="bi bi-file-earmark-arrow-down fs-1"></i><p class="mt-3 mb-0">Định dạng này không hỗ trợ xem trực tiếp. Bạn có thể tải tệp xuống để mở.</p></div>';
                }
                showPreviewModal(link.closest('.modal.show'));
            });
        });
        previewModal.addEventListener('hidden.bs.modal', () => {
            previewGeneration += 1;
            releasePreview();
            body.innerHTML = '';
            const modalToRestore = returnModal;
            returnModal = undefined;
            if (modalToRestore) {
                window.setTimeout(() => bootstrap.Modal.getOrCreateInstance(modalToRestore).show(), 0);
            }
        });
    }

    const commentForm = document.querySelector('form[action*="/comments"] textarea[name="body"]')?.form;
    if (commentForm) {
        const cardBody = commentForm.closest('.card-body');
        const heading = cardBody?.querySelector(':scope > h5');
        if (cardBody && heading) {
            const header = document.createElement('div');
            header.className = 'task-comments-header';
            const openButton = document.createElement('button');
            openButton.type = 'button';
            openButton.className = 'btn btn-primary btn-sm';
            openButton.dataset.keepLabel = '';
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

            const commentTextarea = commentForm.querySelector('textarea[name="body"]');
            const commentEditor = commentForm.querySelector('.task-comment-editor');
            const mentionUsersSelect = commentForm.querySelector('[data-task-mention-users]');
            let parentCommentInput = commentForm.querySelector('input[name="parent_comment_id"]');
            if (!parentCommentInput) {
                parentCommentInput = document.createElement('input');
                parentCommentInput.type = 'hidden';
                parentCommentInput.name = 'parent_comment_id';
                commentForm.append(parentCommentInput);
            }

            const replyBanner = document.createElement('div');
            replyBanner.className = 'task-reply-banner d-none';
            replyBanner.innerHTML = `
                <div class="task-reply-context">
                    <i class="bi bi-reply-fill" aria-hidden="true"></i>
                    <span>
                        <strong>Đang trả lời <span data-task-reply-name></span></strong>
                        <small data-task-reply-preview></small>
                    </span>
                </div>
                <button type="button" data-task-cancel-reply>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                    <span>Hủy trả lời</span>
                </button>`;
            commentEditor?.closest('.task-rich-editor')?.insertAdjacentElement('beforebegin', replyBanner);

            const replyName = replyBanner.querySelector('[data-task-reply-name]');
            const replyPreview = replyBanner.querySelector('[data-task-reply-preview]');
            let autoReplyMention = null;

            const removeAutoReplyMention = () => {
                if (!autoReplyMention?.isConnected) {
                    autoReplyMention = null;
                    return;
                }

                const followingText = autoReplyMention.nextSibling;
                autoReplyMention.remove();
                if (followingText?.nodeType === Node.TEXT_NODE && /^[\s\u00a0]/u.test(followingText.nodeValue || '')) {
                    followingText.nodeValue = (followingText.nodeValue || '').slice(1);
                    if (!followingText.nodeValue) followingText.remove();
                }
                autoReplyMention = null;
            };

            const clearReplyState = ({focusEditor = false} = {}) => {
                removeAutoReplyMention();
                parentCommentInput.value = '';
                replyBanner.classList.add('d-none');
                replyName.textContent = '';
                replyPreview.textContent = '';
                if (focusEditor && commentEditor) {
                    commentEditor.focus();
                }
            };

            const focusCommentEditor = mention => {
                if (!commentEditor) return;

                commentEditor.focus();
                const range = document.createRange();
                if (mention?.isConnected) {
                    const followingText = mention.nextSibling;
                    if (followingText?.nodeType === Node.TEXT_NODE) {
                        range.setStart(followingText, Math.min(1, followingText.nodeValue?.length || 0));
                    } else {
                        range.setStartAfter(mention);
                    }
                } else {
                    range.selectNodeContents(commentEditor);
                    range.collapse(false);
                }
                range.collapse(true);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            };

            const beginReply = (button, {showModal = true} = {}) => {
                const commentId = button.dataset.commentId?.trim();
                if (!commentId) return;

                removeAutoReplyMention();
                parentCommentInput.value = commentId;

                const userId = button.dataset.userId?.trim();
                const mentionOption = userId && mentionUsersSelect
                    ? [...mentionUsersSelect.options].find(option => option.value.trim() === userId)
                    : null;
                const userName = button.dataset.userName?.trim()
                    || mentionOption?.textContent.trim()
                    || 'phản hồi đã chọn';
                replyName.textContent = userName;
                replyPreview.textContent = button.dataset.preview?.trim() || '';
                replyPreview.classList.toggle('d-none', !replyPreview.textContent);
                replyBanner.classList.remove('d-none');

                let mention = userId && commentEditor
                    ? [...commentEditor.querySelectorAll('mark[data-mention-id]')]
                        .find(mark => mark.dataset.mentionId === userId)
                    : null;
                if (!mention && mentionOption && commentEditor) {
                    mention = document.createElement('mark');
                    mention.dataset.mentionId = userId;
                    mention.textContent = `@${userName}`;
                    mention.contentEditable = 'false';
                    mention.spellcheck = false;
                    commentEditor.prepend(mention, document.createTextNode(' '));
                    autoReplyMention = mention;
                }

                const focusEditor = () => window.requestAnimationFrame(() => focusCommentEditor(mention));
                if (!showModal || commentModal.classList.contains('show')) {
                    focusEditor();
                    return;
                }

                commentModal.addEventListener('shown.bs.modal', focusEditor, {once: true});
                bootstrap.Modal.getOrCreateInstance(commentModal).show();
            };

            replyBanner.querySelector('[data-task-cancel-reply]').addEventListener('click', () => {
                clearReplyState({focusEditor: true});
            });
            openButton.addEventListener('click', () => clearReplyState());
            document.addEventListener('click', event => {
                const replyButton = event.target instanceof Element
                    ? event.target.closest('[data-task-reply]')
                    : null;
                if (!replyButton) return;

                event.preventDefault();
                beginReply(replyButton);
            });
            commentForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    clearReplyState();
                    commentForm.querySelectorAll('[data-task-mentioned-user-input]').forEach(input => input.remove());
                    commentForm.querySelector('.task-mention-menu')?.classList.add('d-none');
                    commentForm.querySelector('.task-add-mention')?.setAttribute('aria-expanded', 'false');
                    if (commentEditor && commentTextarea) {
                        commentEditor.innerHTML = sanitizeEditorHtml(commentTextarea.defaultValue, {
                            preserveMentionIds: true,
                        });
                        commentEditor.querySelectorAll('mark[data-mention-id]').forEach(mark => {
                            mark.contentEditable = 'false';
                            mark.spellcheck = false;
                        });
                    }
                }, 0);
            });

            const initialParentCommentId = parentCommentInput.value.trim();
            const initialReplyButton = initialParentCommentId
                ? [...document.querySelectorAll('[data-task-reply]')]
                    .find(button => button.dataset.commentId?.trim() === initialParentCommentId)
                : null;
            if (initialReplyButton) {
                beginReply(initialReplyButton, {showModal: false});
            } else if (initialParentCommentId) {
                parentCommentInput.value = '';
            }
            if (commentForm.hasAttribute('data-open-on-error')) {
                copyValidationAlert(commentModal);
                bootstrap.Modal.getOrCreateInstance(commentModal).show();
            }
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

    const pageParameters = new URLSearchParams(window.location.search);
    const paginationTarget = window.location.hash === '#taskHistory'
        ? document.getElementById('taskHistory')
        : (window.location.hash === '#taskComments'
            ? document.getElementById('taskComments')
            : (pageParameters.has('comments_page') && !pageParameters.has('activities_page')
                ? document.getElementById('taskComments')
                : (pageParameters.has('activities_page') ? document.getElementById('taskHistory') : null)));
    if (paginationTarget) {
        window.requestAnimationFrame(() => paginationTarget.scrollIntoView({block: 'start'}));
    }

    document.addEventListener('submit', event => {
        const form = event.target;
        if (
            !(form instanceof HTMLFormElement)
            || !document.body.classList.contains('work-task-page')
            || (!form.action.includes('/tasks') && !form.action.includes('/plans'))
            || event.defaultPrevented
        ) return;

        if (form.dataset.taskSubmitting === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        form.dataset.taskSubmitting = 'true';
        form.setAttribute('aria-busy', 'true');
        const submitter = event.submitter instanceof HTMLElement
            ? event.submitter
            : form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');

        [...form.elements]
            .filter(control => control.matches?.('button[type="submit"], button:not([type]), input[type="submit"]'))
            .forEach(control => {
                control.dataset.taskOriginalDisabled = control.disabled ? 'true' : 'false';
                control.disabled = true;
            });
        if (submitter instanceof HTMLButtonElement) {
            submitter.dataset.taskOriginalHtml = submitter.innerHTML;
            const icon = submitter.querySelector('i');
            if (icon) {
                icon.className = 'spinner-border spinner-border-sm me-1';
                icon.setAttribute('aria-hidden', 'true');
            } else {
                submitter.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
            }
        }
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-task-submitting]').forEach(form => {
            delete form.dataset.taskSubmitting;
            form.removeAttribute('aria-busy');
            [...form.elements].filter(control => control.hasAttribute?.('data-task-original-disabled')).forEach(control => {
                control.disabled = control.dataset.taskOriginalDisabled === 'true';
                delete control.dataset.taskOriginalDisabled;
            });
            [...form.elements].filter(control => control.hasAttribute?.('data-task-original-html')).forEach(button => {
                button.innerHTML = button.dataset.taskOriginalHtml;
                delete button.dataset.taskOriginalHtml;
            });
        });
    });
});

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
}

function insertEditorLink(content) {
    const selection = window.getSelection();
    const savedRange = selection?.rangeCount && content.contains(selection.anchorNode)
        ? selection.getRangeAt(0).cloneRange()
        : null;
    const value = window.prompt('Nhập đường dẫn (https://...)')?.trim();
    if (!value) return;

    const normalized = /^(https?:\/\/|mailto:)/i.test(value) ? value : `https://${value}`;
    content.focus();
    if (savedRange) {
        selection.removeAllRanges();
        selection.addRange(savedRange);
    }

    const selectedText = savedRange && !savedRange.collapsed ? savedRange.toString() : '';
    if (selectedText && document.execCommand('createLink', false, normalized)) {
        return;
    }

    const html = `<a href="${escapeHtml(normalized)}" target="_blank" rel="noopener noreferrer">${escapeHtml(selectedText || normalized)}</a>`;
    if (!document.execCommand('insertHTML', false, html)) {
        content.insertAdjacentHTML('beforeend', html);
    }
}

function sanitizeEditorHtml(value, { preserveMentionIds = false } = {}) {
    const template = document.createElement('template');
    template.innerHTML = value;
    const allowedTags = new Set(['P', 'DIV', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'H1', 'H2', 'H3', 'A', 'MARK']);

    [...template.content.querySelectorAll('*')].forEach(element => {
        if (!allowedTags.has(element.tagName)) {
            element.replaceWith(...element.childNodes);
            return;
        }

        [...element.attributes].forEach(attribute => {
            const attributeName = attribute.name.toLowerCase();
            const isLinkHref = element.tagName === 'A' && attributeName === 'href';
            const isMentionId = element.tagName === 'MARK'
                && preserveMentionIds
                && attributeName === 'data-mention-id';
            if (!isLinkHref && !isMentionId) {
                element.removeAttribute(attribute.name);
            }
        });
        if (element.tagName === 'A') {
            const href = (element.getAttribute('href') || '').trim();
            if (!/^(https?:\/\/|mailto:)/i.test(href)) {
                element.removeAttribute('href');
            } else {
                element.setAttribute('target', '_blank');
                element.setAttribute('rel', 'noopener noreferrer');
            }
        }
        if (element.tagName === 'MARK' && preserveMentionIds) {
            const mentionId = (element.dataset.mentionId || '').trim();
            if (mentionId) {
                element.dataset.mentionId = mentionId;
            } else {
                element.removeAttribute('data-mention-id');
            }
        }
    });

    return template.innerHTML;
}

function normalizedEditorHtml(content, sanitizerOptions = {}) {
    const clone = content.cloneNode(true);
    const walker = document.createTreeWalker(clone, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let node;

    while ((node = walker.nextNode())) textNodes.push(node);
    for (let index = textNodes.length - 1; index >= 0; index -= 1) {
        const textNode = textNodes[index];
        const value = textNode.nodeValue || '';
        const trimmed = value.replace(/[\s\u00a0]+$/u, '');
        textNode.nodeValue = trimmed;
        if (trimmed) break;
    }

    return sanitizeEditorHtml(clone.innerHTML, sanitizerOptions).trim();
}

function copyValidationAlert(modal) {
    const source = document.querySelector('main.content > .alert-danger');
    const target = modal?.querySelector('.modal-body');
    if (!source || !target || target.querySelector('[data-modal-validation-alert]')) return;

    const alert = source.cloneNode(true);
    alert.dataset.modalValidationAlert = '';
    alert.removeAttribute('data-auto-dismiss');
    alert.querySelector('.btn-close')?.remove();
    target.prepend(alert);
}
