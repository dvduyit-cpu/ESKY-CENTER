(()=>{
    const iconFor=(label,element)=>{
        const text=label.toLocaleLowerCase('vi');
        if(text.includes('khôi phục'))return 'bi-arrow-counterclockwise';
        if(text.includes('xóa'))return 'bi-trash';
        if(text.includes('duyệt'))return 'bi-check-circle';
        if(text.includes('đã trả')||text.includes('thanh toán'))return 'bi-cash-coin';
        if(text.includes('hủy'))return 'bi-x-circle';
        if(text.includes('in '))return 'bi-printer';
        if(text.includes('pdf'))return 'bi-file-earmark-pdf';
        if(text.includes('excel')||text.includes('xuất'))return 'bi-file-earmark-arrow-down';
        if(text.includes('quyền'))return 'bi-shield-lock';
        if(text.includes('mở'))return 'bi-folder2-open';
        if(text.includes('xem')||text.includes('chi tiết'))return 'bi-eye';
        if(text.includes('sửa')||text.includes('cấu hình'))return 'bi-pencil';
        if(text.includes('khóa'))return 'bi-lock';
        if(text.includes('thêm'))return 'bi-plus-lg';
        if(element.matches('button[type="submit"],button:not([type])'))return 'bi-check2';
        return 'bi-arrow-right';
    };
    const labelForIcon=icon=>{
        const names={
            'bi-eye':'Xem chi tiết','bi-pencil':'Chỉnh sửa','bi-trash':'Xóa','bi-trash3':'Xóa vĩnh viễn',
            'bi-lock':'Khóa hoặc mở','bi-key':'Đặt lại mật khẩu','bi-shield-lock':'Phân quyền',
            'bi-printer':'In','bi-file-earmark-pdf':'Tải PDF','bi-download':'Tải xuống',
            'bi-x-circle':'Xóa khỏi danh sách','bi-plus-lg':'Thêm mới','bi-check-circle':'Xác nhận',
            'bi-person-check':'Chuyển thành học viên','bi-headset':'Tư vấn','bi-arrow-counterclockwise':'Khôi phục',
            'bi-chevron-up':'Thu gọn','bi-chevron-down':'Mở rộng','bi-play-fill':'Xem thử'
        };
        return names[icon]||'Thao tác';
    };
    const convert=element=>{
        if(element.dataset.iconOnlyReady||element.matches('.w-100,[data-bulk-submit],[data-loading-preview],[data-keep-label],[data-no-icon-tooltip],[data-add-row],[data-remove-row]')||element.querySelector('[data-bulk-count]'))return;
        const directText=[...element.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).map(node=>node.textContent).join(' ').replace(/\s+/g,' ').trim();
        const currentIcon=element.querySelector(':scope > i.bi');
        const currentIconClass=currentIcon?[...currentIcon.classList].find(name=>name.startsWith('bi-')):null;
        let label=(element.getAttribute('aria-label')||element.getAttribute('title')||directText||labelForIcon(currentIconClass)).trim();
        const iconClass=currentIconClass||iconFor(label,element);
        if(directText||!currentIcon){element.replaceChildren(Object.assign(document.createElement('i'),{className:`bi ${iconClass}`}));}
        else{currentIcon.className=`bi ${iconClass}`;}
        element.classList.add('icon-action-button');
        element.dataset.iconOnlyReady='1';
        element.setAttribute('aria-label',label);
        element.setAttribute('title',label);
        if(window.bootstrap?.Tooltip)new bootstrap.Tooltip(element,{container:'body',placement:'top',trigger:'hover focus'});
    };
    const scan=root=>(root||document).querySelectorAll?.('.btn-sm:not(.w-100):not([data-bulk-submit]):not([data-loading-preview]),.table-modern td .btn').forEach(convert);
    const start=()=>{scan(document);new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===Node.ELEMENT_NODE){if(node.matches?.('.btn-sm,.table-modern td .btn'))convert(node);scan(node)}}))).observe(document.body,{childList:true,subtree:true})};
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
