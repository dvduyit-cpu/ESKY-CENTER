@extends('layouts.app')

@section('title', 'Kiểm thử toàn bộ hệ thống')
@section('header', 'Kiểm thử toàn bộ hệ thống')

@push('styles')
<style>
    .test-progress-wrap { padding:16px 20px }
    .test-progress-wrap .progress { height:8px; background:#eaf0f7 }
    .test-progress-wrap .progress-bar { transition:width .35s ease }
    .test-card { border:1px solid var(--line); border-radius:16px; background:var(--surface); overflow:hidden; box-shadow:0 8px 25px rgba(15,23,42,.05); transition:.2s ease }
    .test-card:hover { box-shadow:0 12px 30px rgba(15,23,42,.08) }
    .test-card-head { padding:16px 18px; display:flex; align-items:center; gap:13px }
    .test-card-body { padding:2px 18px 17px; border-top:1px dashed #e5eaf1 }
    .status-icon { display:grid; place-items:center; flex:0 0 38px; height:38px; border-radius:12px; background:#f1f5f9; font-size:1.1rem }
    .test-card[data-status="running"] { border-color:#60a5fa; box-shadow:0 0 0 3px rgba(59,130,246,.08) }
    .test-card[data-status="running"] .status-icon { background:#dbeafe }
    .test-card[data-status="passed"] { border-left:4px solid #22c55e }
    .test-card[data-status="passed"] .status-icon { background:#dcfce7 }
    .test-card[data-status="failed"] { border-left:4px solid #ef4444 }
    .test-card[data-status="failed"] .status-icon { background:#fee2e2 }
    .test-result { display:flex; align-items:flex-start; gap:5px; font-size:.855rem; padding:10px 12px; border-radius:10px; margin-top:9px; color:#475569; background:#f8fafc; border:1px solid #edf1f5 }
    .test-result.fail { color:#991b1b; background:#fff1f2; border-color:#fecdd3 }
    .test-result.pass { color:#166534; background:#f0fdf4; border-color:#bbf7d0 }
    .capability { font-size:.72rem; font-weight:600; padding:4px 9px; border-radius:20px; background:#f1f5f9; color:#94a3b8 }
    .capability.enabled { background:#ecfdf5; color:#15803d }
    [data-security-panel] { overflow:hidden }
    [data-database] { border:0; border-radius:14px }
    @media(max-width:767px){
        .test-card-head { flex-wrap:wrap }
        .test-card-head .flex-grow-1 { width:calc(100% - 55px) }
        .test-page-actions { width:100% }
        .test-page-actions .btn { flex:1 }
    }
</style>
@endpush

@section('content')
<div id="systemTestApp" data-catalog-url="{{ route('admin.system-test.catalog') }}">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title">Kiểm thử toàn bộ hệ thống</h1>
            <div class="page-subtitle">Kiểm tra giao diện, CRUD, tìm kiếm, phân quyền và cấu hình bảo mật.</div>
        </div>
        <div class="d-flex gap-2 test-page-actions">
            <button class="btn btn-outline-success" type="button" data-export disabled><i class="bi bi-file-earmark-arrow-down me-1"></i>Xuất báo cáo</button>
            <button class="btn btn-primary" type="button" data-run-all disabled><i class="bi bi-play-circle-fill me-1"></i>TEST HỆ THỐNG</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Tổng module','primary','bi-grid-1x2-fill','total'],
            ['Đạt','success','bi-check2-circle','passed'],
            ['Có lỗi','danger','bi-exclamation-octagon','failed'],
            ['Chưa chạy','secondary','bi-hourglass-split','pending']
        ] as [$label,$color,$icon,$target])
        <div class="col-6 col-xl-3">
            <div class="card card-soft stat-card h-100"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="stat-label">{{$label}}</div><div class="stat-value text-{{$color}}" data-{{$target}}>0</div></div>
                    <div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div>
                </div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card card-soft test-progress-wrap mb-4 d-none" data-progress-wrap>
        <div class="d-flex justify-content-between small fw-semibold mb-2"><span data-progress-label>Sẵn sàng kiểm thử</span><span data-progress-percent>0%</span></div>
        <div class="progress" role="progressbar"><div class="progress-bar" data-progress-bar style="width:0%"></div></div>
    </div>
    <div class="alert alert-info" data-loading><span class="spinner-border spinner-border-sm me-2"></span>Đang lập danh sách toàn bộ module...</div>
    <div class="alert d-none" data-database></div>
    <div class="card card-soft mb-4 d-none" data-security-panel>
        <div class="card-header bg-white py-3"><strong><i class="bi bi-shield-lock me-2 text-primary"></i>Kiểm tra bảo mật và cấu hình</strong></div>
        <div class="card-body" data-security-results></div>
    </div>
    <div data-modules></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-system-test.js') }}?v={{ filemtime(public_path('js/admin-system-test.js')) }}"></script>
@endpush
