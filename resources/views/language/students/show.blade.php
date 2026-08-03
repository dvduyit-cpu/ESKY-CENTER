@extends('layouts.app')
@section('title','Hồ sơ '.$item->name)
@section('header','Quản lý học viên')
@section('content')
@php
$studentStatuses=['new'=>'Mới đăng ký','placement_test'=>'Chờ kiểm tra','waiting_class'=>'Chờ xếp lớp','studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'];
$enrollmentStatuses=['studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'];
$scoreTypes=['regular'=>'Thường xuyên','midterm'=>'Giữa kỳ','final'=>'Cuối kỳ','oral'=>'Vấn đáp','other'=>'Khác'];
$allScores=$item->enrollments->flatMap->scores;
$average=$allScores->count()?$allScores->avg(fn($score)=>(float)$score->score/(float)$score->max_score*10):null;
$tuitionPayable=(float)$item->tuitionCharges->sum('payable_amount');
$tuitionPaid=(float)$item->tuitionCharges->sum('paid_amount');
$tuitionCredit=(float)$item->tuitionCharges->sum('credit_amount');
$tuitionRemaining=(float)$item->tuitionCharges->sum(fn($charge)=>$charge->remainingAmount());
$tuitionLabels=['unpaid'=>'Chưa đóng','partial'=>'Đóng một phần','pending_receipt'=>'Chờ phiếu thu','paid'=>'Đã đóng đủ','transferred'=>'Đã quyết toán chuyển lớp'];
$paymentMethods=['cash'=>'Tiền mặt','transfer'=>'Chuyển khoản','card'=>'Thẻ','other'=>'Khác'];
$genderLabels=['male'=>'Nam','female'=>'Nữ','other'=>'Khác'];
$guardianLabels=['father'=>'Cha','mother'=>'Mẹ','guardian'=>'Người giám hộ'];
@endphp
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="page-title">{{$item->code}} – {{$item->name}}</h1><div class="page-subtitle">Hồ sơ cá nhân, lịch sử lớp học, điểm kiểm tra và đánh giá quá trình.</div></div><div class="d-flex gap-2"><a class="btn btn-light" href="{{route('language-students.index')}}"><i class="bi bi-arrow-left me-2"></i>Danh sách</a>@if(auth()->user()->allowed('language_students','update'))<a class="btn btn-primary" href="{{route('language-students.edit',$item)}}"><i class="bi bi-pencil me-2"></i>Chỉnh sửa</a>@endif</div></div>

<div class="row g-3 mb-4"><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Trạng thái</div><div class="fs-5 fw-bold mt-2">{{$studentStatuses[$item->status]??$item->status}}</div></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Số lớp đã học</div><div class="stat-value">{{$item->enrollments->count()}}</div></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Số bài kiểm tra</div><div class="stat-value">{{$allScores->count()}}</div></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Điểm trung bình quy đổi</div><div class="stat-value text-primary">{{$average!==null?number_format($average,1).'/10':'—'}}</div></div></div></div></div>

