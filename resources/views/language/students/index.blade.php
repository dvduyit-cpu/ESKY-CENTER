@extends('layouts.app')
@section('title','Học viên')
@section('header','Quản lý học viên')
@section('content')
@php($labels=['new'=>'Mới đăng ký','placement_test'=>'Chờ kiểm tra','waiting_class'=>'Chờ xếp lớp','studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'])

@if(session('student_import_errors'))
<div class="alert alert-danger border-0 shadow-sm">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Chi tiết lỗi nhập học viên</strong>
        <span class="small">Các dòng này chưa được lưu</span>
    </div>
    <div class="mt-3 overflow-auto" style="max-height:320px">
        <ol class="mb-0">@foreach(session('student_import_errors') as $error)<li class="mb-1">{{$error}}</li>@endforeach</ol>
    </div>
</div>
@endif

@if(session('student_import_warnings'))
<div class="alert alert-warning border-0 shadow-sm">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
        <strong><i class="bi bi-info-circle-fill me-2"></i>Hồ sơ trùng đã không được ghi đè</strong>
        <span class="small">Có thể nhập lại và chọn “Nhập và ghi đè” nếu muốn cập nhật</span>
    </div>
    <div class="mt-3 overflow-auto" style="max-height:320px">
        <ol class="mb-0">@foreach(session('student_import_warnings') as $warning)<li class="mb-1">{{$warning}}</li>@endforeach</ol>
    </div>
</div>
@endif

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4 student-page-header">
    <div>
        <h1 class="page-title">Quản lý học viên</h1>
        <div class="page-subtitle">Tra cứu nhanh tên và số điện thoại; mở hồ sơ để xem lớp học và thông tin chi tiết.</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="badge rounded-pill text-bg-primary"><i class="bi bi-people-fill me-1"></i>Tổng số học viên: {{number_format($totalStudents)}}</span>
            @if($duplicateStudentCount>0)<a class="badge rounded-pill text-bg-warning text-decoration-none" href="{{route('language-students.index',['duplicates'=>1])}}"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{$duplicateStudentCount}} hồ sơ có thể trùng</a>@endif
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-end gap-2 ms-auto student-header-actions">
        <a class="btn btn-outline-warning" href="{{route('language-students.duplicates')}}"><i class="bi bi-intersect me-2"></i>Kiểm tra trùng dữ liệu @if($duplicateStudentCount>0)<span class="badge text-bg-warning ms-1">{{$duplicateStudentCount}}</span>@endif</a>
        @if(auth()->user()->allowed('language_tuition'))
            <a class="btn btn-outline-success" href="{{route('language-tuition.index')}}"><i class="bi bi-cash-coin me-2"></i>Thu học phí</a>
        @endif
        @if(auth()->user()->allowed('language_students','create'))
            <a class="btn btn-outline-primary" href="{{route('language-students.template')}}" data-no-loading download><i class="bi bi-file-earmark-arrow-down me-2"></i>Tải file mẫu</a>
            @if(auth()->user()->isRegistrar())<button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#studentImportModal"><i class="bi bi-cloud-arrow-up me-2"></i>Nhập Excel</button>@endif
            <a class="btn btn-primary" href="{{route('language-students.create')}}"><i class="bi bi-plus-circle me-2"></i>Thêm học viên</a>
        @endif
    </div>
</div>

