<?php
require_once __DIR__.'/_header.php';$page_title='Categories';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$name=trim($_POST['name']??'');
 if($name){try{$s=db()->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)');$s->execute([$name,slugify($name),trim($_POST['description']??'')]);flash('success','Category added.');}catch(Throwable $e){flash('error','Category already exists.');}}
 redirect('/admin/categories.php');
}
$rows=db()->query('SELECT c.*,COUNT(s.id) stories FROM categories c LEFT JOIN stories s ON s.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
?>
<div class="content"><form class="form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><h2>Add category</h2><div class="formgrid"><div class="field"><label>Name</label><input name="name" required></div><div class="field"><label>Description</label><input name="description"></div></div><br><button>Add Category</button></form>
<div class="tablewrap" style="margin-top:20px"><table><tr><th>Name</th><th>Slug</th><th>Stories</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['name'])?></td><td><?=e($r['slug'])?></td><td><?=$r['stories']?></td></tr><?php endforeach;?></table></div></div>
 