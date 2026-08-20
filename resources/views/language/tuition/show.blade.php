@extends('layouts.app')

@section('title', 'Chi tiết thu học phí')
@section('header', 'Quản lý học viên')

@section('content')
@php($labels = ['unpaid' => 'Chưa đóng', 'partial' => 'Đóng một phần', 'pending_receipt' => 'Chờ bổ sung phiếu thu', 'paid' => 'Đã đóng đủ', 'transferred' => 'Đã quyết toán chuyển lớp'])
@php($remaining = $item->remainingAmount())
@php($transferContent = \Illuminate\Support\Str::limit(trim(\Illuminate\Support\Str::ascii($item->student->name.' '.($item->languageClass?->code ?? 'CHUA XEP LOP'))), 50, ''))

<div class="tuition-detail-page">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4 page-toolbar">
        <div>
            <h1 class="page-title">Chi tiết khoản thu {{ $item->code }}</h1>
            <div class="page-subtitle">{{ $item->student->name }} · {{ $item->course->name }}</div>
        </div>
        <a class="btn btn-light" href="{{ route('language-tuition.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Danh sách khoản thu
        </a>
    </div>

    @if(session('receipt_ready'))
        @php($readyPayment = $item->payments->firstWhere('id', (int) session('receipt_ready')))
        @if($readyPayment)
            <div class="alert alert-success receipt-ready-panel">
                <div>
                    <strong><i class="bi bi-check-circle-fill me-2"></i>Phiếu thu {{ $readyPayment->receipt_code ?: 'tạm - số phiếu cập nhật sau' }} đã sẵn sàng</strong>
                    <div class="small mt-1">Bạn có thể tải PDF hoặc in trực tiếp trên giấy A5.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" target="_blank" href="{{ route('language-tuition.receipt.print', [$readyPayment, 'preview' => 1]) }}">
                        <i class="bi bi-eye me-1"></i>Xem hóa đơn
                    </a>
                    <a class="btn btn-success" data-no-loading download href="{{ route('language-tuition.receipt.pdf', $readyPayment) }}">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Tải PDF
                    </a>
                    <a class="btn btn-primary" target="_blank" href="{{ route('language-tuition.receipt.print', $readyPayment) }}">
                        <i class="bi bi-printer me-1"></i>In A5 ngang
                    </a>
                </div>
            </div>
        @endif
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-xl-7">
            <div class="card card-soft mb-4">
                <div class="card-header"><h5>Thông tin học phí</h5></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Học viên</div>
                            <strong>{{ $item->student->code }} – {{ $item->student->name }}</strong>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Khóa học / lớp</div>
                            <strong>{{ $item->course->name }} / {{ $item->languageClass?->code ?: 'Chưa xếp lớp' }}</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Học phí</div>
                            <strong>{{ number_format($item->original_amount) }}đ</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Miễn giảm áp dụng</div>
                            <strong>{{ \App\Support\ValueFormatter::percentage($item->discount_percentage) }}%</strong>
                            <div class="small text-muted">{{ $item->discount?->name ?: 'Không miễn giảm' }} · mức cao nhất</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Đã thu</div>
                            <strong class="text-success">{{ number_format($item->paid_amount) }}đ</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Học phí chuyển sang</div>
                            <strong class="text-primary">{{ number_format($item->credit_amount) }}đ</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Còn lại</div>
                            <strong class="text-danger">{{ number_format($remaining) }}đ</strong>
                        </div>
                        <div class="col-12">
                            <span class="badge-soft {{ in_array($item->status, ['paid', 'transferred']) ? 'badge-success' : (in_array($item->status, ['partial', 'pending_receipt']) ? 'badge-warning' : 'badge-danger') }}">
                                {{ $labels[$item->status] ?? $item->status }}
                            </span>
                        </div>

                        @foreach($item->incomingTransfers as $transfer)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Nhận <strong>{{ number_format($transfer->applied_amount) }}đ</strong>
                                    từ lớp {{ $transfer->fromClass?->code }} ngày {{ $transfer->effective_date->format('d/m/Y') }}.
                                </div>
                            </div>
                        @endforeach

                        @foreach($item->outgoingTransfers as $transfer)
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    Đã chuyển <strong>{{ number_format($transfer->applied_amount) }}đ</strong> sang lớp {{ $transfer->toClass?->code }}.
                                    @if((float) $transfer->surplus_amount > 0)
                                        Còn <strong>{{ number_format($transfer->surplus_amount) }}đ</strong> chờ hoàn hoặc bảo lưu.
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-header"><h5>Lịch sử phiếu thu</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Ngày thu</th>
                                    <th>Số phiếu</th>
                                    <th>Số tiền</th>
                                    <th>Hình thức</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Phiếu thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($item->payments->sortByDesc('paid_at') as $payment)
                                    <tr>
                                        <td>{{ $payment->paid_at?->format('d/m/Y H:i') }}</td>
                                        <td>{{ $payment->receipt_code ?: 'Cập nhật sau' }}</td>
                                        <td>
                                            <strong>{{ number_format((float) $payment->amount + (float) $payment->book_amount) }}đ</strong>
                                            @if((float) $payment->book_amount > 0)
                                                <div class="small text-muted">Học phí {{ number_format($payment->amount) }}đ · Sách {{ number_format($payment->book_amount) }}đ</div>
                                            @endif
                                            @if($payment->note)
                                                <div class="small text-muted text-truncate" style="max-width:220px" title="{{ $payment->note }}">{{ $payment->note }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $payment->payment_method }}</td>
                                        <td>{{ $payment->receipt_status === 'confirmed' ? 'Đã xác nhận' : 'Chờ phiếu' }}</td>
                                        <td class="text-end text-nowrap">
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('language-tuition.receipt.print', [$payment, 'preview' => 1]) }}" title="Xem hóa đơn"><i class="bi bi-eye"></i></a>
                                            <a class="btn btn-sm btn-outline-success" data-no-loading download href="{{ route('language-tuition.receipt.pdf', $payment) }}" title="Tải PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('language-tuition.receipt.print', $payment) }}" title="In A5"><i class="bi bi-printer"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Chưa có lần thu tiền.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 tuition-detail-side">
            @if($bank['enabled'])
                <div class="tuition-bank-card mb-4">
                    <div class="tuition-bank-head">
                        <span class="tuition-bank-icon"><i class="bi bi-bank2"></i></span>
                        <div>
                            <span>Tài khoản nhận học phí</span>
                            <strong>{{ $bank['name'] }}</strong>
                        </div>
                    </div>
                    <div class="tuition-bank-number">
                        <span>Số tài khoản</span>
                        <strong>{{ $bank['account_number'] }}</strong>
                    </div>
                    <div class="tuition-bank-details">
                        <div>
                            <span>Chủ tài khoản</span>
                            <strong>{{ $bank['account_name'] }}</strong>
                        </div>
                        @if($bank['branch'])
                            <div>
                                <span>Chi nhánh</span>
                                <strong>{{ $bank['branch'] }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-warning mb-4">
                    <strong>Chưa cấu hình tài khoản ngân hàng.</strong>
                    <div class="small mt-1">Vào Cấu hình phần mềm để bật nhận học phí qua chuyển khoản.</div>
                </div>
            @endif

            @if(auth()->user()->allowed('language_tuition', 'update') && !$item->outgoingTransfers->count())
                <form method="POST" action="{{ route('language-tuition.discount.update', $item) }}" class="card card-soft mb-4">
                    @csrf
                    @method('PATCH')
                    <div class="card-header"><h5>Chế độ miễn giảm</h5></div>
                    <div class="card-body p-4">
                        <div class="alert alert-light border small">
                            Miễn giảm của lớp {{ $item->languageClass?->code }}:
                            <strong>
                                {{ $item->languageClass?->discountPolicy?->name ?: 'Không có' }}
                                @if($item->languageClass?->discountPolicy)
                                    – {{ \App\Support\ValueFormatter::percentage($item->languageClass->discountPolicy->percentage) }}%
                                @endif
                            </strong>
                        </div>
                        <label class="form-label">Miễn giảm riêng của học viên</label>
                        <select class="form-select @error('language_discount_policy_id') is-invalid @enderror" name="language_discount_policy_id">
                            <option value="">Không miễn giảm riêng</option>
                            @foreach($discounts as $discount)
                                <option value="{{ $discount->id }}" @selected((int) old('language_discount_policy_id', $item->student?->language_discount_policy_id) === $discount->id)>
                                    {{ $discount->name }} – giảm {{ \App\Support\ValueFormatter::percentage($discount->percentage) }}%
                                </option>
                            @endforeach
                        </select>
                        @error('language_discount_policy_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Hệ thống so sánh mức của lớp và mức riêng của học viên, chỉ lấy phần trăm cao hơn và không cộng dồn.</div>
                        <button class="btn btn-outline-primary w-100 mt-3" data-confirm="So sánh và áp dụng mức miễn giảm cao nhất?">
                            <i class="bi bi-percent me-1"></i>Áp dụng mức cao nhất
                        </button>
                    </div>
                </form>
            @endif

            @foreach($item->payments->where('receipt_status', 'pending') as $pendingPayment)
                <form method="POST" action="{{ route('language-tuition.confirm-receipt', $pendingPayment) }}" class="card card-soft mb-4">
                    @csrf
                    <div class="card-body p-4">
                        <h6 class="text-warning fw-bold">Bổ sung phiếu thu</h6>
                        <div class="small text-muted mb-3">Đã nhận {{ number_format((float) $pendingPayment->amount + (float) $pendingPayment->book_amount) }}đ ngày {{ $pendingPayment->paid_at?->format('d/m/Y H:i') }}</div>
                        <input class="form-control mb-3" name="receipt_code" placeholder="Nhập số phiếu thu" required>
                        <button class="btn btn-warning w-100" data-confirm="Xác nhận bổ sung số phiếu thu này?">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận phiếu
                        </button>
                    </div>
                </form>
            @endforeach

            @if($remaining > 0)
                <div class="card card-soft">
                    <div class="card-header"><h5>Ghi nhận tiền</h5></div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('language-tuition.pay', $item) }}" data-tuition-payment data-qr-content="{{ $transferContent }}" data-qr-download="{{ route('language-tuition.qr-download', $item) }}" @if($bank['enabled']) data-qr-base="https://img.vietqr.io/image/{{ $bank['bin'] }}-{{ $bank['account_number'] }}-compact2.png" data-bank-account-name="{{ $bank['account_name'] }}" @endif>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Số phiếu thu <span class="text-muted">(có thể bổ sung sau)</span></label>
                                <input class="form-control" name="receipt_code" placeholder="Để trống nếu cập nhật sau">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tiền học phí</label>
                                    <input class="form-control" type="number" name="amount" min="1" max="{{ $remaining }}" value="{{ $remaining }}" required data-qr-amount>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tiền sách</label>
                                    <input class="form-control" type="number" name="book_amount" min="0" value="0" data-book-amount>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ngày thu</label>
                                    <input class="form-control" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Hình thức</label>
                                <select class="form-select" name="payment_method" data-payment-method>
                                    <option value="cash">Tiền mặt</option>
                                    @if($bank['enabled'])
                                        <option value="transfer">Chuyển khoản</option>
                                    @endif
                                    <option value="card">Thẻ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Ghi chú giao dịch</label>
                                <textarea class="form-control" name="note" rows="2" maxlength="1000" placeholder="Ví dụ: Thu học phí tháng 7 và tiền giáo trình..."></textarea>
                            </div>
                            <div class="mt-3 p-3 rounded bg-light border d-none text-center" data-qr-box>
                                <img class="img-fluid rounded bg-white" style="max-height:320px" alt="VietQR" data-qr-image>
                                <div class="small mt-2">Nội dung: <strong>{{ $transferContent }}</strong></div>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button class="btn btn-primary btn-sm" type="button" data-create-qr>Tạo/cập nhật QR</button>
                                    <a class="btn btn-outline-success btn-sm" href="#" data-download-qr><i class="bi bi-download me-1"></i>Lưu QR</a>
                                </div>
                            </div>
                            <button class="btn btn-success w-100 mt-3" data-confirm="Xác nhận ghi nhận khoản tiền này?">
                                <i class="bi bi-receipt me-1"></i>Ghi nhận tiền
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($item->status === 'paid')
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Khoản thu đã hoàn tất và đủ phiếu thu.</div>
            @else
                <div class="alert alert-warning"><i class="bi bi-hourglass-split me-2"></i>Đã nhận đủ tiền, đang chờ bổ sung phiếu thu.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-tuition-payment]').forEach(function (form) {
    const method = form.querySelector('[name="payment_method"]');
    const amount = form.querySelector('[name="amount"]');
    const bookAmount = form.querySelector('[data-book-amount]');
    const box = form.querySelector('[data-qr-box]');
    const image = form.querySelector('[data-qr-image]');
    const createButton = form.querySelector('[data-create-qr]');
    const downloadLink = form.querySelector('[data-download-qr]');
    if (!method || !amount || !box || !image) return;

    const updateQr = function () {
        const base = form.dataset.qrBase;
        const total = Number(amount.value || 0) + Number(bookAmount?.value || 0);
        const show = method.value === 'transfer' && total > 0 && base;
        box.classList.toggle('d-none', !show);
        if (!show) {
            image.removeAttribute('src');
            return;
        }

        const params = new URLSearchParams({
            amount: Math.round(total),
            addInfo: form.dataset.qrContent,
            accountName: form.dataset.bankAccountName || '',
        });
        image.src = base + '?' + params.toString();
        if (downloadLink) {
            downloadLink.href = form.dataset.qrDownload
                + '?amount=' + encodeURIComponent(Math.round(Number(amount.value)))
                + '&book_amount=' + encodeURIComponent(Math.round(Number(bookAmount?.value || 0)));
        }
    };

    if (createButton) createButton.addEventListener('click', updateQr);
    method.addEventListener('change', updateQr);
    amount.addEventListener('input', updateQr);
    if (bookAmount) bookAmount.addEventListener('input', updateQr);
    updateQr();
});
</script>
@endpush
