@php
    $attendanceStatuses = ['present'=>'Có mặt','late'=>'Đi trễ','excused'=>'Vắng có phép','absent'=>'Vắng'];
    $attendanceColors = ['present'=>'success','late'=>'warning','excused'=>'info','absent'=>'danger'];
    $attendanceEnrollments = $languageClass->enrollments->whereIn('status', ['studying','paused','reserved']);
    $attendanceRows = $selectedLesson?->attendances?->keyBy('language_enrollment_id') ?? collect();
    $defaultLessonDate = $selectedLesson?->lesson_date?->format('Y-m-d')
        ?? ($month->isSameMonth(now()) ? now()->format('Y-m-d') : $month->copy()->endOfMonth()->format('Y-m-d'));
    $defaultStartTime = $selectedLesson ? substr((string) $selectedLesson->start_time, 0, 5) : '18:00';
    $defaultEndTime = $selectedLesson ? substr((string) $selectedLesson->end_time, 0, 5) : '19:30';
@endphp

<section class="mb-4">
    <div class="gradebook-section-heading"><div><span>01</span><div><h4>Theo dõi buổi học tháng {{$month->format('m/Y')}}</h4><p>Điểm danh và sổ đầu bài được lưu chung theo đúng ngày, giờ của từng buổi học.</p></div></div></div>
    <div class="card card-soft lesson-action-panel">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="small text-muted"><i class="bi bi-file-earmark-text me-1"></i>Bản in gồm danh sách điểm danh và sổ đầu bài của toàn bộ khóa.</div>
                <a class="btn btn-outline-primary" href="{{route('teacher-classes.lesson-book.print',$languageClass)}}" target="_blank"><i class="bi bi-printer me-2"></i>In sổ lớp A4 ngang</a>
            </div>
            @if($selectedLesson)
                <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Đang chỉnh sửa buổi <strong>{{$selectedLesson->lesson_date->format('d/m/Y')}}</strong>, {{$defaultStartTime}}–{{$defaultEndTime}}.</span>
                    <a class="btn btn-sm btn-outline-primary" href="{{route('teacher-classes.gradebook',[$languageClass,'month'=>$month->format('Y-m')])}}">Tạo buổi mới</a>
                </div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <button class="lesson-action-button attendance" type="button" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                        <span class="lesson-action-icon"><i class="bi bi-person-check-fill"></i></span>
                        <span><strong>Điểm danh</strong><small>Mở danh sách lớn và đánh dấu từng học viên</small></span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="lesson-action-button lesson-book" type="button" data-bs-toggle="modal" data-bs-target="#lessonBookModal">
                        <span class="lesson-action-icon"><i class="bi bi-journal-text"></i></span>
                        <span><strong>Sổ đầu bài</strong><small>Ngày, giờ, nội dung, đánh giá, chữ ký và ghi chú</small></span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="lesson-history-head"><strong>Các buổi trong tháng</strong><span>{{$lessons->count()}} buổi đã tạo · {{$lessons->whereNotNull('attendance_marked_at')->count()}} buổi đã điểm danh</span></div>
        <div class="table-responsive">
            <table class="table table-modern mb-0 lesson-history-table">
                <thead><tr><th>Ngày / giờ</th><th>Điểm danh</th><th>Nội dung giảng dạy</th><th>Chữ ký</th><th></th></tr></thead>
                <tbody>
                @forelse($lessons as $lesson)
                    @php($presentCount=$lesson->attendances->whereIn('status',['present','late'])->count())
                    <tr>
                        <td class="text-nowrap"><strong>{{$lesson->lesson_date->format('d/m/Y')}}</strong><div class="small text-muted">{{substr((string)$lesson->start_time,0,5)}}–{{substr((string)$lesson->end_time,0,5)}}</div></td>
                        <td>@if($lesson->attendance_marked_at)<span class="badge-soft badge-success">{{$presentCount}}/{{$lesson->attendances->count()}} tham gia</span>@else<span class="badge-soft badge-gray">Chưa điểm danh</span>@endif</td>
                        <td><div class="lesson-content-preview">{{$lesson->content?:'Chưa ghi sổ đầu bài'}}</div>@if($lesson->evaluation)<small class="text-muted">Đánh giá: {{$lesson->evaluation}}</small>@endif</td>
                        <td>{{$lesson->teacher_signature?:'—'}}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#lessonDetailModal{{$lesson->id}}" title="Xem buổi học" aria-label="Xem buổi học"><i class="bi bi-eye"></i></button>
                            <a class="btn btn-sm btn-outline-success" href="{{route('teacher-classes.gradebook',[$languageClass,'month'=>$month->format('Y-m'),'lesson'=>$lesson->id,'open'=>'attendance'])}}" title="Điểm danh"><i class="bi bi-person-check"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{route('teacher-classes.gradebook',[$languageClass,'month'=>$month->format('Y-m'),'lesson'=>$lesson->id,'open'=>'lesson-book'])}}" title="Sổ đầu bài"><i class="bi bi-journal-text"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state py-4">Tháng này chưa có buổi học nào.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($previousLessons->isNotEmpty())
        <div class="card card-soft mt-3">
            <div class="lesson-history-head"><strong>Các buổi đã điểm danh trước tháng {{$month->format('m/Y')}}</strong><span>{{$previousLessons->count()}} buổi trước đó</span></div>
            <div class="table-responsive">
                <table class="table table-modern mb-0 lesson-history-table">
                    <thead><tr><th>Ngày / giờ</th><th>Điểm danh</th><th>Nội dung giảng dạy</th><th>Tháng</th><th></th></tr></thead>
                    <tbody>
                    @foreach($previousLessons as $previousLesson)
                        @php($previousPresentCount=$previousLesson->attendances->whereIn('status',['present','late'])->count())
                        <tr>
                            <td class="text-nowrap"><strong>{{$previousLesson->lesson_date->format('d/m/Y')}}</strong><div class="small text-muted">{{substr((string)$previousLesson->start_time,0,5)}}–{{substr((string)$previousLesson->end_time,0,5)}}</div></td>
                            <td><span class="badge-soft badge-success">{{$previousPresentCount}}/{{$previousLesson->attendances->count()}} tham gia</span></td>
                            <td><div class="lesson-content-preview">{{$previousLesson->content?:'Chưa ghi sổ đầu bài'}}</div></td>
                            <td><span class="badge-soft badge-info">{{$previousLesson->lesson_date->format('m/Y')}}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-success text-nowrap" href="{{route('teacher-classes.gradebook',[$languageClass,'month'=>$previousLesson->lesson_date->format('Y-m'),'lesson'=>$previousLesson->id,'open'=>'attendance'])}}"><i class="bi bi-eye me-1"></i>Xem điểm danh</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

