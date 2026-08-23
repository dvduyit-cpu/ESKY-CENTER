@extends('layouts.app')

@section('title', 'Lập khoản thu học phí')
@section('header', 'Quản lý học viên')

@section('content')
@php($activeMode = old('entry_mode', $selectedMode ?? 'class'))

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">Lập khoản thu học phí</h1>
        <div class="page-subtitle">Bạn có thể lập khoản thu theo lớp như hiện tại hoặc thu nhanh theo học viên, khóa học tự chọn rồi in phiếu ngay.</div>
    </div>
    <a class="btn btn-light" href="{{ route('language-tuition.index') }}">Quay lại</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Vui lòng kiểm tra lại dữ liệu.</strong>
        <ul class="mb-0 mt-2 ps-3">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex flex-wrap gap-2 mb-4" data-mode-switcher>
    <button class="btn {{ $activeMode === 'class' ? 'btn-primary' : 'btn-outline-primary' }}" type="button" data-mode-switch="class">
        <i class="bi bi-diagram-3 me-2"></i>Theo lớp học
    </button>
    <button class="btn {{ $activeMode === 'quick' ? 'btn-success' : 'btn-outline-success' }}" type="button" data-mode-switch="quick">
        <i class="bi bi-lightning-charge me-2"></i>Thu nhanh tự chọn
    </button>
</div>

