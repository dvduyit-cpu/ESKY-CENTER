@extends('layouts.app')
@section('title','Báo cáo tiết dạy năm '.$year)
@section('header','Báo cáo tiết dạy')
@section('content')
<style>
    .teaching-report-month-card { position: relative; z-index: 1; }
    .teaching-report-month-card:has(.dropdown-menu.show) { z-index: 1051; }
    .teaching-report-month-card .dropdown-menu { z-index: 1052; }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">Báo cáo tiết dạy</h1>
        <div class="page-subtitle">Theo dõi và nhập giờ dạy theo tháng.</div>
    </div>
    <form class="d-flex gap-2">
        <input class="form-control" list="teaching-report-years" type="number" name="year" value="{{ $year }}" min="2020" max="2100" style="width:140px">
        <datalist id="teaching-report-years">
            @foreach($availableYears as $availableYear)
                <option value="{{ $availableYear }}"></option>
            @endforeach
        </datalist>
        <button class="btn btn-primary"><i class="bi bi-filter"></i></button>
    </form>
</div>

@if(! $personnel)
    <div class="alert alert-warning border-0 shadow-sm">Tài khoản của bạn chưa liên kết với hồ sơ nhân sự nên chưa thể báo cáo tiết dạy.</div>
@elseif(! $plan)
    <div class="alert alert-warning border-0 shadow-sm">Năm {{ $year }} chưa có kế hoạch chỉ tiêu để gắn báo cáo tiết dạy.</div>