@foreach($lessons as $lesson)
<div class="modal fade lesson-detail-modal" id="lessonDetailModal{{$lesson->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header"><div><h5 class="modal-title"><i class="bi bi-eye text-primary me-2"></i>Chi tiết buổi học {{$lesson->lesson_date->format('d/m/Y')}}</h5><div class="small text-muted">Lớp {{$languageClass->code}} · {{substr((string)$lesson->start_time,0,5)}}–{{substr((string)$lesson->end_time,0,5)}}</div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="lesson-detail-grid mb-4">
                    <div><span>Ngày học</span><strong>{{$lesson->lesson_date->format('d/m/Y')}}</strong></div>
                    <div><span>Thời gian</span><strong>{{substr((string)$lesson->start_time,0,5)}}–{{substr((string)$lesson->end_time,0,5)}}</strong></div>
                    <div><span>Giáo viên ghi nhận</span><strong>{{$lesson->teacher?->name?:'—'}}</strong></div>
                    <div><span>Chữ ký</span><strong class="lesson-detail-signature">{{$lesson->teacher_signature?:'Chưa ký'}}</strong></div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-12"><div class="lesson-detail-block"><span>Nội dung giảng dạy</span><p>{{$lesson->content?:'Chưa ghi sổ đầu bài.'}}</p></div></div>
                    <div class="col-md-6"><div class="lesson-detail-block h-100"><span>Đánh giá buổi học</span><p>{{$lesson->evaluation?:'Chưa có đánh giá.'}}</p></div></div>
                    <div class="col-md-6"><div class="lesson-detail-block h-100"><span>Ghi chú</span><p>{{$lesson->note?:'Không có ghi chú.'}}</p></div></div>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h6 class="mb-0 fw-bold">Danh sách điểm danh</h6>@if($lesson->attendance_marked_at)<span class="badge-soft badge-success">Đã điểm danh {{$lesson->attendance_marked_at->format('H:i d/m/Y')}}</span>@else<span class="badge-soft badge-gray">Chưa điểm danh</span>@endif</div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-modern mb-0"><thead><tr><th>Học viên</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead><tbody>
                    @forelse($lesson->attendances as $attendance)
                        <tr><td><strong>{{$attendance->enrollment?->student?->name}}</strong><div class="small text-muted">{{$attendance->enrollment?->student?->code}}</div></td><td><span class="badge-soft badge-{{$attendanceColors[$attendance->status]??'gray'}}">{{$attendanceStatuses[$attendance->status]??$attendance->status}}</span></td><td>{{$attendance->note?:'—'}}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state py-4">Buổi học chưa có dữ liệu điểm danh.</div></td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>
