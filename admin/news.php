<?php
require_once __DIR__.'/_header.php';
$page_title='News';
$id=(int)($_GET['id']??0);
$row=[
    'title'=>'','slug'=>'','summary'=>'','content'=>'','source_name'=>'',
    'source_url'=>'','image_path'=>'','status'=>'DRAFT','published_at'=>'',
];
$errors=[];

if (isset($_GET['delete'])) {
    verify_csrf();
    $deleteId=(int)$_GET['delete'];
    db()->prepare('DELETE FROM news_articles WHERE id=?')->execute([$deleteId]);
    log_admin_activity('delete_news',(string)$deleteId);
    flash('success','News article deleted.');
    redirect('/admin/news.php');
}

if ($id > 0) {
    $find=db()->prepare('SELECT * FROM news_articles WHERE id=?');
    $find->execute([$id]);
    $article=$find->fetch();
    if (!$article) {
        http_response_code(404);
        exit('News article not found.');
    }
    $row=$article;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $row=array_merge($row,[
        'title'=>trim((string)($_POST['title']??'')),
        'slug'=>trim((string)($_POST['slug']??'')),
        'summary'=>trim((string)($_POST['summary']??'')),
        'content'=>trim((string)($_POST['content']??'')),
        'source_name'=>trim((string)($_POST['source_name']??'')),
        'source_url'=>trim((string)($_POST['source_url']??'')),
        'status'=>($_POST['status']??'DRAFT')==='PUBLISHED'?'PUBLISHED':'DRAFT',
        'published_at'=>trim((string)($_POST['published_at']??'')),
    ]);
    $slugInput=$row['slug']!==''?$row['slug']:$row['title'];
    $row['slug']=trim((string)preg_replace('/[^a-z0-9-]+/','-',strtolower(slugify($slugInput))),'-');
    if ($row['slug']==='') {
        $row['slug']='news-'.time();
    }
    if ($row['title']==='' || $row['content']==='') {
        $errors[]='Title and article content are required.';
    }
    if ($row['source_url']!=='') {
        $sourceParts=parse_url($row['source_url']);
        if ($sourceParts===false
            || !in_array(strtolower($sourceParts['scheme']??''),['http','https'],true)
            || empty($sourceParts['host'])
            || !filter_var($row['source_url'],FILTER_VALIDATE_URL)) {
            $errors[]='Source URL must be a valid http or https link.';
        }
    }
    if ($row['published_at']!=='') {
        $publishedAt=DateTime::createFromFormat('Y-m-d\TH:i',$row['published_at']);
        if (!$publishedAt) {
            $errors[]='Enter a valid publication date and time.';
        } else {
            $row['published_at']=$publishedAt->format('Y-m-d H:i:s');
        }
    } elseif ($row['status']==='PUBLISHED') {
        $row['published_at']=$row['published_at']?:date('Y-m-d H:i:s');
    } else {
        $row['published_at']=null;
    }

    $slugCheck=db()->prepare('SELECT id FROM news_articles WHERE slug=? AND id<>? LIMIT 1');
    $slugCheck->execute([$row['slug'],$id]);
    if ($slugCheck->fetch()) {
        $errors[]='That URL slug is already in use. Choose a different one.';
    }

    $uploadedPath=null;
    if (($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error']!==UPLOAD_ERR_OK || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            $errors[]='The image upload failed. Please try again.';
        } elseif ($_FILES['image']['size']>5242880) {
            $errors[]='News images must be 5 MB or smaller.';
        } else {
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
            $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($extensions[$mime])) {
                $errors[]='Use a JPG, PNG, or WebP image.';
            }
        }
    }

    if (!$errors && isset($extensions) && isset($mime)) {
        $directory=__DIR__.'/../uploads/news';
        if (!is_dir($directory) && !mkdir($directory,0755,true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the news upload directory.');
        }
        $fileName=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if (!move_uploaded_file($_FILES['image']['tmp_name'],$directory.'/'.$fileName)) {
            throw new RuntimeException('Could not save the uploaded news image.');
        }
        $uploadedPath='uploads/news/'.$fileName;
        $row['image_path']=$uploadedPath;
    }

    if (!$errors) {
        $publishedAt=$row['published_at']?:null;
        if ($id) {
            $save=db()->prepare(
                'UPDATE news_articles SET title=?,slug=?,summary=?,content=?,source_name=?,source_url=?,image_path=?,status=?,published_at=?,updated_by=? WHERE id=?'
            );
            $save->execute([
                $row['title'],$row['slug'],$row['summary']?:null,$row['content'],
                $row['source_name']?:null,$row['source_url']?:null,$row['image_path']?:null,
                $row['status'],$publishedAt,user()['id'],$id,
            ]);
        } else {
            $save=db()->prepare(
                'INSERT INTO news_articles(title,slug,summary,content,source_name,source_url,image_path,status,published_at,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)'
            );
            $save->execute([
                $row['title'],$row['slug'],$row['summary']?:null,$row['content'],
                $row['source_name']?:null,$row['source_url']?:null,$row['image_path']?:null,
                $row['status'],$publishedAt,user()['id'],user()['id'],
            ]);
        }
        log_admin_activity($id?'update_news':'create_news',$row['title']);
        flash('success',$id?'News article updated.':'News article saved.');
        redirect('/admin/news.php');
    }
}

