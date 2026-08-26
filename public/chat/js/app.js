/**
 * Main Application Controller for Private AI Chat
 */

document.addEventListener('DOMContentLoaded', () => {
    AppController.init();
});

const AppController = {
    currentAbortController: null,

    // Preset prompts
    SYSTEM_PRESETS: {
        general: 'Bạn là một trợ lý ảo thông minh, hữu ích và luôn trả lời chính xác bằng Tiếng Việt.',
        coder: 'Bạn là một Chuyên Gia Lập Trình Phần Mềm cao cấp (Senior Full-Stack Developer). Bạn viết mã tối ưu, đẹp mắt, giải thích rõ ràng và có ví dụ cụ thể.',
        writer: 'Bạn là một Biên Tập Viên & Nhà Sáng Tạo Nội Dung tài năng. Bạn có lối văn mượt mà, hấp dẫn và tinh tế.',
        translator: 'Bạn là một Thông Dịch Viên Chuyên Nghiệp. Bạn luôn dịch nội dung sang tiếng Việt chuẩn ngữ cảnh, tự nhiên và súc tích.'
    },

    init() {
        this.cacheElements();
        this.bindEvents();
        this.loadSettingsUI();
        this.loadThreadsList();
        
        // Load or create active thread
        let activeThreadId = StorageManager.getActiveThreadId();
        const threads = StorageManager.getThreads();
        
        if (!activeThreadId || !threads.some(t => t.id === activeThreadId)) {
            if (threads.length > 0) {
                activeThreadId = threads[0].id;
                StorageManager.setActiveThreadId(activeThreadId);
            } else {
                const newThread = StorageManager.createThread();
                activeThreadId = newThread.id;
            }
        }
        
        this.switchThread(activeThreadId);
        this.updateApiBadge();
    },

    cacheElements() {
        this.sidebar = document.getElementById('sidebar');
        this.sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        this.sidebarOverlay = document.getElementById('sidebar-overlay');
        this.mobileMenuBtn = document.getElementById('mobile-menu-btn');
        this.newChatBtn = document.getElementById('new-chat-btn');
        this.threadsList = document.getElementById('threads-list');
        this.searchThreadsInput = document.getElementById('search-threads');
        
        // Chat Main UI
        this.welcomeScreen = document.getElementById('welcome-screen');
        this.messagesContainer = document.getElementById('messages-container');
        this.chatScrollContainer = document.getElementById('chat-scroll-container');
        this.chatTextarea = document.getElementById('chat-textarea');
        this.sendBtn = document.getElementById('send-btn');
        this.stopBtn = document.getElementById('stop-btn');
        this.charCounter = document.getElementById('char-counter');
        this.clearInputBtn = document.getElementById('clear-input-btn');
        
        // Model Selector
        this.modelSelectorBtn = document.getElementById('model-selector-btn');
        this.modelDropdown = document.getElementById('model-dropdown');
        this.currentModelName = document.getElementById('current-model-name');
        
        // System Presets
        this.systemPresetSelect = document.getElementById('system-preset-select');
        
        // Status & Settings
        this.apiStatusText = document.getElementById('api-status-text');
        this.settingsBtn = document.getElementById('settings-btn');
        this.headerSettingsBtn = document.getElementById('header-settings-btn');
        this.settingsModal = document.getElementById('settings-modal');
        this.saveSettingsBtn = document.getElementById('save-settings-btn');
        this.geminiApiKeyInput = document.getElementById('gemini-api-key');
        this.openaiApiKeyInput = document.getElementById('openai-api-key');
        this.ollamaUrlInput = document.getElementById('ollama-url');
        this.customSystemPromptInput = document.getElementById('custom-system-prompt');
        this.temperatureInput = document.getElementById('temperature-input');
        this.tempValSpan = document.getElementById('temp-val');
        this.maxTokensInput = document.getElementById('max-tokens-input');

        // Backup & Restore
        this.exportImportBtn = document.getElementById('export-import-btn');
        this.clearAllBtn = document.getElementById('clear-all-btn');
        this.backupModal = document.getElementById('backup-modal');
        this.exportJsonBtn = document.getElementById('export-json-btn');
        this.exportMdBtn = document.getElementById('export-md-btn');
        this.triggerImportBtn = document.getElementById('trigger-import-btn');
        this.importFileInput = document.getElementById('import-file-input');
    },

    bindEvents() {
        // Mobile Sidebar Toggle
        this.mobileMenuBtn.addEventListener('click', () => {
            this.sidebar.classList.add('open');
        });

        this.sidebarCloseBtn?.addEventListener('click', () => {
            this.sidebar.classList.remove('open');
        });

        this.sidebarOverlay.addEventListener('click', () => {
            this.sidebar.classList.remove('open');
        });

        // New Chat
        this.newChatBtn.addEventListener('click', () => {
            const newThread = StorageManager.createThread();
            this.loadThreadsList();
            this.switchThread(newThread.id);
            if (window.innerWidth <= 768) {
                this.sidebar.classList.remove('open');
            }
        });

        // Search Threads
        this.searchThreadsInput.addEventListener('input', (e) => {
            this.loadThreadsList(e.target.value.trim().toLowerCase());
        });

        // Textarea Auto-expand & Keyboard send
        this.chatTextarea.addEventListener('input', () => {
            this.autoResizeTextarea();
            this.charCounter.textContent = `${this.chatTextarea.value.length} ký tự`;
        });

        this.chatTextarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleSendMessage();
            }
        });

        this.sendBtn.addEventListener('click', () => {
            this.handleSendMessage();
        });

        this.stopBtn.addEventListener('click', () => {
            if (this.currentAbortController) {
                this.currentAbortController.abort();
                this.currentAbortController = null;
                this.toggleLoadingState(false);
            }
        });

        this.clearInputBtn.addEventListener('click', () => {
            this.chatTextarea.value = '';
            this.chatTextarea.style.height = 'auto';
            this.charCounter.textContent = '0 ký tự';
        });

        // Prompt suggestions click
        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.addEventListener('click', () => {
                const promptText = card.dataset.prompt;
                if (promptText) {
                    this.chatTextarea.value = promptText;
                    this.handleSendMessage();
                }
            });
        });

        // Model Selector Dropdown
        this.modelSelectorBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.modelDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!this.modelSelectorBtn.contains(e.target) && !this.modelDropdown.contains(e.target)) {
                this.modelDropdown.classList.remove('show');
            }
        });

        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');

                const provider = item.dataset.provider;
                const model = item.dataset.model;
                const title = item.querySelector('.item-title').childNodes[0].textContent.trim();

                this.currentModelName.textContent = title;
                this.modelDropdown.classList.remove('show');

                StorageManager.saveSettings({
                    modelProvider: provider,
                    activeModel: model
                });

                this.updateApiBadge();
            });
        });

        // System Preset Selector
        this.systemPresetSelect.addEventListener('change', (e) => {
            const presetKey = e.target.value;
            let promptText = this.SYSTEM_PRESETS[presetKey] || '';
            if (presetKey === 'custom') {
                const settings = StorageManager.getSettings();
                promptText = settings.customSystemPrompt || '';
            }

            StorageManager.saveSettings({
                systemPreset: presetKey,
                customSystemPrompt: promptText
            });
        });

        // Settings Modal
        const openSettings = () => this.openModal('settings-modal');
        this.settingsBtn.addEventListener('click', openSettings);
        this.headerSettingsBtn.addEventListener('click', openSettings);

        this.temperatureInput.addEventListener('input', (e) => {
            this.tempValSpan.textContent = e.target.value;
        });

        this.saveSettingsBtn.addEventListener('click', () => {
            StorageManager.saveSettings({
                geminiApiKey: this.geminiApiKeyInput.value.trim(),
                openaiApiKey: this.openaiApiKeyInput.value.trim(),
                ollamaUrl: this.ollamaUrlInput.value.trim(),
                customSystemPrompt: this.customSystemPromptInput.value.trim(),
                temperature: parseFloat(this.temperatureInput.value),
                maxTokens: parseInt(this.maxTokensInput.value)
            });

            this.closeModal('settings-modal');
            this.updateApiBadge();
            alert('Đã lưu cấu hình cài đặt thành công!');
        });

        // Backup & Restore
        this.exportImportBtn.addEventListener('click', () => {
            this.openModal('backup-modal');
        });

        this.exportJsonBtn.addEventListener('click', () => {
            const jsonStr = StorageManager.exportDataJSON();
            const blob = new Blob([jsonStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `private_chat_backup_${new Date().toISOString().slice(0, 10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
        });

        this.exportMdBtn.addEventListener('click', () => {
            const activeThreadId = StorageManager.getActiveThreadId();
            const thread = StorageManager.getThread(activeThreadId);
            if (!thread || thread.messages.length === 0) {
                alert('Không có nội dung trò chuyện để xuất file.');
                return;
            }

            let mdContent = `# ${thread.title}\n\n*Ngày tạo: ${new Date(thread.createdAt).toLocaleString()}*\n\n---\n\n`;
            thread.messages.forEach(msg => {
                const roleName = msg.role === 'user' ? '👤 Người Dùng' : '🤖 Trợ Lý AI';
                mdContent += `### ${roleName}\n${msg.content}\n\n`;
            });

            const blob = new Blob([mdContent], { type: 'text/markdown' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${thread.title.replace(/[^a-zA-Z0-9_-]/g, '_')}.md`;
            a.click();
            URL.revokeObjectURL(url);
        });

        this.triggerImportBtn.addEventListener('click', () => {
            this.importFileInput.click();
        });

        this.importFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                const success = StorageManager.importDataJSON(event.target.result);
                if (success) {
                    alert('Khôi phục dữ liệu thành công!');
                    this.closeModal('backup-modal');
                    this.loadThreadsList();
                    const activeId = StorageManager.getActiveThreadId();
                    if (activeId) this.switchThread(activeId);
                } else {
                    alert('Tệp khôi phục không hợp lệ.');
                }
            };
            reader.readAsText(file);
        });

        this.clearAllBtn.addEventListener('click', () => {
            if (confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ lịch sử trò chuyện không? Hành động này không thể hoàn tác.')) {
                StorageManager.clearAllThreads();
                const newThread = StorageManager.createThread();
                this.loadThreadsList();
                this.switchThread(newThread.id);
            }
        });

        // Close modal buttons
        document.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.close;
                this.closeModal(modalId);
            });
        });
    },

    autoResizeTextarea() {
        this.chatTextarea.style.height = 'auto';
        this.chatTextarea.style.height = `${Math.min(this.chatTextarea.scrollHeight, 180)}px`;
    },

    loadSettingsUI() {
        const settings = StorageManager.getSettings();
        this.geminiApiKeyInput.value = settings.geminiApiKey || '';
        this.openaiApiKeyInput.value = settings.openaiApiKey || '';
        this.ollamaUrlInput.value = settings.ollamaUrl || 'http://localhost:11434';
        this.customSystemPromptInput.value = settings.customSystemPrompt || '';
        this.temperatureInput.value = settings.temperature || 0.7;
        this.tempValSpan.textContent = settings.temperature || 0.7;
        this.maxTokensInput.value = settings.maxTokens || 4096;
        this.systemPresetSelect.value = settings.systemPreset || 'general';

        // Set active model dropdown
        const activeModel = settings.activeModel || 'gemini-2.5-flash';
        const activeItem = document.querySelector(`.dropdown-item[data-model="${activeModel}"]`);
        if (activeItem) {
            document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            activeItem.classList.add('active');
            const title = activeItem.querySelector('.item-title').childNodes[0].textContent.trim();
            this.currentModelName.textContent = title;
        }
    },

    updateApiBadge() {
        const settings = StorageManager.getSettings();
        const provider = settings.modelProvider || 'gemini';

        if (provider === 'gemini') {
            if (settings.geminiApiKey) {
                this.apiStatusText.textContent = 'Gemini API: Sẵn sàng';
            } else {
                this.apiStatusText.textContent = 'Cần Gemini API Key';
            }
        } else if (provider === 'openai') {
            if (settings.openaiApiKey) {
                this.apiStatusText.textContent = 'OpenAI API: Sẵn sàng';
            } else {
                this.apiStatusText.textContent = 'Cần OpenAI API Key';
            }
        } else if (provider === 'ollama') {
            this.apiStatusText.textContent = 'Ollama Local AI';
        }
    },

    loadThreadsList(filterQuery = '') {
        const threads = StorageManager.getThreads();
        const activeThreadId = StorageManager.getActiveThreadId();
        this.threadsList.innerHTML = '';

        const filteredThreads = threads.filter(t => t.title.toLowerCase().includes(filterQuery));

        if (filteredThreads.length === 0) {
            this.threadsList.innerHTML = `<div style="padding: 12px; font-size: 12px; color: var(--text-dim); text-align: center;">Chưa có cuộc trò chuyện</div>`;
            return;
        }

        filteredThreads.forEach(thread => {
            const item = document.createElement('div');
            item.className = `thread-item ${thread.id === activeThreadId ? 'active' : ''}`;
            item.innerHTML = `
                <i class="fa-regular fa-message" style="margin-right: 8px; font-size: 13px;"></i>
                <span class="thread-title">${MarkdownRenderer.escapeHtml(thread.title)}</span>
                <div class="thread-actions">
                    <button class="thread-action-btn delete-thread-btn" title="Xóa"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;

            item.addEventListener('click', (e) => {
                if (e.target.closest('.delete-thread-btn')) {
                    e.stopPropagation();
                    if (confirm('Xóa cuộc trò chuyện này?')) {
                        StorageManager.deleteThread(thread.id);
                        this.loadThreadsList();
                        const nextId = StorageManager.getActiveThreadId();
                        if (nextId) this.switchThread(nextId);
                        else {
                            const newT = StorageManager.createThread();
                            this.loadThreadsList();
                            this.switchThread(newT.id);
                        }
                    }
                    return;
                }
                this.switchThread(thread.id);
                if (window.innerWidth <= 768) {
                    this.sidebar.classList.remove('open');
                }
            });

            this.threadsList.appendChild(item);
        });
    },

    switchThread(threadId) {
        StorageManager.setActiveThreadId(threadId);
        const thread = StorageManager.getThread(threadId);
        
        // Update sidebar list active item
        document.querySelectorAll('.thread-item').forEach(el => el.classList.remove('active'));
        this.loadThreadsList(this.searchThreadsInput.value.trim().toLowerCase());

        // Render messages
        this.messagesContainer.innerHTML = '';
        if (!thread || thread.messages.length === 0) {
            this.welcomeScreen.classList.remove('hidden');
            this.messagesContainer.classList.add('hidden');
        } else {
            this.welcomeScreen.classList.add('hidden');
            this.messagesContainer.classList.remove('hidden');
            
            thread.messages.forEach(msg => {
                this.renderMessageBubble(msg.role, msg.content);
            });
        }
        
        this.scrollToBottom();
    },

    renderMessageBubble(role, content) {
        const row = document.createElement('div');
        row.className = `message-row ${role === 'user' ? 'user' : 'ai'}`;
        
        const avatarIcon = role === 'user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-brain"></i>';
        const formattedContent = role === 'user' 
            ? `<p>${MarkdownRenderer.escapeHtml(content).replace(/\n/g, '<br>')}</p>`
            : MarkdownRenderer.render(content);

        row.innerHTML = `
            <div class="avatar">${avatarIcon}</div>
            <div class="message-bubble">${formattedContent}</div>
        `;

        this.messagesContainer.appendChild(row);
        return row;
    },

    async handleSendMessage() {
        const prompt = this.chatTextarea.value.trim();
        if (!prompt) return;

        let activeThreadId = StorageManager.getActiveThreadId();
        if (!activeThreadId) {
            const newT = StorageManager.createThread();
            activeThreadId = newT.id;
        }

        // Hide welcome screen
        this.welcomeScreen.classList.add('hidden');
        this.messagesContainer.classList.remove('hidden');

        // Render & save user message
        StorageManager.addMessage(activeThreadId, 'user', prompt);
        this.renderMessageBubble('user', prompt);
        this.loadThreadsList();

        // Clear input box
        this.chatTextarea.value = '';
        this.chatTextarea.style.height = 'auto';
        this.charCounter.textContent = '0 ký tự';
        this.scrollToBottom();

        // Setup AI Response Bubble
        const settings = StorageManager.getSettings();
        const activeThread = StorageManager.getThread(activeThreadId);

        const aiRow = this.renderMessageBubble('assistant', 'Đang suy nghĩ...');
        const aiBubble = aiRow.querySelector('.message-bubble');
        aiBubble.innerHTML = '<span class="typing-cursor"></span>';
        
        this.toggleLoadingState(true);
        this.currentAbortController = new AbortController();

        try {
            let fullText = '';
            
            // Format system prompt
            let systemPrompt = settings.customSystemPrompt || this.SYSTEM_PRESETS[settings.systemPreset || 'general'];

            fullText = await ApiClient.sendMessage({
                provider: settings.modelProvider || 'gemini',
                model: settings.activeModel || 'gemini-2.5-flash',
                apiKey: settings.geminiApiKey || settings.openaiApiKey,
                systemPrompt: systemPrompt,
                messages: activeThread.messages,
                temperature: settings.temperature,
                maxTokens: settings.maxTokens,
                signal: this.currentAbortController.signal,
                onChunk: (chunkText) => {
                    fullText = chunkText;
                    aiBubble.innerHTML = MarkdownRenderer.render(fullText) + '<span class="typing-cursor"></span>';
                    this.scrollToBottom();
                }
            });

            // Finish response rendering
            aiBubble.innerHTML = MarkdownRenderer.render(fullText);
            StorageManager.addMessage(activeThreadId, 'assistant', fullText);
            this.loadThreadsList();

        } catch (error) {
            if (error.name === 'AbortError') {
                aiBubble.innerHTML += '<p style="color: var(--text-dim); font-size: 12px; margin-top: 8px;">*(Đã dừng bởi người dùng)*</p>';
            } else {
                aiBubble.innerHTML = `<p style="color: #f87171;">⚠️ ${MarkdownRenderer.escapeHtml(error.message)}</p>`;
            }
        } finally {
            this.toggleLoadingState(false);
            this.currentAbortController = null;
            this.scrollToBottom();
        }
    },

    toggleLoadingState(isLoading) {
        if (isLoading) {
            this.sendBtn.classList.add('hidden');
            this.stopBtn.classList.remove('hidden');
        } else {
            this.sendBtn.classList.remove('hidden');
            this.stopBtn.classList.add('hidden');
        }
    },

    scrollToBottom() {
        setTimeout(() => {
            this.chatScrollContainer.scrollTop = this.chatScrollContainer.scrollHeight;
        }, 50);
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }
};
