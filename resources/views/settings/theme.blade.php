@extends('layouts.app')
@section('title','Cấu hình phần mềm')
@section('header','Quản trị hệ thống')
@section('content')
@php($themes=[
'blue'=>['Xanh dương','#2563eb'],'indigo'=>['Chàm','#4f46e5'],'purple'=>['Tím','#7c3aed'],
'pink'=>['Hồng','#d63384'],'red'=>['Đỏ','#dc3545'],'orange'=>['Cam','#ea580c'],
'yellow'=>['Vàng','#d89b00'],'green'=>['Xanh lá','#198754'],'teal'=>['Xanh ngọc','#0d9488'],'slate'=>['Xám xanh','#475569']
])
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="page-title">Cấu hình phần mềm</h1><div class="page-subtitle">Quản lý tên, logo và màu sắc hiển thị trên toàn hệ thống.</div></div></div>
<form method="POST" action="{{ route('settings.theme.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="row g-4">
<div class="col-xl-5"><div class="card card-soft form-card h-100"><div class="card-header"><h5><i class="bi bi-building me-2"></i>Nhận diện thương hiệu</h5></div><div class="card-body p-4">
<div class="mb-4"><label class="form-label required-label" for="software_name">Tên phần mềm</label><input class="form-control @error('software_name') is-invalid @enderror" id="software_name" name="software_name" value="{{ old('software_name',$softwareName) }}" maxlength="80" required><div class="form-text">Tên này xuất hiện trên tiêu đề, sidebar và trang đăng nhập.</div>@error('software_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<label class="form-label" for="logo">Logo phần mềm</label><div class="branding-logo-preview mb-3">@if($logoPath)<img src="{{ asset($logoPath) }}?v={{ @filemtime(public_path($logoPath)) ?: time() }}" alt="Logo hiện tại">@else<span><i class="bi bi-mortarboard-fill"></i></span>@endif<div><strong>Logo hiện tại</strong><small>Khuyến nghị ảnh vuông, nền trong suốt.</small></div></div>
<input class="form-control @error('logo') is-invalid @enderror" id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" data-logo-input>@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">PNG, JPG, WEBP hoặc SVG; tối đa 2 MB.</div>
@if($logoPath)<div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo"><label class="form-check-label" for="remove_logo">Xóa logo hiện tại và dùng biểu tượng mặc định</label></div>@endif
</div></div></div>
<div class="col-xl-7"><div class="card card-soft form-card h-100"><div class="card-header"><h5><i class="bi bi-palette me-2"></i>Màu giao diện</h5></div><div class="card-body p-4"><p class="text-muted small mb-4">Chọn một bảng màu áp dụng cho menu, thanh tiêu đề, nút và các điểm nhấn.</p><div class="theme-grid">@foreach($themes as $key=>$option)<label class="theme-option @if(old('theme_color',$theme)===$key) selected @endif"><input type="radio" name="theme_color" value="{{ $key }}" @checked(old('theme_color',$theme)===$key)><span class="theme-swatch" style="--swatch:{{ $option[1] }}"></span><span><strong>{{ $option[0] }}</strong><small>{{ $option[1] }}</small></span><i class="bi bi-check-circle-fill"></i></label>@endforeach</div>@error('theme_color')<div class="text-danger small mt-2">{{ $message }}</div>@enderror</div></div></div>
</div>
<div class="form-actions"><a href="{{ route('dashboard') }}" class="btn btn-light">Hủy</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i>Lưu cấu hình</button></div>
</form>
@endsection
@push('scripts')<script>document.querySelectorAll('.theme-option input').forEach(i=>i.addEventListener('change',()=>{document.querySelectorAll('.theme-option').forEach(x=>x.classList.remove('selected'));i.closest('.theme-option').classList.add('selected')}));</script>@endpush