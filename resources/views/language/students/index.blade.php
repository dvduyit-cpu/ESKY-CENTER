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

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4 student-page-header">
    <div>
        <h1 class="page-title">Quản lý học viên</h1>
        <div class="page-subtitle">Tra cứu nhanh tên và số điện thoại; mở hồ sơ để xem lớp học và thông tin chi tiết.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 student-header-actions">
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
            <form method="POST" action="{{route('language-students.import')}}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="studentImportModalLabel">Nhập học viên từ Excel</h5>
                        <div class="small text-muted mt-1">Hỗ trợ file .xlsx hoặc .xls, tối đa 10 MB.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="student-import-file">Chọn file Excel <span class="text-danger">*</span></label>
                    <input class="form-control" id="student-import-file" type="file" name="file" accept=".xlsx,.xls" required>
                    <div class="form-text mt-2">Nên tải file mẫu mới nhất, giữ nguyên hàng tiêu đề và xem sheet HƯỚNG DẪN trước khi nhập.</div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-light me-auto" href="{{route('language-students.template')}}" data-no-loading download><i class="bi bi-download me-2"></i>Tải file mẫu</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-2"></i>Bắt đầu nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<form class="filter-panel row g-3 mb-4 student-filter">
    <div class="col-lg-7"><label class="form-label">Tìm học viên</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tên, mã, SĐT học viên hoặc SĐT phụ huynh"></div></div>
    <div class="col-lg-3 col-md-6"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả trạng thái</option>@foreach($labels as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div>
    <div class="col-lg-2 col-md-6 d-flex align-items-end"><button class="btn btn-dark w-100"><i class="bi bi-search me-2"></i>Tìm kiếm</button></div>
</form>

<div class="card card-soft student-list-card">
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Học viên</th><th>SĐT học viên</th><th>Phụ huynh / người giám hộ</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                @php($primaryGuardian=$item->guardians->firstWhere('is_primary',true) ?: $item->guardians->first())
                <tr>
                    <td><a class="student-identity text-decoration-none text-body" href="{{route('language-students.show',$item)}}"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><span><strong class="d-block">{{$item->name}}</strong><span class="small text-muted">{{$item->code}}</span></span></a></td>
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
                <tr><td colspan="5"><div class="empty-state">Chưa có học viên phù hợp với tìm kiếm.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="student-mobile-list d-lg-none">
        @forelse($items as $item)
            @php($primaryGuardian=$item->guardians->firstWhere('is_primary',true) ?: $item->guardians->first())
            <article class="student-mobile-card">
                <div class="student-mobile-head"><div class="student-identity"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><div><strong>{{$item->name}}</strong><div class="small text-muted">{{$item->code}}</div></div></div><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></div>
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