@if(auth()->user()->allowed('language_students','create')&&auth()->user()->isRegistrar())
<div class="modal fade" id="studentImportModal" tabindex="-1" aria-labelledby="studentImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{route('language-students.import')}}" enctype="multipart/form-data" data-student-import-form>
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="studentImportModalLabel">Nhập học viên từ Excel</h5>
                        <div class="small text-muted mt-1">Hỗ trợ file .xlsx hoặc .xls, tối đa 10 MB.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-student-import-close aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div data-student-import-picker>
                        <label class="form-label" for="student-import-file">Chọn file Excel <span class="text-danger">*</span></label>
                        <input class="form-control" id="student-import-file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text mt-2">Nen tai file mau moi nhat, giu nguyen hang tieu de va xem sheet HUONG DAN truoc khi nhap. Bam Kiem tra file truoc, chi khi khong co dong loi moi cho nhap.</div>
                    </div>
                    <div class="d-none" data-student-import-progress aria-live="polite">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <strong data-student-import-status>Đang đọc file Excel...</strong>
                            <span class="badge student-import-count-badge" data-student-import-count>0/0 dòng</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Tiến trình nhập học viên" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:12px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" data-student-import-bar style="width:0%"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3 small">
                            <span class="badge student-import-summary-badge is-created">Thêm: <span data-student-import-created>0</span></span>
                            <span class="badge student-import-summary-badge is-updated">Ghi đè: <span data-student-import-updated>0</span></span>
                            <span class="badge student-import-summary-badge is-skipped">Bỏ qua: <span data-student-import-skipped>0</span></span>
                            <span class="badge student-import-summary-badge is-failed">Lỗi: <span data-student-import-failed>0</span></span>
                        </div>
                        <div class="student-import-live-log mt-3 border rounded-3 bg-light" data-student-import-log></div>
                        <div class="alert alert-danger d-none mt-3 mb-0" data-student-import-error></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-light me-auto" href="{{route('language-students.template')}}" data-no-loading download data-student-import-action><i class="bi bi-download me-2"></i>Tải file mẫu</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-student-import-close data-student-import-action>Hủy</button>
                    <button type="button" class="btn btn-outline-primary" data-student-import-validate data-student-import-action><i class="bi bi-clipboard2-check me-2"></i>Kiem tra file</button>
                    <button class="btn btn-primary" name="duplicate_action" value="skip" data-student-import-action data-student-import-submit disabled><i class="bi bi-shield-check me-2"></i>Nhập, không ghi đè</button>
                    <button class="btn btn-warning" name="duplicate_action" value="overwrite" data-student-import-action data-student-import-submit disabled><i class="bi bi-arrow-repeat me-2"></i>Nhập và ghi đè</button>
                    <button type="button" class="btn btn-primary d-none" data-student-import-finish><i class="bi bi-arrow-clockwise me-2"></i>Đóng và tải lại danh sách</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<form class="filter-panel row g-3 mb-4 student-filter">
    <div class="col-lg-5"><label class="form-label">Tìm học viên</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tên, mã, SĐT học viên hoặc SĐT phụ huynh"></div></div>
    <div class="col-lg-3 col-md-6"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả trạng thái</option>@foreach($labels as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div>
    <div class="col-lg-2 col-md-6"><label class="form-label">Dữ liệu trùng</label><select class="form-select" name="duplicates"><option value="">Tất cả hồ sơ</option><option value="1" @selected(request('duplicates')==='1')>Chỉ hồ sơ trùng</option></select></div>
    <div class="col-lg-2 col-md-6 d-flex align-items-end"><button class="btn btn-dark w-100"><i class="bi bi-search me-2"></i>Tìm kiếm</button></div>
</form>

<div class="card card-soft student-list-card">
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>STT</th><th>Học viên</th><th>SĐT học viên</th><th>Phụ huynh / người giám hộ</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                @php($primaryGuardian=$item->guardians->firstWhere('is_primary',true) ?: $item->guardians->first())
                <tr>
                    <td class="fw-bold text-muted">{{($items->firstItem()??1)+$loop->index}}</td>
                    <td><a class="student-identity text-decoration-none text-body" href="{{route('language-students.show',$item)}}"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><span><strong class="d-block">{{$item->name}} @if(isset($duplicateIdLookup[$item->id]))<span class="badge text-bg-warning ms-1">Có thể trùng</span>@endif</strong><span class="small text-muted">{{$item->code}}</span></span></a></td>
                    <td><strong>{{$item->phone?:'Chưa cập nhật'}}</strong></td>
                    <td>@if($primaryGuardian)<strong>{{$primaryGuardian->name}}</strong><div class="small text-muted">{{$primaryGuardian->phone?:'Chưa có SĐT'}}</div>@else<span class="text-muted">Chưa cập nhật</span>@endif</td>
                    <td><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></td>
                    <td class="text-end text-nowrap student-actions">
                        <a class="btn btn-sm btn-outline-dark" href="{{route('language-students.show',$item)}}" title="Xem hồ sơ và lớp học"><i class="bi bi-eye me-1"></i>Xem hồ sơ</a>
                        @if(auth()->user()->allowed('language_students','update'))<a class="btn btn-sm btn-outline-primary" href="{{route('language-students.edit',$item)}}" title="Sửa học viên" aria-label="Sửa học viên"><i class="bi bi-pencil"></i></a>@endif
                        @if(auth()->user()->allowed('language_students','delete'))<form class="d-inline" method="POST" action="{{route('language-students.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa học viên" aria-label="Xóa học viên" data-confirm="Xóa học viên này?"><i class="bi bi-trash"></i></button></form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">Chưa có học viên phù hợp với tìm kiếm.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="student-mobile-list d-lg-none">
        @forelse($items as $item)
            @php($primaryGuardian=$item->guardians->firstWhere('is_primary',true) ?: $item->guardians->first())
            <article class="student-mobile-card">
                <div class="student-mobile-head"><div class="student-identity"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><div><div class="small text-muted">STT {{($items->firstItem()??1)+$loop->index}}</div><strong>{{$item->name}}</strong>@if(isset($duplicateIdLookup[$item->id]))<div><span class="badge text-bg-warning">Có thể trùng</span></div>@endif<div class="small text-muted">Mã hồ sơ: {{$item->code}}</div></div></div><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></div>
                <div class="student-mobile-grid"><div><span>SĐT học viên</span><strong>{{$item->phone?:'Chưa cập nhật'}}</strong></div><div><span>Phụ huynh / người giám hộ</span><strong>{{$primaryGuardian?->name?:'Chưa cập nhật'}}</strong><small class="d-block text-muted">{{$primaryGuardian?->phone}}</small></div></div>
                <div class="student-mobile-actions">
                    <a class="btn btn-outline-dark flex-grow-1 w-auto px-3" href="{{route('language-students.show',$item)}}" title="Xem hồ sơ và lớp học"><i class="bi bi-eye me-2"></i>Xem hồ sơ và lớp học</a>
                    @if(auth()->user()->allowed('language_students','update'))<a class="btn btn-outline-primary" href="{{route('language-students.edit',$item)}}" title="Sửa học viên"><i class="bi bi-pencil"></i></a>@endif
                    @if(auth()->user()->allowed('language_students','delete'))<form method="POST" action="{{route('language-students.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-outline-danger" title="Xóa học viên" data-confirm="Xóa học viên này?"><i class="bi bi-trash"></i></button></form>@endif
                </div>
            </article>
        @empty
            <div class="empty-state">Chưa có học viên phù hợp với tìm kiếm.</div>
        @endforelse
    </div>
    <div class="card-footer bg-white border-0">{{$items->links()}}</div>
</div>
@endsection
@push('scripts')
<script src="{{asset('js/student-import.js')}}?v={{filemtime(public_path('js/student-import.js'))}}"></script>
@endpush
