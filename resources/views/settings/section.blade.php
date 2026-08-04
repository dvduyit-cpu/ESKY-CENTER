@extends('layouts.app')
@section('title','Cấu hình phần mềm')
@section('header','Cấu hình phần mềm')
@section('content')
@php
$sections=['general'=>['Thiết lập chung','bi-gear','Tên, logo và hiển thị dữ liệu'],'appearance'=>['Giao diện','bi-palette','Màu sắc và hiệu ứng tải'],'payment'=>['Thanh toán','bi-bank','Ngân hàng và VietQR'],'ai'=>['Trí tuệ nhân tạo','bi-stars','OpenAI tổng hợp báo cáo tuần']];
$themes=['blue'=>['Xanh dương','#2563eb'],'navy'=>['Xanh navy','#245b9e'],'azure'=>['Xanh biển sáng','#0077b6'],'sky'=>['Xanh trời','#0284c7'],'cyan'=>['Xanh cyan','#0891b2'],'teal'=>['Xanh ngọc','#0d9488'],'mint'=>['Bạc hà','#2a9d8f'],'emerald'=>['Ngọc lục bảo','#059669'],'green'=>['Xanh lá','#198754'],'lime'=>['Xanh chanh','#65a30d'],'olive'=>['Olive','#708238'],'yellow'=>['Vàng','#d89b00'],'amber'=>['Hổ phách','#d97706'],'orange'=>['Cam','#ea580c'],'coral'=>['San hô','#e76f51'],'red'=>['Đỏ','#dc3545'],'rose'=>['Hồng đỏ','#e11d48'],'pink'=>['Hồng','#d63384'],'fuchsia'=>['Fuchsia','#c026d3'],'purple'=>['Tím','#7c3aed'],'violet'=>['Tím violet','#8b5cf6'],'indigo'=>['Chàm','#4f46e5'],'brown'=>['Nâu','#92400e'],'slate'=>['Xám xanh','#475569'],'graphite'=>['Graphite','#374151']];
$themes=[
    'white'=>['Trắng hiện đại','#ffffff'],
    'gradient-ocean'=>['Gradient đại dương','linear-gradient(135deg,#2563eb,#06b6d4)'],
    'gradient-aurora'=>['Gradient cực quang','linear-gradient(135deg,#059669,#22c55e,#06b6d4)'],
    'gradient-sunset'=>['Gradient hoàng hôn','linear-gradient(135deg,#f97316,#ec4899)'],
    'gradient-galaxy'=>['Gradient ngân hà','linear-gradient(135deg,#312e81,#7c3aed,#db2777)'],
    'gradient-berry'=>['Gradient berry','linear-gradient(135deg,#be185d,#7c3aed)'],
    'gradient-forest'=>['Gradient rừng xanh','linear-gradient(135deg,#14532d,#0d9488)'],
    'gradient-candy'=>['Gradient kẹo ngọt','linear-gradient(135deg,#ec4899,#8b5cf6,#3b82f6)'],
    'gradient-fire'=>['Gradient ánh lửa','linear-gradient(135deg,#dc2626,#f97316,#facc15)'],
    'pastel-blue'=>['Pastel xanh dương','#bfdbfe'],
    'pastel-sky'=>['Pastel xanh trời','#bae6fd'],
    'pastel-mint'=>['Pastel bạc hà','#a7f3d0'],
    'pastel-green'=>['Pastel xanh lá','#bbf7d0'],
    'pastel-lavender'=>['Pastel lavender','#ddd6fe'],
    'pastel-purple'=>['Pastel tím','#e9d5ff'],
    'pastel-pink'=>['Pastel hồng','#fbcfe8'],
    'pastel-peach'=>['Pastel đào','#fed7aa'],
    'pastel-yellow'=>['Pastel vàng','#fef3c7'],
    'pastel-gray'=>['Pastel xám','#e2e8f0'],
] + $themes;
@endphp
<div class="page-toolbar d-flex justify-content-between mb-4"><div><h1 class="page-title">{{$sections[$section][0]}}</h1><div class="page-subtitle">{{$sections[$section][2]}}</div></div><i class="bi {{$sections[$section][1]}} fs-3 text-primary"></i></div>
<nav class="settings-page-tabs mb-4">@foreach($sections as $key=>$item)<a class="{{$section===$key?'active':''}}" href="{{route('settings.edit',$key)}}"><i class="bi {{$item[1]}}"></i><span><strong>{{$item[0]}}</strong><small>{{$item[2]}}</small></span></a>@endforeach</nav>

