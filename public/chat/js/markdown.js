/**
 * Markdown Renderer & Syntax Highlighter Component
 */

const MarkdownRenderer = {
    init() {
        if (window.marked) {
            window.marked.setOptions({
                gfm: true,
                breaks: true,
                headerIds: false,
                mangle: false
            });
        }
    },

    /**
     * Escape HTML helper
     */
    escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    /**
     * Render raw markdown text to safe HTML string with styled code blocks
     */
    render(text) {
        if (!text) return '';

        let html = '';
        if (window.marked) {
            html = window.marked.parse(text);
        } else {
            // Fallback plain text formatting if marked.js CDN is unavailable
            html = this.escapeHtml(text).replace(/\n/g, '<br>');
            return html;
        }

        // Wrap code blocks with container header & copy button
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        const preBlocks = tempDiv.querySelectorAll('pre');
        preBlocks.forEach((pre) => {
            const codeEl = pre.querySelector('code');
            let lang = 'code';
            if (codeEl) {
                const classes = codeEl.className.split(' ');
                classes.forEach(c => {
                    if (c.startsWith('language-')) {
                        lang = c.replace('language-', '');
                    }
                });

                // Apply Highlight.js if available
                if (window.hljs) {
                    try {
                        if (lang !== 'code' && window.hljs.getLanguage(lang)) {
                            codeEl.innerHTML = window.hljs.highlight(codeEl.textContent, { language: lang }).value;
                        } else {
                            codeEl.innerHTML = window.hljs.highlightAuto(codeEl.textContent).value;
                        }
                    } catch (e) {
                        console.warn('Syntax highlight error:', e);
                    }
                }
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper';

            const header = document.createElement('div');
            header.className = 'code-header';
            header.innerHTML = `
                <span class="code-lang">${lang.toUpperCase()}</span>
                <button class="copy-code-btn" onclick="MarkdownRenderer.copyCode(this)">
                    <i class="fa-regular fa-copy"></i>
                    <span>Sao chép</span>
                </button>
            `;

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(header);
            wrapper.appendChild(pre);
        });

        return tempDiv.innerHTML;
    },

    /**
     * Copy code block content to clipboard
     */
    copyCode(btn) {
        const wrapper = btn.closest('.code-block-wrapper');
        if (!wrapper) return;

        const codeEl = wrapper.querySelector('code') || wrapper.querySelector('pre');
        if (!codeEl) return;

        const text = codeEl.textContent;
        navigator.clipboard.writeText(text).then(() => {
            const span = btn.querySelector('span');
            const icon = btn.querySelector('i');
            
            const originalText = span.textContent;
            span.textContent = 'Đã chép!';
            icon.className = 'fa-solid fa-check';

            setTimeout(() => {
                span.textContent = originalText;
                icon.className = 'fa-regular fa-copy';
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy code:', err);
        });
    }
};

// Initialize markdown on load
MarkdownRenderer.init();
