@extends('layouts.app')
@section('title','Cài đặt cá nhân')
@section('header','Cài đặt cá nhân')
@section('content')
<div class="page-toolbar d-flex justify-content-between mb-4"><div><h1 class="page-title">Cài đặt cá nhân</h1><div class="page-subtitle">Tùy chỉnh trải nghiệm cho riêng tài khoản của bạn.</div></div><i class="bi bi-person-gear fs-3 text-primary"></i></div>
<form method="POST" action="{{route('personal-settings.update')}}" data-personal-settings-form>@csrf @method('PUT')
<div class="row g-4">
<div class="col-xl-7">
<section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-palette me-2"></i>Giao diện</h5></div><div class="card-body p-4"><div class="row g-3">
<div class="col-md-7"><label class="form-label">Màu giao diện</label><select class="form-select" name="theme_color" data-personal-theme><option value="">Dùng màu hệ thống</option>@foreach($themes as $key=>$label)<option value="{{$key}}" @selected(old('theme_color',$user->theme_color)===$key)>{{$label}}</option>@endforeach</select><div class="personal-theme-preview mt-3" data-theme-preview><span class="personal-theme-preview-nav"><i></i><i></i><i></i></span><span class="personal-theme-preview-main"><i></i><strong data-theme-preview-label>{{old('theme_color',$user->theme_color)?($themes[old('theme_color',$user->theme_color)]??'Màu cá nhân'):'Dùng màu hệ thống'}}</strong><small><b></b>Màu nhấn và thanh điều hướng</small></span></div><div class="form-text">Thay đổi được xem trước ngay và không ảnh hưởng tài khoản khác.</div></div>
<div class="col-md-5"><label class="form-label">Sidebar trên máy tính</label><select class="form-select" name="sidebar_mode"><option value="remember" @selected(old('sidebar_mode',$sidebarMode)==='remember')>Ghi nhớ lần dùng cuối</option><option value="expanded" @selected(old('sidebar_mode',$sidebarMode)==='expanded')>Luôn mở</option><option value="collapsed" @selected(old('sidebar_mode',$sidebarMode)==='collapsed')>Luôn thu gọn</option></select></div>
<div class="col-12"><label class="form-label">Hiệu ứng giao diện</label><div class="visual-effect-grid"><label class="visual-effect-option @if(old('visual_effect',$visualEffect)===null) selected @endif" data-effect="{{$systemVisualEffect}}"><input type="radio" name="visual_effect" value="" @checked(old('visual_effect',$visualEffect)===null)><span class="visual-effect-demo"><i></i></span><strong>Theo hệ thống</strong><small>Admin đang chọn hiệu ứng mặc định</small></label>@foreach(['standard'=>['Tiêu chuẩn','Ổn định và quen thuộc'],'soft'=>['Mềm mại','Bo góc và nền dịu'],'glass'=>['Kính mờ','Trong suốt và hiện đại'],'glow'=>['Ánh sáng','Nổi bật khi tương tác']] as $key=>$effect)<label class="visual-effect-option @if(old('visual_effect',$visualEffect)===$key) selected @endif" data-effect="{{$key}}"><input type="radio" name="visual_effect" value="{{$key}}" @checked(old('visual_effect',$visualEffect)===$key)><span class="visual-effect-demo"><i></i></span><strong>{{$effect[0]}}</strong><small>{{$effect[1]}}</small></label>@endforeach</div></div>
</div></div></section>
<section class="card card-soft form-card mt-4"><div class="card-header"><h5><i class="bi bi-table me-2"></i>Hiển thị dữ liệu</h5></div><div class="card-body p-4"><div class="row g-3">
<div class="col-md-5"><label class="form-label">Số dòng mỗi trang</label><select class="form-select" name="default_per_page"><option value="">Theo cấu hình hệ thống</option>@foreach([10,20,30,50,100] as $count)<option value="{{$count}}" @selected((string)old('default_per_page',$defaultPerPage)===(string)$count)>{{$count}} dòng</option>@endforeach</select></div>
<div class="col-md-7"><label class="form-label">Trang mở sau đăng nhập</label><select class="form-select" name="landing_page">@foreach($landingPages as $key=>$option)<option value="{{$key}}" @selected(old('landing_page',$landingPage)===$key)>{{$option[0]}}</option>@endforeach</select><div class="form-text">Chỉ gồm những trang bạn được phép truy cập.</div></div>
</div></div></section>
</div>
<div class="col-xl-5">
<section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-bell me-2"></i>Thông báo</h5></div><div class="card-body p-4"><div class="notification-setting"><div><span class="notification-setting-icon"><i class="bi bi-bell-fill"></i></span><span><strong>Thông báo thời gian thực</strong><small>Nhận ngay khi có công việc hoặc nội dung mới.</small></span></div><div class="form-check form-switch"><input type="hidden" name="notifications_enabled" value="0"><input class="form-check-input" type="checkbox" role="switch" name="notifications_enabled" value="1" @checked(old('notifications_enabled',$user->notifications_enabled))></div></div></div></section>
<section class="card card-soft form-card mt-4"><div class="card-header"><h5><i class="bi bi-qr-code-scan me-2"></i>Đăng nhập Zalo</h5></div><div class="card-body p-4">
@if($user->zalo_id)
<div class="alert alert-success"><strong>Đã liên kết</strong><div class="small mt-1">{{$user->zalo_name ?: 'Tài khoản Zalo'}} · {{optional($user->zalo_linked_at)->format('H:i d/m/Y')}}</div></div>
<label class="form-label">Nhập mật khẩu hiện tại để ngắt liên kết</label><input class="form-control mb-2" type="password" name="current_password" form="zalo-disconnect-form" autocomplete="current-password" required><button class="btn btn-outline-danger w-100" type="submit" form="zalo-disconnect-form" data-confirm="Ngắt liên kết Zalo khỏi tài khoản này?"><i class="bi bi-link-45deg me-1"></i>Ngắt liên kết Zalo</button>
@elseif(config('zalo.app_id') && config('zalo.app_secret'))
<p class="small text-muted">Liên kết tài khoản Zalo đã xác thực. Sau đó bạn có thể đăng nhập bằng Zalo hoặc quét mã khi Zalo yêu cầu.</p><a class="btn btn-primary w-100" href="{{route('zalo.connect')}}"><i class="bi bi-link-45deg me-1"></i>Liên kết tài khoản Zalo</a>
@else
<div class="alert alert-light border mb-0"><i class="bi bi-info-circle me-1"></i>Admin chưa cấu hình ứng dụng Zalo.</div>
@endif
</div></section>
<div class="card card-soft mt-4"><div class="card-body p-4"><div class="d-flex gap-3"><i class="bi bi-info-circle-fill text-primary fs-4"></i><div><strong>Kế thừa cấu hình hệ thống</strong><p class="small text-muted mb-0 mt-1">Khi chọn “Dùng/Theo cấu hình hệ thống”, thay đổi của Admin sẽ tự động áp dụng cho tài khoản này.</p></div></div></div></div>
</div>
</div>
<div class="form-actions settings-actions"><a class="btn btn-light" href="{{route('dashboard')}}">Hủy</a><button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Lưu cài đặt cá nhân</button></div>
</form>
@if($user->zalo_id)<form id="zalo-disconnect-form" method="POST" action="{{route('zalo.disconnect')}}" class="d-none">@csrf @method('DELETE')</form>@endif
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const select=document.querySelector('[data-personal-theme]'),label=document.querySelector('[data-theme-preview-label]');select?.addEventListener('change',event=>{document.body.dataset.theme=event.target.value||@js($defaultSystemTheme);if(label)label.textContent=event.target.options[event.target.selectedIndex]?.text||'Dùng màu hệ thống'});document.querySelectorAll('[name="visual_effect"]').forEach(input=>input.addEventListener('change',()=>{document.body.dataset.visualEffect=input.value||@js($systemVisualEffect);document.querySelectorAll('.visual-effect-option').forEach(option=>option.classList.toggle('selected',option.contains(input)))}))});</script>@endpush
