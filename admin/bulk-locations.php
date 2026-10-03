<?php
require_once __DIR__.'/_header.php';
$page_title='Bulk Locations';
$locations=nepal_locations();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$ids=array_map('intval',$_POST['story_ids']??[]);$province=trim($_POST['province']??'');$district=trim($_POST['district']??'');
 if($ids&&$province&&$district){$marks=implode(',',array_fill(0,count($ids),'?'));db()->prepare("UPDATE stories SET province=?,district=?,updated_by=? WHERE id IN ($marks)")->execute(array_merge([$province,$district,user()['id']],$ids));log_admin_activity('bulk_location',count($ids).' stories');flash('success','Locations updated.');redirect('/admin/bulk-locations.php');}
 flash('error','Select stories, province, and district.');
}
$rows=db()->query("SELECT id,title,year_label,province,district FROM stories ORDER BY updated_at DESC")->fetchAll();
?><div class="content"><div class="card"><h2>Assign locations in bulk</h2><p>Select stories that share the same province and district.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="actions"><select name="province" required><option value="">Province</option><?php foreach(array_keys($locations) as $province):?><option><?=e($province)?></option><?php endforeach;?></select><select name="district" required><option value="">District</option><?php foreach($locations as $districts):foreach($districts as $district):?><option><?=e($district)?></option><?php endforeach;endforeach;?></select><button>Apply location</button></div><div class="tablewrap"><table><thead><tr><th>Select</th><th>Story</th><th>Year</th><th>Current location</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><input type="checkbox" name="story_ids[]" value="<?=$row['id']?>"></td><td><?=e($row['title'])?></td><td><?=e($row['year_label'])?></td><td><?=e(trim(($row['province']??'').' '.($row['district']??''))?:'Not assigned')?></td></tr><?php endforeach;?></tbody></table></div></form></div></div>
