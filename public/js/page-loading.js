(()=>{
    const overlay=document.querySelector('[data-page-loading]');
    if(!overlay)return;
    let timer;
    let skipNextUnload=false;
    const downloadPathPattern=/(^|\/)(export|download|template)(\/|$)|qr-download(\/|$)|attachments\/\d+(\/|$)|\/mau-/i;
    const shouldSkipForUrl=url=>{
        if(url.origin!==location.origin)return false;
        const pathname=url.pathname.toLowerCase();
        return downloadPathPattern.test(pathname)
            || /\.(xlsx|xls|csv|pdf|doc|docx|zip|png|jpg|jpeg|svg)$/i.test(pathname);
    };
    const skipLoading=()=>{
        skipNextUnload=true;
        window.setTimeout(()=>{skipNextUnload=false;hide()},3000);
    };
    const show=()=>{clearTimeout(timer);document.body.classList.add('page-is-loading');overlay.setAttribute('aria-hidden','false')};
    const hide=()=>{document.body.classList.remove('page-is-loading');overlay.setAttribute('aria-hidden','true')};
    document.addEventListener('click',event=>{
        const link=event.target.closest('a[href]');
        if(!link||event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
        const url=new URL(link.href,location.href);
        if(link.hasAttribute('download')||link.hasAttribute('data-no-loading')||shouldSkipForUrl(url)){
            skipLoading();
            return;
        }
        if(link.target==='_blank'||link.dataset.bsToggle||link.getAttribute('href').startsWith('#'))return;
        if(url.origin!==location.origin||url.href===location.href)return;
        show();
    });
    document.addEventListener('submit',event=>{
        const form=event.target;
        if(!(form instanceof HTMLFormElement))return;
        window.setTimeout(()=>{
            if(event.defaultPrevented)return;
            const method=(form.getAttribute('method')||'get').toLowerCase();
            const action=new URL(form.getAttribute('action')||location.href,location.href);
            if(form.hasAttribute('data-no-loading')||(method==='get'&&shouldSkipForUrl(action))){
                skipLoading();
                return;
            }
            show();
        },0)
    });
    window.addEventListener('page-loading:show',show);
    window.addEventListener('page-loading:hide',hide);
    window.addEventListener('pageshow',hide);
    window.addEventListener('beforeunload',()=>{if(skipNextUnload){hide();skipNextUnload=false;return}show()});
    window.addEventListener('load',()=>{timer=window.setTimeout(hide,80)});
})();
