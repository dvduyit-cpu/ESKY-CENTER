@extends('layouts.app')
@section('title','Quyền riêng tài khoản') @section('header','Phân quyền tài khoản')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="page-title">Quyền riêng: {{ $user->name }}</h1><div class="page-subtitle">Bật “Ghi đè” để quyền riêng thay thế quyền của vai trò {{ $user->role?->name }}.</div></div><a href="{{ route('users.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-2"></i>Quay lại</a></div>
<form method="POST" action="{{ route('users.permissions.update',$user) }}" data-permission-table data-permission-overrides>@csrf @method('PUT')
<div class="card card-soft permission-card"><div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1 fw-bold">Bảng quyền riêng</h5><small class="text-muted">Chọn tất cả sẽ bật ghi đè và cấp toàn bộ quyền.</small></div><label class="permission-all"><input class="form-check-input" type="checkbox" data-permission-all> <span>Chọn tất cả quyền</span></label></div>
<div class="table-responsive"><table class="table table-modern permission-table"><thead><tr><th>Module</th><th class="text-center">Tất cả phần này</th><th class="text-center">Ghi đè</th><th class="text-center">Xem</th><th class="text-center">Thêm</th><th class="text-center">Sửa</th><th class="text-center">Xóa</th><th class="text-center">Xuất file</th></tr></thead><tbody>
@foreach($moduleGroups as $moduleGroup)
<tr class="permission-group-row"><td colspan="8"><i class="bi bi-folder2-open me-2"></i>{{$moduleGroup['name']}}</td></tr>
@foreach($moduleGroup['modules'] as $m) @php($o=$overrides->get($m->id)) @php($supportedActions=\App\Support\ModulePermissionCatalog::actionsFor($m->code))
<tr data-permission-row data-module-code="{{$m->code}}"><td><strong><i class="bi {{ $m->icon }} me-2"></i>{{ $m->name }}</strong><div class="small text-muted">{{ $m->code }}</div></td><td class="text-center"><input class="form-check-input permission-row-all" type="checkbox" data-permission-row-all aria-label="Chọn tất cả quyền của {{ $m->name }}"></td><td class="text-center"><input class="form-check-input permission-override" type="checkbox" name="override[{{ $m->id }}]" value="1" data-permission-override @checked($o)></td>
@foreach(['view','create','update','delete','export'] as $a)<td class="text-center">@if(in_array($a,$supportedActions,true))<input class="form-check-input permission-item" type="checkbox" name="permissions[{{ $m->id }}][{{ $a }}]" value="1" @checked($o?->{'can_'.$a})>@else<span class="permission-not-applicable" title="Module không có thao tác này">—</span>@endif</td>@endforeach</tr>
@endforeach
@endforeach
</tbody></table></div><div class="card-footer bg-white border-0 p-4 d-flex justify-content-end"><button class="btn btn-primary"><i class="bi bi-shield-check me-2"></i>Lưu quyền riêng</button></div></div></form>
@endsection
