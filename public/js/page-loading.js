(()=>{
    const overlay=document.querySelector('[data-page-loading]');
    if(!overlay)return;
    let timer;
    let skipNextUnload=false;
    const show=()=>{clearTimeout(timer);document.body.classList.add('page-is-loading');overlay.setAttribute('aria-hidden','false')};
    const hide=()=>{document.body.classList.remove('page-is-loading');overlay.setAttribute('aria-hidden','true')};
    document.addEventListener('click',event=>{
        const link=event.target.closest('a[href]');
        if(!link||event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
        if(link.hasAttribute('download')||link.hasAttribute('data-no-loading')){
            skipNextUnload=true;
            window.setTimeout(()=>{skipNextUnload=false;hide()},3000);
            return;
        }
        if(link.target==='_blank'||link.dataset.bsToggle||link.getAttribute('href').startsWith('#'))return;
        const url=new URL(link.href,location.href);
        if(url.origin!==location.origin||url.href===location.href)return;
        show();
    });
    document.addEventListener('submit',event=>{window.setTimeout(()=>{if(!event.defaultPrevented)show()},0)});
    window.addEventListener('page-loading:show',show);
    window.addEventListener('page-loading:hide',hide);
    window.addEventListener('pageshow',hide);
    window.addEventListener('beforeunload',()=>{if(skipNextUnload){hide();skipNextUnload=false;return}show()});
    window.addEventListener('load',()=>{timer=window.setTimeout(hide,80)});
})();