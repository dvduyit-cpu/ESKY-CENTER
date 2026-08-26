/**
 * Private Storage Manager for AI Chat Application
 * Stores conversations, settings, and API keys securely in browser localStorage.
 */

const StorageManager = {
    KEYS: {
        THREADS: 'private_chat_threads',
        ACTIVE_THREAD_ID: 'private_chat_active_thread_id',
        SETTINGS: 'private_chat_settings'
    },

    DEFAULT_SETTINGS: {
        geminiApiKey: '',
        openaiApiKey: '',
        ollamaUrl: 'http://localhost:11434',
        modelProvider: 'gemini',
        activeModel: 'gemini-2.5-flash',
        systemPreset: 'general',
        customSystemPrompt: 'Bạn là một trợ lý ảo thông minh, hữu ích và luôn trả lời chính xác bằng Tiếng Việt.',
        temperature: 0.7,
        maxTokens: 4096
    },

    /**
     * Get user settings from localStorage
     */
    getSettings() {
        try {
            const data = localStorage.getItem(this.KEYS.SETTINGS);
            return data ? { ...this.DEFAULT_SETTINGS, ...JSON.parse(data) } : { ...this.DEFAULT_SETTINGS };
        } catch (e) {
            console.error('Error loading settings:', e);
            return { ...this.DEFAULT_SETTINGS };
        }
    },

    /**
     * Save settings to localStorage
     */
    saveSettings(settings) {
        try {
            const current = this.getSettings();
            const updated = { ...current, ...settings };
            localStorage.setItem(this.KEYS.SETTINGS, JSON.stringify(updated));
            return true;
        } catch (e) {
            console.error('Error saving settings:', e);
            return false;
        }
    },

    /**
     * Load all chat threads
     */
    getThreads() {
        try {
            const data = localStorage.getItem(this.KEYS.THREADS);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error('Error loading chat threads:', e);
            return [];
        }
    },

    /**
     * Save thread array to localStorage
     */
    saveThreads(threads) {
        try {
            localStorage.setItem(this.KEYS.THREADS, JSON.stringify(threads));
        } catch (e) {
            console.error('Error saving threads:', e);
        }
    },

    /**
     * Get a specific thread by ID
     */
    getThread(threadId) {
        const threads = this.getThreads();
        return threads.find(t => t.id === threadId) || null;
    },

    /**
     * Create a new chat thread
     */
    createThread(title = 'Cuộc trò chuyện mới') {
        const threads = this.getThreads();
        const newThread = {
            id: 'thread_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
            title: title,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            messages: []
        };
        threads.unshift(newThread);
        this.saveThreads(threads);
        this.setActiveThreadId(newThread.id);
        return newThread;
    },

    /**
     * Update an existing thread
     */
    updateThread(threadId, updates) {
        const threads = this.getThreads();
        const index = threads.findIndex(t => t.id === threadId);
        if (index !== -1) {
            threads[index] = {
                ...threads[index],
                ...updates,
                updatedAt: new Date().toISOString()
            };
            this.saveThreads(threads);
            return threads[index];
        }
        return null;
    },

    /**
     * Delete a thread
     */
    deleteThread(threadId) {
        let threads = this.getThreads();
        threads = threads.filter(t => t.id !== threadId);
        this.saveThreads(threads);

        if (this.getActiveThreadId() === threadId) {
            const nextThreadId = threads.length > 0 ? threads[0].id : null;
            this.setActiveThreadId(nextThreadId);
        }
    },

    /**
     * Add a message to a thread
     */
    addMessage(threadId, role, content) {
        const thread = this.getThread(threadId);
        if (!thread) return null;

        const message = {
            id: 'msg_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
            role: role, // 'user' or 'assistant' or 'system'
            content: content,
            timestamp: new Date().toISOString()
        };

        thread.messages.push(message);

        // Auto generate title from first message
        if (thread.messages.length === 1 && role === 'user') {
            thread.title = content.substring(0, 32).trim() + (content.length > 32 ? '...' : '');
        }

        this.updateThread(threadId, {
            messages: thread.messages,
            title: thread.title
        });

        return message;
    },

    /**
     * Get active thread ID
     */
    getActiveThreadId() {
        return localStorage.getItem(this.KEYS.ACTIVE_THREAD_ID) || null;
    },

    /**
     * Set active thread ID
     */
    setActiveThreadId(threadId) {
        if (threadId) {
            localStorage.setItem(this.KEYS.ACTIVE_THREAD_ID, threadId);
        } else {
            localStorage.removeItem(this.KEYS.ACTIVE_THREAD_ID);
        }
    },

    /**
     * Clear all chat history
     */
    clearAllThreads() {
        localStorage.removeItem(this.KEYS.THREADS);
        localStorage.removeItem(this.KEYS.ACTIVE_THREAD_ID);
    },

    /**
     * Export full data to JSON string
     */
    exportDataJSON() {
        const data = {
            version: '1.0',
            exportedAt: new Date().toISOString(),
            settings: this.getSettings(),
            threads: this.getThreads()
        };
        return JSON.stringify(data, null, 2);
    },

    /**
     * Import full data from JSON string
     */
    importDataJSON(jsonString) {
        try {
            const data = JSON.parse(jsonString);
            if (data.threads && Array.isArray(data.threads)) {
                this.saveThreads(data.threads);
                if (data.threads.length > 0) {
                    this.setActiveThreadId(data.threads[0].id);
                }
                return true;
            }
            return false;
        } catch (e) {
            console.error('Failed to import data:', e);
            return false;
        }
    }
};
