<?php
require_once __DIR__.'/../config/auth.php';
if(user()) redirect('/admin/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $email=trim(mb_strtolower($_POST['email']??'')); $password=$_POST['password']??'';
 if(login_is_throttled($email)){$error='Too many attempts. Try again in 15 minutes.';} else {
 $s=db()->prepare('SELECT * FROM admins WHERE email=? LIMIT 1');$s->execute([$email]);$u=$s->fetch();
 if($u && password_verify($password,$u['password_hash'])){clear_login_failures($email);login_user($u);log_admin_activity('login');redirect('/admin/index.php');}
 record_login_failure($email);$error='Invalid email or password.';}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Staff Login</title><link rel="stylesheet" href="<?=BASE_URL?>/admin/admin.css"></head>
<body style="display:grid;place-items:center;min-height:100vh"><div class="form" style="width:min(440px,92%)"><h1>Editor Login</h1><p>Nepal Disaster Archive</p><?php if($error):?><p style="color:#a00"><?=e($error)?></p><?php endif;?>
<form method="post">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
    </div><br><div class="field"><label>Password</label><input type="password" name="password" required></div><br><button>Sign in</button></form></div></body></html>
