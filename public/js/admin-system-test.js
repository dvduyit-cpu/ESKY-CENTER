(() => {
    const app = document.querySelector('#systemTestApp');
    if (!app) return;

    const state = { modules: [], results: [], securityChecks: [], systemChecks: [] };
    const nodes = {
        modules: app.querySelector('[data-modules]'), loading: app.querySelector('[data-loading]'),
        database: app.querySelector('[data-database]'), runAll: app.querySelector('[data-run-all]'),
        export: app.querySelector('[data-export]'), total: app.querySelector('[data-total]'),
        passed: app.querySelector('[data-passed]'), failed: app.querySelector('[data-failed]'),
        pending: app.querySelector('[data-pending]'),
        securityPanel: app.querySelector('[data-security-panel]'),
        securityResults: app.querySelector('[data-security-results]'),
        systemHealthPanel: app.querySelector('[data-system-health-panel]'),
        systemHealthResults: app.querySelector('[data-system-health-results]'),
        progressWrap: app.querySelector('[data-progress-wrap]'),
        progressLabel: app.querySelector('[data-progress-label]'),
        progressPercent: app.querySelector('[data-progress-percent]'),
        progressBar: app.querySelector('[data-progress-bar]'),
    };

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
    })[char]);

    function capability(name, enabled) {
        const labels = {view:'Xem', search:'Tìm kiếm', create:'Thêm', update:'Sửa', delete:'Xóa', export:'Xuất file'};
        return `<span class="capability ${enabled ? 'enabled' : ''}">${enabled ? '✓' : '—'} ${labels[name]}</span>`;
    }

    function render() {
        const groups = state.modules.reduce((result, item) => {
            (result[item.group] ||= []).push(item);
            return result;
        }, {});
        const groupIcons = {'Tổng quan & công việc':'bi-house-heart','Trung tâm ngoại ngữ':'bi-mortarboard','KPI & báo cáo':'bi-graph-up-arrow','Quản trị':'bi-gear'};
        nodes.modules.innerHTML = Object.entries(groups).map(([group, modules]) => `
            <div class="system-section-title"><span><i class="bi ${groupIcons[group] || 'bi-folder2-open'}"></i></span><div><h5>${escapeHtml(group)}</h5><small>${modules.length} module cần kiểm tra</small></div></div>
            <div class="d-grid gap-3">${modules.map(module => `
                <section class="test-card" data-module="${module.id}" data-status="pending">
                    <div class="test-card-head">
                        <span class="status-icon text-secondary"><i class="bi bi-circle"></i></span>
                        <div class="flex-grow-1">
                            <strong>${escapeHtml(module.name)}</strong>
                            <div class="d-flex flex-wrap gap-1 mt-2">${Object.entries(module.capabilities).map(([key,value]) => capability(key,value)).join('')}</div>
                        </div>
                        <a href="${escapeHtml(module.url || '#')}" target="_blank" class="btn btn-sm btn-outline-secondary ${module.url ? '' : 'disabled'}"><i class="bi bi-box-arrow-up-right"></i></a>
                        <button class="btn btn-sm btn-outline-primary" data-run="${module.id}"><i class="bi bi-play-fill"></i> Test</button>
                    </div>
                    <div class="test-card-body d-none" data-results></div>
                </section>`).join('')}</div>`).join('');
        updateSummary();
    }

    function updateSummary() {
        const passed = state.modules.filter(item => item.status === 'passed').length;
        const failed = state.modules.filter(item => item.status === 'failed').length;
        nodes.total.textContent = state.modules.length;
        nodes.passed.textContent = passed;
        nodes.failed.textContent = failed;
        nodes.pending.textContent = state.modules.length - passed - failed;
        nodes.export.disabled = !state.results.length;
        const completed = passed + failed;
        const percent = state.modules.length ? Math.round(completed / state.modules.length * 100) : 0;
        nodes.progressPercent.textContent = `${percent}%`;
        nodes.progressBar.style.width = `${percent}%`;
    }

    function showResult(card, ok, title, detail) {
        const box = card.querySelector('[data-results]');
        box.classList.remove('d-none');
        box.insertAdjacentHTML('beforeend', `<div class="test-result ${ok ? 'pass' : 'fail'}"><i class="bi ${ok ? 'bi-check-circle-fill' : 'bi-x-circle-fill'} me-1"></i><strong>${escapeHtml(title)}</strong>${detail ? ` — ${escapeHtml(detail)}` : ''}</div>`);
        state.results.push({ module: card.dataset.module, ok, title, detail, at: new Date().toLocaleString('vi-VN') });
        return ok;
    }

    async function probe(url) {
        const response = await fetch(url, { headers: {'X-System-Smoke-Test':'1'}, redirect:'follow' });
        const text = await response.text();
        const loginRedirect = response.url.includes('/login');
        const serverError = response.status >= 500 || /Whoops|Stack trace|Fatal error|Illuminate\\\\/i.test(text);
        return { ok: response.ok && !loginRedirect && !serverError, status: response.status, loginRedirect, serverError };
    }

    async function run(module) {
        const card = app.querySelector(`[data-module="${CSS.escape(module.id)}"]`);
        const button = card.querySelector('[data-run]');
        card.dataset.status = 'running';
        card.querySelector('[data-results]').innerHTML = '';
        card.querySelector('.status-icon').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
        button.disabled = true;
        let ok = true;

        if (!module.route_ok || !module.url) {
            ok = showResult(card, false, 'Trang danh sách', `Thiếu route ${module.index}`) && ok;
        } else {
            try {
                const page = await probe(module.url);
                ok = showResult(card, page.ok, 'Mở trang giao diện', page.ok ? `HTTP ${page.status}` : `HTTP ${page.status}${page.loginRedirect ? ', bị chuyển về đăng nhập' : ''}`) && ok;
                const separator = module.url.includes('?') ? '&' : '?';
                const search = await probe(`${module.url}${separator}q=__admin_system_test__&search=__admin_system_test__`);
                ok = showResult(card, search.ok, 'Tìm kiếm / bộ lọc', search.ok ? `HTTP ${search.status}, trang xử lý tham số an toàn` : `HTTP ${search.status}`) && ok;
            } catch (error) {
                ok = showResult(card, false, 'Kết nối trang', error.message) && ok;
            }
        }

        const operations = module.operations || [];
        const invalid = operations.filter(item => !item.action || item.action.includes('Closure'));
        ok = showResult(card, operations.length > 0 && invalid.length === 0, 'Route & controller', `${operations.length} chức năng; ${invalid.length} route lỗi`) && ok;
        const unsecured = operations.filter(item =>
            /POST|PUT|PATCH|DELETE/.test(item.methods) &&
            (!item.middleware.includes('auth') || !item.middleware.includes('web'))
        );
        ok = showResult(card, unsecured.length === 0, 'Bảo mật thao tác ghi', unsecured.length === 0
            ? 'Có xác thực và bảo vệ CSRF'
            : `Thiếu bảo vệ: ${unsecured.map(item => item.name).join(', ')}`) && ok;
        const mutationCount = operations.filter(item => /POST|PUT|PATCH|DELETE/.test(item.methods)).length;
        showResult(card, true, 'CRUD an toàn', `${mutationCount} thao tác ghi đã kiểm tra cấu hình (không chạy trên dữ liệu thật)`);

        module.status = ok ? 'passed' : 'failed';
        card.dataset.status = module.status;
        card.querySelector('.status-icon').innerHTML = `<i class="bi ${ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'}"></i>`;
        button.disabled = false;
        updateSummary();
    }

    app.addEventListener('click', event => {
        const button = event.target.closest('[data-run]');
        if (button) run(state.modules.find(item => item.id === button.dataset.run));
    });

    nodes.runAll.addEventListener('click', async () => {
        nodes.runAll.disabled = true;
        nodes.runAll.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ĐANG KIỂM TRA...';
        state.results = [];
        nodes.progressWrap.classList.remove('d-none');
        nodes.progressLabel.textContent = 'Đang kiểm tra bảo mật và cấu hình...';
        nodes.securityPanel.classList.remove('d-none');
        nodes.securityResults.innerHTML = state.securityChecks.map(check => {
            const warning = !check.passed && check.severity === 'warning';
            const css = check.passed ? 'pass' : (warning ? '' : 'fail');
            const icon = check.passed ? 'bi-check-circle-fill text-success' : (warning ? 'bi-exclamation-triangle-fill text-warning' : 'bi-x-circle-fill text-danger');
            state.results.push({module:'Bảo mật', ok:check.passed, title:check.name, detail:check.detail, severity:check.severity, at:new Date().toLocaleString('vi-VN')});
            return `<div class="test-result ${css}"><i class="bi ${icon} me-1"></i><strong>${escapeHtml(check.name)}</strong> — ${escapeHtml(check.detail)}</div>`;
        }).join('');
        nodes.progressLabel.textContent = 'Đang kiểm tra sức khỏe hệ thống và nghiệp vụ...';
        nodes.systemHealthPanel.classList.remove('d-none');
        nodes.systemHealthResults.innerHTML = state.systemChecks.map(check => {
            const warning = !check.passed && check.severity === 'warning';
            const css = check.passed ? 'pass' : (warning ? '' : 'fail');
            const icon = check.passed ? 'bi-check-circle-fill text-success' : (warning ? 'bi-exclamation-triangle-fill text-warning' : 'bi-x-circle-fill text-danger');
            state.results.push({module:check.category || 'Sức khỏe hệ thống', ok:check.passed, title:check.name, detail:check.detail, severity:check.severity, at:new Date().toLocaleString('vi-VN')});
            return `<div class="test-result ${css}"><i class="bi ${icon} me-1"></i><span><strong>${escapeHtml(check.category || 'Hệ thống')} · ${escapeHtml(check.name)}</strong><br>${escapeHtml(check.detail)}</span></div>`;
        }).join('');
        for (const module of state.modules) {
            nodes.progressLabel.textContent = `Đang kiểm tra: ${module.name}`;
            await run(module);
        }
        const moduleFailures = state.modules.filter(item => item.status === 'failed').length;
        const checkFailures = [...state.securityChecks, ...state.systemChecks]
            .filter(check => !check.passed && check.severity !== 'warning').length;
        const warnings = [...state.securityChecks, ...state.systemChecks]
            .filter(check => !check.passed && check.severity === 'warning').length;
        const failures = moduleFailures + checkFailures;
        nodes.progressLabel.textContent = failures
            ? `Hoàn tất — phát hiện ${failures} lỗi cần xử lý (${moduleFailures} module, ${checkFailures} cấu hình/môi trường; ${warnings} cảnh báo)`
            : (warnings ? `Hoàn tất — không có lỗi, còn ${warnings} cảnh báo môi trường` : 'Hoàn tất — hệ thống hoạt động tốt');
        nodes.runAll.disabled = false;
        nodes.runAll.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> TEST LẠI HỆ THỐNG';
    });

    nodes.export.addEventListener('click', () => {
        const payload = {
            exported_at: new Date().toISOString(),
            summary: {
                total: state.modules.length,
                passed: state.modules.filter(x => x.status === 'passed').length,
                failed: state.modules.filter(x => x.status === 'failed').length,
                health_errors: [...state.securityChecks, ...state.systemChecks].filter(x => !x.passed && x.severity !== 'warning').length,
                health_warnings: [...state.securityChecks, ...state.systemChecks].filter(x => !x.passed && x.severity === 'warning').length,
            },
            results: state.results,
        };
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([JSON.stringify(payload, null, 2)], {type:'application/json'}));
        link.download = `bao-cao-kiem-thu-${new Date().toISOString().slice(0,10)}.json`;
        link.click();
        URL.revokeObjectURL(link.href);
    });

    fetch(app.dataset.catalogUrl, {headers:{'Accept':'application/json'}})
        .then(response => { if (!response.ok) throw new Error(`HTTP ${response.status}`); return response.json(); })
        .then(data => {
            state.modules = data.modules.map((item, id) => ({...item, id:String(id), status:'pending'}));
            state.securityChecks = data.security_checks || [];
            state.systemChecks = data.system_checks || [];
            nodes.loading.classList.add('d-none');
            nodes.database.className = `alert ${data.database.ok ? 'alert-success' : 'alert-danger'}`;
            nodes.database.textContent = data.database.ok ? `✓ ${data.database.message}` : `Lỗi cơ sở dữ liệu: ${data.database.message}`;
            nodes.runAll.disabled = false;
            render();
        })
        .catch(error => {
            nodes.loading.className = 'alert alert-danger';
            nodes.loading.textContent = `Không thể tải bộ kiểm thử: ${error.message}`;
        });
})();
