@extends('layouts.app')
@section('title', 'Xem lớp học')
@section('header', 'Trung tâm Ngoại ngữ')

@section('content')
@php($labels=['planned'=>'Dự kiến mở','recruiting'=>'Đang tuyển sinh','upcoming'=>'Sắp khai giảng','active'=>'Đang hoạt động','paused'=>'Tạm dừng','completed'=>'Đã kết thúc','cancelled'=>'Đã hủy'])
@php($enrollmentStatusLabels=['studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">{{ $item->name }}</h1>
        <div class="page-subtitle">{{ $item->code }} · {{ $item->program?->name }} · {{ $item->level?->name }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(auth()->user()->allowed('teacher_classes', 'view') && $item->teacher_user_id)
            <a class="btn btn-outline-primary" href="{{ route('teacher-classes.gradebook', $item) }}">
                <i class="bi bi-journal-check me-2"></i>Sổ điểm
            </a>
        @endif
        @if(auth()->user()->allowed('language_classes', 'update'))
            <a class="btn btn-primary" href="{{ route('language-classes.edit', $item) }}">
                <i class="bi bi-pencil-square me-2"></i>Sửa lớp
            </a>
        @endif
        <a class="btn btn-light" href="{{ route('language-classes.index') }}">
            <i class="bi bi-arrow-left me-2"></i>Quay lại
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <h5 class="mb-3">Thông tin lớp</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Khóa học</div>
                        <strong>{{ $item->course?->name ?: 'Chưa gắn khóa học' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Giáo viên</div>
                        <strong>{{ $item->teacher?->name ?: 'Chưa phân công' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Trạng thái</div>
                        <span class="badge-soft badge-info">{{ $labels[$item->status] ?? $item->status }}</span>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Học phí lớp</div>
                        <strong class="text-success">{{ number_format((float) $item->default_tuition) }}đ</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Miễn giảm lớp</div>
                        <strong>{{ $item->discountPolicy?->name ?: 'Không miễn giảm' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Phòng</div>
                        <strong>{{ $item->room ?: 'Chưa xếp phòng' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Khai giảng</div>
                        <strong>{{ $item->start_date?->format('d/m/Y') ?: 'Chưa đặt' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Dự kiến kết thúc</div>
                        <strong>{{ $item->expected_end_date?->format('d/m/Y') ?: 'Chưa đặt' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Sĩ số</div>
                        <strong>{{ $item->enrollments->count() }}/{{ $item->max_students }}</strong>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Giờ học mặc định</div>
                        <strong>{{ substr((string) $item->default_start_time, 0, 5) }} - {{ substr((string) $item->default_end_time, 0, 5) }}</strong>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Đã học</div>
                        <strong>{{ $item->completed_sessions }}/{{ $item->expected_sessions }} buổi</strong>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Lịch học</div>
                        <strong>{{ $item->schedule_note ?: 'Chưa có ghi chú lịch học' }}</strong>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Ghi chú</div>
                        <div class="rounded-3 bg-light p-3">{{ $item->note ?: 'Không có ghi chú thêm.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <h5 class="mb-3">Buổi học gần nhất</h5>
                @forelse($item->lessons->take(5) as $lesson)
                    <div class="border rounded-3 p-3 mb-3">
                        <strong>{{ $lesson->lesson_date?->format('d/m/Y') }}</strong>
                        <div class="small text-muted mt-1">{{ substr((string) $lesson->start_time, 0, 5) }} - {{ substr((string) $lesson->end_time, 0, 5) }}</div>
                        <div class="small mt-2">{{ $lesson->content ?: 'Chưa có sổ đầu bài.' }}</div>
                    </div>
                @empty
                    <div class="empty-state py-4">Chưa có buổi học nào được ghi nhận.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Học viên hiện tại</h5>
            <span class="small text-muted">{{ $item->enrollments->count() }} học viên</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Học viên</th>
                        <th>Ngày vào lớp</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($item->enrollments as $enrollment)
                        <tr>
                            <td>
                                <strong>{{ $enrollment->student?->name }}</strong>
                                <div class="small text-muted">{{ $enrollment->student?->code }}</div>
                            </td>
                            <td>{{ $enrollment->enrolled_at?->format('d/m/Y') ?: 'Chưa rõ' }}</td>
                            <td><span class="badge-soft badge-info">{{ $enrollmentStatusLabels[$enrollment->status] ?? $enrollment->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"><div class="empty-state">Lớp chưa có học viên.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
