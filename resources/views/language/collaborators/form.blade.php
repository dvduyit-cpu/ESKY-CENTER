@extends('layouts.app')
@section('title',$item->exists?'Sửa CTV':'Thêm CTV')
@section('header','Tư vấn tuyển sinh')
@section('content')
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-title">{{$item->exists?'Cập nhật':'Thêm'}} cộng tác viên</h1><div class="page-subtitle">Có thể chọn account có sẵn hoặc nhập mới cộng tác viên không có account.</div></div><a class="btn btn-light" href="{{route('language-collaborators.index')}}">Quay lại</a></div>
<div class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-person-plus me-2"></i>Thông tin cộng tác viên</h5></div><div class="card-body p-4">
<form method="POST" action="{{$item->exists?route('language-collaborators.update',$item):route('language-collaborators.store')}}" data-collaborator-form>@csrf @if($item->exists)@method('PUT')@endif
<div class="row g-3">
    <div class="col-12"><label class="form-label">Chọn từ account</label><select class="form-select" name="user_id" data-account-select data-searchable-select data-search-placeholder="Tìm tên hoặc email account..."><option value="">Không chọn – thêm CTV mới thủ công</option>@foreach($users as $user)<option value="{{$user->id}}" data-name="{{$user->personnel?->name?:$user->name}}" data-phone="{{$user->personnel?->phone}}" data-email="{{$user->personnel?->email?:$user->email}}" @selected((string)old('user_id',$linkedUserId)===(string)$user->id)>{{$user->name}} · {{$user->email}}</option>@endforeach</select><div class="form-text">Chọn account sẽ tự điền thông tin; bạn vẫn có thể điều chỉnh trước khi lưu.</div></div>
    @if($item->exists)<div class="col-md-3"><label class="form-label">Mã CTV</label><input class="form-control" value="{{$item->code}}" disabled></div>@endif
    <div class="col-md-{{$item->exists?5:6}}"><label class="form-label required-label">Họ tên</label><input class="form-control" name="name" value="{{old('name',$item->name)}}" required></div>
    <div class="col-md-4"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" value="{{old('phone',$item->phone)}}"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{old('email',$item->email)}}"></div>
    <div class="col-md-4"><label class="form-label required-label">Hoa hồng (%)</label><input class="form-control" type="number" min="0" max="100" step="0.01" name="commission_rate" value="{{old('commission_rate',$item->commission_rate?:0)}}" required></div>
    <div class="col-md-8"><label class="form-label">Địa chỉ</label><input class="form-control" name="address" value="{{old('address',$item->address)}}"></div>
    <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note">{{old('note',$item->note)}}</textarea></div>
    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active',$item->exists?$item->active:true))><label class="form-check-label" for="active">Đang hoạt động</label></div></div>
</div><div class="form-actions"><a class="btn btn-light" href="{{route('language-collaborators.index')}}">Hủy</a><button class="btn btn-primary"><i class="bi bi-save"></i>Lưu CTV</button></div></form>
</div></div>
@endsection
@push('scripts')<script>document.querySelectorAll('[data-collaborator-form]').forEach(form=>{const select=form.querySelector('[data-account-select]');select.addEventListener('change',()=>{const option=select.selectedOptions[0];if(!option?.value)return;form.querySelector('[name="name"]').value=option.dataset.name||'';form.querySelector('[name="phone"]').value=option.dataset.phone||'';form.querySelector('[name="email"]').value=option.dataset.email||''})});</script>@endpush
