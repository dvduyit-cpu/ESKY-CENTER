@extends('layouts.app')
@section('title','Kiểm tra trùng học viên')
@section('header','Quản lý học viên')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h1 class="page-title">Kiểm tra trùng dữ liệu học viên</h1><div class="page-subtitle">Các hồ sơ có họ tên giống nhau sau khi chuẩn hóa; dùng SĐT học viên và người giám hộ để đối chiếu trước khi xử lý.</div></div>
    <div class="d-flex flex-wrap gap-2">
        @if($groups&&auth()->user()->allowed('language_students','update')&&auth()->user()->allowed('language_students','delete'))
        <form method="POST" action="{{route('language-students.merge-all-duplicates')}}">@csrf<input type="hidden" name="confirm_all" value="yes"><button class="btn btn-danger" data-confirm="Gộp ALL {{count($groups)}} nhóm: hệ thống tự chọn hồ sơ chính, chuyển dữ liệu và xóa mềm {{number_format($duplicateRecordCount-count($groups))}} hồ sơ phụ. Thao tác sẽ hủy toàn bộ nếu một nhóm gặp xung đột. Bạn chắc chắn muốn tiếp tục?"><i class="bi bi-intersect me-2"></i>Gộp tất cả (All)</button></form>
        @endif
        <a class="btn btn-light" href="{{route('language-students.index')}}"><i class="bi bi-arrow-left me-2"></i>Quay lại danh sách</a>
    </div>
</div>

<div class="alert alert-warning border-0 shadow-sm"><i class="bi bi-shield-exclamation me-2"></i><strong>Trùng tên chưa luôn đồng nghĩa là cùng một học viên.</strong> Hãy đối chiếu SĐT và dữ liệu liên quan trước khi chọn hồ sơ chính. Khi gộp, hệ thống chuyển dữ liệu liên quan và sẽ chặn nếu phát hiện xung đột lớp học hoặc học phí; thao tác xóa chỉ xóa mềm.</div>
@if($multipleContactGroupCount>0)<div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-octagon-fill me-2"></i><strong>{{$multipleContactGroupCount}} nhóm có nhiều SĐT khác nhau.</strong> Nút All vẫn gộp theo họ tên; hãy cân nhắc xử lý riêng các nhóm này nếu có thể là những học viên khác nhau.</div>@endif

@forelse($groups as $groupIndex=>$group)
<div class="card card-soft mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0"><i class="bi bi-people-fill text-warning me-2"></i>Nhóm trùng {{number_format($groupIndex+1)}}</h5><span class="badge text-bg-warning">{{count($group)}} hồ sơ</span></div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Hồ sơ</th><th>SĐT học viên</th><th>SĐT phụ huynh/người giám hộ</th><th>Dữ liệu liên quan</th><th class="text-end">Xử lý</th></tr></thead>
            <tbody>
            @foreach($group as $student)
                <tr>
                    <td><strong>{{$student->name}}</strong>@if(($recommendedPrimaryIds[$groupIndex]??null)===$student->id)<span class="badge text-bg-success ms-1">All ưu tiên giữ</span>@endif<div class="small text-muted">{{$student->code}} · ID {{$student->id}}</div></td>
                    <td>{{$student->phone?:'Chưa có'}}</td>
                    <td>@forelse($student->guardians as $guardian)<div><strong>{{$guardian->name}}</strong> · {{$guardian->phone?:'Chưa có SĐT'}}</div>@empty<span class="text-muted">Chưa có</span>@endforelse</td>
                    <td><span class="badge bg-light text-dark border me-1">{{$student->enrollments_count}} lớp</span><span class="badge bg-light text-dark border">{{$student->tuition_charges_count}} khoản học phí</span></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-dark" href="{{route('language-students.show',$student)}}"><i class="bi bi-eye"></i></a>
                        @if(auth()->user()->allowed('language_students','update'))<a class="btn btn-sm btn-outline-primary" href="{{route('language-students.edit',$student)}}"><i class="bi bi-pencil"></i></a>@endif
                        @if(auth()->user()->allowed('language_students','delete'))<form class="d-inline" method="POST" action="{{route('language-students.destroy',$student)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa hồ sơ {{$student->name}} ({{$student->code}})?"><i class="bi bi-trash"></i></button></form>@endif
                    </td>
                </tr>
                @if(auth()->user()->allowed('language_students','update')&&auth()->user()->allowed('language_students','delete'))
                <tr class="table-light"><td colspan="5" class="text-end">
                    <form method="POST" action="{{route('language-students.merge-duplicates',$student)}}">@csrf @foreach($group as $duplicate)@if($duplicate->id!==$student->id)<input type="hidden" name="duplicate_ids[]" value="{{$duplicate->id}}">@endif @endforeach<button class="btn btn-sm btn-warning" data-confirm="Giữ {{$student->name}} ({{$student->code}}) làm hồ sơ chính và gộp {{count($group)-1}} hồ sơ còn lại? Dữ liệu lớp học và học phí sẽ được chuyển nếu không có xung đột."><i class="bi bi-intersect me-2"></i>Giữ hồ sơ này và gộp các hồ sơ còn lại</button></form>
                </td></tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="card card-soft"><div class="empty-state py-5"><i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>Không phát hiện hồ sơ học viên trùng.</div></div>
@endforelse
@endsection
