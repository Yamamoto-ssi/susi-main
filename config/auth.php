<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function require_login(): int {
 if (empty($_SESSION['user_id'])) { header('Location: ../index.php?login=required'); exit; }
 return (int)$_SESSION['user_id'];
}
function require_roles(array $roles): int {
 $id=require_login(); $role=$_SESSION['role']??'';
 if (!in_array($role,$roles,true)) { http_response_code(403); exit('You do not have permission to access this page.'); }
 return $id;
}
function csrf_token(): string {
 if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
 return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
 $sent=$_POST['csrf_token']??'';
 if (!is_string($sent)||!hash_equals(csrf_token(),$sent)) { http_response_code(419); exit('Session expired. Refresh and try again.'); }
}
function h($value): string { return htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8'); }
