<?php
require_once __DIR__.'/config/config.php';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$count = (int)db()->query("SELECT COUNT(*) FROM news_articles WHERE status='PUBLISHED'")->fetchColumn();
$pages = max(1, (int)ceil($count / $perPage));
$page = min($page, $pages);
$statement = db()->prepare(
    "SELECT title,slug,summary,content,source_name,image_path,published_at
     FROM news_articles
     WHERE status='PUBLISHED'
     ORDER BY published_at DESC,id DESC
     LIMIT ? OFFSET ?"
);
$statement->bindValue(1, $perPage, PDO::PARAM_INT);
$statement->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$articles = $statement->fetchAll();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Latest news and updates related to accidents, disasters, and public safety in Nepal.">
    <title>Latest News — Nepal Disaster Archive</title>
    <link rel="canonical" href="<?=e(absolute_url('/news'.($page > 1 ? '?page='.$page : '')))?>">
    <style>
        :root{--ink:#17202a;--paper:#f7f3ed;--red:#9e2b25;--green:#263d3a;--line:#d9d0c6}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Georgia,serif}
        .hero{padding:70px 7% 58px;background:var(--green);color:#fff}.wrap{width:min(1180px,90%);margin:auto}
        .eyebrow{font:800 12px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;color:#e5b5a9}
        h1{font-size:clamp(42px,7vw,74px);line-height:1;margin:14px 0 18px}.hero p{max-width:800px;font-size:20px;line-height:1.6;color:#e0e7e2}
        .content{padding:48px 0 70px}.news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
        .news-card{overflow:hidden;background:#fff;border:1px solid var(--line);border-radius:9px}
        .news-card img{display:block;width:100%;height:210px;object-fit:cover;background:#e8e2da}
        .news-card-body{padding:22px}.date{font:700 12px Arial,sans-serif;color:var(--red);text-transform:uppercase;letter-spacing:.6px}
        .news-card h2{font-size:24px;line-height:1.2;margin:10px 0}.news-card p{color:#555;line-height:1.65}
        .read{display:inline-block;margin-top:8px;color:var(--red);font:700 14px Arial,sans-serif;text-decoration:none}
        .empty{padding:28px;background:#fff;border:1px solid var(--line);border-radius:8px;color:#555}
        .pagination{display:flex;justify-content:center;gap:8px;margin-top:30px}.pagination a,.pagination span{padding:10px 14px;border:1px solid var(--line);border-radius:5px;background:#fff;color:var(--ink);font:700 14px Arial;text-decoration:none}
        .pagination .current{background:var(--red);border-color:var(--red);color:#fff}
        @media(max-width:850px){.news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:580px){.news-grid{grid-template-columns:1fr}.hero{padding:52px 0}}
    </style>
</head>
<body>
<?php require __DIR__.'/includes/public-nav.php'; ?>
<header class="hero"><div class="wrap"><div class="eyebrow">Latest updates · Nepal</div><h1>News &amp; Updates</h1><p>Recent reports and developments related to accidents, disasters, preparedness, and public safety. Check original sources for the latest verified information.</p></div></header>
<main class="content wrap">
    <?php if ($articles): ?>
        <div class="news-grid">
            <?php foreach ($articles as $article): ?>
                <article class="news-card">
                    <?php if ($article['image_path']): ?><img src="<?=e(BASE_URL.'/'.ltrim($article['image_path'],'/'))?>" alt=""><?php endif; ?>
                    <div class="news-card-body">
                        <div class="date"><?=e($article['published_at'] ? date('F j, Y', strtotime($article['published_at'])) : '')?></div>
                        <h2><?=e($article['title'])?></h2>
                        <p><?=e($article['summary'] ?: mb_substr(strip_tags($article['content']),0,190).'…')?></p>
                        <?php if ($article['source_name']): ?><div class="date">Source: <?=e($article['source_name'])?></div><?php endif; ?>
                        <a class="read" href="<?=e(BASE_URL.'/news/'.rawurlencode($article['slug']))?>">Read report →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="News pages">
                <?php for ($number = 1; $number <= $pages; $number++): ?>
                    <?php if ($number === $page): ?><span class="current" aria-current="page"><?=$number?></span>
                    <?php else: ?><a href="<?=e(BASE_URL.'/news?page='.$number)?>"><?=$number?></a><?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty">No news has been published yet. Please check back for updates.</div>
    <?php endif; ?>
</main>
<?php require __DIR__.'/includes/public-footer.php'; ?>
</body>
</html>
