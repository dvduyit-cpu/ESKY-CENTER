@extends('layouts.app')
@section('title',$user->exists?'Sửa tài khoản':'Tạo tài khoản')
@section('header','Quản trị tài khoản')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">{{ $user->exists?'Cập nhật tài khoản':'Tạo tài khoản mới' }}</h1><div class="page-subtitle">Liên kết account với nhân sự và cộng tác viên của module Tư vấn.</div></div>
    <a href="{{ route('users.index') }}" class="btn btn-light">Quay lại</a>
</div>
<div class="card card-soft form-card">
    <div class="card-header"><h5><i class="bi bi-person-gear me-2"></i>Thông tin tài khoản</h5></div>
    <div class="card-body p-4">
        <form method="POST" action="{{ $user->exists?route('users.update',$user):route('users.store') }}">
            @csrf @if($user->exists)@method('PUT')@endif
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label required-label">Họ tên</label><input class="form-control" name="name" value="{{ old('name',$user->name) }}" required></div>
                <div class="col-md-6"><label class="form-label required-label">Email đăng nhập</label><input class="form-control" type="email" name="email" value="{{ old('email',$user->email ?: '@bdu.edu.vn') }}" required><div class="form-text">Mặc định dùng đuôi @bdu.edu.vn; có thể xóa để nhập tên miền email khác.</div></div>
                <div class="col-md-6"><label class="form-label required-label">Vai trò</label><select class="form-select" name="role_id" required data-account-role>@foreach($roles as $role)<option value="{{ $role->id }}" data-role-code="{{$role->code}}" @selected((string)old('role_id',$user->role_id)===(string)$role->id)>{{ $role->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Hồ sơ nhân sự</label><select class="form-select" name="personnel_id" data-searchable-select data-search-placeholder="Tìm hồ sơ nhân sự..."><option value="">Không liên kết</option>@foreach($personnels as $personnel)<option value="{{ $personnel->id }}" @selected((string)old('personnel_id',$user->personnel_id)===(string)$personnel->id)>{{ $personnel->name }} – {{ $personnel->typeLabel() }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Cộng tác viên liên kết – module Tư vấn</label><select class="form-select" name="language_collaborator_id" data-searchable-select data-search-placeholder="Tìm mã, họ tên hoặc điện thoại CTV..."><option value="">Không liên kết cộng tác viên</option>@foreach($collaborators as $collaborator)<option value="{{ $collaborator->id }}" @selected((string)old('language_collaborator_id',$user->language_collaborator_id)===(string)$collaborator->id)>{{ $collaborator->code }} · {{ $collaborator->name }} · {{ $collaborator->phone?:'Chưa có SĐT' }}</option>@endforeach</select><div class="form-text">Khi account gửi chỉ tiêu, khách hàng sẽ tự ghi nhận CTV được chọn tại đây.</div></div>
                @unless($user->exists)
                    <div class="col-md-6"><label class="form-label required-label">Mật khẩu</label><input class="form-control" type="password" name="password" value="{{ old('password','Esky123@') }}" minlength="8" required><div class="form-text">Mật khẩu mặc định: Esky123@</div></div>
                    <div class="col-md-6"><label class="form-label required-label">Nhập lại mật khẩu</label><input class="form-control" type="password" name="password_confirmation" value="{{ old('password_confirmation','Esky123@') }}" minlength="8" required></div>
                @endunless
                <div class="col-md-3"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active',$user->exists?$user->active:true))><label class="form-check-label" for="active">Tài khoản hoạt động</label></div></div>
                <div class="col-md-3"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="is_registrar" value="1" id="registrar" data-registrar-checkbox @checked(old('is_registrar',$user->is_registrar))><label class="form-check-label fw-semibold" for="registrar">Giáo vụ</label><div class="form-text">Quản lý học viên, xếp/chuyển lớp và thu học phí.</div></div></div>
                <div class="col-md-3"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="is_instructor" value="1" id="instructor" data-instructor-checkbox @checked(old('is_instructor',$user->is_instructor))><label class="form-check-label fw-semibold" for="instructor">Kiêm giảng dạy</label><div class="form-text">Có thể được phân công lớp và dùng sổ điểm như giáo viên.</div></div></div>
                <div class="col-md-3"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="must_change_password" value="1" id="force" @checked(old('must_change_password',$user->exists?$user->must_change_password:true))><label class="form-check-label" for="force">Yêu cầu đổi mật khẩu khi đăng nhập</label></div></div>
            </div>
            <div class="form-actions"><a class="btn btn-light" href="{{ route('users.index') }}">Hủy</a><button class="btn btn-primary"><i class="bi bi-save"></i>Lưu tài khoản</button></div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const role=document.querySelector('[data-account-role]');
    const registrar=document.querySelector('[data-registrar-checkbox]');
    const instructor=document.querySelector('[data-instructor-checkbox]');
    if(!role||!registrar||!instructor)return;
    const sync=()=>{
        const code=role.selectedOptions[0]?.dataset.roleCode;
        registrar.disabled=code==='teacher'||code==='admin';
        if(code==='teacher')registrar.checked=false;
        if(code==='admin')registrar.checked=true;
        instructor.disabled=code==='teacher';
        if(code==='teacher')instructor.checked=true;
    };
    role.addEventListener('change',sync);
    sync();
});
</script>
@endpush
@endsection