<form method="POST" action="{{route('settings.update',$section)}}" enctype="multipart/form-data" data-settings-form>@csrf @method('PUT')
@if($section==='general')
<div class="row g-4"><div class="col-xl-8"><section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-building me-2"></i>Thông tin phần mềm</h5></div><div class="card-body p-4"><div class="row g-3"><div class="col-md-8"><label class="form-label">Tên phần mềm</label><input class="form-control" name="software_name" value="{{old('software_name',$softwareName)}}" maxlength="80" required><div class="form-text">Hiển thị trên trình duyệt, menu và trang đăng nhập.</div></div><div class="col-md-4"><label class="form-label">Số dòng mặc định</label><select class="form-select" name="default_per_page">@foreach([10,20,30,50,100] as $count)<option value="{{$count}}" @selected((int)old('default_per_page',$defaultPerPage)===$count)>{{$count}} dòng</option>@endforeach</select></div><div class="col-12"><label class="form-label">Nội dung chân trang</label><input class="form-control" name="footer_text" value="{{old('footer_text',$footerText)}}" maxlength="200" required></div></div></div></section></div><div class="col-xl-4"><section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-image me-2"></i>Logo</h5></div><div class="card-body p-4"><div class="branding-logo-box mb-3">@if($logoPath)<img src="{{asset($logoPath)}}" alt="Logo">@else<i class="bi bi-mortarboard-fill"></i>@endif</div><input class="form-control" type="file" name="logo" accept="image/png,image/jpeg,image/webp"><div class="form-text">PNG, JPG hoặc WEBP; tối đa 2 MB.</div>@if($logoPath)<label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="remove_logo" value="1"><span class="form-check-label">Dùng biểu tượng mặc định</span></label>@endif</div></section></div></div>
@elseif($section==='appearance')
<section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-palette me-2"></i>Màu chủ đạo</h5></div><div class="card-body p-4"><div class="theme-grid theme-grid-expanded">@foreach($themes as $key=>$option)<label class="theme-option @if(old('theme_color',$theme)===$key) selected @endif"><input type="radio" name="theme_color" value="{{$key}}" @checked(old('theme_color',$theme)===$key)><span class="theme-swatch" style="--swatch:{{$option[1]}}"></span><span><strong>{{$option[0]}}</strong><small>{{$option[1]}}</small></span><i class="bi bi-check-circle-fill"></i></label>@endforeach</div></div></section><section class="card card-soft form-card mt-4"><div class="card-header"><h5><i class="bi bi-hourglass-split me-2"></i>Hiển thị khi tải</h5></div><div class="card-body p-4"><div class="loading-style-grid"><label class="loading-style-option @if(old('loading_style',$loadingStyle)==='center') selected @endif"><input type="radio" name="loading_style" value="center" @checked(old('loading_style',$loadingStyle)==='center')><span class="loading-demo center-demo"><i class="bi bi-arrow-repeat"></i></span><span><strong>Giữa màn hình</strong><small>Khóa giao diện trong lúc xử lý</small></span><i class="bi bi-check-circle-fill option-check"></i></label><label class="loading-style-option @if(old('loading_style',$loadingStyle)==='top') selected @endif"><input type="radio" name="loading_style" value="top" @checked(old('loading_style',$loadingStyle)==='top')><span class="loading-demo top-demo"><i></i></span><span><strong>Thanh phía trên</strong><small>Gọn nhẹ khi chuyển trang</small></span><i class="bi bi-check-circle-fill option-check"></i></label></div></div></section>
<section class="card card-soft form-card mt-4"><div class="card-header"><h5><i class="bi bi-stars me-2"></i>Hiệu ứng mặc định toàn hệ thống</h5></div><div class="card-body p-4"><p class="text-muted small">Áp dụng cho các tài khoản chưa chọn hiệu ứng cá nhân.</p><div class="visual-effect-grid">@foreach(['standard'=>['Tiêu chuẩn','Ổn định và quen thuộc'],'soft'=>['Mềm mại','Bo góc và nền dịu'],'glass'=>['Kính mờ','Trong suốt và hiện đại'],'glow'=>['Ánh sáng','Nổi bật khi tương tác']] as $key=>$effect)<label class="visual-effect-option @if(old('visual_effect',$visualEffect)===$key) selected @endif" data-effect="{{$key}}"><input type="radio" name="visual_effect" value="{{$key}}" @checked(old('visual_effect',$visualEffect)===$key)><span class="visual-effect-demo"><i></i></span><strong>{{$effect[0]}}</strong><small>{{$effect[1]}}</small></label>@endforeach</div></div></section>
@elseif($section==='ai')
<div class="row g-4">
    <div class="col-xl-8">
        <section class="card card-soft form-card">
            <div class="card-header"><h5><i class="bi bi-stars me-2"></i>OpenAI tổng hợp báo cáo</h5></div>
            <div class="card-body p-4">
                <div class="bank-setting-toggle mb-4">
                    <div><strong>Bật phân tích báo cáo bằng AI</strong><small>Khi tắt hoặc API gặp lỗi, hệ thống vẫn dùng cách tổng hợp nội bộ hiện có.</small></div>
                    <div class="form-check form-switch"><input type="hidden" name="openai_enabled" value="0"><input class="form-check-input" type="checkbox" name="openai_enabled" value="1" @checked(old('openai_enabled',$openAiEnabled))></div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Khóa OpenAI API</label>
                        <input class="form-control" type="password" name="openai_api_key" value="" maxlength="500" autocomplete="new-password" placeholder="{{$openAiKeyConfigured ? 'Đã cấu hình — để trống nếu không muốn đổi' : 'Nhập khóa API'}}">
                        <div class="form-text"><i class="bi {{$openAiKeyConfigured ? 'bi-shield-check text-success' : 'bi-exclamation-circle text-warning'}} me-1"></i>{{$openAiKeyConfigured ? 'Đã có khóa API. Khóa không được hiển thị lại trên trình duyệt.' : 'Chưa có khóa API; AI sẽ chưa hoạt động.'}}</div>
                        @if($openAiKeyStored)<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_openai_api_key" value="1"><span class="form-check-label">Xóa khóa đã lưu và dùng khóa trong máy chủ (nếu có)</span></label>@endif
                    </div>
                    <div class="col-md-7"><label class="form-label">Model tổng hợp báo cáo</label><select class="form-select" name="openai_report_model" required>@foreach(['gpt-5.6-luna'=>'GPT-5.6 Luna — nhanh, tiết kiệm','gpt-5.6-terra'=>'GPT-5.6 Terra — cân bằng','gpt-5.6-sol'=>'GPT-5.6 Sol — chất lượng cao'] as $model=>$label)<option value="{{$model}}" @selected(old('openai_report_model',$openAiModel)===$model)>{{$label}}</option>@endforeach</select></div>
                    <div class="col-md-5"><label class="form-label">Thời gian chờ tối đa</label><select class="form-select" name="openai_timeout" required>@foreach([15,30,45,60,90] as $seconds)<option value="{{$seconds}}" @selected((int)old('openai_timeout',$openAiTimeout)===$seconds)>{{$seconds}} giây</option>@endforeach</select></div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-info-circle me-2"></i>Chi phí và bảo mật</h5></div><div class="card-body p-4"><p class="mb-2">OpenAI API tính phí theo lượng dữ liệu gửi vào và nội dung trả về. GPT-5.6 Luna phù hợp để tổng hợp báo cáo với chi phí thấp hơn.</p><p class="text-muted small mb-3">Khóa được mã hóa bằng khóa ứng dụng của máy chủ. Địa chỉ API được cố định trong cấu hình để tránh gửi khóa nhầm nơi.</p><a class="btn btn-outline-primary btn-sm" href="https://developers.openai.com/api/docs/models/gpt-5.6-luna" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right me-1"></i>Xem giá OpenAI</a></div></section>
    </div>
