@extends('layouts.app')
@section('title',$task->title) @section('header','Chi tiết công việc')
@section('content')
@php
    $mine=$task->assignees->firstWhere('user_id',auth()->id());
    $allDone=$task->assignees->every(fn($a)=>$a->completed_at);
    $mentionUsers=$task->assignees
        ->pluck('user')
        ->push($task->creator)
        ->filter(fn($user)=>$user && (int)$user->id!==auth()->id())
        ->unique('id')
        ->sortBy('name')
        ->values();
@endphp
<div class="d-flex justify-content-between gap-3 mb-4"><div><a href="{{route('tasks.index')}}" class="small text-decoration-none">← Danh sách</a><h1 class="page-title mt-2">{{$task->title}}</h1><div class="page-subtitle">Người giao: {{$task->creator->name}} · Hạn {{$task->due_at->format('H:i d/m/Y')}}</div></div><div class="d-flex gap-2 align-items-start">@if($canEdit && !$task->closed_at)<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTaskModal"><i class="bi bi-pencil-square me-1"></i>Chỉnh sửa</button>@endif @if($canClose && ($allDone || $task->closed_at))<form method="POST" action="{{route('tasks.close',$task)}}">@csrf @method('PATCH')<input type="hidden" name="closed" value="{{$task->closed_at?0:1}}"><button class="btn {{$task->closed_at?'btn-outline-primary':'btn-dark'}}"><i class="bi {{$task->closed_at?'bi-unlock':'bi-archive'}} me-1"></i>{{$task->closed_at?'Mở lại task':'Đóng task'}}</button></form>@endif @if($canDelete)<form method="POST" action="{{route('tasks.destroy',$task)}}" data-confirm="Xóa công việc này?">@csrf @method('DELETE')<button class="btn btn-outline-danger">Xóa</button></form>@endif</div></div>
<div class="row g-4"><div class="col-lg-8"><section class="card card-soft mb-4"><div class="card-body p-4"><span class="badge-soft {{$task->closed_at?'badge-gray':($allDone?'badge-success':($task->due_at->isPast()?'badge-danger':'badge-warning'))}}">{{$task->closed_at?'Đã đóng':($allDone?'Hoàn thành':($task->due_at->isPast()?'Quá hạn':'Chưa hoàn thành'))}}</span><h5 class="mt-3">Nội dung</h5><p data-task-rich-display>{!!nl2br(e($task->description ?: 'Không có ghi chú.'))!!}</p></div></section>
@if($mine && !$task->closed_at)<section class="card card-soft mb-4"><div class="card-body p-4"><h5>Báo cáo kết quả thực hiện</h5><form method="POST" action="{{route('tasks.acknowledge',$task)}}" class="mb-3">@csrf @method('PATCH')<input type="hidden" name="acknowledged" value="{{$mine->acknowledged_at?0:1}}"><button class="btn {{$mine->acknowledged_at?'btn-success':'btn-outline-primary'}}" @disabled($mine->completed_at) title="{{$mine->completed_at?'Hãy mở lại công việc trước khi thay đổi xác nhận nhận việc.':''}}"><i class="bi {{$mine->acknowledged_at?'bi-check-circle-fill':'bi-check2-square'}} me-1"></i>{{$mine->acknowledged_at?'Đã nhận việc':'Xác nhận nhận việc'}}</button></form><form method="POST" action="{{route('tasks.complete',$task)}}">@csrf @method('PATCH')<input type="hidden" name="_task_form" value="complete-task"><input type="hidden" name="completed" value="{{$mine->completed_at?0:1}}"><textarea class="form-control mb-2" name="note" rows="2" placeholder="Ghi chú kết quả">{{old('note',$mine->note)}}</textarea><button class="btn {{$mine->completed_at?'btn-outline-warning':'btn-primary'}}" @disabled(!$mine->acknowledged_at && !$mine->completed_at) title="{{!$mine->acknowledged_at && !$mine->completed_at?'Vui lòng xác nhận nhận việc trước.':''}}"><i class="bi {{$mine->completed_at?'bi-arrow-counterclockwise':'bi-check-circle-fill'}} me-1"></i>{{$mine->completed_at?'Mở lại công việc':'Xác nhận đã hoàn thành'}}</button></form></div></section>@endif
<section class="card card-soft" id="taskComments">
    <div class="card-body p-4">
        <h5>Phản hồi</h5>
        @if($task->closed_at)
            <div class="alert alert-secondary py-2"><i class="bi bi-lock me-1"></i>Task đã đóng. Mở lại task để tiếp tục phản hồi.</div>
        @elseif($canParticipate)
            <form method="POST" action="{{route('tasks.comments.store',$task)}}" enctype="multipart/form-data" @if($errors->any() && old('_task_form')==='comment-task') data-open-on-error @endif>
                @csrf
                <input type="hidden" name="_task_form" value="comment-task">
                <input type="hidden" name="parent_comment_id" value="{{old('parent_comment_id')}}">
                <select class="d-none" data-task-mention-users disabled tabindex="-1" aria-hidden="true">
                    @foreach($mentionUsers as $mentionUser)
                        <option value="{{$mentionUser->id}}">{{$mentionUser->name}}</option>
                    @endforeach
                </select>
                <textarea class="form-control mb-2" name="body" rows="3" required placeholder="Nhập nội dung phản hồi...">{{old('body')}}</textarea>
                <div class="task-file-box mb-2">
                    <label class="form-label mb-1"><i class="bi bi-paperclip me-1"></i>File đính kèm</label>
                    <input class="form-control" type="file" name="attachments[]" multiple>
                    <small class="text-muted">Tối đa 5 file, mỗi file không quá 10 MB.</small>
                </div>
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi phản hồi</button>
            </form>
        @else
            <div class="alert alert-info py-2"><i class="bi bi-eye me-1"></i>Bạn đang xem công việc ở chế độ giám sát.</div>
        @endif

        @forelse($comments as $comment)
            @php
                $commentPreview=\Illuminate\Support\Str::limit(
                    trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($comment->body),ENT_QUOTES|ENT_HTML5,'UTF-8'))),
                    140
                );
                $replyUserName=$comment->parent?->user?->name ?: $comment->reply_to_user_name;
                $parentPreview=$comment->parent
                    ? \Illuminate\Support\Str::limit(
                        trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($comment->parent->body),ENT_QUOTES|ENT_HTML5,'UTF-8'))),
                        140
                    )
                    : $comment->reply_excerpt;
            @endphp
            <div class="border-top py-3" data-task-comment="{{$comment->id}}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <strong>{{$comment->user->name}}</strong>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <small class="text-muted">{{$comment->created_at->format('H:i d/m/Y')}}</small>
                        @if($canParticipate && !$task->closed_at)
                            <button
                                class="btn btn-sm btn-light py-1 px-2"
                                type="button"
                                data-task-reply
                                data-keep-label
                                data-comment-id="{{$comment->id}}"
                                data-user-id="{{$comment->user_id}}"
                                data-user-name="{{$comment->user->name}}"
                                data-preview="{{$commentPreview}}"
                                title="Trả lời phản hồi"
                            ><i class="bi bi-reply me-1"></i>Trả lời</button>
                        @endif
                        @if($comment->user_id===auth()->id() && $comment->created_at->gte(now()->subHours(24)))
                            <form method="POST" action="{{route('tasks.comments.retract',[$task,$comment])}}" data-confirm="Thu hồi phản hồi này? Nội dung và file đính kèm sẽ bị xóa.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-1 px-2" title="Thu hồi phản hồi"><i class="bi bi-arrow-counterclockwise me-1"></i>Thu hồi</button>
                            </form>
                        @endif
                    </div>
                </div>
                @if($replyUserName)
                    <div class="task-reply-reference">
                        <i class="bi bi-reply-fill" aria-hidden="true"></i>
                        <div>
                            <small>
                                Trả lời <strong>{{$replyUserName}}</strong>
                                @unless($comment->parent)<span>· phản hồi gốc đã được thu hồi</span>@endunless
                            </small>
                            <div>{{$parentPreview}}</div>
                        </div>
                    </div>
                @endif
                <div data-task-rich-display>{{$comment->body}}</div>
                @if($comment->attachments->isNotEmpty())
                    <div class="mt-2 d-flex flex-column gap-1">
                        @foreach($comment->attachments as $attachment)
                            <a class="small text-decoration-none" href="{{route('tasks.attachments.download',[$task,$attachment])}}" data-attachment-name="{{$attachment->original_name}}"><i class="bi bi-paperclip me-1"></i>{{$attachment->original_name}} <span class="text-muted">({{number_format($attachment->size / 1024,0)}} KB)</span> <i class="bi bi-download ms-1"></i></a>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="text-muted mt-3">Chưa có phản hồi.</p>
        @endforelse
        @if($comments->hasPages())<div class="mt-3">{{$comments->links()}}</div>@endif
    </div>
