@extends('layouts.app')
@section('title','Tài khoản')
@section('header','Quản trị tài khoản')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">{{auth()->user()->isDirector()?'Quản lý Phó giám đốc':'Tài khoản hệ thống'}}</h1>
        <div class="page-subtitle">{{auth()->user()->isDirector()?'Giám đốc có thể cập nhật, khóa/mở và đặt lại mật khẩu tài khoản Phó giám đốc.':'Tạo, khóa, xóa mềm, đặt lại mật khẩu và cấp quyền riêng.'}}</div>
    </div>
    @if(auth()->user()->allowed('users','create'))
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Tạo tài khoản</a>
    @endif
</div>
<form class="filter-panel row g-3 mb-4">
    <div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc email..."></div>
    <div class="col-md-3"><select class="form-select" name="role_id"><option value="">Tất cả vai trò</option>@foreach($roles as $r)<option value="{{ $r->id }}" @selected((string)request('role_id')===(string)$r->id)>{{ $r->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select class="form-select" name="status"><option value="">Mọi trạng thái</option><option value="active" @selected(request('status')==='active')>Hoạt động</option><option value="locked" @selected(request('status')==='locked')>Đã khóa</option><option value="deleted" @selected(request('status')==='deleted')>Đã xóa</option></select></div>
    <div class="col-md-2"><button class="btn btn-dark w-100">Lọc</button></div>
</form>
@if(auth()->user()->allowed('users','delete'))
<form id="bulk-users" method="POST" action="{{ route('users.bulk-destroy') }}" data-bulk-form="users" data-bulk-confirm="Xóa các tài khoản đã chọn?" class="mb-3 d-flex flex-wrap align-items-center gap-2">@csrf @method('DELETE')<label class="me-2"><input class="form-check-input me-1" type="checkbox" data-bulk-all="users"> Chọn tất cả trang này</label><select class="form-select form-select-sm w-auto" name="delete_type"><option value="soft">Xóa mềm</option>@if(auth()->user()->isAdmin())<option value="force">Xóa vĩnh viễn</option>@endif</select><button class="btn btn-sm btn-outline-danger" data-bulk-submit disabled><i class="bi bi-trash me-1"></i>Xóa đã chọn (<span data-bulk-count>0</span>)</button></form>
@endif
<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Nhân sự liên kết</th><th>Đăng nhập gần nhất</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td><div class="d-flex gap-3 align-items-center"><span class="avatar">{{ mb_strtoupper(mb_substr($u->name,0,1)) }}</span><div><strong>{{ $u->name }}</strong><div class="small text-muted">{{ $u->email }}</div></div></div></td>
                    <td><span class="badge-soft badge-info">{{ $u->role?->name }}</span></td>
                    <td>{{ $u->personnel?->name ?: 'Chưa liên kết' }}</td>
                    <td>{{ $u->last_login_at?->format('d/m/Y H:i') ?: 'Chưa đăng nhập' }}<div class="small text-muted">{{ $u->last_login_ip }}</div></td>
                    <td>
                        @if($u->trashed())<span class="badge-soft badge-danger">Đã xóa</span>
                        @elseif($u->active)<span class="badge-soft badge-success">Hoạt động</span>
                        @else<span class="badge-soft badge-warning">Đã khóa</span>@endif
                        @if($u->must_change_password)<div class="small text-warning mt-1">Phải đổi mật khẩu</div>@endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($u->trashed() && auth()->user()->allowed('users','delete'))
                            <form class="d-inline" method="POST" action="{{ route('users.restore',$u->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Khôi phục</button></form>
                        @else
                            @if(auth()->user()->allowed('users','update'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit',$u) }}" title="Sửa"><i class="bi bi-pencil"></i></a>
                                @if(auth()->user()->isAdmin())<a class="btn btn-sm btn-outline-dark" href="{{ route('users.permissions',$u) }}" title="Quyền riêng"><i class="bi bi-shield-lock"></i></a>@endif
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reset{{ $u->id }}" title="Đặt lại mật khẩu"><i class="bi bi-key"></i></button>
                                @if(!auth()->user()->is($u))
                                    <form class="d-inline" method="POST" action="{{ route('users.toggle',$u) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning" data-confirm="Xác nhận khóa/mở tài khoản?"><i class="bi bi-lock"></i></button></form>
                                @endif
                            @endif
                            @if(auth()->user()->allowed('users','delete') && !auth()->user()->is($u))
                                <form class="d-inline" method="POST" action="{{ route('users.destroy',$u) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa mềm tài khoản này?"><i class="bi bi-trash"></i></button></form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">Không tìm thấy tài khoản.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0 p-3">{{ $users->links() }}</div>
</div>

@foreach($users as $u)
    @if(!$u->trashed())
    <div class="modal fade" id="reset{{ $u->id }}" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content border-0 rounded-4">
            <form method="POST" action="{{ route('users.reset-password',$u) }}">@csrf
                <div class="modal-header"><h5 class="modal-title">Đặt lại mật khẩu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><p>Đặt mật khẩu mới cho <strong>{{ $u->email }}</strong>.</p><label class="form-label">Mật khẩu mới</label><input class="form-control mb-1" type="password" name="password" value="Esky123@" minlength="8" required><div class="form-text mb-3">Mật khẩu mặc định: Esky123@</div><label class="form-label">Nhập lại mật khẩu</label><input class="form-control" type="password" name="password_confirmation" value="Esky123@" minlength="8" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Đặt lại</button></div>
            </form>
        </div></div>
    </div>
    @endif
@endforeach
@endsection
