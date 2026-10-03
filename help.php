<?php
require_once __DIR__.'/config/config.php';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Help the Nepal Disaster Archive build a stable, independent home online.">
    <title>Help Us — Nepal Disaster Archive</title>
    <style>
        :root{--ink:#17202a;--paper:#f7f3ed;--red:#9e2b25;--green:#263d3a;--line:#d9d0c6}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Georgia,serif}
        .hero{padding:72px 7%;background:var(--green);color:#fff}.wrap{width:min(980px,90%);margin:auto}
        .eyebrow{font:800 12px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;color:#e5b5a9}
        h1{font-size:clamp(42px,7vw,72px);line-height:1;margin:14px 0 20px}
        .hero p,.content p{font-size:18px;line-height:1.75}.hero p{max-width:760px;color:#e0e7e2}
        .content{padding:48px 0 70px}.panel{padding:28px;background:#fff;border:1px solid var(--line);border-radius:10px;margin-bottom:20px}
        .panel h2{margin-top:0;font-size:27px}.panel p{color:#4f5757}.support{display:inline-block;margin-top:10px;padding:12px 18px;background:var(--red);color:#fff;border-radius:6px;text-decoration:none;font:700 15px Arial,sans-serif}
        .note{font:14px/1.6 Arial,sans-serif;color:#626967}
    </style>
</head>
<body>
<?php require __DIR__.'/includes/public-nav.php'; ?>
<header class="hero"><div class="wrap"><div class="eyebrow">Support public-interest information</div><h1>Help us build a lasting home for the archive.</h1><p>The Nepal Disaster Archive brings disaster history, lived experience, and preparedness information together. Support can help us secure our own domain and dependable hosting.</p></div></header>
<main class="content wrap">
    <section class="panel">
        <h2>Support the archive</h2>
        <p>Contributions are optional and will help with the costs of domain registration, web hosting, and keeping this public resource available. Any future fundraising will be handled transparently and used for the archive’s operating needs.</p>
        <?php if (DONATION_URL !== ''): ?>
            <a class="support" href="<?=e(DONATION_URL)?>" target="_blank" rel="noopener noreferrer">Support us with Buy Me a MoMo</a>
        <?php else: ?>
            <p class="note">Our Buy Me a MoMo donation link will be added here when it is ready.</p>
        <?php endif; ?>
    </section>
    <p class="note">Please use only the official support link shown on this page. Never send payment details or passwords through email or social media messages.</p>
</main>
<?php require __DIR__.'/includes/public-footer.php'; ?>
</body>
</html>
