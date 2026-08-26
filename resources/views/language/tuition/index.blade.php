@extends('layouts.app')

@section('title', 'Thu học phí')
@section('header', 'Quản lý học viên')

@section('content')
@php($labels = ['unpaid' => 'Chưa đóng', 'partial' => 'Đóng một phần', 'pending_receipt' => 'Chờ bổ sung phiếu thu', 'paid' => 'Đã đóng đủ', 'transferred' => 'Đã quyết toán chuyển lớp'])

@if(session('tuition_import_errors'))
<div class="alert alert-danger border-0 shadow-sm">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Chi tiết lỗi import học phí</strong>
        <span class="small">Các dòng này chưa được lưu</span>
    </div>
    <div class="mt-3 overflow-auto" style="max-height:320px">
        <ol class="mb-0">@foreach(session('tuition_import_errors') as $error)<li class="mb-1">{{$error}}</li>@endforeach</ol>
    </div>
</div>
@endif

@php($monthlySyncReport = session('tuition_monthly_sync_report'))
@php($syncDate = $monthlySyncReport['scope_date'] ?? now()->format('Y-m-d'))
@php($targetPaidDate = session('tuition_target_paid_date', now()->format('Y-m-d')))
@php($isAdmin = auth()->user()?->isAdmin())
@if($isAdmin)
<div class="card card-soft mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
            <div class="me-lg-auto">
                <h5 class="mb-1">Đồng bộ thu học phí theo tháng</h5>
                <div class="text-muted small">Dùng khi đã cập nhật phiếu thu qua file upload nhưng cần rà lại và lưu những học viên đã đóng tiền sang phần `Thu học phí theo tháng`. Chọn `Ngày import / cập nhật` để quét riêng nhóm vừa nhập; để trống nếu muốn quét toàn bộ.</div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <form method="POST" action="{{ route('language-tuition.monthly-sync.check') }}" class="d-flex flex-wrap gap-2 align-items-end">
                    @csrf
                    <div>
                        <label class="form-label small mb-1">Ngày import / cập nhật</label>
                        <input class="form-control" type="date" name="sync_date" value="{{ $syncDate }}">
                    </div>
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i>Kiểm tra theo ngày
                    </button>
                </form>
                <form method="POST" action="{{ route('language-tuition.monthly-sync.apply') }}" class="d-flex flex-wrap gap-2 align-items-end" data-confirm="Xác nhận cập nhật lại dữ liệu Thu học phí theo tháng từ các phiếu đã thu?">
                    @csrf
                    <input type="hidden" name="sync_date" value="{{ $syncDate }}">
                    <button class="btn btn-primary" @disabled(!($monthlySyncReport['has_issues'] ?? false))>
                        <i class="bi bi-arrow-repeat me-2"></i>Cập nhật theo ngày
                    </button>
                </form>
                <form method="POST" action="{{ route('language-tuition.monthly-sync.check') }}" class="d-flex align-items-end">
                    @csrf
                    <input type="hidden" name="sync_date" value="">
                    <button class="btn btn-light">
                        <i class="bi bi-collection me-2"></i>Kiểm tra toàn bộ
                    </button>
                </form>
            </div>
        </div>

        @if($monthlySyncReport)
            <div class="row g-3 mt-1">
                <div class="col-md-6 col-xl">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted">Phiếu chờ đã có số phiếu</div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format($monthlySyncReport['pending_with_receipt_code']) }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted">Thiếu ở dữ liệu tháng</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($monthlySyncReport['missing_monthly_records']) }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted">Bản ghi tháng bị lệch</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($monthlySyncReport['stale_monthly_records']) }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted">Bản ghi tháng dư</div>
                        <div class="fs-4 fw-bold">{{ number_format($monthlySyncReport['orphan_monthly_records']) }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl">
                    <div class="border rounded-3 p-3 h-100 bg-light">
                        <div class="small text-muted">Khoản thu cần tính lại</div>
                        <div class="fs-4 fw-bold text-info">{{ number_format($monthlySyncReport['charge_mismatches']) }}</div>
                    </div>
                </div>
            </div>

            <div class="alert {{ $monthlySyncReport['has_issues'] ? 'alert-warning' : 'alert-success' }} border-0 mt-3 mb-0">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $monthlySyncReport['has_issues'] ? 'Đã phát hiện dữ liệu cần đồng bộ.' : 'Dữ liệu thu học phí theo tháng đã khớp.' }}</strong>
                    <span class="small">
                        {{ !empty($monthlySyncReport['scope_date']) ? 'Phạm vi ngày '.$monthlySyncReport['scope_date'].' · ' : '' }}
                        Đã rà {{ number_format($monthlySyncReport['scanned_payments']) }} phiếu thu và {{ number_format($monthlySyncReport['scanned_charges']) }} khoản thu.
                    </span>
                </div>
                @if(!empty($monthlySyncReport['sample_issues']))
                    <div class="mt-2 small">
                        @foreach($monthlySyncReport['sample_issues'] as $issue)
                            <div><strong>{{ $issue['type'] }}:</strong> {{ $issue['label'] }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="border-top mt-4 pt-4">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
                <div class="me-lg-auto">
                    <h6 class="mb-1">Sửa nhanh ngày thu bị nhập sai</h6>
                    <div class="text-muted small">Dùng khi các phiếu vừa import sáng nay bị sai `Ngày thu`. Hệ thống sẽ đổi phần ngày về ngày bạn chọn, giữ nguyên giờ thu, rồi cập nhật lại dữ liệu `Thu học phí theo tháng`.</div>
                </div>
                <form method="POST" action="{{ route('language-tuition.monthly-sync.shift-paid-date') }}" class="d-flex flex-wrap gap-2 align-items-end" data-confirm="Xác nhận đổi ngày thu cho các phiếu đã cập nhật trong ngày đã chọn? Thao tác này sẽ ảnh hưởng báo cáo doanh thu theo ngày/tháng.">
                    @csrf
                    <div>
                        <label class="form-label small mb-1">Ngày import / cập nhật</label>
                        <input class="form-control" type="date" name="sync_date" value="{{ $syncDate }}">
                    </div>
                    <div>
                        <label class="form-label small mb-1">Chuyển ngày thu về</label>
                        <input class="form-control" type="date" name="target_paid_date" value="{{ $targetPaidDate }}" required>
                    </div>
                    <button class="btn btn-outline-danger">
                        <i class="bi bi-calendar-date me-2"></i>Đổi ngày thu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<div class="d-flex flex-column flex-lg-row align-items-start gap-3 mb-4">
    <div class="me-lg-auto">
        <h1 class="page-title">Thu học phí</h1>
        <div class="page-subtitle">Danh sách khoản phải thu của từng học viên và lớp học. Chọn nhiều dòng để xuất nhanh hoặc áp dụng lại mức miễn giảm cao nhất.</div>
        <div class="small text-muted mt-2">Số đã thu chỉ tính phiếu đã xác nhận. Phiếu chờ bổ sung số phiếu/ngày thu sẽ chưa trừ công nợ.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 ms-lg-auto justify-content-lg-end">
        @if(auth()->user()->allowed('language_tuition', 'update'))
            <a class="btn btn-outline-primary" href="{{ route('language-tuition.template') }}" data-no-loading download>
                <i class="bi bi-file-earmark-arrow-down me-2"></i>File mẫu
            </a>
            <a class="btn btn-outline-warning" href="{{ route('language-tuition.outstanding-sheet', request()->query()) }}" data-no-loading>
                <i class="bi bi-journal-arrow-down me-2"></i>DS còn nợ
            </a>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#tuitionImportModal">
                <i class="bi bi-cloud-arrow-up me-2"></i>Nhập Excel
            </button>
        @endif
        <a class="btn btn-outline-primary" href="{{ route('language-tuition.monthly') }}">
            <i class="bi bi-calendar2-check me-2"></i>Theo tháng
        </a>
        <a class="btn btn-outline-success" href="{{ route('language-tuition.export', request()->query()) }}">
            <i class="bi bi-file-earmark-excel me-2"></i>Excel
        </a>
        <a class="btn btn-primary" href="{{ route('language-tuition.create') }}">
            <i class="bi bi-plus-circle me-2"></i>Lập khoản thu
        </a>
    </div>
</div>

@if(auth()->user()->allowed('language_tuition', 'update'))
<div class="modal fade" id="tuitionImportModal" tabindex="-1" aria-labelledby="tuitionImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{route('language-tuition.import')}}" enctype="multipart/form-data" data-tuition-import-form>
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="tuitionImportModalLabel">Cập nhật thu học phí từ Excel</h5>
                        <div class="small text-muted mt-1">Đối chiếu theo họ tên + ngày sinh, hỗ trợ file .xlsx, .xls hoặc .csv tối đa 10 MB.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-tuition-import-close aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div data-tuition-import-picker>
                        <label class="form-label" for="tuition-import-file">Chọn file Excel <span class="text-danger">*</span></label>
                        <input class="form-control" id="tuition-import-file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text mt-2">Nếu học viên học nửa lớp, điền `Thu nửa lớp = Có` hoặc `Tỷ lệ thu (%) = 50` trong file mẫu để hệ thống tính lại đúng số tiền. Với file công nợ, chỉ khi bổ sung đủ số phiếu/ngày thu thì hệ thống mới trừ công nợ trên các trang.</div>
                    </div>
                    <div class="d-none" data-tuition-import-progress aria-live="polite">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <strong data-tuition-import-status>Đang đọc file Excel...</strong>
                            <span class="badge student-import-count-badge" data-tuition-import-count>0/0 dòng</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Tiến trình import học phí" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:12px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" data-tuition-import-bar style="width:0%"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3 small">
                            <span class="badge student-import-summary-badge is-created">Tạo mới: <span data-tuition-import-created>0</span></span>
                            <span class="badge student-import-summary-badge is-updated">Cập nhật: <span data-tuition-import-updated>0</span></span>
                            <span class="badge student-import-summary-badge is-failed">Lỗi: <span data-tuition-import-failed>0</span></span>
                        </div>
                        <div class="student-import-live-log mt-3 border rounded-3 bg-light" data-tuition-import-log></div>
                        <div class="alert alert-danger d-none mt-3 mb-0" data-tuition-import-error></div>
                        <div class="d-none mt-3" data-tuition-import-preview>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <strong>Xem trước dữ liệu hợp lệ</strong>
                                <span class="small text-muted" data-tuition-import-preview-note></span>
                            </div>
                            <div class="table-responsive border rounded-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Học viên</th>
                                            <th>Lớp / khoản thu</th>
                                            <th>Số phiếu</th>
                                            <th>Thu dự kiến</th>
                                            <th>Hình thức</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody data-tuition-import-preview-body></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-light me-auto" href="{{route('language-tuition.template')}}" data-no-loading download data-tuition-import-action><i class="bi bi-download me-2"></i>Tải file mẫu</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-tuition-import-close data-tuition-import-action>Hủy</button>
                    <button type="button" class="btn btn-outline-primary" data-tuition-import-validate data-tuition-import-action><i class="bi bi-clipboard2-check me-2"></i>Kiểm tra file</button>
                    <button class="btn btn-primary" data-tuition-import-action data-tuition-import-submit disabled><i class="bi bi-cash-coin me-2"></i>Cập nhật học phí</button>
                    <button type="button" class="btn btn-primary d-none" data-tuition-import-finish><i class="bi bi-arrow-clockwise me-2"></i>Đóng và tải lại danh sách</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<form class="filter-panel row g-3 mb-4">
    <div class="col-lg-3">
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Mã lớp, khoản thu hoặc học viên">
    </div>
    <div class="col-lg-3">
        <select class="form-select" name="class">
            <option value="">Mọi mã lớp</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((int) request('class') === $class->id)>{{ $class->code }} – {{ $class->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2">
        <select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            @foreach($labels as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-1">
        <input class="form-control" type="number" name="year" value="{{ $filterYear }}" min="2020" max="2100" aria-label="Năm">
    </div>
    <div class="col-lg-1">
        <select class="form-select" name="quarter">
            <option value="">Quý</option>
            @for($q = 1; $q <= 4; $q++)
                <option value="{{ $q }}" @selected(request('quarter') == $q)>Q{{ $q }}</option>
            @endfor
        </select>
    </div>
    <div class="col-lg-1">
        <select class="form-select" name="month">
            <option value="">Tháng</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected(request('month') == $m)>T{{ $m }}</option>
            @endfor
        </select>
    </div>
    <div class="col-lg-1">
        <button class="btn btn-dark w-100" title="Lọc">
            <i class="bi bi-filter"></i>
        </button>
    </div>
</form>

<form id="bulk-tuition" method="POST" action="{{ route('language-tuition.discount.bulk-highest') }}" data-bulk-form="tuition" data-bulk-confirm="Áp dụng mức miễn giảm cao nhất cho các khoản thu đã chọn?" class="mb-3 d-flex flex-wrap align-items-center gap-2">
    @csrf
    @method('PATCH')
    <label class="me-2">
        <input class="form-check-input me-1" type="checkbox" data-bulk-all="tuition"> Chọn tất cả trang này
    </label>
    @if(auth()->user()->allowed('language_tuition', 'update'))
        <button class="btn btn-sm btn-outline-primary" data-bulk-submit disabled>
            <i class="bi bi-percent me-1"></i>Áp dụng mức cao nhất (<span data-bulk-count>0</span>)
        </button>
    @endif
</form>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th data-selection-column class="text-center" style="width:42px"></th>
                    <th>Mã khoản thu</th>
                    <th>Học viên</th>
                    <th>Khóa học / lớp</th>
                    <th>Giảm</th>
                    <th>Phải thu</th>
                    <th>Đã thu</th>
                    <th>Chuyển sang</th>
                    <th>Còn lại</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php($remaining = $item->remainingAmount())
                    <tr>
                        <td data-selection-column class="text-center">
                            <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-tuition" data-bulk-item="tuition">
                        </td>
                        <td>
                            <strong>{{ $item->code }}</strong>
                            <div class="small text-muted">{{ $item->created_at?->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <strong>{{ $item->student->name }}</strong>
                            <div class="small text-muted">{{ $item->student->code }}</div>
                        </td>
                        <td>
                            {{ $item->course->name }}
                            <div class="small text-muted">Lớp: {{ $item->languageClass?->code ?: 'Chưa xếp' }}</div>
                        </td>
                        <td>
                            <strong>{{ \App\Support\ValueFormatter::percentage($item->discount_percentage) }}%</strong>
                            <div class="small text-muted">{{ $item->discount?->name ?: 'Không miễn giảm' }}</div>
                        </td>
                        <td>{{ number_format($item->payable_amount) }}đ</td>
                        <td class="fw-semibold text-success">{{ number_format($item->paid_amount) }}đ</td>
                        <td class="fw-semibold text-primary">{{ number_format($item->credit_amount) }}đ</td>
                        <td class="fw-semibold {{ $remaining > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($remaining) }}đ</td>
                        <td>
                            <span class="badge-soft {{ in_array($item->status, ['paid', 'transferred']) ? 'badge-success' : (in_array($item->status, ['partial', 'pending_receipt']) ? 'badge-warning' : 'badge-danger') }}">
                                {{ $labels[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary text-nowrap" href="{{ route('language-tuition.show', $item) }}">
                                <i class="bi bi-eye me-1"></i>Chi tiết
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">Không có khoản thu trong kỳ đã chọn.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0">{{ $items->links() }}</div>
</div>
@endsection

@push('scripts')
<script src="{{asset('js/tuition-import.js')}}?v={{filemtime(public_path('js/tuition-import.js'))}}"></script>
@endpush