</div>
@else
<section class="card card-soft form-card"><div class="card-header"><h5><i class="bi bi-bank me-2"></i>Ngân hàng thu học phí</h5></div><div class="card-body p-4"><div class="bank-setting-toggle mb-4"><div><strong>Bật chuyển khoản và VietQR</strong><small>Dùng thông tin này trên phiếu thu học phí.</small></div><div class="form-check form-switch"><input type="hidden" name="bank_enabled" value="0"><input class="form-check-input" type="checkbox" name="bank_enabled" value="1" @checked(old('bank_enabled',$bankEnabled)) data-bank-enabled></div></div><div class="row g-3" data-bank-fields><div class="col-md-4"><label class="form-label">Mã BIN VietQR</label><input class="form-control" name="bank_bin" value="{{old('bank_bin',$bankBin)}}" maxlength="6"></div><div class="col-md-8"><label class="form-label">Tên ngân hàng</label><input class="form-control" name="bank_name" value="{{old('bank_name',$bankName)}}" maxlength="100"></div><div class="col-md-6"><label class="form-label">Số tài khoản</label><input class="form-control" name="bank_account_number" value="{{old('bank_account_number',$bankAccountNumber)}}" maxlength="30"></div><div class="col-md-6"><label class="form-label">Chi nhánh</label><input class="form-control" name="bank_branch" value="{{old('bank_branch',$bankBranch)}}" maxlength="150"></div><div class="col-12"><label class="form-label">Tên chủ tài khoản</label><input class="form-control text-uppercase" name="bank_account_name" value="{{old('bank_account_name',$bankAccountName)}}" maxlength="150"></div></div></div></section>
@endif
<div class="form-actions settings-actions"><a class="btn btn-light" href="{{route('dashboard')}}">Hủy</a>@if(auth()->user()->allowed('software_settings','update'))<button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Lưu {{$sections[$section][0]}}</button>@else<span class="text-muted small"><i class="bi bi-eye me-1"></i>Chế độ chỉ xem</span>@endif</div>
</form>
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.theme-option input').forEach(input=>input.addEventListener('change',()=>{document.body.dataset.theme=input.value;document.querySelectorAll('.theme-option').forEach(option=>option.classList.toggle('selected',option.contains(input)))}));document.querySelectorAll('[name="loading_style"]').forEach(input=>input.addEventListener('change',()=>{document.querySelectorAll('.loading-style-option').forEach(option=>option.classList.toggle('selected',option.contains(input)))}));document.querySelectorAll('[name="visual_effect"]').forEach(input=>input.addEventListener('change',()=>{document.body.dataset.visualEffect=input.value;document.querySelectorAll('.visual-effect-option').forEach(option=>option.classList.toggle('selected',option.contains(input)))}));const toggle=document.querySelector('[data-bank-enabled]'),fields=document.querySelector('[data-bank-fields]'),sync=()=>fields?.classList.toggle('bank-disabled',!toggle?.checked);toggle?.addEventListener('change',sync);sync()});</script>@endpush
