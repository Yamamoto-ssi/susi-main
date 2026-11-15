<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';
if (($_SESSION['role'] ?? '') === 'Administrator') {
    header('Location: admin/dashboard.php');
    exit;
}
if (($_SESSION['role'] ?? '') === 'Staff') {
    header('Location: staff/dashboard.php');
    exit;
}
if (!empty($_SESSION['user_id'])) {
    header('Location: client/dashboard.php');
    exit;
}
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff & Administrator Login | HomeBid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:linear-gradient(135deg,#0b1736,#172d59);font-family:'DM Sans',sans-serif;color:#172033;display:grid;place-items:center;padding:24px}.login-wrap{width:min(100%,440px)}.brand{display:flex;align-items:center;justify-content:center;gap:11px;color:#fff;text-decoration:none;font:800 24px Manrope,sans-serif;margin-bottom:26px}.brand-mark{width:42px;height:42px;border-radius:12px;background:#d97706;display:grid;place-items:center;font-size:25px}.panel{background:#fff;border-radius:18px;padding:34px;box-shadow:0 24px 70px #0005}.eyebrow{color:#b45309;font-weight:700;letter-spacing:.13em;font-size:11px}.panel h1{font:800 27px Manrope,sans-serif;margin:9px 0}.sub{color:#64748b;line-height:1.6;margin:0 0 25px}.field{display:block;font-size:13px;font-weight:700;margin:16px 0 7px}.field input{display:block;width:100%;padding:13px 14px;border:1px solid #cbd5e1;border-radius:9px;margin-top:7px;font:inherit;outline:none}.field input:focus{border-color:#d97706;box-shadow:0 0 0 3px #d9770620}.submit{width:100%;border:0;border-radius:9px;background:#d97706;color:#fff;padding:14px;font-weight:700;font-size:15px;margin-top:22px;cursor:pointer}.submit:disabled{opacity:.6;cursor:wait}.message{min-height:22px;color:#b91c1c;font-size:13px;line-height:1.5;margin:14px 0 0}.back{text-align:center;margin:22px 0 0;font-size:13px;color:#64748b}.back a{color:#0b1736;font-weight:700}.security{text-align:center;color:#94a3b8;font-size:11px;margin-top:18px}@media(max-width:480px){.panel{padding:25px 21px}}
</style>
</head>
<body>
<div class="login-wrap">
<a class="brand" href="index.php"><span class="brand-mark">⌂</span>HomeBid</a>
<section class="panel">
<p class="eyebrow">HOMEBID WORKSPACE</p>
<h1>Staff &amp; Administrator Login</h1>
<p class="sub">Sign in using your registered staff or administrator account to access the management dashboard.</p>
<form id="staffLoginForm">
<input type="hidden" name="csrf_token" value="<?=h($csrf)?>">
<label class="field">Email address<input type="email" name="email" placeholder="name@example.com" autocomplete="username" required></label>
<label class="field">Password<input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></label>
<button class="submit" type="submit">Log in to Staff Workspace</button>
<p class="message" id="message" role="status" aria-live="polite"></p>
</form>
<p class="back">Are you a buyer or client? <a href="index.php">Go to customer login</a></p>
</section>
<p class="security">Restricted access · Authorized HomeBid personnel only</p>
</div>
<script>
const form=document.getElementById('staffLoginForm');
const message=document.getElementById('message');
form.addEventListener('submit',async event=>{
 event.preventDefault();
 const button=form.querySelector('button[type="submit"]');
 button.disabled=true;message.style.color='#64748b';message.textContent='Checking your account…';
 const data=new FormData(form);data.set('action','staff_login');
 try{
  const response=await fetch('login-agent.php',{method:'POST',body:data,headers:{'Accept':'application/json'}});
  const result=await response.json();
  if(!response.ok||!result.ok)throw new Error(result.message||'Unable to sign in.');
  message.style.color='#15803d';message.textContent=result.message||'Login successful.';
  if(result.redirect)window.location.href=result.redirect;
 }catch(error){message.style.color='#b91c1c';message.textContent=error.message||'A network error occurred.';}
 finally{button.disabled=false;}
});
</script>
</body>
</html>
