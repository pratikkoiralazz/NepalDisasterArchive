<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-main">
            <section class="site-footer-brand" aria-labelledby="site-footer-title">
                <a class="site-footer-wordmark" href="<?=e(BASE_URL)?>/" id="site-footer-title">Nepal Disaster Archive</a>
                <p>Disaster history · Human stories · Preparedness</p>
                <p>Documenting Disasters. Building Resilience — an AcademiX Digital initiative.</p>
                <div class="site-footer-social" aria-label="Social media links">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3.3 0-5 1.8-5 5v3H6v4h3v8h4v-8h3.2l.8-4H13V9c0-.7.3-1 1-1z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/academix_digital/" target="_blank" rel="noopener noreferrer" aria-label="AcademiX Digital on Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" class="site-footer-icon-fill"/></svg>
                    </a>
                </div>
            </section>

            <nav class="site-footer-column" aria-label="Archive">
                <h2>Explore</h2>
                <a href="<?=e(BASE_URL)?>/">Disaster archive</a>
                <a href="<?=e(BASE_URL)?>/human-stories">Human stories</a>
                <a href="<?=e(BASE_URL)?>/documentaries">Documentaries</a>
                <a href="<?=e(BASE_URL)?>/resources">Emergency help</a>
                <a href="<?=e(BASE_URL)?>/preparedness">Preparedness guides</a>
            </nav>

            <nav class="site-footer-column" aria-label="About and participation">
                <h2>About &amp; participate</h2>
                <a href="<?=e(BASE_URL)?>/about">About us</a>
                <a href="<?=e(BASE_URL)?>/about#standards-heading">Editorial standards</a>
          
                <a href="<?=e(BASE_URL)?>/help">Help us</a>
                <a href="<?=e(BASE_URL)?>/career">Career</a>
                <a href="<?=e(BASE_URL)?>/advertise">Advertise with us</a>
            </nav>
        </div>

        <div class="site-footer-bottom">
            <span>Copyright &copy; <?=date('Y')?> Nepal Disaster Archive. Content is provided for research and educational use with attribution.</span>
            <a href="<?=e(BASE_URL)?>/about#standards-heading">Sources &amp; editorial approach</a>
        </div>
    </div>
</footer>
<style>
.site-footer{background:#f0efed;color:#454b50;padding:48px max(5%,calc((100% - 1200px)/2));font:14px/1.6 Arial,sans-serif;border-top:1px solid #dedbd7}
.site-footer *{box-sizing:border-box}
.site-footer-inner{max-width:1200px;margin:0 auto}
.site-footer-main{display:grid;grid-template-columns:minmax(230px,1.6fr) repeat(2,minmax(150px,1fr));gap:38px;padding-bottom:34px}
.site-footer-wordmark{color:#17202a;font-size:20px;font-weight:800;letter-spacing:.3px;text-decoration:none}
.site-footer-brand p{max-width:390px;margin:10px 0;color:#59625f}
.site-footer-column{display:grid;align-content:start;gap:9px}
.site-footer-column h2{margin:0 0 8px;color:#17202a;font-size:15px}
.site-footer-column a,.site-footer-bottom a{color:#454b50;text-decoration:none;text-underline-offset:3px}
.site-footer-column a:hover,.site-footer-bottom a:hover{text-decoration:underline;color:#9e2b25}
.site-footer-social{display:flex;gap:10px;margin-top:18px}
.site-footer-social a{display:grid;place-items:center;width:38px;height:38px;border:1px solid #aeb3b5;border-radius:50%;color:#454b50}
.site-footer-social a:hover{background:#9e2b25;border-color:#9e2b25;color:#fff}
.site-footer-social svg{width:19px;height:19px;fill:currentColor;stroke:currentColor;stroke-width:1.5}
.site-footer-social svg rect,.site-footer-social svg circle{fill:none}
.site-footer-social svg .site-footer-icon-fill{fill:currentColor;stroke:none}
.site-footer-bottom{display:flex;justify-content:space-between;gap:18px;padding-top:20px;border-top:1px solid #d4d1cd;color:#59625f;font-size:12px}
.site-footer-bottom a{flex:0 0 auto}
@media(max-width:750px){.site-footer-main{grid-template-columns:repeat(2,minmax(0,1fr));gap:28px}.site-footer-brand{grid-column:1/-1}}
@media(max-width:480px){.site-footer{padding:36px 6%}.site-footer-main{gap:24px 16px}.site-footer-column{font-size:13px}.site-footer-bottom{flex-direction:column}}
@media print{.site-footer{display:none!important}}
</style>
