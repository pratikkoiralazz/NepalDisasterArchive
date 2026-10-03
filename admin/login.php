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
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Staff Login — <?=SITE_NAME?></title><link rel="stylesheet" href="<?=BASE_URL?>/admin/admin.css"><style>.login-brand{display:flex;align-items:center;gap:12px;margin-bottom:28px}.login-brand-mark{display:grid;place-items:center;width:44px;height:44px;border-radius:9px;background:#9e2b25}.login-brand-mark svg{width:29px;height:29px;fill:none;stroke:#fff;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}.login-brand-copy{display:grid;gap:4px}.login-brand-copy strong{font-size:16px;letter-spacing:.5px}.login-brand-copy small{color:#8d2424;font-size:9px;font-weight:700;letter-spacing:.6px}</style></head>
<body style="display:grid;place-items:center;min-height:100vh"><div class="form" style="width:min(440px,92%)"><div class="login-brand"><span class="login-brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 22 12 10l5 7 4-5 7 10"/><path d="M5 25h22M8 28h16"/></svg></span><span class="login-brand-copy"><strong>Nepal Disaster Archive</strong><small>DISASTER HISTORY · HUMAN STORIES · PREPAREDNESS</small></span></div><h1>Editor Login</h1><?php if($error):?><p style="color:#a00"><?=e($error)?></p><?php endif;?>
<form method="post">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <div class="field">
        <label>Email</label>
        <input type="email" name="email" required>
    </div><br><div class="field"><label>Password</label><input type="password" name="password" required></div><br><button>Sign in</button></form></div><script>(function(){var icon=document.createElement('link');icon.rel='icon';icon.type='image/svg+xml';icon.href=<?=json_encode(BASE_URL . '/favicon.svg')?>;document.head.appendChild(icon)})();</script></body></html>
