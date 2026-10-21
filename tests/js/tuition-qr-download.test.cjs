const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/tools/tuition-qr-preview.blade.php'), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

async function download(responses) {
    const requests = [];
    const timers = new Set();
    const saved = [];
    const drawnText = [];
    const error = { hidden: true };
    const image = { dataset: { fallbackSrc: 'https://img.vietqr.io/example.png' } };
    let click;
    const link = {
        dataset: { filename: 'qr-hoc-phi-01.png' },
        href: 'http://192.168.1.2/tools/tuition-qr/download/0',
        textContent: 'Tải ảnh QR',
        closest: () => ({
            dataset: { name: 'Nguyễn Văn An', classCode: 'A01', amount: '1.500.000đ' },
            querySelector: selector => selector === '[data-qr-image]' ? image : error,
        }),
        addEventListener: (event, handler) => { click = handler; },
    };
    const document = {
        querySelectorAll: selector => selector === '[data-qr-download]' ? [link] : [],
        body: { appendChild() {} },
        createElement: tag => tag === 'canvas' ? {
            getContext: () => ({
                measureText: text => ({ width: text.length * 8 }),
                fillRect() {}, drawImage() {},
                fillText: text => drawnText.push(text),
            }),
            toBlob: callback => callback(new Blob(['png'], { type: 'image/png' })),
        } : { click() { saved.push(this.download); }, remove() {} },
    };
    vm.runInNewContext(script, {
        document, Blob, Uint8Array, AbortController,
        Image: class {
            naturalWidth = 400;
            naturalHeight = 500;
            async decode() {}
        },
        // Simulate browsers/HTTP contexts where AbortSignal.timeout is unavailable.
        AbortSignal: {},
        URL: { createObjectURL: () => 'blob:qr', revokeObjectURL() {} },
        setTimeout: (callback, delay) => { const timer = { delay }; timers.add(timer); return timer; },
        clearTimeout: timer => timers.delete(timer),
        fetch: async (url, options) => {
            requests.push({ url, options });
            const response = responses.shift();
            if (response instanceof Error) throw response;
            return response;
        },
    });
    await click({ preventDefault() {} });
    return { requests, timers, saved, error, link, drawnText };
}

const png = () => ({
    ok: true,
    headers: { get: () => '1' },
    blob: async () => new Blob([new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10])]),
});

test('downloads over LAN HTTP without AbortSignal.timeout', async () => {
    const result = await download([png()]);
    assert.deepEqual(result.saved, ['qr-hoc-phi-01.png']);
    assert.equal(result.requests.length, 1);
    assert.equal(result.requests[0].options.signal.aborted, false);
    assert.equal(result.link.textContent, 'Tải ảnh QR');
    assert.equal(result.link.dataset.busy, undefined);
    assert.deepEqual([...result.timers].map(timer => timer.delay), [60000]);
});

test('falls back to VietQR when the application download fails', async () => {
    const result = await download([{ ok: false }, png()]);
    assert.equal(result.requests.length, 2);
    assert.equal(result.requests[1].url, 'https://img.vietqr.io/example.png');
    assert.equal(result.saved.length, 1);
    assert.equal(result.error.hidden, true);
    assert.deepEqual(result.drawnText, ['Họ và tên: Nguyễn Văn An', 'Mã lớp: A01', 'Số tiền: 1.500.000đ']);
});

test('adds student details when server cannot annotate the PNG', async () => {
    const response = png();
    response.headers.get = () => '0';
    const result = await download([response]);
    assert.deepEqual(result.drawnText, ['Họ và tên: Nguyễn Văn An', 'Mã lớp: A01', 'Số tiền: 1.500.000đ']);
    assert.equal(result.saved.length, 1);
});

test('reports failure and restores the button when both sources fail', async () => {
    const result = await download([new Error('Network failure'), { ok: false }]);
    assert.equal(result.saved.length, 0);
    assert.equal(result.error.hidden, false);
    assert.equal(result.link.dataset.busy, undefined);
    assert.equal(result.link.textContent, 'Tải ảnh QR');
    assert.equal(result.timers.size, 0);
});
