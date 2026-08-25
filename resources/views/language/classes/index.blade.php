@extends('layouts.app')
@section('title','Lớp học')
@section('header','Trung tâm ngoại ngữ')

@section('content')
@php($labels=['planned'=>'Dự kiến mở','recruiting'=>'Đang tuyển sinh','upcoming'=>'Sắp khai giảng','active'=>'Đang hoạt động','paused'=>'Tạm dừng','completed'=>'Đã kết thúc','cancelled'=>'Đã hủy'])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Lớp học</h1>
        <div class="page-subtitle">Chương trình, học phí và miễn giảm riêng, giáo viên và sĩ số lớp.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{route('language-classes.template')}}" data-no-loading download>
            <i class="bi bi-download me-2"></i>Tải mẫu Excel
        </a>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#classImportModal">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>Tạo lớp bằng Excel
        </button>
        <a class="btn btn-primary" href="{{route('language-classes.create')}}">
            <i class="bi bi-plus-circle me-2"></i>Tạo lớp
        </a>
        @if($canViewTrash)
            @if(request('status')==='deleted')
                <a class="btn btn-outline-dark" href="{{route('language-classes.index')}}">
                    <i class="bi bi-arrow-left-circle me-2"></i>Danh sách lớp
                </a>
            @else
                <a class="btn btn-outline-danger" href="{{route('language-classes.index',['status'=>'deleted'])}}">
                    <i class="bi bi-trash3 me-2"></i>Thùng rác
                </a>
            @endif
        @endif
    </div>
</div>

@if(session('class_import_errors'))
    <div class="alert alert-warning">
        <div class="fw-semibold mb-2">File tạo lớp có một số dòng chưa xử lý được:</div>
        <ul class="mb-0">
            @foreach(session('class_import_errors') as $error)
                <li>{{$error}}</li>
            @endforeach
        </ul>
    </div>
@endif

<form class="filter-panel row g-3 mb-4">
    <div class="col-md-7">
        <input class="form-control" name="q" value="{{request('q')}}" placeholder="Tìm mã hoặc tên lớp">
    </div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            @foreach($labels as $k=>$v)
                <option value="{{$k}}" @selected(request('status')===$k)>{{$v}}</option>
            @endforeach
            @if($canViewTrash)
                <option value="deleted" @selected(request('status')==='deleted')>Đã xóa</option>
            @endif
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-dark w-100">Lọc</button>
    </div>
</form>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
            <tr>
                <th>Lớp</th>
                <th>Chương trình</th>
                <th>Học phí / miễn giảm lớp</th>
                <th>Giáo viên</th>
                <th>Khai giảng</th>
                <th>Sĩ số</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $i)
                <tr>
                    <td>
                        <strong>{{$i->name}}</strong>
                        <div class="small text-muted">{{$i->code}} · {{$i->room?:'Chưa xếp phòng'}}</div>
                    </td>
                    <td>
                        {{$i->program->name}}
                        <div class="small text-muted">{{$i->level?->name}}</div>
                    </td>
                    <td>
                        <strong class="text-success">{{number_format($i->default_tuition)}}đ</strong>
                        <div class="small text-muted">{{$i->discountPolicy?->name?($i->discountPolicy->name.' · '.\App\Support\ValueFormatter::percentage($i->discountPolicy->percentage).'%'):'Không miễn giảm'}}</div>
                    </td>
                    <td>{{$i->teacher?->name?:'Chưa phân công'}}</td>
                    <td>{{$i->start_date?->format('d/m/Y')?:'—'}}</td>
                    <td>{{$i->enrollments_count}} / {{$i->max_students}}</td>
                    <td>
                        @if($i->trashed())
                            <span class="badge-soft badge-danger">Đã xóa</span>
                            <div class="small text-muted mt-1">
                                {{$i->deleted_at?->format('d/m/Y H:i')}}
                                @if(($deleteLogs[$i->id] ?? null)?->user)
                                    · {{$deleteLogs[$i->id]->user->name}}
                                @endif
                            </div>
                        @else
                            <span class="badge-soft badge-info">{{$labels[$i->status]??$i->status}}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($i->trashed())
                            <form class="d-inline" method="POST" action="{{route('language-classes.restore',$i->id)}}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success" data-confirm="Khôi phục lớp này?"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </form>
                        @else
                            <a class="btn btn-sm btn-outline-secondary" href="{{route('language-classes.show',$i)}}" title="Xem lớp"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{route('language-classes.edit',$i)}}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{route('language-classes.destroy',$i)}}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa lớp này?"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8"><div class="empty-state">{{request('status')==='deleted'?'Chưa có lớp nào trong thùng rác.':'Chưa có lớp học.'}}</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0">{{$items->links()}}</div>
</div>

<div class="modal fade" id="classImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="POST" action="{{route('language-classes.import')}}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Tạo lớp bằng file Excel</h5>
                        <div class="small text-muted">Tải mẫu, điền dữ liệu rồi nhập một lần nhiều lớp học.</div>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border">
                        <a href="{{route('language-classes.template')}}" class="fw-semibold text-decoration-none" data-no-loading download>
                            <i class="bi bi-download me-1"></i>Tải file mẫu lớp học
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Excel</label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback">{{$message}}</div>@enderror
                    </div>
                    <div class="small text-muted">Hỗ trợ file `.xlsx`, `.xls`, `.csv`, tối đa 10 MB.</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-2"></i>Nhập file</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const shouldOpen=@json((bool) session('open_class_import_modal') || $errors->has('file') || request('open')==='import');
    if(shouldOpen){
        bootstrap.Modal.getOrCreateInstance(document.getElementById('classImportModal')).show();
    }
});
</script>
@endpush
