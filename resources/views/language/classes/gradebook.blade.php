@extends('layouts.app')
@section('title','Sổ điểm '.$languageClass->code)
@section('header','Quản lý giảng dạy')

@section('content')
@php
$statusLabels=['studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'];
$scoreTypes=['regular'=>'Thường xuyên','midterm'=>'Giữa kỳ','final'=>'Cuối kỳ','oral'=>'Vấn đáp','other'=>'Khác'];
$activeCount=$languageClass->enrollments->where('status','studying')->count();
$monthScores=$languageClass->enrollments->flatMap->scores;
$monthAverage=$monthScores->count()?$monthScores->avg(fn($score)=>(float)$score->score/(float)$score->max_score*10):null;
@endphp

@unless(auth()->user()->allowed('teacher_classes','update'))
<div class="alert alert-info"><i class="bi bi-eye me-2"></i>Bạn đang xem sổ điểm ở chế độ chỉ đọc.</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.content form[method="POST"]').forEach(form=>{
        form.querySelectorAll('input,select,textarea,button').forEach(control=>control.disabled=true);
        form.querySelectorAll('button').forEach(button=>button.classList.add('d-none'));
    });
});
</script>
@endpush
@endunless

<div class="card card-soft mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <h5><i class="bi bi-clipboard-check me-2"></i>Hoàn thành và đóng lớp</h5>
                @if($languageClass->completion_requested_at)
                    <p class="mb-0">Giáo viên đã gửi đề nghị lúc <strong>{{$languageClass->completion_requested_at->format('H:i d/m/Y')}}</strong>. {{$languageClass->completion_note}}</p>
                @else
                    <p class="mb-0 text-muted">Giáo viên gửi đề nghị khi đủ số buổi hoặc đến ngày kết thúc; giáo vụ kiểm tra học phí rồi mới đóng lớp.</p>
                @endif
            </div>
            <div>
                @if($languageClass->status!=='completed'&&!$languageClass->completion_requested_at)
                    <form method="POST" action="{{route('teacher-classes.completion.request',$languageClass)}}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" {{$languageClass->isCompletionDue()?'':'disabled'}}><i class="bi bi-send-check me-2"></i>Đề nghị hoàn thành</button>
                    </form>
                @elseif($languageClass->status!=='completed'&&auth()->user()->isRegistrar())
                    <form method="POST" action="{{route('teacher-classes.close',$languageClass)}}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success" {{$tuitionCheck['ready']?'':'disabled'}} data-confirm="Xác nhận đã kiểm tra học phí và đóng lớp?"><i class="bi bi-lock-fill me-2"></i>Giáo vụ đóng lớp</button>
                    </form>
                @elseif($languageClass->completion_requested_at)
                    <span class="badge-soft badge-warning">Đang chờ giáo vụ kiểm tra</span>
                @endif
            </div>
        </div>
        @if($languageClass->completion_requested_at&&!$tuitionCheck['ready'])
            <div class="alert alert-warning mt-3 mb-0">
                <strong>Chưa thể đóng lớp vì học phí:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($tuitionCheck['blockers'] as $blocker)
                        <li>{{$blocker['student']}}: {{$blocker['reason']}}</li>
                    @endforeach
                </ul>
            </div>
        @elseif($languageClass->completion_requested_at&&$tuitionCheck['ready'])
            <div class="alert alert-success mt-3 mb-0">Đã kiểm tra {{$tuitionCheck['total']}} học viên: học phí đầy đủ, có thể đóng lớp.</div>
        @endif
    </div>
</div>

