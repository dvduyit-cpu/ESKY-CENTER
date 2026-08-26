/**
 * API Client Module for AI Providers (Gemini, OpenAI, Ollama)
 */

const ApiClient = {
    /**
     * Send chat completion request
     */
    async sendMessage({ provider, model, apiKey, systemPrompt, messages, temperature, maxTokens, onChunk, signal }) {
        if (provider === 'gemini') {
            return await this.callGeminiAPI({ apiKey, model, systemPrompt, messages, temperature, maxTokens, onChunk, signal });
        } else if (provider === 'openai') {
            return await this.callOpenAIAPI({ apiKey, model, systemPrompt, messages, temperature, maxTokens, onChunk, signal });
        } else if (provider === 'ollama') {
            return await this.callOllamaAPI({ model, systemPrompt, messages, temperature, onChunk, signal });
        } else {
            throw new Error(`Mô hình '${provider}' chưa được hỗ trợ.`);
        }
    },

    /**
     * Google Gemini API Call (REST / SSE)
     */
    async callGeminiAPI({ apiKey, model, systemPrompt, messages, temperature, maxTokens, onChunk, signal }) {
        if (!apiKey) {
            throw new Error('Chưa cấu hình Google Gemini API Key. Vui lòng mở Cài Đặt để nhập chìa khóa miễn phí từ Google.');
        }

        // Format model name for Gemini
        let modelName = model;
        if (modelName === 'gemini-2.5-flash' || modelName === 'gemini-1.5-flash') {
            modelName = 'gemini-1.5-flash';
        } else if (modelName === 'gemini-2.5-pro' || modelName === 'gemini-1.5-pro') {
            modelName = 'gemini-1.5-pro';
        }

        const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/${modelName}:generateContent?key=${apiKey}`;

        // Format messages for Gemini API
        const contents = messages.map(msg => {
            return {
                role: msg.role === 'user' ? 'user' : 'model',
                parts: [{ text: msg.content }]
            };
        });

        const requestBody = {
            contents: contents,
            generationConfig: {
                temperature: parseFloat(temperature) || 0.7,
                maxOutputTokens: parseInt(maxTokens) || 4096
            }
        };

        if (systemPrompt) {
            requestBody.systemInstruction = {
                parts: [{ text: systemPrompt }]
            };
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody),
            signal: signal
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            const errMsg = errData.error?.message || `Lỗi từ Gemini API (Mã: ${response.status})`;
            throw new Error(errMsg);
        }

        const data = await response.json();
        const fullText = data.candidates?.[0]?.content?.parts?.[0]?.text || '';
        
        if (onChunk && fullText) {
            onChunk(fullText);
        }

        return fullText;
    },

    /**
     * OpenAI API Call
     */
    async callOpenAIAPI({ apiKey, model, systemPrompt, messages, temperature, maxTokens, onChunk, signal }) {
        if (!apiKey) {
            throw new Error('Chưa cấu hình OpenAI API Key trong Cài đặt.');
        }

        const formattedMsgs = [];
        if (systemPrompt) {
            formattedMsgs.push({ role: 'system', content: systemPrompt });
        }
        messages.forEach(m => {
            formattedMsgs.push({ role: m.role, content: m.content });
        });

        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: model || 'gpt-4o',
                messages: formattedMsgs,
                temperature: parseFloat(temperature) || 0.7,
                max_tokens: parseInt(maxTokens) || 4096
            }),
            signal: signal
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.error?.message || `Lỗi từ OpenAI API (Mã ${response.status})`);
        }

        const data = await response.json();
        const fullText = data.choices?.[0]?.message?.content || '';
        if (onChunk && fullText) {
            onChunk(fullText);
        }
        return fullText;
    },

    /**
     * Ollama Local AI Call
     */
    async callOllamaAPI({ model, systemPrompt, messages, temperature, onChunk, signal }) {
        const settings = StorageManager.getSettings();
        const baseUrl = (settings.ollamaUrl || 'http://localhost:11434').replace(/\/$/, '');
        const endpoint = `${baseUrl}/api/chat`;

        const formattedMsgs = [];
        if (systemPrompt) {
            formattedMsgs.push({ role: 'system', content: systemPrompt });
        }
        messages.forEach(m => {
            formattedMsgs.push({ role: m.role, content: m.content });
        });

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: model || 'llama3',
                messages: formattedMsgs,
                stream: false,
                options: {
                    temperature: parseFloat(temperature) || 0.7
                }
            }),
            signal: signal
        });

        if (!response.ok) {
            throw new Error(`Không thể kết nối đến Ollama Local AI tại ${baseUrl}. Vui lòng kiểm tra dịch vụ Ollama đang chạy.`);
        }

        const data = await response.json();
        const fullText = data.message?.content || '';
        if (onChunk && fullText) {
            onChunk(fullText);
        }
        return fullText;
    }
};
