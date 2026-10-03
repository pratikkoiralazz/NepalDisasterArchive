<?php
require_once __DIR__.'/config/config.php';

if (!extension_loaded('gd') || !function_exists('imagettftext')) {
    error_log('Story social preview requires the GD extension with FreeType support.');
    http_response_code(503);
    exit('Story preview image is unavailable.');
}

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    exit('Story not found.');
}

$query = db()->prepare(
    "SELECT s.title,s.slug,s.year_label,s.location,s.district,s.summary,s.impact,s.content,s.image_path,
            s.updated_at,c.name AS category
     FROM stories s
     LEFT JOIN categories c ON c.id=s.category_id
     WHERE s.slug=? AND s.status='PUBLISHED'
     LIMIT 1"
);
$query->execute([$slug]);
$story = $query->fetch();
if (!$story) {
    http_response_code(404);
    exit('Story not found.');
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$font = null;
foreach ([
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/segoeui.ttf',
] as $candidate) {
    if (is_file($candidate)) {
        $font = $candidate;
        break;
    }
}
if ($font === null) {
    error_log('Story social preview requires an installed TrueType font.');
    http_response_code(503);
    exit('Story preview image is unavailable.');
}

const SHARE_IMAGE_WIDTH = 1200;
const SHARE_IMAGE_HEIGHT = 630;
$image = imagecreatetruecolor(SHARE_IMAGE_WIDTH, SHARE_IMAGE_HEIGHT);
if ($image === false) {
    throw new RuntimeException('Could not create the story preview image.');
}
imageantialias($image, true);

$background = imagecolorallocate($image, 23, 32, 42);
$panel = imagecolorallocate($image, 31, 48, 60);
$red = imagecolorallocate($image, 158, 43, 37);
$white = imagecolorallocate($image, 255, 255, 255);
$muted = imagecolorallocate($image, 221, 228, 232); 
$soft = imagecolorallocate($image, 210, 173, 147);
imagefill($image, 0, 0, $background);

$imagePath = trim((string)($story['image_path'] ?? ''));
$uploadRoot = realpath(__DIR__.'/uploads/stories');
$coverPath = $imagePath !== '' ? realpath(__DIR__.'/'.ltrim($imagePath, '/\\')) : false;
if ($uploadRoot !== false && $coverPath !== false
    && str_starts_with($coverPath, $uploadRoot.DIRECTORY_SEPARATOR)
    && is_file($coverPath)
    && filesize($coverPath) <= 5 * 1024 * 1024) {
    $dimensions = @getimagesize($coverPath);
    if ($dimensions !== false && $dimensions[0] <= 10000 && $dimensions[1] <= 10000
        && $dimensions[0] * $dimensions[1] <= 30000000) {
        $coverData = file_get_contents($coverPath);
        $cover = $coverData === false ? false : @imagecreatefromstring($coverData);
        if ($cover !== false) {
            $coverWidth = imagesx($cover);
            $coverHeight = imagesy($cover);
            $scale = max(SHARE_IMAGE_WIDTH / $coverWidth, SHARE_IMAGE_HEIGHT / $coverHeight);
            $cropWidth = (int)(SHARE_IMAGE_WIDTH / $scale);
            $cropHeight = (int)(SHARE_IMAGE_HEIGHT / $scale);
            imagecopyresampled(
                $image,
                $cover,
                0,
                0,
                (int)(($coverWidth - $cropWidth) / 2),
                (int)(($coverHeight - $cropHeight) / 2),
                SHARE_IMAGE_WIDTH,
                SHARE_IMAGE_HEIGHT,
                $cropWidth,
                $cropHeight
            );
        }
    }
}

imagefilledrectangle($image, 0, 0, SHARE_IMAGE_WIDTH, SHARE_IMAGE_HEIGHT, imagecolorallocatealpha($image, 14, 25, 34, 42));
imagefilledrectangle($image, 0, 0, 15, SHARE_IMAGE_HEIGHT, $red);
imagefilledrectangle($image, 54, 54, 1146, 576, imagecolorallocatealpha($image, 12, 24, 34, 24));
imagefilledrectangle($image, 78, 91, 128, 96, $red);

function preview_text(string $value, int $maxCharacters): string {
    $value = preg_replace('/\s+/u', ' ', trim(strip_tags($value))) ?? '';
    if (mb_strlen($value) > $maxCharacters) {
        $value = rtrim(mb_substr($value, 0, $maxCharacters - 1)).'…';
    }
    return $value;
}

function preview_wrap(string $text, string $font, int $fontSize, int $maxWidth, int $maxLines): array {
    $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line.' '.$word;
        $bounds = imagettfbbox($fontSize, 0, $font, $candidate);
        if ($line !== '' && $bounds !== false && $bounds[2] - $bounds[0] > $maxWidth) {
            $lines[] = $line;
            $line = $word;
            if (count($lines) >= $maxLines) {
                break;
            }
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '' && count($lines) < $maxLines) {
        $lines[] = $line;
    }
    if (count($lines) === $maxLines && count($words) > 0) {
        $last = array_key_last($lines);
        $lines[$last] = rtrim($lines[$last], " .\t\n\r\0\x0B").'…';
    }
    return $lines;
}

$category = preview_text((string)($story['category'] ?: 'DISASTER HISTORY'), 42);
$year = preview_text((string)($story['year_label'] ?: 'NEPAL DISASTER ARCHIVE'), 35);
$title = preview_text((string)$story['title'], 130);
$summarySource = (string)($story['summary'] ?: $story['impact'] ?: strip_tags((string)$story['content']));
$summary = preview_text($summarySource, 190);
$location = preview_text(implode(' · ', array_filter([
    (string)($story['district'] ?? ''),
    (string)($story['location'] ?? ''),
])), 75);

imagettftext($image, 18, 0, 78, 82, $soft, $font, mb_strtoupper($category).'  ·  '.$year);
$titleLines = preview_wrap($title, $font, 43, 1000, 3);
$titleY = 178;
foreach ($titleLines as $line) {
    imagettftext($image, 43, 0, 78, $titleY, $white, $font, $line);
    $titleY += 60;
}

$summaryLines = preview_wrap($summary, $font, 21, 980, 3);
$summaryY = max($titleY + 18, 390);
foreach ($summaryLines as $line) {
    if ($summaryY > 500) {
        break;
    }
    imagettftext($image, 21, 0, 80, $summaryY, $muted, $font, $line);
    $summaryY += 34;
}

imagefilledrectangle($image, 78, 528, 1122, 530, imagecolorallocatealpha($image, 255, 255, 255, 75));
if ($location !== '') {
    imagettftext($image, 17, 0, 80, 565, $white, $font, $location);
}
imagettftext($image, 15, 0, 80, 597, $soft, $font, 'NEPAL DISASTER ARCHIVE  ·  DISASTER HISTORY  ·  HUMAN STORIES');

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="nepal-disaster-story.png"');
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
imagepng($image);
