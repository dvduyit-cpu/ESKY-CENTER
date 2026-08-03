@extends('layouts.app') @section('title','Cộng tác viên') @section('header','Trung tâm ngoại ngữ') @section('content')
<div class="d-flex justify-content-between gap-3 mb-4"><div><h1 class="page-title">Cộng tác viên trung tâm</h1><div class="page-subtitle">Quản lý nguồn giới thiệu học viên và tỷ lệ hoa hồng.</div></div><div class="d-flex gap-2">@if(auth()->user()->allowed('language_collaborators','export'))<a class="btn btn-outline-success" href="{{route('language-collaborators.export')}}"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a>@endif<a class="btn btn-primary" href="{{route('language-collaborators.create')}}"><i class="bi bi-plus-circle me-2"></i>Thêm CTV</a></div></div>
<form class="filter-panel row g-2 align-items-end mb-4" data-date-period-filter>
    <div class="col-lg-4 col-md-12"><label class="form-label">Tìm cộng tác viên</label><input class="form-control form-control-sm" name="q" value="{{request('q')}}" placeholder="Tên hoặc điện thoại"></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Ngày</label><input class="form-control form-control-sm" type="date" name="date" value="{{request('date')}}"></div>
    <div class="col-lg-2 col-md-3"><label class="form-label">Tháng</label><select class="form-select form-select-sm" name="month"><option value="">Tất cả</option>@for($month=1;$month<=12;$month++)<option value="{{$month}}" @selected((int)request('month')===$month)>Tháng {{$month}}</option>@endfor</select></div>
    <div class="col-lg-2 col-md-3"><label class="form-label">Năm</label><select class="form-select form-select-sm" name="year"><option value="">Tất cả</option>
        @foreach($years as $year)<option value="{{$year}}" @selected((int)request('year')===(int)$year)>{{$year}}</option>@endforeach
        @if(!$years->contains((int)now()->year))<option value="{{now()->year}}" @selected((int)request('year')===now()->year)>{{now()->year}}</option>@endif
    </select></div>
    <div class="col-lg-2 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-dark flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>@if($hasPeriodFilter||request()->filled('q'))<a class="btn btn-sm btn-light" href="{{route('language-collaborators.index')}}" title="Xóa lọc"><i class="bi bi-arrow-counterclockwise"></i></a>@endif</div>
</form>
<div class="card card-soft"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>CTV</th><th>Liên hệ</th><th>Hoa hồng</th><th>Tổng số học viên giới thiệu</th><th>Trạng thái</th><th></th></tr></thead><tbody>@forelse($items as $i)<tr><td><strong>{{$i->name}}</strong><div class="small text-muted">{{$i->code}}</div></td><td>{{$i->phone?:'—'}}<div class="small text-muted">{{$i->email}}</div></td><td>{{$i->commission_rate}}%</td><td>{{$i->referred_students_count}}</td><td><span class="badge-soft {{$i->active?'badge-success':'badge-gray'}}">{{$i->active?'Hoạt động':'Ngừng'}}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{route('language-collaborators.edit',$i)}}"><i class="bi bi-pencil"></i></a><form class="d-inline" method="POST" action="{{route('language-collaborators.destroy',$i)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa CTV này?"><i class="bi bi-trash"></i></button></form></td></tr>@empty<tr><td colspan="6"><div class="empty-state">Chưa có cộng tác viên.</div></td></tr>@endforelse</tbody></table></div>{{$items->links()}}</div>@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-date-period-filter]').forEach(form=>{
    const date=form.querySelector('[name="date"]');
    const month=form.querySelector('[name="month"]');
    const year=form.querySelector('[name="year"]');
    date?.addEventListener('change',()=>{if(date.value){month.value='';year.value='';}});
    [month,year].forEach(field=>field?.addEventListener('change',()=>{if(field.value)date.value='';}));
});
</script>
@endpush