<div class="card card-soft mb-4">
    <div class="card-header bg-white p-4"><h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Thông tin học viên</h5></div>
    <div class="card-body p-4">
        <div class="row g-3 student-profile-grid">
            <div class="col-md-4"><div class="student-profile-field"><span>Mã học viên</span><strong>{{$item->code}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Họ và tên</span><strong>{{$item->name}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Giới tính</span><strong>{{$genderLabels[$item->gender]??'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Ngày sinh</span><strong>{{$item->date_of_birth?->format('d/m/Y')?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Điện thoại học viên</span><strong>{{$item->phone?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Email học viên</span><strong>{{$item->email?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-8"><div class="student-profile-field"><span>Địa chỉ</span><strong>{{$item->address?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Nguồn tiếp nhận</span><strong>{{$item->source?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Trường đang học</span><strong>{{$item->school?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Lớp tại trường</span><strong>{{$item->school_class?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Khóa học trung tâm</span><strong>{{$item->course?->name?:'Chưa chọn'}}</strong>@if($item->course?->code)<small>{{$item->course->code}}</small>@endif</div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Ngày đăng ký</span><strong>{{$item->registered_at?->format('d/m/Y')?:'Chưa cập nhật'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Ngày nhập học chính thức</span><strong>{{$item->official_enrollment_date?->format('d/m/Y')?:'Chưa nhập học'}}</strong></div></div>
            <div class="col-md-4"><div class="student-profile-field"><span>Đối tượng miễn giảm</span><strong>{{$item->discountPolicy?->name?:'Không miễn giảm'}}</strong>@if($item->discountPolicy)<small>{{$item->discountPolicy->percentage}}%</small>@endif</div></div>
            <div class="col-12"><div class="student-profile-field"><span>Ghi chú hồ sơ</span><strong class="fw-normal">{!!$item->note?nl2br(e($item->note)):'Chưa cập nhật'!!}</strong></div></div>
        </div>

        <hr class="my-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-people-fill me-2 text-primary"></i>Cha, mẹ và người giám hộ</h6>
        @if($item->guardians->isEmpty())
            <div class="alert alert-light border mb-0">Chưa cập nhật thông tin phụ huynh/người giám hộ.</div>
        @else
            <div class="row g-3">
                @foreach($item->guardians->sortByDesc('is_primary') as $guardian)
                    <div class="col-md-6 col-xl-4"><div class="student-guardian-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2"><div><span class="student-guardian-type">{{$guardianLabels[$guardian->relationship]??($guardian->relationship?:'Người giám hộ')}}</span><strong class="d-block mt-1">{{$guardian->name?:'Chưa cập nhật họ tên'}}</strong></div>@if($guardian->is_primary)<span class="badge-soft badge-success">Liên hệ chính</span>@endif</div>
                        <div class="student-guardian-contact"><span><i class="bi bi-telephone"></i>{{$guardian->phone?:'Chưa có số điện thoại'}}</span><span><i class="bi bi-envelope"></i>{{$guardian->email?:'Chưa có email'}}</span>@if($guardian->zalo)<span><i class="bi bi-chat-dots"></i>Zalo: {{$guardian->zalo}}</span>@endif</div>
                    </div></div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="card card-soft mb-4"><div class="card-header bg-white p-4 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="mb-1"><i class="bi bi-cash-coin me-2 text-success"></i>Tình hình đóng học phí</h5><div class="small text-muted">Tự động cập nhật từ các phiếu thu và lịch sử chuyển lớp.</div></div>@if(auth()->user()->allowed('language_tuition','create'))<a class="btn btn-sm btn-outline-success" href="{{route('language-tuition.create',['student'=>$item->id,'course'=>$item->language_course_id])}}"><i class="bi bi-plus-circle me-1"></i>Lập khoản thu</a>@endif</div><div class="card-body p-4"><div class="row g-3"><div class="col-md-3"><div class="tuition-student-summary"><span>Tổng phải đóng</span><strong>{{number_format($tuitionPayable)}}đ</strong></div></div><div class="col-md-3"><div class="tuition-student-summary paid"><span>Đã thu tiền</span><strong>{{number_format($tuitionPaid)}}đ</strong></div></div><div class="col-md-3"><div class="tuition-student-summary paid"><span>Học phí chuyển sang</span><strong>{{number_format($tuitionCredit)}}đ</strong></div></div><div class="col-md-3"><div class="tuition-student-summary {{$tuitionRemaining>0?'due':'complete'}}"><span>Còn phải đóng</span><strong>{{number_format($tuitionRemaining)}}đ</strong></div></div></div>@if($item->tuitionCharges->isEmpty())<div class="alert alert-light border mt-3 mb-0">Học viên chưa được lập khoản thu học phí.</div>@elseif($tuitionRemaining<=0)<div class="alert alert-success mt-3 mb-0"><i class="bi bi-check-circle-fill me-2"></i>Học viên đã hoàn thành toàn bộ học phí đã lập.</div>@endif</div></div>

@if($item->tuitionCharges->isNotEmpty())
<div class="card card-soft mb-4"><div class="card-header bg-white p-4"><h5 class="mb-1">Chi tiết khoản thu và các lần đóng tiền</h5><div class="small text-muted">Bao gồm cả khoản thu chưa gắn lớp.</div></div><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Khoản thu</th><th>Khóa học / lớp</th><th>Phải đóng</th><th>Đã đóng</th><th>Còn lại</th><th>Trạng thái</th><th>Lần đóng gần nhất</th><th></th></tr></thead><tbody>@foreach($item->tuitionCharges as $charge)@php($remaining=$charge->remainingAmount())@php($latestPayment=$charge->payments->sortByDesc('paid_at')->first())<tr><td><strong>{{$charge->code}}</strong><div class="small text-muted">{{$charge->created_at?->format('d/m/Y')}}</div></td><td>{{$charge->course?->name}}<div class="small text-muted">{{$charge->languageClass?->code?:'Chưa gắn lớp'}}</div></td><td>{{number_format($charge->payable_amount)}}đ</td><td class="fw-bold text-success">{{number_format($charge->paid_amount)}}đ @if((float)$charge->credit_amount>0)<div class="small text-primary">Chuyển sang {{number_format($charge->credit_amount)}}đ</div>@endif</td><td class="fw-bold {{$remaining>0?'text-danger':'text-muted'}}">{{number_format($remaining)}}đ</td><td><span class="badge-soft {{in_array($charge->status,['paid','transferred'])?'badge-success':(in_array($charge->status,['partial','pending_receipt'])?'badge-warning':'badge-danger')}}">{{$tuitionLabels[$charge->status]??$charge->status}}</span></td><td>@if($latestPayment){{$latestPayment->paid_at?->format('d/m/Y')}}<div class="small text-muted">{{number_format((float)$latestPayment->amount+(float)$latestPayment->book_amount)}}đ · {{$paymentMethods[$latestPayment->payment_method]??$latestPayment->payment_method}}</div>@else<span class="text-muted">Chưa đóng</span>@endif</td><td>@if(auth()->user()->allowed('language_tuition'))<a class="btn btn-sm btn-outline-success" href="{{route('language-tuition.show',$charge)}}"><i class="bi bi-eye"></i></a>@endif</td></tr>@endforeach</tbody></table></div></div>
@endif

@if($item->classTransfers->isNotEmpty())
<div class="card card-soft mb-4"><div class="card-header bg-white p-4"><h5 class="mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Lịch sử chuyển lớp</h5><div class="small text-muted">Theo dõi học phí đã sử dụng, chuyển sang và số dư chờ xử lý.</div></div><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Ngày</th><th>Từ lớp</th><th>Sang lớp</th><th>Số tiết đã học</th><th>Đã sử dụng</th><th>Chuyển sang</th><th>Số dư</th><th>Người thực hiện</th></tr></thead><tbody>@foreach($item->classTransfers as $transfer)<tr><td>{{$transfer->effective_date->format('d/m/Y')}}</td><td><strong>{{$transfer->fromClass?->code}}</strong></td><td><strong>{{$transfer->toClass?->code}}</strong></td><td>{{$transfer->sessions_used}}</td><td>{{number_format($transfer->used_amount)}}đ</td><td class="fw-bold text-primary">{{number_format($transfer->applied_amount)}}đ</td><td class="fw-bold {{(float)$transfer->surplus_amount>0?'text-warning':'text-muted'}}">{{number_format($transfer->surplus_amount)}}đ</td><td>{{$transfer->creator?->name?:'—'}}</td></tr>@endforeach</tbody></table></div></div>
@endif

<div class="d-flex justify-content-between align-items-end mb-3"><div><h4 class="mb-1">Các lớp đã tham gia</h4><div class="text-muted">Chọn một thẻ lớp để xem học phí, điểm và đánh giá chi tiết.</div></div></div>
@if($item->enrollments->isEmpty())
<div class="card card-soft"><div class="empty-state">Học viên chưa có lịch sử lớp học.</div></div>
@else
<div class="row g-3 mb-4 student-class-grid">
@foreach($item->enrollments as $enrollment)
@php($class=$enrollment->languageClass)
@php($classAverage=$enrollment->scores->count()?$enrollment->scores->avg(fn($score)=>(float)$score->score/(float)$score->max_score*10):null)
@php($classCharges=$item->tuitionCharges->where('language_class_id',$class?->id))
@php($classDue=(float)$classCharges->sum(fn($charge)=>$charge->remainingAmount()))
<div class="col-sm-6 col-lg-4 col-xxl-3"><button class="student-class-tile" type="button" data-bs-toggle="modal" data-bs-target="#student-class-detail-{{$enrollment->id}}" aria-label="Xem chi tiết lớp {{$class?->code}}"><span class="student-class-icon"><i class="bi bi-easel2-fill"></i></span><span class="student-class-code">{{$class?->code?:'Lớp không còn hoạt động'}}</span><strong>{{$class?->name?:'Thông tin lớp cũ'}}</strong><small>{{$class?->program?->name}} · {{$class?->level?->name}}</small><span class="student-class-meta"><span class="badge-soft {{$enrollment->status==='studying'?'badge-success':'badge-gray'}}">{{$enrollmentStatuses[$enrollment->status]??$enrollment->status}}</span><span>{{$enrollment->scores->count()}} bài · {{$classAverage!==null?'TB '.number_format($classAverage,1):'Chưa có điểm'}}</span></span><span class="student-class-tuition {{$classCharges->isNotEmpty()&&$classDue<=0?'is-paid':'is-due'}}"><i class="bi bi-cash-coin"></i> {{$classCharges->isEmpty()?'Chưa lập học phí':($classDue<=0?'Đã đóng đủ':'Còn '.number_format($classDue).'đ')}}</span><span class="student-class-period">{{$enrollment->enrolled_at?->format('d/m/Y')}} – {{$enrollment->ended_at?->format('d/m/Y')?:'Hiện tại'}}</span><span class="student-class-open"><i class="bi bi-arrows-fullscreen"></i> Xem chi tiết</span></button></div>
@endforeach
</div>

<div class="student-class-details">
@foreach($item->enrollments as $enrollment)
@php($class=$enrollment->languageClass)
@php($classCharges=$item->tuitionCharges->where('language_class_id',$class?->id))
<div class="modal fade student-class-modal" id="student-class-detail-{{$enrollment->id}}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0 rounded-4"><div class="modal-header p-4"><div><h5 class="modal-title mb-1">{{$class?->code}} – {{$class?->name}}</h5><div class="text-muted">{{$class?->program?->name}} · {{$class?->level?->name}} · GV: {{$class?->teacher?->name?:'Chưa phân công'}}</div></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
<div class="modal-body p-4"><h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-success"></i>Học phí của lớp này</h6><div class="table-responsive mb-4"><table class="table table-modern"><thead><tr><th>Mã khoản thu</th><th>Phải đóng</th><th>Đã đóng</th><th>Còn lại</th><th>Trạng thái</th><th></th></tr></thead><tbody>@forelse($classCharges as $charge)@php($remaining=$charge->remainingAmount())<tr><td><strong>{{$charge->code}}</strong><div class="small text-muted">{{$charge->course?->name}}</div></td><td>{{number_format($charge->payable_amount)}}đ</td><td class="fw-bold text-success">{{number_format($charge->paid_amount)}}đ @if((float)$charge->credit_amount>0)<div class="small text-primary">Chuyển sang {{number_format($charge->credit_amount)}}đ</div>@endif</td><td class="fw-bold {{$remaining>0?'text-danger':'text-muted'}}">{{number_format($remaining)}}đ</td><td><span class="badge-soft {{in_array($charge->status,['paid','transferred'])?'badge-success':(in_array($charge->status,['partial','pending_receipt'])?'badge-warning':'badge-danger')}}">{{$tuitionLabels[$charge->status]??$charge->status}}</span></td><td>@if(auth()->user()->allowed('language_tuition'))<a class="btn btn-sm btn-outline-success" href="{{route('language-tuition.show',$charge)}}"><i class="bi bi-eye me-1"></i>Phiếu thu</a>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Chưa có khoản thu gắn với lớp này.</td></tr>@endforelse</tbody></table></div>
@if($classCharges->isNotEmpty())<h6 class="fw-bold mb-3">Lịch sử các lần đóng tiền</h6><div class="table-responsive mb-4"><table class="table table-modern"><thead><tr><th>Ngày đóng</th><th>Số phiếu</th><th>Số tiền</th><th>Hình thức</th><th>Người thu</th><th>Trạng thái phiếu</th></tr></thead><tbody>@forelse($classCharges->flatMap->payments->sortByDesc('paid_at') as $payment)<tr><td>{{$payment->paid_at?->format('d/m/Y H:i')}}</td><td>{{$payment->receipt_code?:'Chưa bổ sung'}}</td><td><strong>{{number_format((float)$payment->amount+(float)$payment->book_amount)}}đ</strong></td><td>{{$paymentMethods[$payment->payment_method]??$payment->payment_method}}</td><td>{{$payment->collector?->name?:'—'}}</td><td>{{$payment->receipt_status==='confirmed'?'Đã xác nhận':'Chờ phiếu'}}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Chưa có lần đóng tiền.</td></tr>@endforelse</tbody></table></div>@endif
<h6 class="fw-bold mb-3">Điểm kiểm tra của học viên trong lớp này</h6><div class="table-responsive mb-4"><table class="table table-modern"><thead><tr><th>Ngày</th><th>Bài kiểm tra</th><th>Loại</th><th>Điểm</th><th>Quy đổi /10</th><th>Giáo viên</th><th>Ghi chú</th></tr></thead><tbody>@forelse($enrollment->scores as $score)<tr><td>{{$score->test_date->format('d/m/Y')}}</td><td><strong>{{$score->test_name}}</strong></td><td>{{$scoreTypes[$score->test_type]??$score->test_type}}</td><td>{{$score->score}}/{{$score->max_score}}</td><td class="fw-bold text-primary">{{number_format((float)$score->score/(float)$score->max_score*10,1)}}</td><td>{{$score->teacher?->name?:'—'}}</td><td>{{$score->note?:'—'}}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">Học viên chưa có điểm kiểm tra trong lớp này.</td></tr>@endforelse</tbody></table></div>
<h6 class="fw-bold mb-3">Đánh giá quá trình theo tháng</h6><div class="table-responsive"><table class="table table-modern"><thead><tr><th>Tháng</th><th>Chuyên cần</th><th>Tham gia</th><th>Bài tập</th><th>Đánh giá</th><th>Ghi chú</th><th>Giáo viên</th></tr></thead><tbody>@forelse($enrollment->monthlyProgress as $progress)<tr><td><strong>{{$progress->month->format('m/Y')}}</strong></td><td>{{$progress->attended_sessions}}/{{$progress->planned_sessions}} buổi</td><td>{{$progress->participation_score!==null?$progress->participation_score.'/10':'—'}}</td><td>{{$progress->homework_score!==null?$progress->homework_score.'/10':'—'}}</td><td>{{$progress->assessment?:'—'}}</td><td>{{$progress->learning_note?:'—'}}</td><td>{{$progress->teacher?->name?:'—'}}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">Chưa có đánh giá quá trình theo tháng.</td></tr>@endforelse</tbody></table></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button></div></div></div></div>
@endforeach
</div>
@endif
@endsection
