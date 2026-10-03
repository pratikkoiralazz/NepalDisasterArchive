<?php
require_once __DIR__.'/../config/auth.php'; require_login();
$me=user(); $flash=get_flash();
$notification_sources = [
    'stories' => ['table' => 'story_submissions', 'status' => 'SUBMITTED', 'page' => 'human-submissions.php'],
    'corrections' => ['table' => 'correction_reports', 'status' => 'OPEN', 'page' => 'corrections.php'],
    'volunteers' => ['table' => 'volunteers', 'status' => 'PENDING', 'page' => 'volunteers.php'],
];
if ($me['role'] === 'ADMIN') {
    $notification_read_key = 'admin_notification_read_' . (int)$me['id'];
    if (!isset($_SESSION[$notification_read_key]) || !is_array($_SESSION[$notification_read_key])) {
        $_SESSION[$notification_read_key] = [];
    }
    $current_admin_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $admin_notifications = [];
    foreach ($notification_sources as $key => $source) {
        if ($current_admin_page === $source['page']) {
            $latestId = (int)db()->query('SELECT COALESCE(MAX(id),0) FROM '.$source['table'])->fetchColumn();
            $_SESSION[$notification_read_key][$key] = max(
                (int)($_SESSION[$notification_read_key][$key] ?? 0),
                $latestId
            );
        }
        $count = db()->prepare('SELECT COUNT(*) FROM '.$source['table'].' WHERE status=? AND id>?');
        $count->execute([
            $source['status'],
            (int)($_SESSION[$notification_read_key][$key] ?? 0),
        ]);
        $admin_notifications[$key] = (int)$count->fetchColumn();
    }
} else {
    $admin_notifications = ['stories' => 0, 'corrections' => 0, 'volunteers' => 0];
}
$admin_notification_total = array_sum($admin_notifications);
function admin_notification_badge(int $count): string {
    return $count > 0 ? '<span class="notification-badge">'.($count > 99 ? '99+' : $count).'</span>' : '';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($page_title??'Admin')?> — <?=SITE_NAME?></title><link rel="stylesheet" href="<?=BASE_URL?>/admin/admin.css"><style>
.sidebar nav a{display:flex;align-items:center;justify-content:space-between;gap:8px}
.notification-badge,.notification-count{display:inline-flex;align-items:center;justify-content:center;min-width:19px;height:19px;padding:0 5px;border-radius:999px;background:#c0392b;color:#fff;font:700 11px Arial,sans-serif}
.top .notifications{position:relative;margin-left:auto}
.notifications summary{position:relative;display:flex;align-items:center;justify-content:center;width:40px;height:40px;cursor:pointer;list-style:none;border:1px solid #ddd8d0;border-radius:50%;background:#fff}
.notifications summary::-webkit-details-marker{display:none}
.notifications summary svg{width:21px;height:21px;fill:none;stroke:#17202a;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.notifications .notification-count{position:absolute;top:-6px;right:-7px}
.notification-menu{position:absolute;top:48px;right:0;width:min(320px,calc(100vw - 36px));padding:14px;background:#fff;border:1px solid #ddd8d0;border-radius:10px;box-shadow:0 8px 24px #17202a22;z-index:20}
.notification-menu>strong{display:block;margin:2px 4px 10px}
.notification-menu a{display:flex;justify-content:space-between;gap:12px;padding:11px 8px;border-radius:6px;color:#17202a;text-decoration:none;font-size:14px}
.notification-menu a:hover{background:#f4f1ec}
.notification-menu a b{color:#a11}
.notification-menu p{margin:8px 4px;color:#667;font-size:14px}
.top .site-link{margin-left:0}
</style></head><body>
<aside class="sidebar"><div class="brand">NEPAL<br><span>DISASTER ARCHIVE</span></div><nav>
<a href="<?=BASE_URL?>/admin/index.php">Dashboard</a><?php if($me['role']==='ADMIN'):?><a href="<?=BASE_URL?>/admin/analytics.php">Analytics</a><?php endif;?><a href="<?=BASE_URL?>/admin/stories.php">Stories</a><a href="<?=BASE_URL?>/admin/bulk-locations.php">Bulk Locations</a><?php if($me['role']==='ADMIN'):?><a href="<?=BASE_URL?>/admin/corrections.php">Corrections<?=admin_notification_badge($admin_notifications['corrections'])?></a><a href="<?=BASE_URL?>/admin/human-submissions.php">Human Submissions<?=admin_notification_badge($admin_notifications['stories'])?></a><a href="<?=BASE_URL?>/admin/volunteers.php">Volunteer Network<?=admin_notification_badge($admin_notifications['volunteers'])?></a><a href="<?=BASE_URL?>/admin/documentaries.php">Documentary Archive</a><?php endif;?><a href="<?=BASE_URL?>/admin/story-edit.php">New Story</a><a href="<?=BASE_URL?>/admin/categories.php">Categories</a><a href="<?=BASE_URL?>/admin/users.php">Users</a>
</nav><div class="sidebottom"><small><?=e($me['name'])?> · <?=e($me['role'])?></small><a href="<?=BASE_URL?>/admin/logout.php">Logout</a></div></aside>
<main class="main"><header class="top"><button class="menu" onclick="document.body.classList.toggle('navopen')">☰</button><strong><?=e($page_title??'Admin')?></strong>
<?php if($me['role']==='ADMIN'):?><details class="notifications"><summary aria-label="Notifications, <?=$admin_notification_total?> pending"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><?php if($admin_notification_total):?><span class="notification-count"><?=$admin_notification_total>99?'99+':$admin_notification_total?></span><?php endif;?></summary><div class="notification-menu"><strong>New activity</strong><?php if($admin_notification_total):?><?php if($admin_notifications['stories']):?><a href="<?=BASE_URL?>/admin/human-submissions.php"><span>Story submissions</span><b><?=$admin_notifications['stories']?></b></a><?php endif;?><?php if($admin_notifications['corrections']):?><a href="<?=BASE_URL?>/admin/corrections.php"><span>Correction messages</span><b><?=$admin_notifications['corrections']?></b></a><?php endif;?><?php if($admin_notifications['volunteers']):?><a href="<?=BASE_URL?>/admin/volunteers.php"><span>Volunteer registrations</span><b><?=$admin_notifications['volunteers']?></b></a><?php endif;?><?php else:?><p>You're all caught up.</p><?php endif;?></div></details><?php endif;?>
<a class="site-link" href="<?=BASE_URL?>/" target="_blank">View site ↗</a></header>
<?php if($flash):?><div class="flash <?=$flash['type']?>"><?=e($flash['message'])?></div><?php endif;?>