@elseif(! $target || (float) $assignedTeachingLoad <= 0)
    <div class="alert alert-warning border-0 shadow-sm">Bạn chưa được giao số tiết dạy cho năm {{ $year }}. Quản trị viên cần tạo dòng chỉ tiêu năm và nhập trường tiết dạy được giao.</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-soft"><div class="card-body"><div class="stat-label">Tiết dạy được giao</div><div class="stat-value text-primary">{{ number_format($assignedTeachingLoad, 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-soft"><div class="card-body"><div class="stat-label">Đã báo cáo</div><div class="stat-value text-success">{{ number_format($reportedTeachingLoad, 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-soft"><div class="card-body"><div class="stat-label">Còn lại</div><div class="stat-value text-warning">{{ number_format($remainingTeachingLoad, 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-soft"><div class="card-body"><div class="stat-label">Vượt so với giao</div><div class="stat-value text-danger">{{ number_format($exceededTeachingLoad, 2) }}</div></div></div>
        </div>
    </div>

    <div class="card card-soft mb-4">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="mb-1">Hướng dẫn nhập báo cáo</h5>
            <small class="text-muted">Nhập giờ dạy Trung tâm hoặc thêm dòng Đào tạo khi cần.</small>
        </div>
    </div>

    <div class="d-grid gap-3">
        @foreach($monthlyRows as $row)
            @php
                $formId = 'teaching-report-'.$row['month'];
                $modalId = 'teaching-report-modal-'.$row['month'];
                $detailRows = (int) old('report_month') === $row['month']
                    ? array_values(old('rows', $row['detail_rows']))
                    : $row['detail_rows'];
                $centerRows = collect($detailRows)->filter(fn ($item) => ($item['type'] ?? 'center') !== 'training');
                $trainingRows = collect($detailRows)->filter(fn ($item) => ($item['type'] ?? 'center') === 'training');
                $monthTotal = collect($detailRows)->sum(fn ($item) => (float) ($item['lesson_count'] ?? 0));
                $isOpen = (int) old('report_month') === $row['month'];
            @endphp
            <div class="card card-soft teaching-report-month-card">
                <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="mb-1">Tháng {{ $row['month'] }}/{{ $year }}</h5>
                        <div class="small text-muted">Chỉ hiện tổng giờ của tháng này. Bấm mở để nhập chi tiết.</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="text-md-end">
                            <div class="small text-muted">Tổng giờ tháng này</div>
                            <div class="fs-5 fw-bold text-primary" data-month-total-display data-month-key="{{ $row['month'] }}">{{ number_format($monthTotal, 2) }}</div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download me-1"></i>Tải về
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <a class="dropdown-item" href="{{ route('teacher-classes.teaching-load.word', ['year' => $year, 'report_month' => $row['month'], 'scope' => 'center']) }}" data-no-loading>
                                        Trung tâm
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('teacher-classes.teaching-load.word', ['year' => $year, 'report_month' => $row['month'], 'scope' => 'training']) }}" data-no-loading>
                                        Đào tạo
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                            <i class="bi bi-eye me-1"></i>Mở
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title mb-1">Tháng {{ $row['month'] }}/{{ $year }}</h5>
                                <div class="small text-muted">Nhập chi tiết từng buổi học theo dạng bảng.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h6 class="mb-1">Chi tiết báo cáo tháng</h6>
                                    <small class="text-muted">Điền theo dạng bảng: Ngày, Lớp/Mã lớp, Khung giờ, Số tiết, Ghi chú.</small>
                                </div>
                                <div class="d-flex flex-wrap justify-content-md-end align-items-stretch gap-2">
                                    <div class="border rounded-3 px-3 py-2 bg-light">
                                        <div class="small text-muted">Cập nhật cuối</div>
                                        <div class="fw-semibold text-nowrap">{{ $row['report']?->updated_at?->format('d/m/Y H:i') ?: 'Chưa báo cáo' }}</div>
                                    </div>
                                    <div class="border rounded-3 px-3 py-2 bg-light">
                                        <div class="small text-muted">Tổng giờ tháng này</div>
                                        <div class="fs-5 fw-bold text-primary text-nowrap" data-month-total-display data-month-key="{{ $row['month'] }}">{{ number_format($monthTotal, 2) }}</div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary h-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-download me-1"></i>Tải về
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('teacher-classes.teaching-load.word', ['year' => $year, 'report_month' => $row['month'], 'scope' => 'center']) }}" data-no-loading>
                                                    Trung tâm
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('teacher-classes.teaching-load.word', ['year' => $year, 'report_month' => $row['month'], 'scope' => 'training']) }}" data-no-loading>
                                                    Đào tạo
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <form id="{{ $formId }}" method="POST" action="{{ route('teacher-classes.teaching-load.store') }}" data-teaching-report-form data-month-key="{{ $row['month'] }}">
                                @csrf
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="report_month" value="{{ $row['month'] }}">

                                <div class="table-responsive">
                                    <table class="table table-modern align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:70px">STT</th>
                                                <th style="min-width:150px">Ngày</th>
                                                <th style="min-width:280px">Lớp/Mã lớp</th>
                                                <th style="min-width:160px">Khung giờ</th>
                                                <th style="min-width:120px">Số tiết</th>
                                                <th style="min-width:220px">Ghi chú</th>
                                                <th class="text-end" style="width:80px">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody data-report-rows>
                                            @foreach($centerRows as $index => $detailRow)
                                                <tr data-report-row>
                                                    <td class="text-center fw-semibold text-muted" data-row-number><input type="hidden" name="rows[{{ $index }}][type]" value="center">{{ $loop->iteration }}</td>
                                                    <td>
                                                        <input class="form-control" type="date" name="rows[{{ $index }}][date]" value="{{ $detailRow['date'] ?? '' }}">
                                                    </td>
                                                    <td>
                                                        <input class="form-control" type="text" name="rows[{{ $index }}][class_name]" value="{{ $detailRow['class_name'] ?? '' }}" placeholder="Ví dụ: THTN.2602 (THCS)">
                                                    </td>
                                                    <td>
                                                        <input class="form-control" type="text" name="rows[{{ $index }}][time_slot]" value="{{ $detailRow['time_slot'] ?? '' }}" placeholder="Ví dụ: 09:00-10:30">
                                                    </td>
                                                    <td>
                                                        <input class="form-control" type="number" step="0.01" min="0" name="rows[{{ $index }}][lesson_count]" value="{{ $detailRow['lesson_count'] ?? '' }}" data-lesson-count>
                                                    </td>
                                                    <td>
                                                        <input class="form-control" type="text" maxlength="1000" name="rows[{{ $index }}][note]" value="{{ $detailRow['note'] ?? '' }}" placeholder="Ghi chú thêm nếu có">
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-danger" type="button" data-remove-row data-no-icon-tooltip>
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4 {{ $trainingRows->isEmpty() ? 'd-none' : '' }}" data-training-section>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <h6 class="mb-0">Giờ dạy Đào tạo</h6>
                                            <small class="text-muted">Khai báo môn học, mã học phần, thời gian giảng dạy và số tiết.</small>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:70px">STT</th>
                                                    <th style="min-width:230px">Môn học</th>
                                                    <th style="min-width:150px">Mã học phần</th>
                                                    <th style="min-width:150px">Từ ngày</th>
                                                    <th style="min-width:150px">Đến ngày</th>
                                                    <th style="min-width:120px">Số tiết</th>
                                                    <th style="min-width:200px">Ghi chú</th>
                                                    <th class="text-end" style="width:80px">Xóa</th>
                                                </tr>
                                            </thead>
                                            <tbody data-training-report-rows>
                                                @foreach($trainingRows as $index => $detailRow)
                                                    <tr data-report-row>
                                                        <td class="text-center fw-semibold text-muted" data-row-number><input type="hidden" name="rows[{{ $index }}][type]" value="training">{{ $loop->iteration }}</td>
                                                        <td><input class="form-control" type="text" maxlength="255" name="rows[{{ $index }}][subject_name]" value="{{ $detailRow['subject_name'] ?? '' }}" placeholder="Tên môn học"></td>
                                                        <td><input class="form-control" type="text" maxlength="100" name="rows[{{ $index }}][course_code]" value="{{ $detailRow['course_code'] ?? '' }}" placeholder="Mã học phần"></td>
                                                        <td><input class="form-control" type="date" name="rows[{{ $index }}][from_date]" value="{{ $detailRow['from_date'] ?? '' }}"></td>
                                                        <td><input class="form-control" type="date" name="rows[{{ $index }}][to_date]" value="{{ $detailRow['to_date'] ?? '' }}"></td>
                                                        <td><input class="form-control" type="number" step="0.01" min="0" name="rows[{{ $index }}][lesson_count]" value="{{ $detailRow['lesson_count'] ?? '' }}" data-lesson-count></td>
                                                        <td><input class="form-control" type="text" maxlength="1000" name="rows[{{ $index }}][note]" value="{{ $detailRow['note'] ?? '' }}" placeholder="Ghi chú thêm nếu có"></td>
                                                        <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row data-no-icon-tooltip><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-outline-primary" type="button" data-add-center-row data-no-icon-tooltip>
                                            <i class="bi bi-plus-circle me-1"></i>Thêm dòng Trung tâm
                                        </button>
                                        <button class="btn btn-outline-success" type="button" data-add-training-row data-no-icon-tooltip>
                                            <i class="bi bi-mortarboard me-1"></i>Thêm dòng Đào tạo
                                        </button>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="small text-muted">Hệ thống tự cộng cột <strong>Số tiết</strong> để lưu tổng tiết dạy của tháng.</div>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-save me-1"></i>Lưu tháng {{ $row['month'] }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template id="teaching-report-row-template">
        <tr data-report-row>
            <td class="text-center fw-semibold text-muted" data-row-number><input type="hidden" name="rows[__INDEX__][type]" value="center">__ROW_NUMBER__</td>
            <td>
                <input class="form-control" type="date" name="rows[__INDEX__][date]">
            </td>
            <td>
                <input class="form-control" type="text" name="rows[__INDEX__][class_name]" placeholder="Ví dụ: THTN.2602 (THCS)">
            </td>
            <td>
                <input class="form-control" type="text" name="rows[__INDEX__][time_slot]" placeholder="Ví dụ: 09:00-10:30">
            </td>
            <td>
                <input class="form-control" type="number" step="0.01" min="0" name="rows[__INDEX__][lesson_count]" data-lesson-count>
            </td>
            <td>
                <input class="form-control" type="text" maxlength="1000" name="rows[__INDEX__][note]" placeholder="Ghi chú thêm nếu có">
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-row data-no-icon-tooltip>
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
    <template id="teaching-training-row-template">
        <tr data-report-row>
            <td class="text-center fw-semibold text-muted" data-row-number><input type="hidden" name="rows[__INDEX__][type]" value="training">__ROW_NUMBER__</td>
            <td><input class="form-control" type="text" maxlength="255" name="rows[__INDEX__][subject_name]" placeholder="Tên môn học"></td>
            <td><input class="form-control" type="text" maxlength="100" name="rows[__INDEX__][course_code]" placeholder="Mã học phần"></td>
            <td><input class="form-control" type="date" name="rows[__INDEX__][from_date]"></td>
            <td><input class="form-control" type="date" name="rows[__INDEX__][to_date]"></td>
            <td><input class="form-control" type="number" step="0.01" min="0" name="rows[__INDEX__][lesson_count]" data-lesson-count></td>
            <td><input class="form-control" type="text" maxlength="1000" name="rows[__INDEX__][note]" placeholder="Ghi chú thêm nếu có"></td>
            <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row data-no-icon-tooltip><i class="bi bi-trash"></i></button></td>
        </tr>
    </template>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const template = document.getElementById('teaching-report-row-template');
    const trainingTemplate = document.getElementById('teaching-training-row-template');
    document.querySelectorAll('[data-add-center-row],[data-add-training-row],[data-remove-row]').forEach(button => {
        button.removeAttribute('title');
        button.removeAttribute('aria-label');
        button.removeAttribute('data-bs-original-title');
        if (window.bootstrap?.Tooltip) {
            bootstrap.Tooltip.getInstance(button)?.dispose();
        }
    });

    const updateMonthTotal = form => {
        const total = [...form.querySelectorAll('[data-lesson-count]')].reduce((sum, input) => {
            const value = parseFloat(input.value || 0);
            return sum + (Number.isFinite(value) ? value : 0);
        }, 0);

        const totalText = total.toLocaleString('vi-VN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const monthKey = form.dataset.monthKey;

        document.querySelectorAll(`[data-month-total-display][data-month-key="${monthKey}"]`).forEach(totalBox => {
            totalBox.textContent = totalText;
        });
    };

    const nextRowIndex = form => {
        const indexes = [...form.querySelectorAll('[data-report-row] input[name^="rows["]')]
            .map(input => {
                const match = input.name.match(/^rows\[(\d+)\]/);
                return match ? parseInt(match[1], 10) : -1;
            })
            .filter(index => index >= 0);

        return indexes.length ? Math.max(...indexes) + 1 : 0;
    };

    const renumberRows = form => {
        form.querySelectorAll('[data-report-rows],[data-training-report-rows]').forEach(tbody => {
            tbody.querySelectorAll('[data-report-row]').forEach((row, index) => {
                const numberBox = row.querySelector('[data-row-number]');
                if (numberBox) {
                    const hiddenInput = numberBox.querySelector('input[type="hidden"]');
                    numberBox.textContent = String(index + 1);
                    if (hiddenInput) {
                        numberBox.prepend(hiddenInput);
                    }
                }
            });
        });
    };

    const addRow = (form, type, values = {}) => {
        const tbody = form.querySelector(type === 'training' ? '[data-training-report-rows]' : '[data-report-rows]');
        const rowTemplate = type === 'training' ? trainingTemplate : template;
        if (!tbody || !rowTemplate) {
            return;
        }

        if (type === 'training') {
            form.querySelector('[data-training-section]')?.classList.remove('d-none');
        }

        const nextIndex = nextRowIndex(form);
        const nextRowNumber = tbody.querySelectorAll('[data-report-row]').length + 1;
        tbody.insertAdjacentHTML(
            'beforeend',
            rowTemplate.innerHTML
                .replaceAll('__INDEX__', String(nextIndex))
                .replaceAll('__ROW_NUMBER__', String(nextRowNumber))
        );
        const row = tbody.lastElementChild;
        Object.entries(values).forEach(([field, value]) => {
            const input = row?.querySelector(`[name$="[${field}]"]`);
            if (input) {
                input.value = value;
            }
        });
        renumberRows(form);
        updateMonthTotal(form);
    };

    document.querySelectorAll('[data-teaching-report-form]').forEach(form => {
        renumberRows(form);
        updateMonthTotal(form);

        form.addEventListener('input', event => {
            if (event.target.matches('[data-lesson-count]')) {
                updateMonthTotal(form);
            }
        });

        form.addEventListener('click', event => {
            const centerAddButton = event.target.closest('[data-add-center-row]');
            const trainingAddButton = event.target.closest('[data-add-training-row]');
            if (centerAddButton || trainingAddButton) {
                event.preventDefault();
                addRow(form, trainingAddButton ? 'training' : 'center');
                return;
            }

            const removeButton = event.target.closest('[data-remove-row]');
            if (!removeButton) {
                return;
            }

            event.preventDefault();
            const row = removeButton.closest('[data-report-row]');
            const trainingRows = row?.closest('[data-training-report-rows]');
            row?.remove();
            if (trainingRows && !trainingRows.querySelector('[data-report-row]')) {
                form.querySelector('[data-training-section]')?.classList.add('d-none');
            }
            renumberRows(form);
            updateMonthTotal(form);
        });
    });

    @if(old('report_month'))
    const requestedModal = document.getElementById(@json('teaching-report-modal-'.old('report_month')));
    if (requestedModal) {
        bootstrap.Modal.getOrCreateInstance(requestedModal).show();
    }
    @endif
});
</script>
@endpush
