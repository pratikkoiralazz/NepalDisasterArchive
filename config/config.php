<?php
declare(strict_types=1);
ob_start();
session_start();

// Use environment variables provided by cloud hosting or fallback to defaults
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'nepal_disaster_archive');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
$baseUrl = getenv('BASE_URL');
if ($baseUrl === false) {
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (preg_match('#/admin$#', $scriptDirectory)) {
        $scriptDirectory = substr($scriptDirectory, 0, -strlen('/admin'));
    }   
    if ($scriptDirectory === '.' || $scriptDirectory === '/') {
        $scriptDirectory = '';
    }
    $baseUrl = $scriptDirectory;
}
$baseUrl = trim($baseUrl);
define('BASE_URL', $baseUrl === '' || trim($baseUrl, '/') === '' ? '' : '/' . trim($baseUrl, '/'));
$appUrl = rtrim(trim((string)(getenv('APP_URL') ?: '')), '/');
if ($appUrl !== '') {
    $appUrlParts = parse_url($appUrl);
    if ($appUrlParts === false
        || !in_array(strtolower($appUrlParts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($appUrlParts['host'])
        || isset($appUrlParts['user'])
        || isset($appUrlParts['pass'])
        || isset($appUrlParts['query'])
        || isset($appUrlParts['fragment'])
        || (isset($appUrlParts['path']) && $appUrlParts['path'] !== '')) {
        throw new RuntimeException('APP_URL must be an absolute http(s) origin without a path.');
    }
    define('APP_URL', $appUrl);
} else {
    define('APP_URL', '');
}
define('SITE_NAME', 'Nepal Disaster Archive');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    return $pdo;
}
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function absolute_url(string $path): string {
    if (!str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/[\r\n]/', $path)) {
        throw new InvalidArgumentException('URL path must be a safe local path.');
    }

    $origin = APP_URL;
    if ($origin === '') {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if ($host === '' || preg_match('/[^A-Za-z0-9.:\-\[\]]/', $host)) {
            throw new RuntimeException('Set APP_URL to the public site origin to generate share links.');
        }
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || $forwardedProto === 'https';
        $origin = ($isHttps ? 'https://' : 'http://') . $host;
    }

    return $origin . rtrim(BASE_URL, '/') . $path;
}
function redirect(string $path): never {
    if (!str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/[\r\n]/', $path)) {
        throw new InvalidArgumentException('Redirect path must be a safe local path.');
    }

    $location = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    if (!headers_sent()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: ' . $location, true, 303);
        exit;
    }

    error_log('Redirect requested after response headers were sent; using client-side navigation.');
    $safeLocation = json_encode($location, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url='
        . htmlspecialchars($location, ENT_QUOTES, 'UTF-8')
        . '"><title>Redirecting</title></head><body><script>window.location.replace('
        . $safeLocation
        . ');</script><a href="' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '">Continue</a></body></html>';
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid CSRF token.');
    }
}
function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function slugify(string $text): string {
    $text = trim(mb_strtolower($text));
    $text = preg_replace('/[^\pL\pN]+/u','-',$text);
    return trim($text,'-') ?: 'story-'.time();
}

function nepal_locations(): array {
    return [
        'Koshi'=>['Bhojpur','Dhankuta','Ilam','Jhapa','Khotang','Morang','Okhaldhunga','Panchthar','Sankhuwasabha','Solukhumbu','Sunsari','Taplejung','Terhathum','Udayapur'],
        'Madhesh'=>['Bara','Dhanusha','Mahottari','Parsa','Rautahat','Saptari','Sarlahi','Siraha'],
        'Bagmati'=>['Bhaktapur','Chitwan','Dhading','Dolakha','Kathmandu','Kavrepalanchok','Lalitpur','Makwanpur','Nuwakot','Ramechhap','Rasuwa','Sindhuli','Sindhupalchok'],
        'Gandaki'=>['Baglung','Gorkha','Kaski','Lamjung','Manang','Mustang','Myagdi','Nawalpur','Parbat','Syangja','Tanahun'],
        'Lumbini'=>['Arghakhanchi','Banke','Bardiya','Dang','Gulmi','Kapilvastu','Nawalparasi West','Palpa','Pyuthan','Rolpa','Rukum East','Rupandehi'],
        'Karnali'=>['Dailekh','Dolpa','Humla','Jajarkot','Jumla','Kalikot','Mugu','Rukum West','Salyan','Surkhet'],
        'Sudurpashchim'=>['Achham','Baitadi','Bajhang','Bajura','Dadeldhura','Darchula','Doti','Kailali','Kanchanpur']
    ];
}

function log_admin_activity(string $action, string $details = ''): void {
    if (!function_exists('user') || !user()) return;
    try { db()->prepare('INSERT INTO admin_activity(admin_id,action,details) VALUES(?,?,?)')->execute([user()['id'],$action,$details]); } catch (Throwable $e) { }
}

function track_event(string $eventType, ?int $storyId = null, string $searchTerm = ''): void {
    try { db()->prepare('INSERT INTO analytics_events(story_id,event_type,search_term) VALUES(?,?,?)')->execute([$storyId,$eventType,$searchTerm?:null]); } catch (Throwable $e) { }
}