@endforeach

<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <form method="POST" action="{{route('teacher-classes.attendance.store',$languageClass)}}" class="d-flex flex-column flex-grow-1" style="min-height:0">@csrf
                <input type="hidden" name="_lesson_form" value="attendance">
                <input type="hidden" name="lesson_id" value="{{old('lesson_id',$selectedLesson?->id)}}">
                <div class="modal-header"><div><h5 class="modal-title"><i class="bi bi-person-check-fill text-success me-2"></i>Điểm danh lớp {{$languageClass->code}}</h5><div class="small text-muted">Đánh dấu tình trạng tham gia của từng học viên trong buổi học.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label">Ngày học</label><input class="form-control" type="date" name="lesson_date" value="{{old('lesson_date',$defaultLessonDate)}}" required></div>
                        <div class="col-md-4"><label class="form-label">Giờ bắt đầu</label><input class="form-control" type="time" name="start_time" value="{{old('start_time',$defaultStartTime)}}" required></div>
                        <div class="col-md-4"><label class="form-label">Giờ kết thúc</label><input class="form-control" type="time" name="end_time" value="{{old('end_time',$defaultEndTime)}}" required></div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div><strong>{{$attendanceEnrollments->count()}}</strong> học viên cần điểm danh</div>
                        <button class="btn btn-sm btn-outline-success" type="button" data-mark-all-present><i class="bi bi-check2-all me-1"></i>Tất cả có mặt</button>
                    </div>
                    <div class="table-responsive attendance-table-wrap">
                        <table class="table table-modern align-middle mb-0">
                            <thead><tr><th>Học viên</th><th style="min-width:190px">Trạng thái</th><th style="min-width:280px">Ghi chú điểm danh</th></tr></thead>
                            <tbody>
                            @forelse($attendanceEnrollments as $enrollment)
                                @php($savedAttendance=$attendanceRows->get($enrollment->id))
                                <tr>
                                    <td><strong>{{$enrollment->student?->name}}</strong><div class="small text-muted">{{$enrollment->student?->code}} · {{$statusLabels[$enrollment->status]??$enrollment->status}}</div></td>
                                    <td><select class="form-select" name="attendance[{{$enrollment->id}}][status]" data-attendance-status required>@foreach($attendanceStatuses as $key=>$label)<option value="{{$key}}" @selected(old('attendance.'.$enrollment->id.'.status',$savedAttendance?->status?:'present')===$key)>{{$label}}</option>@endforeach</select></td>
                                    <td><input class="form-control" name="attendance[{{$enrollment->id}}][note]" value="{{old('attendance.'.$enrollment->id.'.note',$savedAttendance?->note)}}" placeholder="Lý do vắng, đi trễ..."></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty-state">Lớp chưa có học viên đang học.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button><button class="btn btn-success" {{$attendanceEnrollments->isEmpty()?'disabled':''}}><i class="bi bi-save me-2"></i>Lưu điểm danh</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="lessonBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <form method="POST" action="{{route('teacher-classes.lesson-book.store',$languageClass)}}" class="d-flex flex-column flex-grow-1" style="min-height:0">@csrf
                <input type="hidden" name="_lesson_form" value="lesson-book">
                <input type="hidden" name="lesson_id" value="{{old('lesson_id',$selectedLesson?->id)}}">
                <div class="modal-header"><div><h5 class="modal-title"><i class="bi bi-journal-text text-primary me-2"></i>Sổ đầu bài · {{$languageClass->code}}</h5><div class="small text-muted">Ghi nhận nội dung và xác nhận của giáo viên cho từng buổi học.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Ngày học</label><input class="form-control" type="date" name="lesson_date" value="{{old('lesson_date',$defaultLessonDate)}}" required></div>
                        <div class="col-md-4"><label class="form-label">Giờ bắt đầu</label><input class="form-control" type="time" name="start_time" value="{{old('start_time',$defaultStartTime)}}" required></div>
                        <div class="col-md-4"><label class="form-label">Giờ kết thúc</label><input class="form-control" type="time" name="end_time" value="{{old('end_time',$defaultEndTime)}}" required></div>
                        <div class="col-12"><label class="form-label">Nội dung giảng dạy</label><textarea class="form-control" name="content" rows="5" required placeholder="Bài học, kiến thức và hoạt động đã thực hiện...">{{old('content',$selectedLesson?->content)}}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Đánh giá buổi học</label><textarea class="form-control" name="evaluation" rows="4" placeholder="Mức độ tiếp thu, thái độ và kết quả chung...">{{old('evaluation',$selectedLesson?->evaluation)}}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="4" placeholder="Nội dung cần lưu ý cho buổi sau...">{{old('note',$selectedLesson?->note)}}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Chữ ký giáo viên</label><input class="form-control lesson-signature" name="teacher_signature" value="{{old('teacher_signature',$selectedLesson?->teacher_signature?:auth()->user()->name)}}" required></div>
                        <div class="col-md-6"><div class="signature-preview"><span>Xác nhận bởi</span><strong>{{old('teacher_signature',$selectedLesson?->teacher_signature?:auth()->user()->name)}}</strong><small>{{$languageClass->teacher?->name?'Giáo viên phụ trách: '.$languageClass->teacher->name:'Lớp chưa phân công giáo viên'}}</small></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary"><i class="bi bi-journal-check me-2"></i>Lưu sổ đầu bài</button></div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelector('[data-mark-all-present]')?.addEventListener('click',()=>document.querySelectorAll('[data-attendance-status]').forEach(select=>select.value='present'));
    const signature=document.querySelector('.lesson-signature'),preview=document.querySelector('.signature-preview strong');
    signature?.addEventListener('input',()=>preview.textContent=signature.value||'Chưa ký');
    const requestedModal=@json(request('open')==='attendance'||old('_lesson_form')==='attendance'?'attendanceModal':(request('open')==='lesson-book'||old('_lesson_form')==='lesson-book'?'lessonBookModal':null));
    if(requestedModal)bootstrap.Modal.getOrCreateInstance(document.getElementById(requestedModal)).show();
});
</script>
@endpush
