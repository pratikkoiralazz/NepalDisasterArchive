<div id="page-navigation-loader" class="page-navigation-loader" role="status" aria-live="polite" aria-label="Please wait" hidden>
    <div class="page-navigation-loader-panel">
        <div class="page-navigation-loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        <span>Please wait...</span>
    </div>
</div>
<style>
.page-navigation-loader{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:24px;background:transparent;color:#b8b8b8;font:16px Arial,sans-serif;pointer-events:none}
.page-navigation-loader[hidden]{display:none}
.page-navigation-loader-panel{display:grid;justify-items:center;gap:14px}
.page-navigation-loader-dots{display:flex;gap:10px}
.page-navigation-loader-dots span{width:18px;height:18px;border-radius:50%;background:#3b82f6;animation:page-navigation-loader-pulse .8s ease-in-out infinite alternate}
.page-navigation-loader-dots span:nth-child(2){animation-delay:.15s}
.page-navigation-loader-dots span:nth-child(3){animation-delay:.3s}
@keyframes page-navigation-loader-pulse{from{opacity:.45;transform:scale(.82)}to{opacity:1;transform:scale(1)}}
@media(prefers-reduced-motion:reduce){.page-navigation-loader-dots span{animation:none}}
</style>
<script>
(function(){
    var loader=document.getElementById('page-navigation-loader');
    if(!loader)return;
    var hideTimer;
    function hide(){
        window.clearTimeout(hideTimer);
        loader.hidden=true;
        document.documentElement.removeAttribute('aria-busy');
    }
    function show(){
        if(!loader.hidden)return;
        loader.hidden=false;
        document.documentElement.setAttribute('aria-busy','true');
        hideTimer=window.setTimeout(hide,1000);
    }
    document.addEventListener('click',function(event){
        if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
        var target=event.target;
        var link=target instanceof Element?target.closest('a[href]'):null;
        if(!link||link.hasAttribute('download')||link.relList.contains('external'))return;
        if(link.target&&link.target.toLowerCase()!=='_self')return;
        var destination=new URL(link.href,window.location.href);
        if(destination.origin!==window.location.origin)return;
        if(destination.pathname===window.location.pathname&&destination.search===window.location.search)return;
        show();
    });
    document.addEventListener('submit',function(event){
        if(event.defaultPrevented)return;
        var form=event.target;
        var submitter=event.submitter;
        var target=submitter&&submitter.formTarget?submitter.formTarget:form.target;
        if(target&&target.toLowerCase()!=='_self')return;
        if(form.method.toLowerCase()==='dialog')return;
        if(new URL(form.action,window.location.href).origin!==window.location.origin)return;
        show();
    });
    window.addEventListener('pageshow',hide);
})();
</script>