<div class="gradebook-hero mb-4">
    <div>
        <a class="gradebook-back" href="{{route('teacher-classes.index')}}"><i class="bi bi-arrow-left"></i> Lớp giảng dạy</a>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
            <span class="gradebook-class-icon"><i class="bi bi-easel2-fill"></i></span>
            <div>
                <h1>{{$languageClass->code}} – {{$languageClass->name}}</h1>
                <p>{{$languageClass->program?->name}} · {{$languageClass->level?->name}} · {{$languageClass->schedule_note?:'Chưa có lịch học'}}</p>
            </div>
        </div>
    </div>
    <div class="gradebook-teacher"><span>Giáo viên phụ trách</span><strong>{{$languageClass->teacher?->name?:'Chưa phân công'}}</strong></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="gradebook-stat"><i class="bi bi-people-fill text-primary"></i><div><span>Đang học</span><strong>{{$activeCount}}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="gradebook-stat"><i class="bi bi-journal-check text-success"></i><div><span>Bài kiểm tra tháng</span><strong>{{$monthScores->count()}}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="gradebook-stat"><i class="bi bi-star-fill text-warning"></i><div><span>Điểm TB tháng</span><strong>{{$monthAverage!==null?number_format($monthAverage,1):'—'}}</strong></div></div></div>
    <div class="col-6 col-xl-3"><div class="gradebook-stat"><i class="bi bi-calendar3 text-info"></i><div><span>Tháng đang xem</span><strong>{{$month->format('m/Y')}}</strong></div></div></div>
</div>

<div class="card card-soft gradebook-toolbar mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-3">
                <form class="gradebook-toolbar-control">
                    <div><label class="form-label fw-semibold">Tháng theo dõi</label><input class="form-control" type="month" name="month" value="{{$month->format('Y-m')}}"></div>
                    <button class="btn btn-primary" title="Đổi tháng" aria-label="Đổi tháng"><i class="bi bi-arrow-repeat"></i></button>
                </form>
            </div>
            <div class="col-lg-3">
                <form class="gradebook-toolbar-control" method="POST" action="{{route('teacher-classes.sessions.update',$languageClass)}}">
                    @csrf
                    @method('PATCH')
                    <div><label class="form-label fw-semibold">Số buổi đã học</label><input class="form-control" type="number" name="completed_sessions" min="0" value="{{$languageClass->completed_sessions}}" required></div>
                    <button class="btn btn-outline-success" title="Cập nhật số buổi" aria-label="Cập nhật số buổi"><i class="bi bi-check2"></i></button>
                </form>
            </div>
            @if(auth()->user()->isRegistrar())
                <div class="col-lg-6 gradebook-toolbar-add">
                    <label class="form-label fw-semibold">Giáo vụ xếp thêm học viên</label>
                    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#gradebookEnrollmentModal"><i class="bi bi-people me-2"></i>Tìm và thêm một hoặc nhiều học viên</button>
                </div>
            @else
                <div class="col-lg-6"><div class="alert alert-light border mb-0"><i class="bi bi-shield-lock me-2"></i>Chỉ giáo vụ được xếp thêm học viên và tạo khoản thu học phí.</div></div>
            @endif
        </div>
        <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>Lớp tự kết thúc khi đạt {{$languageClass->expected_sessions}} buổi hoặc qua ngày {{$languageClass->expected_end_date?->format('d/m/Y')?:'chưa thiết lập'}}.</div>
    </div>
</div>

@if(session('class_enrollment_import_errors'))
<div class="alert alert-warning mb-4">
    <div class="fw-semibold mb-2">File thêm học viên có một số dòng chưa nhập được:</div>
    <ul class="mb-0">
        @foreach(session('class_enrollment_import_errors') as $error)
            <li>{{$error}}</li>
        @endforeach
    </ul>
</div>
@endif

@if(auth()->user()->isRegistrar())
    @include('language.classes._enroll-modal',[
        'modalId'=>'gradebookEnrollmentModal',
        'enrollmentAction'=>route('teacher-classes.enrollments.store',$languageClass),
        'enrollmentImportAction'=>route('teacher-classes.enrollments.import',$languageClass),
        'enrollmentTemplateUrl'=>route('teacher-classes.enrollments.template',$languageClass),
        'students'=>$availableStudents
    ])
@endif

@include('language.classes._attendance-lesson-book')

