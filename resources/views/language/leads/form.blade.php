@extends('layouts.app')
@section('title',$item->exists?'Sửa khách hàng':'Thêm khách hàng') @section('header','Tư vấn tuyển sinh')
@section('content')
@php($labels=['new'=>'Mới tiếp nhận','contacted'=>'Đã liên hệ','consulting'=>'Đang tư vấn','placement_test'=>'Hẹn kiểm tra','waiting'=>'Chờ phản hồi','registered'=>'Đã đăng ký','not_interested'=>'Không quan tâm','follow_up'=>'Chăm sóc lại'])
@php($selectedCollaborator=old('language_collaborator_id',session('selected_collaborator',$item->language_collaborator_id)))
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-title">{{$item->exists?'Cập nhật':'Thêm'}} khách hàng</h1><div class="page-subtitle">Mã khách hàng tự động. Các trường có dấu <span class="text-danger">*</span> là bắt buộc.</div></div><a class="btn btn-light" href="{{route('language-leads.index')}}">Quay lại</a></div>
<div class="card card-soft form-card"><div class="card-header"><h5>Thông tin khách hàng</h5></div><div class="card-body p-4"><form method="POST" action="{{$item->exists?route('language-leads.update',$item):route('language-leads.store')}}">@csrf @if($item->exists)@method('PUT')@endif<div class="row g-3">
@if($item->exists)<div class="col-md-4"><label class="form-label">Mã khách hàng</label><input class="form-control" value="{{$item->code}}" disabled></div>@endif
<div class="col-md-{{$item->exists?8:6}}"><label class="form-label required-label">Họ tên</label><input class="form-control" name="name" value="{{old('name',$item->name)}}" required></div>
<div class="col-md-4"><label class="form-label">Ngày sinh</label><input class="form-control" type="date" name="date_of_birth" value="{{old('date_of_birth',$item->date_of_birth?->format('Y-m-d'))}}"></div><div class="col-md-4"><label class="form-label required-label">Điện thoại</label><input class="form-control" name="phone" value="{{old('phone',$item->phone)}}" required></div><div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{old('email',$item->email)}}"></div><div class="col-md-4"><label class="form-label">Zalo</label><input class="form-control" name="zalo" value="{{old('zalo',$item->zalo)}}"></div><div class="col-md-4"><label class="form-label">Nguồn khách hàng</label><input class="form-control" name="source" value="{{old('source',$item->source)}}"></div><div class="col-md-4"><label class="form-label required-label">Ngày nhập vào</label><input class="form-control" type="date" name="received_at" value="{{old('received_at',$item->received_at?->format('Y-m-d')?:date('Y-m-d'))}}" required></div>
<div class="col-md-6"><label class="form-label required-label">Khóa học quan tâm</label><select class="form-select" name="language_course_id" required data-searchable-select data-search-placeholder="Tìm tên khóa học, giáo trình..."><option value="">Chọn khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected(old('language_course_id',$item->language_course_id)==$course->id)>{{$course->name}} · {{$course->textbook?:'Chưa có giáo trình'}} · {{number_format($course->tuition)}}đ</option>@endforeach</select></div>
<div class="col-md-6"><div class="d-flex justify-content-between align-items-center"><label class="form-label required-label">Cộng tác viên giới thiệu</label>@if(auth()->user()->allowed('language_collaborators','create'))<button class="btn btn-sm btn-outline-success mb-1" type="button" data-bs-toggle="modal" data-bs-target="#quickCollaboratorModal" title="Thêm cộng tác viên"><i class="bi bi-plus-lg"></i></button>@endif</div><select class="form-select" name="language_collaborator_id" required data-searchable-select data-search-placeholder="Tìm mã, họ tên hoặc điện thoại CTV..."><option value="">Chọn cộng tác viên</option>@foreach($collaborators as $collaborator)<option value="{{$collaborator->id}}" @selected($selectedCollaborator==$collaborator->id)>{{$collaborator->code}} · {{$collaborator->name}} · {{$collaborator->phone?:'Chưa có SĐT'}}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Tư vấn viên</label><select class="form-select" name="consultant_user_id"><option value="">Chưa phân công</option>@foreach($users as $user)<option value="{{$user->id}}" @selected(old('consultant_user_id',$item->consultant_user_id)==$user->id)>{{$user->name}}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label required-label">Trạng thái</label><select class="form-select" name="status" required>@foreach($labels as $key=>$label)<option value="{{$key}}" @selected(old('status',$item->status?:'new')===$key)>{{$label}}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Lịch hẹn</label><input class="form-control" type="datetime-local" name="appointment_at" value="{{old('appointment_at',$item->appointment_at?->format('Y-m-d\TH:i'))}}"></div><div class="col-12"><label class="form-label">Nội dung tư vấn</label><textarea class="form-control" name="consultation">{{old('consultation',$item->consultation)}}</textarea></div><div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note">{{old('note',$item->note)}}</textarea></div>
</div><div class="form-actions"><a class="btn btn-light" href="{{route('language-leads.index')}}">Hủy</a><button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu khách hàng</button></div></form></div></div>
@if(auth()->user()->allowed('language_collaborators','create'))<div class="modal fade" id="quickCollaboratorModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Thêm cộng tác viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="{{route('language-collaborators.store')}}">@csrf<input type="hidden" name="quick_create" value="1"><input type="hidden" name="active" value="1"><div class="modal-body"><div class="mb-3"><label class="form-label required-label">Họ tên CTV</label><input class="form-control" name="name" required></div><div class="row g-3"><div class="col-6"><label class="form-label">Điện thoại</label><input class="form-control" name="phone"></div><div class="col-6"><label class="form-label">Hoa hồng (%)</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="commission_rate" value="0" required></div></div><div class="mt-3"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu CTV</button></div></form></div></div></div>@endif
@endsection
@push('scripts')
<script>
const quickCollaboratorModal=document.getElementById('quickCollaboratorModal');
if(quickCollaboratorModal){
    const quickForm=quickCollaboratorModal.querySelector('form');
    quickForm.addEventListener('submit',async(event)=>{
        event.preventDefault();
        const button=quickForm.querySelector('button[type="submit"],button:not([type])');
        button.disabled=true;
        try{
            const response=await fetch(quickForm.action,{method:'POST',headers:{'Accept':'application/json'},body:new FormData(quickForm)});
            const data=await response.json();
            if(!response.ok)throw new Error(Object.values(data.errors||{}).flat()[0]||'Không thể thêm cộng tác viên.');
            const select=document.querySelector('select[name="language_collaborator_id"]');
            select.add(new Option(data.label,data.id,true,true));
            select.dispatchEvent(new Event('searchable-select:refresh'));
            select.dispatchEvent(new Event('change',{bubbles:true}));
            bootstrap.Modal.getOrCreateInstance(quickCollaboratorModal).hide();
            quickForm.reset();
            window.alert(data.message);
        }catch(error){window.alert(error.message)}finally{button.disabled=false}
    });
}
</script>
@endpush