</section></div>
<div class="col-lg-4"><section class="card card-soft mb-4"><div class="card-body p-4"><h5>Người thực hiện</h5>@foreach($task->assignees as $a)<div class="border-top py-3"><strong>@if($a->is_lead)<i class="bi bi-star-fill text-warning"></i>@endif {{$a->user->name}}</strong><span class="float-end badge-soft {{$a->completed_at?'badge-success':($a->acknowledged_at?'badge-info':'badge-warning')}}">{{$a->completed_at?'Hoàn thành':($a->acknowledged_at?'Đã nhận việc':'Chưa xác nhận')}}</span>@if($a->note)<div class="small text-muted mt-2" data-task-rich-display>{{$a->note}}</div>@endif</div>@endforeach</div></section><section class="card card-soft"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center"><h5 class="mb-0">Lịch sử</h5><button class="btn btn-sm btn-light" type="button" data-keep-label data-bs-toggle="collapse" data-bs-target="#taskHistory"><i class="bi bi-clock-history me-1"></i>Xem lịch sử</button></div><div class="collapse mt-3 @if(request()->has('activities_page')) show @endif" id="taskHistory"><div style="max-height:360px;overflow-y:auto">@foreach($activities as $activity)<div class="border-start ps-3 py-2"><strong class="small">{{$activity->user?->name ?? 'Hệ thống'}}</strong><div class="small">{{$activity->description}}</div><small class="text-muted">{{$activity->created_at->format('H:i d/m/Y')}}</small></div>@endforeach</div>@if($activities->hasPages())<div class="mt-2">{{$activities->links()}}</div>@endif</div></div></section></div></div>
@if($task->attachments->isNotEmpty())
<section class="card card-soft mb-4"><div class="card-body p-4"><h5><i class="bi bi-paperclip me-1"></i>File đính kèm</h5><div class="list-group list-group-flush">@foreach($task->attachments as $attachment)<a class="list-group-item list-group-item-action px-0 d-flex align-items-center justify-content-between" href="{{route('tasks.attachments.download',[$task,$attachment])}}" data-attachment-name="{{$attachment->original_name}}"><span><i class="bi bi-file-earmark me-2"></i>{{$attachment->original_name}}</span><small class="text-muted">{{number_format($attachment->size / 1024, 0)}} KB <i class="bi bi-download ms-2"></i></small></a>@endforeach</div></div></section>
@endif
@if($canEdit && !$task->closed_at)
<div class="modal fade" id="editTaskModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0"><div class="modal-header"><h5 class="modal-title">Chỉnh sửa công việc</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST" action="{{route('tasks.update',$task)}}" enctype="multipart/form-data" class="row g-3" @if($errors->any() && old('_task_form')==='edit-task') data-open-on-error @endif>@csrf @method('PUT')<input type="hidden" name="_task_form" value="edit-task"><div class="col-md-8"><label class="form-label">Tên công việc</label><input class="form-control" name="title" value="{{old('title',$task->title)}}" maxlength="180" required></div><div class="col-md-4"><label class="form-label">Hạn hoàn thành</label><input class="form-control" type="datetime-local" name="due_at" value="{{old('due_at',$task->due_at->format('Y-m-d\TH:i'))}}" required></div><div class="col-md-8"><label class="form-label">Nội dung / ghi chú</label><textarea class="form-control" name="description" rows="3" maxlength="5000">{{old('description',$task->description)}}</textarea></div><div class="col-md-4"><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="normal" @selected(old('priority',$task->priority)==='normal')>Bình thường</option><option value="high" @selected(old('priority',$task->priority)==='high')>Quan trọng</option><option value="low" @selected(old('priority',$task->priority)==='low')>Thấp</option></select></div><div class="col-12"><label class="form-label">Thêm file đính kèm</label><input class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror" type="file" name="attachments[]" multiple>@error('attachments')<div class="invalid-feedback">{{$message}}</div>@enderror @error('attachments.*')<div class="invalid-feedback">{{$message}}</div>@enderror<small class="text-muted">Mỗi công việc tối đa 5 file, mỗi file không quá 10 MB.</small></div><div class="col-lg-8"><label class="form-label">Người nhận (chọn nhiều)</label><div class="border rounded-3 p-3" style="max-height:260px;overflow:auto">@php($selectedIds=collect(old('assignee_ids',$task->assignees->pluck('user_id')->all()))->map(fn($id)=>(int)$id)->all()) @foreach($users as $user)<label class="d-flex gap-2 py-1"><input class="form-check-input" type="checkbox" name="assignee_ids[]" value="{{$user->id}}" data-edit-assignee-name="{{$user->name}}" @checked(in_array($user->id,$selectedIds,true))><span><strong>{{$user->name}}</strong> <small class="text-muted">{{$user->email}}</small></span></label>@endforeach</div></div><div class="col-lg-4"><label class="form-label">Người chủ trì</label><select class="form-select" name="lead_id" data-edit-task-lead data-selected-value="{{old('lead_id',$task->assignees->firstWhere('is_lead',true)?->user_id)}}" required><option value="">Chọn người nhận trước</option></select><small class="text-muted">Chỉ hiển thị người đã tích.</small></div><div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Lưu thay đổi</button></div></form></div></div></div></div>
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const lead=document.querySelector('[data-edit-task-lead]'),items=[...document.querySelectorAll('[data-edit-assignee-name]')];if(lead){const refresh=()=>{const selected=lead.value||lead.dataset.selectedValue;lead.innerHTML='<option value="">Chọn người chủ trì</option>';items.filter(item=>item.checked).forEach(item=>lead.add(new Option(item.dataset.editAssigneeName,item.value,false,item.value===selected)));if(![...lead.options].some(option=>option.value===selected))lead.value=''};items.forEach(item=>item.addEventListener('change',refresh));refresh()}@if($errors->any() && old('_task_form')==='edit-task')bootstrap.Modal.getOrCreateInstance(document.getElementById('editTaskModal')).show();@endif});</script>@endpush
@endif
@endsection
