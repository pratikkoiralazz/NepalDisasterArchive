<?php
require_once __DIR__.'/config/config.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo "User-agent: *\n";
echo 'Disallow: '.rtrim(BASE_URL, '/')."/admin/\n";
echo 'Disallow: '.rtrim(BASE_URL, '/')."/live-status\n";
echo 'Sitemap: '.absolute_url('/sitemap.xml')."\n";
