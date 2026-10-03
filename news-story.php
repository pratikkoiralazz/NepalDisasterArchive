<?php
require_once __DIR__.'/config/config.php';
$slug = trim((string)($_GET['slug'] ?? ''));
$statement = db()->prepare(
    "SELECT title,slug,summary,content,source_name,source_url,image_path,published_at
     FROM news_articles
     WHERE slug=? AND status='PUBLISHED'
     LIMIT 1"
);
$statement->execute([$slug]);
$article = $statement->fetch();
if (!$article) {
    http_response_code(404);
    exit('News report not found.');
}
$canonicalUrl = absolute_url('/news/'.rawurlencode($article['slug']));
$description = trim((string)($article['summary'] ?: mb_substr(strip_tags($article['content']),0,260)));
$imageUrl = $article['image_path']
    ? absolute_url('/'.ltrim((string)$article['image_path'],'/'))
    : null;
$sourceParts = parse_url((string)$article['source_url']);
$sourceUrl = $sourceParts !== false
    && in_array(strtolower($sourceParts['scheme'] ?? ''), ['http','https'], true)
    && !empty($sourceParts['host'])
    ? (string)$article['source_url']
    : '';
$paragraphs = preg_split("/\R{2,}/", trim((string)$article['content'])) ?: [];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e($article['title'])?> — Nepal Disaster Archive News</title>
    <meta name="description" content="<?=e($description)?>">
    <link rel="canonical" href="<?=e($canonicalUrl)?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Nepal Disaster Archive">
    <meta property="og:title" content="<?=e($article['title'])?>">
    <meta property="og:description" content="<?=e($description)?>">
    <meta property="og:url" content="<?=e($canonicalUrl)?>">
    <?php if ($imageUrl): ?><meta property="og:image" content="<?=e($imageUrl)?>"><?php endif; ?>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f7f3ed;color:#17202a;font-family:Georgia,serif}
        .article{width:min(850px,90%);margin:58px auto 76px}.date{font:800 12px Arial,sans-serif;color:#9e2b25;text-transform:uppercase;letter-spacing:.8px}
        h1{font-size:clamp(38px,7vw,66px);line-height:1.04;margin:15px 0}.summary{font-size:21px;line-height:1.65;color:#505956}
        .source{padding:14px 17px;margin:26px 0;background:#fff;border-left:4px solid #9e2b25;font:14px/1.6 Arial,sans-serif}
        .source a{color:#9e2b25;font-weight:700}.cover{display:block;width:100%;height:auto;max-height:560px;object-fit:cover;border-radius:8px;margin:28px 0}
        .story-content{font-size:19px;line-height:1.85}.story-content p{margin:0 0 24px}
        .back{display:inline-block;margin-top:18px;color:#9e2b25;font:700 14px Arial,sans-serif}
    </style>
</head>
<body>
<?php require __DIR__.'/includes/public-nav.php'; ?>
<main class="article">
    <div class="date"><?=e($article['published_at'] ? date('F j, Y', strtotime($article['published_at'])) : '')?> · News report</div>
    <h1><?=e($article['title'])?></h1>
    <?php if ($article['summary']): ?><p class="summary"><?=e($article['summary'])?></p><?php endif; ?>
    <?php if ($imageUrl): ?><img class="cover" src="<?=e(BASE_URL.'/'.ltrim((string)$article['image_path'],'/'))?>" alt="<?=e($article['title'])?>"><?php endif; ?>
    <?php if ($article['source_name']): ?><div class="source"><strong>Source:</strong> <?=e($article['source_name'])?><?php if ($sourceUrl): ?> · <a href="<?=e($sourceUrl)?>" target="_blank" rel="noopener noreferrer">View original report ↗</a><?php endif; ?></div><?php endif; ?>
    <div class="story-content"><?php foreach ($paragraphs as $paragraph): ?><p><?=nl2br(e($paragraph))?></p><?php endforeach; ?></div>
    <a class="back" href="<?=e(BASE_URL)?>/news">← Back to news</a>
</main>
<?php require __DIR__.'/includes/public-footer.php'; ?>
</body>
</html>
