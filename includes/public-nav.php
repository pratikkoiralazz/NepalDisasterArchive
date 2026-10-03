<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$navItems = [
    ['label' => 'Home', 'href' => BASE_URL . '/#home', 'pages' => ['index.php'], 'section' => 'home'],
    ['label' => 'Stories', 'href' => BASE_URL . '/#stories', 'pages' => ['story.php'], 'section' => 'stories'],
    ['label' => 'Categories', 'href' => BASE_URL . '/#hazards', 'pages' => [], 'section' => 'hazards'],
    ['label' => 'News', 'href' => BASE_URL . '/#news', 'pages' => ['news.php', 'news-story.php'], 'section' => 'news'],
    ['label' => 'Explore', 'href' => BASE_URL . '/#explore', 'pages' => [], 'section' => 'explore'],
    ['label' => 'Emergency Help', 'href' => BASE_URL . '/resources', 'pages' => ['resources.php']],
    ['label' => 'Guides', 'href' => BASE_URL . '/preparedness', 'pages' => ['preparedness.php']],
    ['label' => 'Human Stories', 'href' => BASE_URL . '/human-stories', 'pages' => ['human-stories.php']],
    ['label' => 'Documentaries', 'href' => BASE_URL . '/documentaries', 'pages' => ['documentaries.php']],
    ['label' => 'Volunteer Network', 'href' => BASE_URL . '/volunteer', 'pages' => ['volunteer.php']],
    ['label' => 'About Us', 'href' => BASE_URL . '/about', 'pages' => ['about.php']],
];
?>
<style>
.public-nav{position:sticky;top:0;z-index:20;background:#111820;color:#fff;padding:12px 4%;display:flex;align-items:center;gap:22px;font:13px Arial,sans-serif}
.public-nav-brand{flex:0 0 auto;display:flex;align-items:center;gap:10px;color:#fff;text-decoration:none}
.archive-mark{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;background:#9e2b25;border-radius:9px}
.archive-mark svg{width:27px;height:27px;fill:none;stroke:#fff;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
.archive-wordmark{display:grid;gap:2px}
.archive-wordmark strong{font-size:15px;font-weight:900;letter-spacing:.8px;line-height:1.1}
.archive-wordmark small{color:#d2ad93;font-size:9px;font-weight:700;letter-spacing:.8px;white-space:nowrap}
.public-nav-links{margin-left:auto;display:flex;align-items:center;gap:4px;max-width:100%;overflow-x:auto;scrollbar-width:thin;scrollbar-color:#59636a #111820}
.public-nav-links a{flex:0 0 auto;color:#e1e5e8;text-decoration:none;padding:10px 11px;border-radius:5px;white-space:nowrap}
.public-nav-links a:hover{background:#26333e;color:#fff}
.public-nav-links a[aria-current="page"]{background:#9e2b25;color:#fff;font-weight:700}
.public-nav a:focus-visible{outline:2px solid #f0c2b8;outline-offset:2px}
@media(max-width:900px){.public-nav{align-items:flex-start;flex-direction:column;gap:8px;padding:12px 5%}.public-nav-links{margin:0;width:100%;max-width:none}.public-nav-links a{padding:9px 10px}}
@media(max-width:480px){.archive-mark{flex-basis:38px;width:38px;height:38px}.archive-wordmark strong{font-size:14px}.archive-wordmark small{font-size:8px;letter-spacing:.5px}.public-nav-links{gap:2px}.public-nav-links a{font-size:12px;padding:9px 8px}}
</style>
<nav class="public-nav" aria-label="Main navigation">
    <a class="public-nav-brand" href="<?= BASE_URL ?>/#home" aria-label="Nepal Disaster Archive: disaster history, human stories, preparedness">
        <span class="archive-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 22 12 10l5 7 4-5 7 10"/><path d="M5 25h22M8 28h16"/></svg></span>
        <span class="archive-wordmark"><strong>Nepal Disaster Archive</strong><small>DISASTER HISTORY · HUMAN STORIES · PREPAREDNESS</small></span>
    </a>
    <div class="public-nav-links"><?php foreach ($navItems as $item): $active = in_array($currentPage, $item['pages'], true); ?><a href="<?= e($item['href']) ?>" data-section="<?= e($item['section'] ?? '') ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a><?php endforeach; ?></div>
</nav>
<script>
(function(){var icon=document.createElement('link');icon.rel='icon';icon.type='image/svg+xml';icon.href=<?= json_encode(BASE_URL . '/favicon.svg') ?>;document.head.appendChild(icon);var links=document.querySelectorAll('.public-nav-links a[data-section]');if(!links.length)return;function activate(section){links.forEach(function(link){if(link.dataset.section===section)link.setAttribute('aria-current','page');else if(['index.php','story.php'].includes(<?= json_encode($currentPage) ?>))link.removeAttribute('aria-current')})}if(location.hash){activate(location.hash.slice(1))}links.forEach(function(link){link.addEventListener('click',function(){if(link.dataset.section)activate(link.dataset.section)})})})();
</script>
<?php require __DIR__.'/live-updates.php'; ?>
