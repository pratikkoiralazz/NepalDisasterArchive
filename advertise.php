<?php
require_once __DIR__.'/config/config.php';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Partnership and advertising information for the Nepal Disaster Archive.">
    <title>Advertise &amp; Partner — Nepal Disaster Archive</title>
    <style>
        :root{--ink:#17202a;--paper:#f7f3ed;--red:#9e2b25;--green:#263d3a;--line:#d9d0c6}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Georgia,serif}
        .hero{padding:72px 7%;background:var(--green);color:#fff}.wrap{width:min(980px,90%);margin:auto}
        .eyebrow{font:800 12px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;color:#e5b5a9}
        h1{font-size:clamp(42px,7vw,72px);line-height:1;margin:14px 0 20px}
        .hero p,.content p,.content li{font-size:18px;line-height:1.75}.hero p{max-width:760px;color:#e0e7e2}
        .content{padding:48px 0 70px}.panel{padding:32px;background:#fff;border:1px solid var(--line);border-radius:10px;margin-bottom:20px}
        .panel h2{margin-top:0;font-size:27px;color:var(--ink)}
        .panel p{color:#4f5757;margin-bottom:24px}
        .action{display:inline-block;margin-top:10px;padding:12px 20px;background:var(--red);color:#fff;border-radius:6px;text-decoration:none;font:700 15px Arial,sans-serif}
        .action-secondary{display:inline-block;margin-top:10px;margin-left:10px;padding:12px 20px;background:transparent;color:var(--green);border:2px solid var(--green);border-radius:6px;text-decoration:none;font:700 15px Arial,sans-serif}
    </style>
</head>
<body>
<?php require __DIR__.'/includes/public-nav.php'; ?>
<header class="hero">
    <div class="wrap">
        <div class="eyebrow">Partnerships &amp; Advertising</div>
        <h1>Coming Soon.</h1>
        <p>We are building structured opportunities for brand partnerships and advertising. Check back soon for updates.</p>
    </div>
</header>
<main class="content wrap">
    <section class="panel">
        <h2>Advertising Programs Are in Development</h2>
        <p>We are currently focused on expanding our historical archive, compiling comprehensive data, and documenting Nepal's disaster history. Structured advertising and partnership packages will be introduced soon to support our public-interest mission.</p>
        <p>In the meantime, we invite you to keep exploring our published records, stories, and research timelines.</p>
        <a class="action" href="<?=e(BASE_URL)?>/#stories">Explore Our Stories</a>
    </section>

    <section class="panel">
        <h2>Support Our Work</h2>
        <p>If you genuinely value the work we are doing to document Nepal's disaster history and keep this public resource online, your support goes a long way toward covering our domain and hosting expenses.</p>
        <a class="action" href="<?= BASE_URL ?>/help">Support the Archive</a>
    </section>
</main>
<?php require __DIR__.'/includes/public-footer.php'; ?>
</body>
</html>