$articles=db()->query('SELECT id,title,slug,status,published_at,updated_at FROM news_articles ORDER BY updated_at DESC,id DESC')->fetchAll();
?><div class="content">
    <form class="form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <div class="formgrid">
            <div class="field full"><label for="news-title">Title *</label><input id="news-title" name="title" maxlength="255" value="<?=e($row['title'])?>" required></div>
            <div class="field full"><label for="news-slug">URL slug</label><input id="news-slug" name="slug" value="<?=e($row['slug'])?>" placeholder="Generated from the title if blank"><div class="help">Public address will be /news/your-slug.</div></div>
            <div class="field full"><label for="news-summary">Summary</label><textarea id="news-summary" name="summary" style="min-height:100px"><?=e($row['summary'])?></textarea></div>
            <div class="field full"><label for="news-content">News report *</label><textarea id="news-content" name="content" required><?=e($row['content'])?></textarea><div class="help">Separate paragraphs with a blank line. Attribute reporting to its original source.</div></div>
            <div class="field"><label for="source-name">Source / publisher</label><input id="source-name" name="source_name" value="<?=e($row['source_name'])?>"></div>
            <div class="field"><label for="source-url">Original report URL</label><input id="source-url" type="url" name="source_url" value="<?=e($row['source_url'])?>" placeholder="https://..."></div>
            <div class="field full"><label for="news-image">Cover image (JPG, PNG, WebP; up to 5 MB)</label><input id="news-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><?php if ($row['image_path']): ?><div class="help">Current image: <?=e(basename($row['image_path']))?></div><?php endif; ?></div>
            <div class="field"><label for="news-status">Status</label><select id="news-status" name="status"><option value="DRAFT" <?=$row['status']==='DRAFT'?'selected':''?>>Draft</option><option value="PUBLISHED" <?=$row['status']==='PUBLISHED'?'selected':''?>>Published</option></select></div>
            <?php $publishedInput=$row['published_at']?date('Y-m-d\TH:i',strtotime((string)$row['published_at'])):''; ?>
            <div class="field"><label for="published-at">Publication date / time</label><input id="published-at" type="datetime-local" name="published_at" value="<?=e($publishedInput)?>"><div class="help">If blank, the current time is used when publishing.</div></div>
        </div>
        <?php foreach ($errors as $error): ?><p class="flash error"><?=e($error)?></p><?php endforeach; ?>
        <div class="actions"><button type="submit"><?=$id?'Update news':'Save news'?></button><?php if ($id): ?><a class="btn secondary" href="<?=e(BASE_URL)?>/admin/news">New article</a><?php endif; ?></div>
    </form>
    <div class="tablewrap" style="margin-top:25px"><table><thead><tr><th>Title</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($articles as $article): ?><tr>
            <td><?=e($article['title'])?></td><td><?=e($article['status'])?></td><td><?=e($article['published_at']??'—')?></td>
            <td><a class="btn secondary" href="?id=<?=$article['id']?>">Edit</a>
                <?php if ($article['status']==='PUBLISHED'): ?><a class="btn secondary" target="_blank" rel="noopener noreferrer" href="<?=e(BASE_URL.'/news/'.rawurlencode($article['slug']))?>">View</a><?php endif; ?>
                <form method="post" action="?delete=<?=$article['id']?>" style="display:inline" onsubmit="return confirm('Delete this news article?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn danger" type="submit">Delete</button></form>
            </td>
        </tr><?php endforeach; ?>
        <?php if (!$articles): ?><tr><td colspan="4">No news articles have been added yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
<?php require_once __DIR__.'/_footer.php'; ?>
