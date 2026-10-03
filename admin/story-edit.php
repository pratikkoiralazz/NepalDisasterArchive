<?php
require_once __DIR__.'/_header.php';$id=(int)($_GET['id']??0);$page_title=$id?'Edit Story':'New Story';
$cats=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$locationData=nepal_locations();
$row=['title'=>'','slug'=>'','category_id'=>'','event_date'=>'','year_label'=>'','location'=>'','district'=>'','province'=>'','latitude'=>'','longitude'=>'','image_path'=>'','summary'=>'','content'=>'','deaths'=>'','injuries'=>'','magnitude'=>'','impact'=>'','sources'=>'','featured'=>0,'status'=>'DRAFT'];
if($id){$s=db()->prepare('SELECT * FROM stories WHERE id=?');$s->execute([$id]);$found=$s->fetch();if(!$found)exit('Story not found');$row=$found;}
$restore=(int)($_GET['restore']??0);if($id&&$restore){$r=db()->prepare('SELECT title,content FROM story_revisions WHERE id=? AND story_id=?');$r->execute([$restore,$id]);if($revision=$r->fetch()){$row['title']=$revision['title'];$row['content']=$revision['content'];}}
$revisions=$id?db()->prepare('SELECT r.id,r.created_at,a.name FROM story_revisions r LEFT JOIN admins a ON a.id=r.saved_by WHERE r.story_id=? ORDER BY r.created_at DESC'):null;if($revisions){$revisions->execute([$id]);$revisions=$revisions->fetchAll();}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $data=[
 'title'=>trim($_POST['title']??''),'slug'=>slugify($_POST['slug']??$_POST['title']??''),
 'category_id'=>($_POST['category_id']??'')?:null,'event_date'=>($_POST['event_date']??'')?:null,
 'year_label'=>trim($_POST['year_label']??''),'location'=>trim($_POST['location']??''),'district'=>trim($_POST['district']??''),'province'=>trim($_POST['province']??''),
 'latitude'=>($_POST['latitude']??'')!==''?(float)$_POST['latitude']:null,'longitude'=>($_POST['longitude']??'')!==''?(float)$_POST['longitude']:null,'image_path'=>$row['image_path']??null,
 'summary'=>trim($_POST['summary']??''),'content'=>trim($_POST['content']??''),
 'deaths'=>trim($_POST['deaths']??''),'injuries'=>trim($_POST['injuries']??''),
 'magnitude'=>trim($_POST['magnitude']??''),'impact'=>trim($_POST['impact']??''),
 'sources'=>trim($_POST['sources']??''),'featured'=>isset($_POST['featured'])?1:0,
 'status'=>in_array($_POST['status']??'DRAFT',['DRAFT','REVIEW','PUBLISHED'],true)?$_POST['status']:'DRAFT'
 ];
 if(!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])){
  $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
  if(isset($allowed[$mime]) && $_FILES['image']['size']<=5242880){$name=bin2hex(random_bytes(12)).'.'.$allowed[$mime];$dir=__DIR__.'/../uploads/stories';if(!is_dir($dir))mkdir($dir,0755,true);if(move_uploaded_file($_FILES['image']['tmp_name'],$dir.'/'.$name))$data['image_path']='uploads/stories/'.$name;}
 }
 $sourceTitle=trim($_POST['source_title']??'');$sourcePublisher=trim($_POST['source_publisher']??'');$sourceUrl=trim($_POST['source_url']??'');
 $validDistricts=$data['province']!==''?($locationData[$data['province']]??[]):[];if($data['province']===''||!in_array($data['district'],$validDistricts,true)){$data['district']='';}
 if($data['title']===''||$data['content']===''){flash('error','Title and article content are required.');}
 else {
  try {
   if($id){
    $sql='UPDATE stories SET title=?,slug=?,category_id=?,event_date=?,year_label=?,location=?,district=?,province=?,latitude=?,longitude=?,image_path=?,summary=?,content=?,deaths=?,injuries=?,magnitude=?,impact=?,sources=?,featured=?,status=?,updated_by=? WHERE id=?';
    db()->prepare($sql)->execute([...array_values($data),user()['id'],$id]);
   } else {
    $sql='INSERT INTO stories(title,slug,category_id,event_date,year_label,location,district,province,latitude,longitude,image_path,summary,content,deaths,injuries,magnitude,impact,sources,featured,status,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    db()->prepare($sql)->execute([...array_values($data),user()['id'],user()['id']]);
   }
    $savedId=$id?:(int)db()->lastInsertId();db()->prepare('INSERT INTO story_revisions(story_id,title,content,saved_by) VALUES(?,?,?,?)')->execute([$savedId,$data['title'],$data['content'],user()['id']]);
    if($sourceTitle!=='')db()->prepare('INSERT INTO story_sources(story_id,title,publisher,url,verified) VALUES(?,?,?,?,?)')->execute([$savedId,$sourceTitle,$sourcePublisher,$sourceUrl,user()['role']==='ADMIN'?1:0]);
    flash('success',$id?'Story updated.':'Story created.');redirect('/admin/stories.php');
  } catch(Throwable $e){flash('error','Could not save. Check that the slug is unique.');}
 }
 $row=array_merge($row,$data);
}
?>
<div class="content"><form class="form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="formgrid">
<div class="field full"><label>Story title *</label><input name="title" value="<?=e($row['title'])?>" required></div>
<div class="field"><label>Slug</label><input name="slug" value="<?=e($row['slug'])?>"></div>
<div class="field"><label>Category</label><select name="category_id"><option value="">Uncategorized</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=$row['category_id']==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></div>
<div class="field"><label>Event date</label><input type="date" name="event_date" value="<?=e($row['event_date'])?>"></div>
<div class="field"><label>Year / historical label</label><input name="year_label" value="<?=e($row['year_label'])?>"></div>
<div class="field"><label>Location</label><input name="location" value="<?=e($row['location'])?>"></div>
<div class="field"><label for="district">District</label><select name="district" id="district" required><option value="">Select a province first</option></select></div>
<div class="field"><label>Province</label><select name="province" id="province"><option value="">Select province</option><?php foreach(array_keys($locationData) as $provinceName):?><option value="<?=e($provinceName)?>" <?=$row['province']===$provinceName?'selected':''?>><?=e($provinceName)?></option><?php endforeach;?></select></div>
<div class="field"><label>Latitude</label><input type="number" step="any" name="latitude" value="<?=e((string)$row['latitude'])?>"></div>
<div class="field"><label>Longitude</label><input type="number" step="any" name="longitude" value="<?=e((string)$row['longitude'])?>"></div>
<div class="field full"><label>Cover image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><div class="help">JPG, PNG or WebP up to 5 MB.</div></div>
<div class="field"><label>Magnitude / severity</label><input name="magnitude" value="<?=e($row['magnitude'])?>"></div>
<div class="field"><label>Deaths</label><input name="deaths" value="<?=e($row['deaths'])?>" placeholder="Use estimated/range if uncertain"></div>
<div class="field"><label>Injuries</label><input name="injuries" value="<?=e($row['injuries'])?>"></div>
<div class="field full"><label>Summary</label><textarea style="min-height:110px" name="summary"><?=e($row['summary'])?></textarea></div>
<div class="field full"><label>Article content *</label><textarea class="editor" name="content" required><?=e($row['content'])?></textarea><div class="help">Use blank lines between paragraphs. Plain text is safest.</div></div>
<div class="field full"><label>Impact / damage</label><textarea name="impact"><?=e($row['impact'])?></textarea></div>
<div class="field full"><label>Sources / references</label><textarea name="sources" placeholder="One source per line"><?=e($row['sources'])?></textarea></div>
<div class="field"><label>Structured source title</label><input name="source_title" placeholder="Source title"></div><div class="field"><label>Publisher</label><input name="source_publisher" placeholder="Publisher or organization"></div><div class="field full"><label>Source URL</label><input type="url" name="source_url" placeholder="https://..."></div>
<div class="field"><label>Status</label><select name="status"><option value="DRAFT" <?=$row['status']==='DRAFT'?'selected':''?>>Draft</option><option value="REVIEW" <?=$row['status']==='REVIEW'?'selected':''?>>Needs review</option><option value="PUBLISHED" <?=$row['status']==='PUBLISHED'?'selected':''?>>Published</option></select></div>
<div class="field"><label><input type="checkbox" name="featured" <?=$row['featured']?'checked':''?>> Featured story</label></div>
</div><div class="actions"><button>Save Story</button><a class="btn secondary" href="<?=BASE_URL?>/admin/stories">Cancel</a><?php if($id):?><a class="btn secondary" target="_blank" href="<?=BASE_URL?>/story/<?=rawurlencode($row['slug'])?>">Preview</a><a class="btn secondary" href="<?=BASE_URL?>/admin/gallery?story_id=<?=$id?>">Gallery</a><?php endif;?></div></form></div>
<script>var provinceSelect=document.getElementById('province');var districtSelect=document.getElementById('district');var districtsByProvince=<?=json_encode($locationData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;var savedProvince=<?=json_encode((string)$row['province'])?>;var savedDistrict=<?=json_encode((string)$row['district'])?>;function refreshDistricts(){var province=provinceSelect.value;districtSelect.innerHTML='';var placeholder=document.createElement('option');placeholder.value='';placeholder.textContent=province?'Select district':'Select a province first';districtSelect.appendChild(placeholder);(districtsByProvince[province]||[]).forEach(function(name){var option=document.createElement('option');option.value=name;option.textContent=name;districtSelect.appendChild(option)});if(savedProvince===province&&savedDistrict&&Array.from(districtSelect.options).some(function(option){return option.value===savedDistrict})){districtSelect.value=savedDistrict}else{districtSelect.value='';}savedDistrict='';}provinceSelect.addEventListener('change',refreshDistricts);provinceSelect.value=savedProvince;refreshDistricts();</script>
<?php if($revisions):?><div class="content"><div class="card"><h3>Revision history</h3><?php foreach($revisions as $revision):?><p><?=e($revision['created_at'])?> · <?=e($revision['name']??'Unknown editor')?> <?php if(isset($revision['id'])):?><a class="btn secondary" href="?id=<?=$id?>&restore=<?=$revision['id']?>">Restore</a><?php endif;?></p><?php endforeach;?></div></div><?php endif;?>
 