<?php
require_once __DIR__.'/_header.php'; $page_title='Stories';
if(isset($_GET['delete']) && user()['role']==='ADMIN'){
 $s=db()->prepare('DELETE FROM stories WHERE id=?');$s->execute([(int)$_GET['delete']]);flash('success','Story deleted.');redirect('/admin/stories.php');
}
$locations=nepal_locations();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['bulk_location'])){verify_csrf();$ids=array_map('intval',$_POST['story_ids']??[]);$province=trim($_POST['province']??'');$district=trim($_POST['district']??'');if($ids&&$province&&$district){$marks=implode(',',array_fill(0,count($ids),'?'));db()->prepare("UPDATE stories SET province=?,district=?,updated_by=? WHERE id IN ($marks)")->execute(array_merge([$province,$district,user()['id']],$ids));log_admin_activity('bulk_location',count($ids).' stories');flash('success','Locations updated.');}redirect('/admin/stories.php');}
$q=trim($_GET['q']??'');$where='';$params=[];
if($q!==''){$where='WHERE s.title LIKE ? OR s.location LIKE ? OR s.year_label LIKE ?';$params=["%$q%","%$q%","%$q%"];}
$s=db()->prepare("SELECT s.*,c.name category FROM stories s LEFT JOIN categories c ON c.id=s.category_id $where ORDER BY s.updated_at DESC");$s->execute($params);$rows=$s->fetchAll();
?>
<div class="content"><div class="toolbar"><form><input class="search" name="q" value="<?=e($q)?>" placeholder="Search title, location, year..."> <button>Search</button></form><a class="btn" href="<?=BASE_URL?>/admin/story-edit.php">+ New Story</a></div><form method="post" id="bulk-form" class="actions"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="bulk_location" value="1"><select name="province" required><option value="">Province</option><?php foreach(array_keys($locations) as $p):?><option><?=e($p)?></option><?php endforeach;?></select><select name="district" required><option value="">District</option><?php foreach($locations as $districts):foreach($districts as $d):?><option><?=e($d)?></option><?php endforeach;endforeach;?></select><button>Apply location to selected</button></form>
<div class="tablewrap"><table><thead><tr><th>Title</th><th>Section</th><th>Category</th><th>Year</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=e($r['title'])?></td><td><?=e($r['story_type']==='HUMAN'?'Human story':'Disaster history')?></td><td><?=e($r['category']??'—')?></td><td><?=e($r['year_label'])?></td><td><span class="badge <?=strtolower($r['status'])?>"><?=e($r['status'])?></span></td><td><?=e($r['updated_at'])?></td><td><a class="btn secondary" href="<?=BASE_URL?>/admin/story-edit.php?id=<?=$r['id']?>">Edit</a> <?php if(user()['role']==='ADMIN'):?><a class="btn danger" data-confirm="Delete this story?" href="?delete=<?=$r['id']?>">Delete</a><?php endif;?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="6">No stories found.</td></tr><?php endif;?></tbody></table></div></div>
 