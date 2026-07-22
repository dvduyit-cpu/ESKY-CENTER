@if ($paginator->hasPages() || $paginator->total() > 0)
<nav class="app-pagination d-flex flex-column flex-md-row justify-content-md-between align-items-center gap-3 w-100 p-3 border-top" role="navigation" aria-label="Phân trang">
    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">Hiển thị</span>
        <form method="GET" action="{{ request()->url() }}" class="d-inline-flex align-items-center gap-2">
            @foreach(request()->except(['per_page','page','activity_page','login_page']) as $key => $value)
                @if(is_scalar($value))<input type="hidden" name="{{$key}}" value="{{$value}}">@endif
            @endforeach
            <select class="form-select form-select-sm" name="per_page" onchange="this.form.submit()" aria-label="Số dòng mỗi trang" style="width:auto;min-height:34px">                @foreach([10,20,30,40,50,100] as $size)
                    <option value="{{$size}}" @selected($paginator->perPage()===$size)>{{$size}}</option>
                @endforeach
            </select>
            <span class="small text-muted">dòng · {{number_format($paginator->total())}} kết quả</span>
        </form>
    </div>
    @if($paginator->hasPages())
    <ul class="pagination pagination-sm mb-0" dir="ltr">
        <li class="page-item {{$paginator->onFirstPage()?'disabled':''}}"><a class="page-link" href="{{$paginator->previousPageUrl() ?: '#'}}" aria-label="Trang trước">‹</a></li>
        @foreach($elements as $element)
            @if(is_string($element))<li class="page-item disabled"><span class="page-link">…</span></li>@endif
            @if(is_array($element))@foreach($element as $page=>$url)<li class="page-item {{$page===$paginator->currentPage()?'active':''}}"><a class="page-link" href="{{$url}}">{{$page}}</a></li>@endforeach @endif
        @endforeach
        <li class="page-item {{!$paginator->hasMorePages()?'disabled':''}}"><a class="page-link" href="{{$paginator->nextPageUrl() ?: '#'}}" aria-label="Trang sau">›</a></li>
    </ul>
    @endif
</nav>
@endif
