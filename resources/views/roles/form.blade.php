@extends('layouts.app')
@section('title',$role->exists?'Cấu hình vai trò':'Thêm vai trò')
@section('header','Vai trò và phân quyền')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">{{ $role->exists?'Cấu hình vai trò '.$role->name:'Tạo vai trò mới' }}</h1><div class="page-subtitle">Quyền Xem, Thêm, Sửa, Xóa và Xuất file theo từng module.</div></div>
    <a href="{{ route('roles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-2"></i>Quay lại</a>
</div>
<form method="POST" action="{{ $role->exists?route('roles.update',$role):route('roles.store') }}" data-permission-table>
    @csrf @if($role->exists)@method('PUT')@endif
    <div class="card card-soft form-card mb-4"><div class="card-header"><h5><i class="bi bi-person-badge me-2"></i>Thông tin vai trò</h5></div><div class="card-body p-4"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Mã vai trò</label><input class="form-control" name="code" value="{{ old('code',$role->code) }}" @readonly($role->is_system) required></div>
        <div class="col-md-4"><label class="form-label">Tên vai trò</label><input class="form-control" name="name" value="{{ old('name',$role->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Mô tả</label><input class="form-control" name="description" value="{{ old('description',$role->description) }}"></div>
    </div></div></div>
    <div class="card card-soft permission-card">
        <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1 fw-bold">Bảng phân quyền</h5><small class="text-muted">Có thể chọn tất cả hoặc chọn nhanh từng module.</small></div><label class="permission-all"><input class="form-check-input" type="checkbox" data-permission-all> <span>Chọn tất cả quyền</span></label></div>
        <div class="table-responsive"><table class="table table-modern permission-table"><thead><tr><th>Module</th><th class="text-center">Tất cả</th><th class="text-center">Xem</th><th class="text-center">Thêm</th><th class="text-center">Sửa</th><th class="text-center">Xóa</th><th class="text-center">Xuất file</th></tr></thead><tbody>
        @foreach($modules as $m) @php($p=$permissions->get($m->id))
            <tr data-permission-row><td><strong><i class="bi {{ $m->icon }} me-2"></i>{{ $m->name }}</strong><div class="small text-muted">{{ $m->code }}</div></td><td class="text-center"><input class="form-check-input permission-row-all" type="checkbox" data-permission-row-all aria-label="Chọn tất cả quyền của {{ $m->name }}"></td>
            @foreach(['view','create','update','delete','export'] as $a)<td class="text-center"><input class="form-check-input permission-item" type="checkbox" name="permissions[{{ $m->id }}][{{ $a }}]" value="1" @checked($p?->{'can_'.$a})></td>@endforeach</tr>
        @endforeach
        </tbody></table></div>
        <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end"><button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu vai trò và quyền</button></div>
    </div>
</form>
@endsection
