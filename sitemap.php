<?php
require_once __DIR__.'/config/config.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$urls = [
    '/',
    '/about',
    '/advertise',
    '/career',
    '/documentaries',
    '/help',
    '/human-stories',
    '/news',
    '/preparedness',
    '/resources',
    '/volunteer',
];
$xml = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $path) {
    echo '  <url><loc>'.$xml(absolute_url($path))."</loc></url>\n";
}

$stories = db()->query(
    "SELECT slug, updated_at FROM stories WHERE status='PUBLISHED' ORDER BY id"
)->fetchAll();
foreach ($stories as $story) {
    echo '  <url><loc>'.$xml(absolute_url('/story/'.rawurlencode((string)$story['slug']))).'</loc>';
    if (!empty($story['updated_at'])) {
        echo '<lastmod>'.$xml(date(DATE_W3C, strtotime((string)$story['updated_at']))).'</lastmod>';
    }
    echo "</url>\n";
}

$articles = db()->query(
    "SELECT slug, updated_at FROM news_articles WHERE status='PUBLISHED' ORDER BY id"
)->fetchAll();
foreach ($articles as $article) {
    echo '  <url><loc>'.$xml(absolute_url('/news/'.rawurlencode((string)$article['slug']))).'</loc>';
    if (!empty($article['updated_at'])) {
        echo '<lastmod>'.$xml(date(DATE_W3C, strtotime((string)$article['updated_at']))).'</lastmod>';
    }
    echo "</url>\n";
}

echo "</urlset>\n";
