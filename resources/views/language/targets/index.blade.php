@extends('layouts.app')

@section('title','Đối chiếu chỉ tiêu CTV')
@section('header','Trung tâm ngoại ngữ')

@section('content')
@php($periodLabel=$filters['period']==='quarter' ? 'Quý '.$filters['quarter'].'/'.$filters['year'] : 'Tháng '.$filters['month'].'/'.$filters['year'])
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><h1 class="page-title">Đối chiếu chỉ tiêu cộng tác viên</h1><div class="page-subtitle">Chỉ ghi nhận học viên do CTV giới thiệu khi hồ sơ đã đăng ký học và học phí đã đóng đủ, phiếu thu đã xác nhận.</div></div>
    <a class="btn btn-outline-success" href="{{route('language-targets.export',$filters)}}"><i class="bi bi-file-earmark-excel me-2"></i>Xuất Excel</a>
</div>

<form class="filter-panel row g-3 mb-4" data-target-period-filter>
    <div class="col-md-2"><label class="form-label">Năm</label><input class="form-control" type="number" name="year" min="2020" max="2100" value="{{$filters['year']}}"></div>
    <div class="col-md-2"><label class="form-label">Kỳ đối chiếu</label><select class="form-select" name="period" data-period-type><option value="month" @selected($filters['period']==='month')>Theo tháng</option><option value="quarter" @selected($filters['period']==='quarter')>Theo quý</option></select></div>
    <div class="col-md-2" data-month-field><label class="form-label">Tháng</label><select class="form-select" name="month">@for($month=1;$month<=12;$month++)<option value="{{$month}}" @selected($filters['month']===$month)>Tháng {{$month}}</option>@endfor</select></div>
    <div class="col-md-2" data-quarter-field><label class="form-label">Quý</label><select class="form-select" name="quarter">@for($quarter=1;$quarter<=4;$quarter++)<option value="{{$quarter}}" @selected($filters['quarter']===$quarter)>Quý {{$quarter}}</option>@endfor</select></div>
    <div class="col-md-3"><label class="form-label">Cộng tác viên</label><input class="form-control" name="collaborator" value="{{$filters['collaborator']}}" placeholder="Tên, mã hoặc điện thoại CTV"></div>
    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100" title="Xem đối chiếu"><i class="bi bi-funnel"></i></button></div>
</form>

<div class="alert alert-light border mb-4"><i class="bi bi-shield-check text-success me-2"></i><strong>Điều kiện ghi nhận:</strong> khách hàng có CTV giới thiệu, trạng thái <strong>Đã đăng ký</strong>, khoản thu <strong>đã đóng đủ</strong> và phiếu thu <strong>đã xác nhận</strong>.</div>

<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Lượt đăng ký đủ điều kiện · {{$periodLabel}}</div><div class="stat-value">{{number_format($totalQuantity,0)}}</div></div></div></div>
    <div class="col-md-6"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Doanh thu đối chiếu · {{$periodLabel}}</div><div class="stat-value text-success">{{number_format($totalRevenue)}}đ</div></div></div></div>
</div>

<div class="card card-soft"><div class="card-header bg-white p-4 pb-0"><h5 class="mb-1">Chi tiết đối chiếu · {{$periodLabel}}</h5><div class="small text-muted">Dùng mã phiếu thu để đối chiếu với chứng từ thu học phí.</div></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Ngày thanh toán</th><th>Phiếu thu</th><th>Học viên</th><th>Khách hàng</th><th>CTV</th><th>Khóa học</th><th>Số lượng</th><th>Doanh thu</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{$item->payment?->paid_at?->format('d/m/Y')}}</td><td><strong>{{$item->payment?->receipt_code}}</strong></td><td><strong>{{$item->student?->name}}</strong><div class="small text-muted">{{$item->student?->code}}</div></td><td>{{$item->lead?->name?:'—'}}<div class="small text-muted">{{$item->lead?->code}}</div></td><td><strong>{{$item->collaborator?->name}}</strong><div class="small text-muted">{{$item->collaborator?->code}}</div></td><td>{{$item->course?->name}}</td><td>{{$item->quantity}}</td><td class="fw-bold text-success">{{number_format($item->revenue)}}đ</td></tr>@empty<tr><td colspan="8"><div class="empty-state">Không có chỉ tiêu CTV đủ điều kiện trong kỳ đã chọn.</div></td></tr>@endforelse</tbody></table></div></div><div class="card-footer bg-white border-0 p-3">{{$items->links()}}</div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const period=document.querySelector('[data-period-type]');
    const month=document.querySelector('[data-month-field]');
    const quarter=document.querySelector('[data-quarter-field]');
    const sync=()=>{
        const isQuarter=period?.value==='quarter';
        month?.classList.toggle('d-none',isQuarter);
        quarter?.classList.toggle('d-none',!isQuarter);
    };
    period?.addEventListener('change',sync);
    sync();
});
</script>
@endpush
