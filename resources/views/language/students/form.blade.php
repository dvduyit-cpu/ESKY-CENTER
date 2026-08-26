@extends('layouts.app')
@section('title', $item->exists ? 'Sửa học viên' : 'Thêm học viên')
@section('header', 'Quản lý học viên')

@section('content')
@php
$labels = [
    'new' => 'Mới đăng ký',
    'placement_test' => 'Chờ kiểm tra',
    'waiting_class' => 'Chờ xếp lớp',
    'studying' => 'Đang học',
    'paused' => 'Tạm nghỉ',
    'reserved' => 'Bảo lưu',
    'completed' => 'Hoàn thành',
    'dropped' => 'Thôi học',
];
$guardianTypes = ['father' => 'Cha', 'mother' => 'Mẹ', 'guardian' => 'Người giám hộ'];
$guardianRows = collect(old(
    'guardians',
    $item->guardians?->map(fn ($guardian) => [
        'name' => $guardian->name,
        'relationship' => $guardian->relationship,
        'phone' => $guardian->phone,
        'email' => $guardian->email,
    ])->all() ?? []
));
foreach (array_keys($guardianTypes) as $type) {
    if (! $guardianRows->contains(fn ($guardian) => ($guardian['relationship'] ?? null) === $type)) {
        $guardianRows->push([
            'name' => '',
            'relationship' => $type,
            'phone' => '',
            'email' => '',
        ]);
    }
}
$activeEnrollment = $item->enrollments?->whereIn('status', ['studying', 'paused', 'reserved'])->sortByDesc('enrolled_at')->first();
@endphp

<div class="d-flex justify-content-between mb-4">
    <div>
        <h1 class="page-title">{{ $item->exists ? 'Cập nhật' : 'Thêm' }} học viên</h1>
        <div class="page-subtitle">Hồ sơ, xếp lớp, miễn giảm và người liên hệ của học viên.</div>
    </div>
    <a class="btn btn-light" href="{{ route('language-students.index') }}">
        <i class="bi bi-arrow-left me-2"></i>Quay lại
    </a>
</div>

