<?php
require_once __DIR__.'/_header.php'; $page_title='Dashboard';
$stories=(int)db()->query('SELECT COUNT(*) FROM stories')->fetchColumn();
$published=(int)db()->query("SELECT COUNT(*) FROM stories WHERE status='PUBLISHED'")->fetchColumn();
$drafts=(int)db()->query("SELECT COUNT(*) FROM stories WHERE status='DRAFT'")->fetchColumn();
$cats=(int)db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$users=(int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
?>
<div class="content"><div class="grid"><div class="card"><h3>Total Stories</h3><div class="stat"><?=$stories?></div></div><div class="card"><h3>Published</h3><div class="stat"><?=$published?></div></div><div class="card"><h3>Drafts</h3><div class="stat"><?=$drafts?></div></div><div class="card"><h3>Categories</h3><div class="stat"><?=$cats?></div></div></div>
<div class="card" style="margin-top:20px"><h2>Editorial workspace</h2><p>Create, edit, review and publish disaster stories from one place. Historical uncertainty should be explicitly identified and source notes attached to articles.</p><a class="btn" href="<?=BASE_URL?>/admin/story-edit">+ Create story</a> <a class="btn secondary" href="<?=BASE_URL?>/admin/stories">Manage stories</a></div>
<?php if(user()['role']==='ADMIN'):?><div class="card" style="margin-top:20px"><h3>Editorial accounts</h3><p><?=$users?> administrator/editor account(s).</p><a class="btn secondary" href="<?=BASE_URL?>/admin/users">Manage users</a></div><?php endif;?></div>
 