<section>
    <div class="gradebook-section-heading">
        <div><span>02</span><div><h4>Điểm kiểm tra và trạng thái học</h4><p>Hiển thị danh sách học viên trong lớp; bấm nút để mở modal nhập điểm hoặc cập nhật trạng thái học.</p></div></div>
    </div>

    <div class="card card-soft">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="small text-muted">Tên bài kiểm tra và nhận xét có thể nhập tự do theo từng lần đánh giá.</div>
                <div class="small text-muted">Tháng đang xem: <strong>{{$month->format('m/Y')}}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>Học viên</th>
                            <th>Trạng thái</th>
                            <th>Điểm tháng</th>
                            <th>Nhận xét nhanh</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($languageClass->enrollments as $enrollment)
                        @php($studentScores=$enrollment->scores)
                        @php($studentAverage=$studentScores->count()?$studentScores->avg(fn($score)=>(float)$score->score/(float)$score->max_score*10):null)
                        @php($latestScoreNote=$studentScores->sortByDesc('test_date')->first()?->note)
                        <tr>
                            <td>
                                <strong>{{$enrollment->student?->name}}</strong>
                                <div class="small text-muted">{{$enrollment->student?->code}} · Vào lớp {{$enrollment->enrolled_at?->format('d/m/Y')}}</div>
                            </td>
                            <td><span class="badge-soft badge-info">{{$statusLabels[$enrollment->status]??$enrollment->status}}</span></td>
                            <td>
                                <strong>{{$studentAverage!==null?number_format($studentAverage,1).'/10':'Chưa có điểm'}}</strong>
                                <div class="small text-muted">{{$studentScores->count()}} bài trong tháng</div>
                            </td>
                            <td class="text-muted">{{\Illuminate\Support\Str::limit($latestScoreNote?:'Chưa có nhận xét',70)}}</td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#scoreModal{{$enrollment->id}}">
                                    <i class="bi bi-journal-plus me-1"></i>Nhập điểm
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#statusModal{{$enrollment->id}}">
                                    <i class="bi bi-person-check me-1"></i>Trạng thái học
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Lớp chưa có học viên.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@foreach($languageClass->enrollments as $enrollment)
    @php($studentScores=$enrollment->scores)
    @php($studentAverage=$studentScores->count()?$studentScores->avg(fn($score)=>(float)$score->score/(float)$score->max_score*10):null)
    @php($scoreModalTarget=old('gradebook_modal')==='score' && (int) old('modal_enrollment_id')===$enrollment->id)
    @php($statusModalTarget=old('gradebook_modal')==='status' && (int) old('modal_enrollment_id')===$enrollment->id)

    <div class="modal fade" id="scoreModal{{$enrollment->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><i class="bi bi-journal-plus text-primary me-2"></i>Điểm kiểm tra · {{$enrollment->student?->name}}</h5>
                        <div class="small text-muted">{{$enrollment->student?->code}} · Tự tùy biến tên bài kiểm tra và nhận xét cho từng lần nhập.</div>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-xl-8">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <h6 class="mb-0 fw-bold">Danh sách điểm tháng {{$month->format('m/Y')}}</h6>
                                <span class="badge-soft badge-info">{{$studentScores->count()}} bài · {{$studentAverage!==null?number_format($studentAverage,1).'/10':'Chưa có điểm'}}</span>
                            </div>
                            <div class="table-responsive border rounded-3">
                                <table class="table table-modern mb-0">
                                    <thead><tr><th>Ngày</th><th>Bài kiểm tra</th><th>Loại</th><th>Điểm</th><th>Quy đổi</th><th></th></tr></thead>
                                    <tbody>
                                    @forelse($studentScores as $score)
                                        <tr>
                                            <td>{{$score->test_date->format('d/m/Y')}}</td>
                                            <td><strong>{{$score->test_name}}</strong><div class="small text-muted">{{$score->note?:'Không có nhận xét'}}</div></td>
                                            <td>{{$scoreTypes[$score->test_type]??$score->test_type}}</td>
                                            <td>{{$score->score}}/{{$score->max_score}}</td>
                                            <td class="fw-bold text-primary">{{number_format((float)$score->score/(float)$score->max_score*10,1)}}/10</td>
                                            <td>
                                                <form method="POST" action="{{route('teacher-classes.scores.destroy',[$languageClass,$enrollment,$score])}}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa điểm kiểm tra này?"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6"><div class="empty-state py-4">Chưa có điểm trong tháng này.</div></td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="gradebook-score-form">
                                <h6 class="gradebook-subtitle">Thêm điểm kiểm tra mới</h6>
                                <form method="POST" action="{{route('teacher-classes.scores.store',[$languageClass,$enrollment])}}">
                                    @csrf
                                    <input type="hidden" name="gradebook_modal" value="score">
                                    <input type="hidden" name="modal_enrollment_id" value="{{$enrollment->id}}">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label">Ngày</label>
                                            <input class="form-control" type="date" name="test_date" value="{{$scoreModalTarget?old('test_date'):now()->format('Y-m-d')}}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Loại</label>
                                            <select class="form-select" name="test_type">
                                                @foreach($scoreTypes as $key=>$label)
                                                    <option value="{{$key}}" @selected(($scoreModalTarget?old('test_type','regular'):'regular')===$key)>{{$label}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Tên bài kiểm tra</label>
                                            <input class="form-control" name="test_name" value="{{$scoreModalTarget?old('test_name'):''}}" required placeholder="Ví dụ: Kiểm tra từ vựng, Bài nói cuối tháng...">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Điểm</label>
                                            <input class="form-control" type="number" step="0.01" min="0" name="score" value="{{$scoreModalTarget?old('score'):''}}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Điểm tối đa</label>
                                            <input class="form-control" type="number" step="0.01" min="0.01" name="max_score" value="{{$scoreModalTarget?old('max_score',10):10}}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Nhận xét</label>
                                            <textarea class="form-control" name="note" rows="4" placeholder="Nhận xét tự do về kết quả, thế mạnh, nội dung cần ôn thêm...">{{$scoreModalTarget?old('note'):''}}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-2"></i>Lưu điểm kiểm tra</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusModal{{$enrollment->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <form method="POST" action="{{route('teacher-classes.enrollments.status',[$languageClass,$enrollment])}}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="gradebook_modal" value="status">
                    <input type="hidden" name="modal_enrollment_id" value="{{$enrollment->id}}">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title"><i class="bi bi-person-check text-secondary me-2"></i>Trạng thái học · {{$enrollment->student?->name}}</h5>
                            <div class="small text-muted">Cập nhật trạng thái và ghi nhận nhận xét hoặc lý do nếu học viên tạm nghỉ, bảo lưu hoặc thôi học.</div>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Trạng thái học</label>
                                <select class="form-select" name="status">
                                    @foreach($statusLabels as $key=>$label)
                                        <option value="{{$key}}" @selected(($statusModalTarget?old('status',$enrollment->status):$enrollment->status)===$key)>{{$label}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày kết thúc / tạm dừng</label>
                                <input class="form-control" type="date" name="ended_at" value="{{$statusModalTarget?old('ended_at',$enrollment->ended_at?->format('Y-m-d')):$enrollment->ended_at?->format('Y-m-d')}}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Điểm trung bình tháng</label>
                                <input class="form-control" value="{{$studentAverage!==null?number_format($studentAverage,1).'/10':'Chưa có điểm'}}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nhận xét / lý do</label>
                                <textarea class="form-control" name="exit_reason" rows="4" maxlength="255" placeholder="Ví dụ: Bảo lưu 1 tháng theo yêu cầu phụ huynh, cần bổ sung củng cố từ vựng...">{{$statusModalTarget?old('exit_reason',$enrollment->exit_reason):$enrollment->exit_reason}}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button>
                        <button class="btn btn-outline-secondary"><i class="bi bi-check2 me-1"></i>Cập nhật trạng thái</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const requestedModal=@json(old('gradebook_modal')==='score' ? 'scoreModal'.old('modal_enrollment_id') : (old('gradebook_modal')==='status' ? 'statusModal'.old('modal_enrollment_id') : null));
    if(requestedModal){
        bootstrap.Modal.getOrCreateInstance(document.getElementById(requestedModal)).show();
    }
});
</script>
@endpush
