@extends('layouts.app')
@section('title','Hướng dẫn sử dụng')
@section('header','Hướng dẫn sử dụng')
@section('content')
@php
    $currentUser=auth()->user();
    $roleName=$currentUser->role?->name?:'Nhân viên';
    $roleAnchor=match(true){
        $currentUser->isAdmin()=>'quan-tri',
        $currentUser->isDirector()||$currentUser->isDeputyDirector()||$currentUser->isLeader()=>'lanh-dao',
        $currentUser->isTeacher()||$currentUser->canTeach()=>'giao-vien',
        $currentUser->isRegistrar()=>'giao-vu',
        $currentUser->allowed('language_consulting')=>'tu-van',
        default=>'nhan-vien',
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h1 class="page-title">Hướng dẫn sử dụng {{ $systemName }}</h1><div class="page-subtitle">Việc cần làm hằng ngày, quy trình chi tiết và trách nhiệm của từng vai trò.</div></div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="#{{$roleAnchor}}"><i class="bi bi-person-check me-2"></i>Xem việc tôi cần làm</a><a class="btn btn-light" href="{{route('welcome')}}"><i class="bi bi-house-door me-2"></i>Trang chủ</a></div>
</div>

<div class="guide-role-banner mb-4"><span><i class="bi bi-person-badge"></i></span><div><small>Vai trò đang đăng nhập</small><strong>{{$roleName}}</strong><p>Chỉ các chức năng đã được cấp quyền mới xuất hiện trong menu. Nếu thiếu chức năng cần thiết, báo lãnh đạo hoặc quản trị viên kiểm tra quyền.</p></div></div>

<div class="guide-role-shortcuts mb-4">
    <a href="#nhan-vien"><i class="bi bi-person-workspace"></i><span><strong>Nhân viên</strong><small>Nhận việc, báo cáo và phối hợp</small></span></a>
    <a href="#tu-van"><i class="bi bi-headset"></i><span><strong>Tư vấn tuyển sinh</strong><small>Tiếp nhận và chăm sóc khách hàng</small></span></a>
    <a href="#giao-vu"><i class="bi bi-mortarboard"></i><span><strong>Giáo vụ</strong><small>Hồ sơ, lớp và học phí</small></span></a>
    <a href="#giao-vien"><i class="bi bi-journal-check"></i><span><strong>Giáo viên</strong><small>Điểm danh, điểm và nhận xét</small></span></a>
    <a href="#lanh-dao"><i class="bi bi-graph-up-arrow"></i><span><strong>Lãnh đạo</strong><small>Giao việc, kiểm tra và điều hành</small></span></a>
    <a href="#quan-tri"><i class="bi bi-shield-lock"></i><span><strong>Quản trị viên</strong><small>Tài khoản, quyền và hệ thống</small></span></a>
</div>

<div class="guide-layout">
<aside class="card card-soft guide-nav"><div class="card-body"><strong>Mục lục hướng dẫn</strong>
    <a href="#bat-dau"><i class="bi bi-play-circle"></i>Bắt đầu mỗi ngày</a>
    <a href="#nhan-vien"><i class="bi bi-person-workspace"></i>Nhân viên</a>
    <a href="#tu-van"><i class="bi bi-headset"></i>Tư vấn tuyển sinh</a>
    <a href="#giao-vu"><i class="bi bi-mortarboard"></i>Giáo vụ trung tâm</a>
    <a href="#giao-vien"><i class="bi bi-journal-check"></i>Giáo viên</a>
    <a href="#lanh-dao"><i class="bi bi-graph-up-arrow"></i>Lãnh đạo</a>
    <a href="#quan-tri"><i class="bi bi-shield-lock"></i>Quản trị viên</a>
    <a href="#quy-trinh"><i class="bi bi-diagram-3"></i>Quy trình phối hợp</a>
    <a href="#du-lieu"><i class="bi bi-database-check"></i>Quy tắc dữ liệu</a>
    <a href="#xu-ly-loi"><i class="bi bi-tools"></i>Lỗi thường gặp</a>
</div></aside>

<div class="guide-content">
<section id="bat-dau" class="card card-soft guide-section"><span>01</span><div>
    <h4>Việc mọi người nên làm khi bắt đầu và kết thúc ngày</h4>
    <div class="guide-checklist">
        <div><strong>Đầu ngày</strong><ul><li>Mở chuông thông báo và xử lý việc sắp đến hạn hoặc quá hạn.</li><li>Vào “Giao & theo dõi công việc” để xem việc được giao, hạn và mức ưu tiên.</li><li>Kiểm tra “Kế hoạch & lịch cá nhân”, bổ sung lịch hẹn trong ngày.</li><li>Mở đúng phân hệ nghiệp vụ của mình và xử lý bản ghi còn chờ.</li></ul></div>
        <div><strong>Cuối ngày</strong><ul><li>Cập nhật trạng thái công việc và ghi rõ kết quả đã thực hiện.</li><li>Bổ sung nhận xét, dữ liệu hoặc tệp minh chứng còn thiếu.</li><li>Kiểm tra lại các bản ghi vừa thêm, đặc biệt SĐT, số tiền và ngày tháng.</li><li>Đăng xuất nếu dùng máy chung; không chia sẻ mật khẩu.</li></ul></div>
    </div>
    <div class="guide-note"><i class="bi bi-shield-check"></i><span>Mỗi người chỉ thao tác trong phạm vi được giao. Không dùng chung tài khoản vì hệ thống lưu người tạo và người cập nhật dữ liệu.</span></div>
</div></section>

<section id="nhan-vien" class="card card-soft guide-section"><span>02</span><div>
    <h4>Nhân viên nên làm gì?</h4>
    <p>Nhân viên tập trung vào công việc cá nhân, nhiệm vụ được giao và nghiệp vụ đúng phòng ban.</p>
    <h6 class="guide-subtitle">Quy trình xử lý công việc được giao</h6>
    <ol class="guide-steps"><li>Mở <strong>Giao & theo dõi công việc</strong>, ưu tiên việc quá hạn, quan trọng và sắp đến hạn.</li><li>Mở chi tiết, đọc nội dung, người chủ trì, người phối hợp và tệp đính kèm.</li><li>Xác nhận đã nhận việc; trao đổi bằng bình luận để lịch sử được lưu tập trung.</li><li>Đính kèm kết quả khi cần và đánh dấu hoàn thành khi đã làm xong thực tế.</li><li>Nếu chưa thể hoàn thành, ghi rõ vướng mắc và báo người giao việc; không tự đóng việc.</li></ol>
    <h6 class="guide-subtitle">Kế hoạch cá nhân</h6>
    <ul><li>Tạo lịch hẹn có ngày giờ, mức ưu tiên và thời gian nhắc.</li><li>Đánh dấu hoàn thành để chuông không tiếp tục báo quá hạn.</li><li>Không dùng kế hoạch cá nhân thay cho công việc cần lãnh đạo theo dõi.</li></ul>
    <div class="guide-do-dont"><div><strong>Nên</strong><span>Cập nhật ngắn gọn, có kết quả và đúng hạn.</span></div><div><strong>Không nên</strong><span>Xóa bình luận hoặc tệp đang là bằng chứng công việc.</span></div></div>
</div></section>

<section id="tu-van" class="card card-soft guide-section"><span>03</span><div>
    <h4>Nhân viên tư vấn và tuyển sinh nên làm gì?</h4>
    <ol class="guide-steps"><li>Vào <strong>Công việc tư vấn</strong>, xem tổng số còn chờ và xử lý hồ sơ mới trước.</li><li>Dùng bộ lọc trạng thái, ngày, tháng hoặc năm; ưu tiên dòng đỏ đã quá 3 ngày chưa tư vấn.</li><li>Mở hồ sơ, cập nhật lần tư vấn gần nhất, nội dung trao đổi, lịch hẹn và trạng thái phù hợp.</li><li>Không để trạng thái “Mới tiếp nhận” nếu đã gọi hoặc nhắn cho khách.</li><li>Khi khách đồng ý đăng ký, đổi trạng thái thành <strong>Đã đăng ký</strong> rồi bấm <strong>Chuyển thành học viên</strong>.</li><li>Sau chuyển đổi, hệ thống mở trang chi tiết học viên; kiểm tra lại thông tin cá nhân, khóa học và người giám hộ.</li></ol>
    <h6 class="guide-subtitle">Tiếp nhận khách hàng đúng cách</h6>
    <ul><li>Kiểm tra trùng theo SĐT trước khi tạo hồ sơ mới.</li><li>Ghi rõ nguồn: Fanpage, Zalo, Zalo OA, Web, Hotline hoặc nguồn tự đến.</li><li>Ghi chú giờ có thể liên hệ, nhu cầu học, độ tuổi và vấn đề cần tư vấn.</li><li>Không tạo lại khách hàng chỉ vì đổi tư vấn viên hoặc đổi trạng thái.</li></ul>
    <div class="guide-note"><i class="bi bi-exclamation-circle"></i><span>Chỉ chuyển thành học viên khi khách đã xác nhận đăng ký. Hồ sơ “Không quan tâm” phải có ghi chú nguyên nhân để phục vụ báo cáo.</span></div>
</div></section>

<section id="giao-vu" class="card card-soft guide-section"><span>04</span><div>
    <h4>Giáo vụ trung tâm nên làm gì?</h4>
    <h6 class="guide-subtitle">Hồ sơ học viên</h6>
    <ol class="guide-steps"><li>Kiểm tra họ tên, ngày sinh, SĐT học viên, SĐT cha/mẹ/người giám hộ, trường/lớp và khóa học.</li><li>Dùng <strong>Kiểm tra trùng dữ liệu</strong> để gộp, cập nhật hoặc xóa hồ sơ trùng; không xóa khi chưa kiểm tra học phí và lịch sử lớp.</li><li>Khi nhập Excel, dùng file mẫu mới nhất, tối đa 5.000 dòng/lần và theo dõi tiến trình từng dòng.</li><li>Nếu dữ liệu đã có, chọn không ghi đè hoặc ghi đè theo nội dung file; xem kết quả Thêm/Ghi đè/Bỏ qua/Lỗi.</li></ol>
    <h6 class="guide-subtitle">Xếp lớp và điều hành lớp</h6>
    <ol class="guide-steps"><li>Kiểm tra khóa học và trình độ trước khi xếp lớp.</li><li>Chỉ xếp khi lớp còn chỗ; hệ thống tự tạo khoản học phí theo lớp và chính sách miễn giảm.</li><li>Khi chuyển lớp, dùng chức năng chuyển lớp để giữ đúng học phí đã sử dụng, số tiền chuyển sang và số dư.</li><li>Khi học viên nghỉ, cập nhật trạng thái thay vì xóa lịch sử.</li><li>Chỉ đóng lớp khi đã kiểm tra đủ buổi, sổ đầu bài và yêu cầu hoàn thành của giáo viên.</li></ol>
    <h6 class="guide-subtitle">Học phí</h6>
    <ul><li>Đối chiếu đúng học viên, khóa/lớp, miễn giảm và số còn phải đóng trước khi thu.</li><li>Nhập đúng ngày, số tiền, phương thức; bổ sung số phiếu thu nếu đang ở trạng thái chờ.</li><li>Không tạo khoản thu thứ hai nếu khoản thu của cùng lớp đã tồn tại.</li></ul>
</div></section>

<section id="giao-vien" class="card card-soft guide-section"><span>05</span><div>
    <h4>Giáo viên nên làm gì?</h4>
    <p>Giáo viên chỉ nhìn thấy và cập nhật các lớp được phân công giảng dạy.</p>
    <div class="guide-checklist">
        <div><strong>Mỗi buổi học</strong><ol><li>Mở <strong>Lớp giảng dạy & điểm</strong>.</li><li>Chọn đúng lớp và đúng ngày học.</li><li>Điểm danh từng học viên.</li><li>Ghi sổ đầu bài: nội dung, giờ bắt đầu/kết thúc và ghi chú.</li><li>Kiểm tra số buổi hoàn thành đã tăng đúng.</li></ol></div>
        <div><strong>Trong tháng</strong><ol><li>Nhập điểm kiểm tra đúng loại và thang điểm.</li><li>Cập nhật chuyên cần, mức tham gia, bài tập và nhận xét tháng.</li><li>Ghi nhận xét cụ thể để giáo vụ và phụ huynh hiểu tình hình.</li><li>Khi hoàn tất chương trình, gửi yêu cầu đóng lớp cho giáo vụ.</li></ol></div>
    </div>
    <div class="guide-do-dont"><div><strong>Nên</strong><span>Nhập dữ liệu ngay sau buổi học, kiểm tra đúng ngày và đúng học viên.</span></div><div><strong>Không nên</strong><span>Tự xếp, chuyển hoặc xóa học viên khỏi lớp; hãy báo giáo vụ xử lý.</span></div></div>
</div></section>

<section id="lanh-dao" class="card card-soft guide-section"><span>06</span><div>
    <h4>Lãnh đạo nên làm gì?</h4>
    <h6 class="guide-subtitle">Điều hành hằng ngày</h6>
    <ol class="guide-steps"><li>Mở <strong>Tổng quan điều hành</strong> để xem nhân sự, tuyển sinh, đào tạo, học phí và việc quá hạn.</li><li>Vào <strong>Giao & theo dõi công việc</strong>, tạo việc có người chủ trì, người phối hợp, thời hạn và kết quả cần đạt.</li><li>Theo dõi theo thành viên; nhắc việc chậm và đóng việc sau khi đã kiểm tra kết quả.</li><li>Xem hàng đợi tư vấn, hồ sơ quá 3 ngày và tỷ lệ chuyển đổi để phân công lại khi cần.</li><li>Xem chỉ tiêu, doanh thu và báo cáo theo đúng tháng/quý/năm trước khi ra quyết định.</li></ol>
    <h6 class="guide-subtitle">Trách nhiệm theo cấp</h6>
    <ul><li><strong>Giám đốc:</strong> xem toàn cảnh, quản lý tài khoản Phó giám đốc và kiểm tra mọi công việc mà không trở thành người tham gia.</li><li><strong>Phó giám đốc/Trưởng bộ phận:</strong> quản lý công việc trong phạm vi được giao và phối hợp các bộ phận.</li><li><strong>Lãnh đạo nghiệp vụ:</strong> duyệt số liệu, kiểm tra ngoại lệ và chịu trách nhiệm chất lượng dữ liệu của bộ phận.</li></ul>
    <div class="guide-note"><i class="bi bi-graph-up-arrow"></i><span>Lãnh đạo nên dùng báo cáo và lịch sử thao tác để kiểm tra; hạn chế sửa trực tiếp dữ liệu nghiệp vụ nếu nhân viên phụ trách vẫn có thể cập nhật.</span></div>
</div></section>

<section id="quan-tri" class="card card-soft guide-section"><span>07</span><div>
    <h4>Quản trị viên nên làm gì?</h4>
    <ol class="guide-steps"><li>Tạo hồ sơ nhân sự trước, sau đó tạo tài khoản và liên kết đúng nhân sự.</li><li>Chọn đúng vai trò; chỉ bật <strong>Giáo vụ</strong> cho người được phép xếp/chuyển học viên và bật <strong>Kiêm giảng dạy</strong> cho người thực sự phụ trách lớp.</li><li>Cấp quyền theo nguyên tắc tối thiểu: đủ để làm việc, không cấp quyền xóa hoặc quản trị nếu không cần.</li><li>Khi nhân sự nghỉ, khóa tài khoản và chuyển công việc/lớp đang phụ trách trước khi xử lý hồ sơ.</li><li>Kiểm tra Nhật ký hệ thống khi có sai lệch dữ liệu; dùng trang Kiểm thử hệ thống sau thay đổi cấu hình.</li><li>Thay đổi tên phần mềm, logo, màu và hiệu ứng tại Cấu hình phần mềm; kiểm tra trên máy tính và điện thoại.</li></ol>
    <div class="guide-do-dont"><div><strong>Nên</strong><span>Khóa hoặc ngừng hoạt động để giữ lịch sử; ghi nhận lý do khi thay đổi quyền.</span></div><div><strong>Không nên</strong><span>Xóa tài khoản, khóa học hoặc chương trình đang được dữ liệu khác sử dụng.</span></div></div>
</div></section>

<section id="quy-trinh" class="card card-soft guide-section"><span>08</span><div>
    <h4>Quy trình phối hợp giữa các vai trò</h4>
    <div class="table-responsive"><table class="table table-modern guide-responsibility-table"><thead><tr><th>Giai đoạn</th><th>Người thực hiện chính</th><th>Việc phải hoàn tất</th><th>Bàn giao cho</th></tr></thead><tbody>
        <tr><td>Tiếp nhận nhu cầu</td><td>Nhân viên/CTV</td><td>Gửi chỉ tiêu, nguồn khách và ghi chú đầy đủ</td><td>Tư vấn viên</td></tr>
        <tr><td>Tư vấn</td><td>Tư vấn viên</td><td>Liên hệ, cập nhật trạng thái, lịch hẹn và kết quả</td><td>Giáo vụ khi khách đăng ký</td></tr>
        <tr><td>Tạo hồ sơ</td><td>Tư vấn viên/Giáo vụ</td><td>Chuyển thành học viên, kiểm tra hồ sơ và người giám hộ</td><td>Giáo vụ</td></tr>
        <tr><td>Xếp lớp & học phí</td><td>Giáo vụ/Thu ngân</td><td>Xếp đúng lớp, lập và thu đúng khoản học phí</td><td>Giáo viên</td></tr>
        <tr><td>Giảng dạy</td><td>Giáo viên</td><td>Điểm danh, sổ đầu bài, điểm và nhận xét tháng</td><td>Giáo vụ/Lãnh đạo</td></tr>
        <tr><td>Kiểm tra kết quả</td><td>Lãnh đạo</td><td>Theo dõi chỉ tiêu, chất lượng, doanh thu và việc quá hạn</td><td>Các bộ phận xử lý</td></tr>
    </tbody></table></div>
</div></section>

<section id="du-lieu" class="card card-soft guide-section"><span>09</span><div>
    <h4>Quy tắc nhập và bảo vệ dữ liệu</h4>
    <ul><li>Tra cứu bằng tên và SĐT học viên/SĐT người giám hộ trước khi tạo mới.</li><li>Không dùng dữ liệu giả, tên viết tắt khó hiểu hoặc ghi chú chứa mật khẩu.</li><li>Khi nhập Excel, giữ nguyên tiêu đề cột và xem sheet HƯỚNG DẪN trong file mẫu.</li><li>Chỉ chọn ghi đè khi dữ liệu trong file mới hơn; ô trống không nên dùng để xóa nhầm dữ liệu cũ.</li><li>Trước khi gộp hồ sơ trùng, chọn hồ sơ có lịch sử lớp/học phí đầy đủ làm hồ sơ chính.</li><li>Không tải danh sách học viên hoặc báo cáo ra máy cá nhân nếu không phục vụ công việc.</li></ul>
    <div class="guide-note"><i class="bi bi-database-check"></i><span>Số liệu sai phải được sửa tại bản ghi nguồn. Không sửa báo cáo để che sai lệch vì báo cáo được tổng hợp lại từ dữ liệu nghiệp vụ.</span></div>
</div></section>

<section id="xu-ly-loi" class="card card-soft guide-section"><span>10</span><div>
    <h4>Xử lý lỗi thường gặp</h4>
    <div class="guide-faq">
        <details><summary>Không thấy chức năng hoặc nút thao tác</summary><p>Tài khoản chưa có quyền hoặc chưa được đánh dấu đúng vai trò Giáo vụ/Kiêm giảng dạy. Liên hệ quản trị viên, không dùng tài khoản của người khác.</p></details>
        <details><summary>Lọc tháng không đúng</summary><p>Xóa ô ngày cụ thể rồi chọn lại tháng và năm. Nếu vẫn còn điều kiện cũ, nhấn nút xóa lọc.</p></details>
        <details><summary>Nhập Excel thiếu dòng</summary><p>Xem tổng số dòng Thêm, Ghi đè, Bỏ qua và Lỗi. Những dòng trống hoặc không hợp lệ không được tạo; tải file mẫu mới nhất và sửa theo thông báo từng dòng.</p></details>
        <details><summary>Không thấy SĐT phụ huynh</summary><p>Mở chi tiết hoặc chỉnh sửa học viên để kiểm tra từng Cha/Mẹ/Người giám hộ. Nếu hồ sơ chưa có dữ liệu, bổ sung thủ công hoặc nhập lại bằng file có đúng cột.</p></details>
        <details><summary>Phát hiện học viên trùng</summary><p>Vào Kiểm tra trùng dữ liệu, so sánh tên, SĐT học viên và SĐT người giám hộ rồi chọn gộp, cập nhật hoặc xóa. Kiểm tra lịch sử học phí/lớp trước khi xác nhận.</p></details>
        <details><summary>Giao diện chưa cập nhật</summary><p>Nhấn Ctrl + F5. Trên điện thoại, đóng tab rồi mở lại. Nếu vẫn lỗi, chụp toàn màn hình và gửi quản trị viên kèm tên trang, thời gian thao tác.</p></details>
        <details><summary>In hoặc tải PDF không chạy</summary><p>Cho phép trình duyệt mở cửa sổ bật lên và tải xuống; không nhấn nút nhiều lần khi hệ thống đang xử lý.</p></details>
        <details><summary>Số liệu báo cáo chưa đúng</summary><p>Kiểm tra lại kỳ ngày/tháng/năm, trạng thái bản ghi và dữ liệu nguồn. Báo quản trị viên nếu dữ liệu nguồn đúng nhưng báo cáo vẫn sai.</p></details>
    </div>
</div></section>
</div></div>
@endsection