<div data-mode-panel="class" @class(['d-none' => $activeMode !== 'class'])>
    <div class="card card-soft form-card">
        <div class="card-header">
            <h5>Lập khoản thu theo lớp</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('language-tuition.store') }}">
                @csrf
                <input type="hidden" name="entry_mode" value="class">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Học viên - Lớp - Khóa học</label>
                        <select class="form-select" data-enrollment-choice data-searchable-select data-search-placeholder="Tìm học viên, lớp học, khóa học..." required>
                            <option value="">Chọn học viên trong lớp chưa có phiếu thu</option>
                            @foreach($enrollments as $enrollment)
                                <option
                                    value="{{ $enrollment->id }}"
                                    data-student="{{ $enrollment->language_student_id }}"
                                    data-class="{{ $enrollment->language_class_id }}"
                                    data-course="{{ $enrollment->languageClass?->language_course_id }}"
                                    @selected((old('language_student_id', $selectedStudent) == $enrollment->language_student_id) && (old('language_class_id', $selectedClass) == $enrollment->language_class_id))
                                >
                                    {{ $enrollment->student?->code }} - {{ $enrollment->student?->name }} · Lớp {{ $enrollment->languageClass?->code }} - {{ $enrollment->languageClass?->name }} · Khóa {{ $enrollment->languageClass?->course?->name }} · Học phí {{ number_format($enrollment->languageClass?->default_tuition ?? 0) }}đ
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="language_student_id" value="{{ old('language_student_id', $selectedStudent) }}">
                        <input type="hidden" name="language_class_id" value="{{ old('language_class_id', $selectedClass) }}">
                        <input type="hidden" name="language_course_id" value="{{ old('language_course_id') }}">
                        <div class="form-text">Mỗi học viên trong một lớp chỉ lập khoản thu một lần, học phí sẽ lấy tự động theo lớp.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Miễn giảm riêng của học viên</label>
                        <select class="form-select" name="language_discount_policy_id">
                            <option value="">Dùng mức riêng đã lưu của học viên</option>
                            @foreach($discounts as $discount)
                                <option value="{{ $discount->id }}" @selected((string) old('language_discount_policy_id') === (string) $discount->id)>
                                    {{ $discount->name }} - giảm {{ \App\Support\ValueFormatter::percentage($discount->percentage) }}%
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hệ thống sẽ so sánh với miễn giảm của lớp và chỉ áp dụng mức cao hơn.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Hạn đóng</label>
                        <input class="form-control" type="date" name="due_date" value="{{ old('due_date') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="note" rows="3">{{ old('note') }}</textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn btn-light" href="{{ route('language-tuition.index') }}">Hủy</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-receipt me-2"></i>Lập khoản thu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div data-mode-panel="quick" @class(['d-none' => $activeMode !== 'quick'])>
    <div class="card card-soft form-card">
        <div class="card-header">
            <h5>Thu nhanh theo học viên và khóa học tự chọn</h5>
        </div>
        <div class="card-body p-4">
            <form
                method="POST"
                action="{{ route('language-tuition.store') }}"
                data-quick-collection-form
                @if($bank['enabled'])
                    data-qr-base="https://img.vietqr.io/image/{{ $bank['bin'] }}-{{ $bank['account_number'] }}-compact2.png"
                    data-bank-account-name="{{ $bank['account_name'] }}"
                @endif
            >
                @csrf
                <input type="hidden" name="entry_mode" value="quick">

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Thông tin học viên</label>
                        <select
                            class="form-select @error('language_student_id') is-invalid @enderror"
                            name="language_student_id"
                            data-student-select
                            data-searchable-select
                            data-search-placeholder="Tìm mã học viên, tên, số điện thoại..."
                            required
                        >
                            <option value="">Chọn học viên</option>
                            @foreach($students as $student)
                                <option
                                    value="{{ $student->id }}"
                                    data-code="{{ $student->code }}"
                                    data-name="{{ $student->name }}"
                                    data-phone="{{ $student->phone }}"
                                    data-email="{{ $student->email }}"
                                    data-address="{{ $student->address }}"
                                    @selected((int) old('language_student_id', $selectedStudent) === $student->id)
                                >
                                    {{ $student->code }} - {{ $student->name }} @if($student->phone) · {{ $student->phone }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('language_student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label">Khóa học tự chọn</label>
                        <select
                            class="form-select @error('language_course_id') is-invalid @enderror"
                            name="language_course_id"
                            data-course-select
                            data-searchable-select
                            data-search-placeholder="Tìm tên khóa học, giáo trình..."
                            required
                        >
                            <option value="">Chọn khóa học</option>
                            @foreach($courses as $course)
                                <option
                                    value="{{ $course->id }}"
                                    data-code="{{ $course->code }}"
                                    data-name="{{ $course->name }}"
                                    data-tuition="{{ (float) $course->tuition }}"
                                    data-textbook="{{ $course->textbook }}"
                                    @selected((int) old('language_course_id', $selectedCourse) === $course->id)
                                >
                                    {{ $course->name }} · {{ $course->code }} · {{ number_format($course->tuition) }}đ
                                </option>
                            @endforeach
                        </select>
                        @error('language_course_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                            <div class="small text-muted mb-2">Tóm tắt học viên</div>
                            <div class="fw-semibold" data-student-summary-name>Chưa chọn học viên</div>
                            <div class="small text-muted mt-2" data-student-summary-detail>Mã học viên, số điện thoại và email sẽ hiện ở đây.</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                            <div class="small text-muted mb-2">Tóm tắt khóa học</div>
                            <div class="fw-semibold" data-course-summary-name>Chưa chọn khóa học</div>
                            <div class="small text-muted mt-2" data-course-summary-detail>Học phí tham khảo và giáo trình sẽ hiện ở đây.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Chế độ miễn giảm</label>
                        <select class="form-select @error('language_discount_policy_id') is-invalid @enderror" name="language_discount_policy_id" data-discount-select>
                            <option value="">Không miễn giảm</option>
                            @foreach($discounts as $discount)
                                <option
                                    value="{{ $discount->id }}"
                                    data-percentage="{{ (float) $discount->percentage }}"
                                    @selected((int) old('language_discount_policy_id') === $discount->id)
                                >
                                    {{ $discount->name }} - giảm {{ \App\Support\ValueFormatter::percentage($discount->percentage) }}%
                                </option>
                            @endforeach
                        </select>
                        @error('language_discount_policy_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Học phí gốc</label>
                        <div class="input-group">
                            <input
                                class="form-control @error('original_amount') is-invalid @enderror"
                                type="number"
                                name="original_amount"
                                min="1"
                                step="0.01"
                                value="{{ old('original_amount') }}"
                                data-original-amount
                                required
                            >
                            <span class="input-group-text">đ</span>
                            @error('original_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Số tiền thu</label>
                        <div class="input-group">
                            <input
                                class="form-control @error('collected_amount') is-invalid @enderror"
                                type="number"
                                name="collected_amount"
                                min="1"
                                step="0.01"
                                value="{{ old('collected_amount') }}"
                                data-collected-amount
                                required
                            >
                            <span class="input-group-text">đ</span>
                            @error('collected_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">Giảm theo chế độ</div>
                                    <div class="fw-semibold text-warning mt-1" data-discount-summary>0%</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">Số phải thu sau miễn giảm</div>
                                    <div class="fw-semibold text-primary mt-1" data-payable-summary>0đ</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">Còn lại sau lần thu này</div>
                                    <div class="fw-semibold text-danger mt-1" data-remaining-summary>0đ</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ngày thu</label>
                        <input
                            class="form-control @error('paid_at') is-invalid @enderror"
                            type="datetime-local"
                            name="paid_at"
                            value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"
                            required
                        >
                        @error('paid_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Số phiếu thu</label>
                        <input class="form-control @error('receipt_code') is-invalid @enderror" name="receipt_code" value="{{ old('receipt_code') }}" placeholder="Để trống nếu cập nhật sau">
                        @error('receipt_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hình thức thu</label>
                        <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" data-payment-method required>
                            @php($paymentMethod = old('payment_method', 'cash'))
                            <option value="cash" @selected($paymentMethod === 'cash')>Tiền mặt</option>
                            @if($bank['enabled'])
                                <option value="transfer" @selected($paymentMethod === 'transfer')>Chuyển khoản</option>
                            @endif
                            <option value="card" @selected($paymentMethod === 'card')>Thẻ</option>
                            <option value="other" @selected($paymentMethod === 'other')>Khác</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mã lớp dùng cho chuyển khoản</label>
                        <input
                            class="form-control"
                            name="transfer_class_code"
                            value="{{ old('transfer_class_code') }}"
                            data-transfer-class-code
                            placeholder="Ví dụ: SKY-A1-01"
                        >
                        <div class="form-text">Nội dung chuyển khoản sẽ ghép theo: Họ tên học viên + Mã lớp. Nếu khoản thu này chưa gắn lớp, bạn có thể nhập mã lớp tại đây.</div>
                    </div>

                    @if($bank['enabled'])
                        <div class="col-12 d-none" data-qr-section>
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-lg-4 text-center">
                                            <img class="img-fluid rounded bg-white border p-2" alt="VietQR" data-qr-image style="max-height: 260px;">
                                        </div>
                                        <div class="col-lg-8">
                                            <h6 class="fw-bold mb-3">Chuyển khoản qua mã QR</h6>
                                            <div class="small text-muted mb-2">Ngân hàng nhận</div>
                                            <div class="fw-semibold mb-3">{{ $bank['name'] }} - {{ $bank['account_number'] }} - {{ $bank['account_name'] }}</div>

                                            <label class="form-label">Nội dung chuyển khoản</label>
                                            <input class="form-control mb-2" type="text" readonly data-transfer-content>
                                            <div class="form-text">Nội dung sẽ tự đổi theo học viên, khóa học và dùng để tạo mã QR.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(old('payment_method') === 'transfer')
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">Chưa cấu hình tài khoản ngân hàng nhận học phí nên chưa thể dùng hình thức chuyển khoản.</div>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label">Mã giao dịch / Tham chiếu</label>
                        <input class="form-control @error('reference') is-invalid @enderror" name="reference" value="{{ old('reference') }}" placeholder="Ví dụ: UNC0823">
                        @error('reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control @error('note') is-invalid @enderror" name="note" rows="3" placeholder="Nội dung thu học phí tự chọn...">{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn btn-light" href="{{ route('language-tuition.index') }}">Hủy</a>
                    <button class="btn btn-success" name="submit_action" value="save">
                        <i class="bi bi-save me-2"></i>Lưu phiếu thu
                    </button>
                    <button class="btn btn-primary" name="submit_action" value="print">
                        <i class="bi bi-printer me-2"></i>Lưu và in ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeButtons = document.querySelectorAll('[data-mode-switch]');
    const modePanels = document.querySelectorAll('[data-mode-panel]');
    const setMode = function (mode) {
        modePanels.forEach(function (panel) {
            panel.classList.toggle('d-none', panel.dataset.modePanel !== mode);
        });
        modeButtons.forEach(function (button) {
            const active = button.dataset.modeSwitch === mode;
            if (button.dataset.modeSwitch === 'quick') {
                button.classList.toggle('btn-success', active);
                button.classList.toggle('btn-outline-success', !active);
            } else {
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-primary', !active);
            }
        });
    };

    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.dataset.modeSwitch);
        });
    });

    setMode(@json($activeMode));

    const choice = document.querySelector('[data-enrollment-choice]');
    const student = document.querySelector('[name="language_student_id"][type="hidden"]');
    const classInput = document.querySelector('[name="language_class_id"]');
    const course = document.querySelector('[name="language_course_id"][type="hidden"]');
    if (choice && student && classInput && course) {
        const syncEnrollment = function () {
            const option = choice.options[choice.selectedIndex];
            student.value = option?.dataset.student || '';
            classInput.value = option?.dataset.class || '';
            course.value = option?.dataset.course || '';
        };
        choice.addEventListener('change', syncEnrollment);
        syncEnrollment();
    }

    const quickForm = document.querySelector('[data-quick-collection-form]');
    if (!quickForm) {
        return;
    }

    const formatter = new Intl.NumberFormat('vi-VN');
    const studentSelect = quickForm.querySelector('[data-student-select]');
    const courseSelect = quickForm.querySelector('[data-course-select]');
    const discountSelect = quickForm.querySelector('[data-discount-select]');
    const originalAmountInput = quickForm.querySelector('[data-original-amount]');
    const collectedAmountInput = quickForm.querySelector('[data-collected-amount]');
    const paymentMethodSelect = quickForm.querySelector('[data-payment-method]');
    const transferClassCodeInput = quickForm.querySelector('[data-transfer-class-code]');
    const studentName = quickForm.querySelector('[data-student-summary-name]');
    const studentDetail = quickForm.querySelector('[data-student-summary-detail]');
    const courseName = quickForm.querySelector('[data-course-summary-name]');
    const courseDetail = quickForm.querySelector('[data-course-summary-detail]');
    const discountSummary = quickForm.querySelector('[data-discount-summary]');
    const payableSummary = quickForm.querySelector('[data-payable-summary]');
    const remainingSummary = quickForm.querySelector('[data-remaining-summary]');
    const qrSection = quickForm.querySelector('[data-qr-section]');
    const qrImage = quickForm.querySelector('[data-qr-image]');
    const transferContentInput = quickForm.querySelector('[data-transfer-content]');
    let lastSuggestedOriginal = '';
    let lastSuggestedCollected = '';

    const formatMoney = function (value) {
        return formatter.format(Math.max(0, Number(value) || 0)) + 'đ';
    };

    const normalizeTransferText = function (value) {
        return value.replace(/\s+/g, ' ').trim();
    };

    const renderStudent = function () {
        const option = studentSelect?.options[studentSelect.selectedIndex];
        if (!option || !option.value) {
            studentName.textContent = 'Chưa chọn học viên';
            studentDetail.textContent = 'Mã học viên, số điện thoại và email sẽ hiện ở đây.';
            return;
        }

        const parts = [
            option.dataset.code || '',
            option.dataset.phone || 'Chưa có số điện thoại',
            option.dataset.email || 'Chưa có email',
            option.dataset.address || '',
        ].filter(Boolean);

        studentName.textContent = option.dataset.name || option.textContent.trim();
        studentDetail.textContent = parts.join(' · ');
    };

    const selectedDiscountPercentage = function () {
        const option = discountSelect?.options[discountSelect.selectedIndex];
        return option && option.value ? Number(option.dataset.percentage || 0) : 0;
    };

    const selectedTransferContent = function () {
        const studentOption = studentSelect?.options[studentSelect.selectedIndex];
        const classCode = transferClassCodeInput?.value?.trim() || '';
        return normalizeTransferText([
            studentOption?.dataset.name || '',
            classCode,
        ].join(' '));
    };

    const syncFinancialSummary = function () {
        const originalAmount = Number(originalAmountInput?.value || 0);
        const discountPercent = selectedDiscountPercentage();
        const discountAmount = Math.round(originalAmount * discountPercent) / 100;
        const payableAmount = Math.max(0, originalAmount - discountAmount);
        const collectedAmount = Number(collectedAmountInput?.value || 0);
        const remainingAmount = Math.max(0, payableAmount - collectedAmount);

        discountSummary.textContent = discountPercent > 0 ? (discountPercent.toLocaleString('vi-VN') + '%') : '0%';
        payableSummary.textContent = formatMoney(payableAmount);
        remainingSummary.textContent = formatMoney(remainingAmount);

        return {
            payableAmount: payableAmount,
            collectedAmount: collectedAmount,
        };
    };

    const syncQr = function () {
        if (!qrSection || !paymentMethodSelect) {
            return;
        }

        const details = syncFinancialSummary();
        const showQr = paymentMethodSelect.value === 'transfer' && details.collectedAmount > 0 && quickForm.dataset.qrBase;
        qrSection.classList.toggle('d-none', !showQr);

        if (!showQr) {
            if (qrImage) {
                qrImage.removeAttribute('src');
            }
            if (transferContentInput) {
                transferContentInput.value = '';
            }
            return;
        }

        const transferContent = selectedTransferContent();
        if (transferContentInput) {
            transferContentInput.value = transferContent;
        }

        const params = new URLSearchParams({
            amount: Math.round(details.collectedAmount),
            addInfo: transferContent,
            accountName: quickForm.dataset.bankAccountName || '',
        });
        if (qrImage) {
            qrImage.src = quickForm.dataset.qrBase + '?' + params.toString();
        }
    };

    const renderCourse = function () {
        const option = courseSelect?.options[courseSelect.selectedIndex];
        if (!option || !option.value) {
            courseName.textContent = 'Chưa chọn khóa học';
            courseDetail.textContent = 'Học phí tham khảo và giáo trình sẽ hiện ở đây.';
            syncFinancialSummary();
            syncQr();
            return;
        }

        const tuition = option.dataset.tuition ? Number(option.dataset.tuition) : 0;
        const details = [
            option.dataset.code || '',
            tuition > 0 ? ('Học phí tham khảo ' + formatter.format(tuition) + 'đ') : '',
            option.dataset.textbook || 'Chưa có giáo trình',
        ].filter(Boolean);

        courseName.textContent = option.dataset.name || option.textContent.trim();
        courseDetail.textContent = details.join(' · ');

        if (originalAmountInput && (!originalAmountInput.value || originalAmountInput.value === lastSuggestedOriginal) && tuition > 0) {
            lastSuggestedOriginal = String(Math.round(tuition));
            originalAmountInput.value = lastSuggestedOriginal;
        }

        const summary = syncFinancialSummary();
        if (collectedAmountInput && (!collectedAmountInput.value || collectedAmountInput.value === lastSuggestedCollected) && summary.payableAmount > 0) {
            lastSuggestedCollected = String(Math.round(summary.payableAmount));
            collectedAmountInput.value = lastSuggestedCollected;
        }

        syncFinancialSummary();
        syncQr();
    };

    studentSelect?.addEventListener('change', function () {
        renderStudent();
        syncQr();
    });
    courseSelect?.addEventListener('change', renderCourse);
    discountSelect?.addEventListener('change', function () {
        const summary = syncFinancialSummary();
        if (collectedAmountInput && (!collectedAmountInput.value || collectedAmountInput.value === lastSuggestedCollected || Number(collectedAmountInput.value) > summary.payableAmount)) {
            lastSuggestedCollected = String(Math.round(summary.payableAmount));
            collectedAmountInput.value = lastSuggestedCollected;
        }
        syncFinancialSummary();
        syncQr();
    });
    originalAmountInput?.addEventListener('input', function () {
        lastSuggestedOriginal = originalAmountInput.value;
        const summary = syncFinancialSummary();
        if (collectedAmountInput && (!collectedAmountInput.value || collectedAmountInput.value === lastSuggestedCollected || Number(collectedAmountInput.value) > summary.payableAmount)) {
            lastSuggestedCollected = String(Math.round(summary.payableAmount));
            collectedAmountInput.value = lastSuggestedCollected;
        }
        syncFinancialSummary();
        syncQr();
    });
    collectedAmountInput?.addEventListener('input', function () {
        lastSuggestedCollected = collectedAmountInput.value;
        syncFinancialSummary();
        syncQr();
    });
    paymentMethodSelect?.addEventListener('change', syncQr);
    transferClassCodeInput?.addEventListener('input', syncQr);

    renderStudent();
    renderCourse();
    syncFinancialSummary();
    syncQr();
});
</script>
@endpush