<form method="POST" action="{{ $item->exists ? route('language-students.update', $item) : route('language-students.store') }}">
    @csrf
    @if($item->exists)
        @method('PUT')
    @endif

    <div class="card card-soft form-card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-mortarboard-fill me-2"></i>Thông tin học viên</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mã học viên</label>
                    <input class="form-control" value="{{ $item->code ?: 'Tự động khi lưu' }}" readonly>
                    <div class="form-text">Hệ thống tự sinh dạng HV-NĂM-00001.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                    <input class="form-control" name="name" value="{{ old('name', $item->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày sinh</label>
                    <input class="form-control" type="date" name="date_of_birth" value="{{ old('date_of_birth', $item->date_of_birth?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Giới tính</label>
                    <select class="form-select" name="gender">
                        <option value="">—</option>
                        <option value="male" @selected(old('gender', $item->gender) === 'male')>Nam</option>
                        <option value="female" @selected(old('gender', $item->gender) === 'female')>Nữ</option>
                        <option value="other" @selected(old('gender', $item->gender) === 'other')>Khác</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Điện thoại</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $item->phone) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" value="{{ old('email', $item->email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trường đang học</label>
                    <input class="form-control" name="school" value="{{ old('school', $item->school) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lớp tại trường</label>
                    <input class="form-control" name="school_class" value="{{ old('school_class', $item->school_class) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nguồn tiếp nhận</label>
                    <input class="form-control" name="source" value="{{ old('source', $item->source) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Địa chỉ</label>
                    <input class="form-control" name="address" value="{{ old('address', $item->address) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày đăng ký</label>
                    <input class="form-control" type="date" name="registered_at" value="{{ old('registered_at', $item->registered_at?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày nhập học chính thức</label>
                    <input class="form-control" type="date" name="official_enrollment_date" value="{{ old('official_enrollment_date', $item->official_enrollment_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Khóa học trung tâm</label>
                    <select class="form-select" name="language_course_id">
                        <option value="">Chưa chọn khóa học</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('language_course_id', $item->language_course_id) == $course->id)>{{ $course->code }} – {{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isRegistrar())
                    @if($item->exists)
                        <div class="col-md-6">
                            <label class="form-label">Giáo vụ xếp lớp (chương trình / cấp độ)</label>
                            <div class="form-control bg-light">
                                @if($activeEnrollment)
                                    {{ $activeEnrollment->languageClass?->code ?: 'Lớp không còn hoạt động' }}
                                    @if($activeEnrollment->languageClass?->name)
                                        – {{ $activeEnrollment->languageClass->name }}
                                    @endif
                                @else
                                    Chưa xếp lớp
                                @endif
                            </div>
                            <div class="form-text">Thêm hoặc đưa khỏi lớp trực tiếp tại hồ sơ học viên để giữ đúng lịch sử học phí.</div>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label">Giáo vụ xếp lớp (chương trình / cấp độ)</label>
                            <select class="form-select @error('language_class_id') is-invalid @enderror" name="language_class_id">
                                <option value="">Chưa xếp lớp</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected(old('language_class_id', $activeEnrollment?->language_class_id) == $class->id)>{{ $class->code }} – {{ $class->name }} · {{ $class->program?->name }} / {{ $class->level?->name }}</option>
                                @endforeach
                            </select>
                            @error('language_class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Khi xếp lớp, hệ thống tự động tạo khoản phải thu học phí.</div>
                        </div>
                    @endif
                @else
                    <div class="col-md-6">
                        <label class="form-label">Lớp học</label>
                        <div class="form-control bg-light">Giáo vụ phụ trách xếp lớp</div>
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Đối tượng miễn giảm</label>
                    <select class="form-select" name="language_discount_policy_id">
                        <option value="">Không miễn giảm</option>
                        @foreach($discounts as $discount)
                            <option value="{{ $discount->id }}" @selected(old('language_discount_policy_id', $item->language_discount_policy_id) == $discount->id)>{{ $discount->name }} – {{ $discount->eligible_subject }} ({{ \App\Support\ValueFormatter::percentage($discount->percentage) }}%)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        @foreach($labels as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $item->status ?: 'new') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note">{{ old('note', $item->note) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft form-card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-people-fill me-2"></i>Cha, mẹ và người giám hộ</h5>
        </div>
        <div class="card-body p-4">
            <div class="form-text mb-3">Có thể chỉ nhập số điện thoại; hệ thống sẽ tự đặt tên liên hệ theo quan hệ đã chọn.</div>
            <div class="row g-3">
                @foreach($guardianRows->take(3)->values() as $index => $guardian)
                    <div class="col-12">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Quan hệ</label>
                                <select class="form-select" name="guardians[{{ $index }}][relationship]">
                                    @foreach($guardianTypes as $key => $label)
                                        <option value="{{ $key }}" @selected(($guardian['relationship'] ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Họ tên</label>
                                <input class="form-control" name="guardians[{{ $index }}][name]" value="{{ $guardian['name'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Điện thoại</label>
                                <input class="form-control" type="tel" inputmode="tel" name="guardians[{{ $index }}][phone]" value="{{ $guardian['phone'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="guardians[{{ $index }}][email]" value="{{ $guardian['email'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($item->exists)
        <div class="card card-soft mb-4">
            <div class="card-header">
                <h5><i class="bi bi-graph-up me-2"></i>Lịch sử học tập và điểm</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Chương trình / cấp độ</th>
                                <th>Thời gian học</th>
                                <th>Đánh giá tháng</th>
                                <th>Điểm kiểm tra</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->enrollments->sortBy('enrolled_at') as $enrollment)
                                <tr>
                                    <td>
                                        <strong>{{ $enrollment->languageClass?->code }}</strong>
                                        <div class="small text-muted">{{ $enrollment->languageClass?->name }}</div>
                                    </td>
                                    <td>
                                        {{ $enrollment->languageClass?->program?->name }}
                                        <div class="small text-muted">{{ $enrollment->languageClass?->level?->name }}</div>
                                    </td>
                                    <td>{{ $enrollment->enrolled_at?->format('d/m/Y') }} – {{ $enrollment->ended_at?->format('d/m/Y') ?: 'Hiện tại' }}</td>
                                    <td>
                                        {{ number_format($enrollment->monthlyProgress->count()) }} tháng
                                        <div class="small text-muted">{{ $enrollment->monthlyProgress->sortByDesc('month')->first()?->assessment ?: 'Chưa đánh giá' }}</div>
                                    </td>
                                    <td>
                                        {{ number_format($enrollment->scores->count()) }} bài
                                        <div class="small text-muted">
                                            @php($average = $enrollment->scores->avg(fn ($score) => (float) $score->score / (float) $score->max_score * 10))
                                            {{ $average !== null ? 'TB '.number_format($average, 1).'/10' : 'Chưa có điểm' }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $enrollment->status }}
                                        @if($enrollment->exit_reason)
                                            <div class="small text-muted">{{ $enrollment->exit_reason }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($enrollment->languageClass)
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('teacher-classes.gradebook', $enrollment->languageClass) }}" title="Mở sổ điểm">
                                                <i class="bi bi-journal-check"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Chưa có lịch sử lớp học.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="form-actions">
        <a class="btn btn-light" href="{{ route('language-students.index') }}">Hủy</a>
        <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu học viên</button>
    </div>
</form>
@endsection
