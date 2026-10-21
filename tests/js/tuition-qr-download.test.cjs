const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/tools/tuition-qr-preview.blade.php'), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

async function download(responses, batch = false, count = 1) {
    const requests = [];
    const timers = new Set();
    const saved = [];
    const drawnText = [];
    const blobs = [];
    const status = { hidden: true };
    const button = { textContent: 'Download all', disabled: false, addEventListener: (event, handler) => { batchClick = handler; } };
    let batchClick;
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
        querySelector: () => status,
        querySelectorAll: selector => selector === '[data-qr-download]' ? Array(count).fill(link) : selector === '[data-qr-download-all]' ? [button] : [],
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
        document, Blob, Uint8Array, DataView, TextEncoder, AbortController,
        Image: class {
            naturalWidth = 400;
            naturalHeight = 500;
            async decode() {}
        },
        // Simulate browsers/HTTP contexts where AbortSignal.timeout is unavailable.
        AbortSignal: {},
        URL: { createObjectURL: blob => { blobs.push(blob); return 'blob:qr'; }, revokeObjectURL() {} },
        setTimeout: (callback, delay) => { const timer = { delay }; timers.add(timer); return timer; },
        clearTimeout: timer => timers.delete(timer),
        fetch: async (url, options) => {
            requests.push({ url, options });
            const response = responses.shift();
            if (response instanceof Error) throw response;
            return response;
        },
    });
    await (batch ? batchClick() : click({ preventDefault() {} }));
    return { requests, timers, saved, error, link, drawnText, blobs, status, button };
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

test('bulk download packages fallback PNG in a valid ZIP directory', async () => {
    const result = await download([{ ok: false }, png()], true);
    assert.deepEqual(result.saved, ['danh-sach-qr-hoc-phi.zip']);
    const zip = result.blobs.at(-1);
    assert.equal(zip.type, 'application/zip');
    const bytes = new Uint8Array(await zip.arrayBuffer());
    const header = new DataView(bytes.buffer);
    assert.equal(header.getUint32(0, true), 0x04034b50);
    const nameLength = header.getUint16(26, true);
    assert.equal(new TextDecoder().decode(bytes.slice(30, 30 + nameLength)), 'qr-hoc-phi-01.png');
    assert.equal(header.getUint32(14, true), 0x83180390); // CRC32 of "png"
    const centralOffset = 30 + nameLength + header.getUint32(18, true);
    assert.equal(header.getUint32(centralOffset, true), 0x02014b50);
    assert.equal(header.getUint32(bytes.length - 22, true), 0x06054b50);
    assert.equal(header.getUint16(bytes.length - 14, true), 1);
    assert.equal(header.getUint32(bytes.length - 6, true), centralOffset);
    assert.equal(result.button.disabled, false);
    assert.match(result.status.textContent, /1\/1/);
});

test('bulk failure shows an error and enables retry without saving an empty ZIP', async () => {
    const result = await download([{ ok: false }, { ok: false }], true);
    assert.deepEqual(result.saved, []);
    assert.equal(result.status.hidden, false);
    assert.equal(result.button.disabled, false);
    assert.equal(result.button.textContent, 'Download all');
});

test('bulk download keeps successful QR images and reports failed students', async () => {
    const result = await download([png(), { ok: false }, { ok: false }], true, 2);
    assert.deepEqual(result.saved, ['danh-sach-qr-hoc-phi.zip']);
    assert.match(result.status.textContent, /1\/2/);
    assert.ok(result.status.textContent.includes('Nguyễn Văn An'));
    assert.equal(result.button.disabled, false);
});
