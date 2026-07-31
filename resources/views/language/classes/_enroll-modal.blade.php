@php
    $modalId=$modalId??'enrollStudentsModal';
    $studentRows=$students??collect();
    $selectedIds=collect(old('language_student_ids',[]))->map(fn($id)=>(string)$id);
@endphp
<div class="modal fade" id="{{$modalId}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0 rounded-4" data-enrollment-picker>
        <form method="POST" action="{{$enrollmentAction}}" class="d-flex flex-column flex-grow-1" style="min-height:0">@csrf
            <div class="modal-header"><div><h5 class="modal-title">Thêm học viên vào lớp</h5><div class="small text-muted">Chọn một hoặc nhiều học viên rồi thêm cùng lúc.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 d-flex flex-column" style="min-height:0;overflow:hidden">
                <div class="row g-3 mb-3">
                    <div class="col-md-8"><label class="form-label">Tìm học viên</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" type="search" placeholder="Tên, mã, SĐT học viên hoặc phụ huynh..." data-student-search autocomplete="off"></div></div>
                    <div class="col-md-4"><label class="form-label">Ngày vào lớp</label><input class="form-control" type="date" name="enrolled_at" value="{{old('enrolled_at',now()->format('Y-m-d'))}}" required></div>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div><strong data-selected-count>0</strong> học viên đã chọn <span class="text-muted">· <span data-visible-count>{{$studentRows->count()}}</span> kết quả</span></div>
                    <div class="d-flex gap-2"><button class="btn btn-sm btn-outline-primary" type="button" data-select-visible>Chọn kết quả đang hiện</button><button class="btn btn-sm btn-outline-secondary" type="button" data-clear-selection title="Bỏ chọn" aria-label="Bỏ chọn"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                </div>
                <div class="border rounded-3 flex-grow-1" data-student-picker-list style="min-height:120px;max-height:430px;overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch">
                    @forelse($studentRows as $student)
                        @php($guardianPhones=$student->guardians->pluck('phone')->filter()->implode(' '))
                        <label class="d-flex gap-3 align-items-start p-3 border-bottom student-picker-row" data-student-row data-search="{{$student->code}} {{$student->name}} {{$student->phone}} {{$guardianPhones}}">
                            <input class="form-check-input mt-1" type="checkbox" name="language_student_ids[]" value="{{$student->id}}" data-student-checkbox @checked($selectedIds->contains((string)$student->id))>
                            <span class="flex-grow-1"><strong>{{$student->name}}</strong><span class="badge bg-light text-dark border ms-2">{{$student->code}}</span><span class="d-block small text-muted mt-1">SĐT: {{$student->phone?:'—'}}@if($guardianPhones) · Phụ huynh: {{$student->guardians->pluck('phone')->filter()->implode(', ')}}@endif</span></span>
                        </label>
                    @empty
                        <div class="empty-state">Không còn học viên phù hợp để thêm vào lớp.</div>
                    @endforelse
                    <div class="empty-state d-none" data-no-search-results>Không tìm thấy học viên phù hợp.</div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" type="submit" data-submit-selected disabled><i class="bi bi-person-plus me-2"></i>Thêm học viên đã chọn</button></div>
        </form>
    </div></div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const root=document.querySelector('#{{$modalId}} [data-enrollment-picker]');
    if(!root)return;
    const rows=[...root.querySelectorAll('[data-student-row]')];
    const checks=[...root.querySelectorAll('[data-student-checkbox]')];
    const search=root.querySelector('[data-student-search]');
    const normalize=value=>(value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
    const refresh=()=>{
        const term=normalize(search.value);
        let visible=0;
        rows.forEach(row=>{const show=!term||normalize(row.dataset.search).includes(term);row.classList.toggle('d-none',!show);if(show)visible++;});
        const selected=checks.filter(check=>check.checked).length;
        root.querySelector('[data-selected-count]').textContent=selected;
        root.querySelector('[data-visible-count]').textContent=visible;
        root.querySelector('[data-submit-selected]').disabled=selected===0;
        root.querySelector('[data-no-search-results]').classList.toggle('d-none',visible!==0||rows.length===0);
    };
    search.addEventListener('input',refresh);
    checks.forEach(check=>check.addEventListener('change',refresh));
    root.querySelector('[data-select-visible]').addEventListener('click',()=>{rows.filter(row=>!row.classList.contains('d-none')).forEach(row=>row.querySelector('[data-student-checkbox]').checked=true);refresh();});
    root.querySelector('[data-clear-selection]').addEventListener('click',()=>{checks.forEach(check=>check.checked=false);refresh();});
    refresh();
    @if($errors->has('language_student_ids')||$errors->has('language_student_ids.*')||$errors->has('enrolled_at'))
        bootstrap.Modal.getOrCreateInstance(document.getElementById('{{$modalId}}')).show();
    @endif
});
</script>
@endpush
