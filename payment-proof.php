<?php
declare(strict_types=1);
require_once __DIR__.'/config/auth.php';
require_roles(['Administrator','Staff']);
require_once __DIR__.'/config/db.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){http_response_code(404);exit('Proof not found.');}
$stmt=$conn->prepare("SELECT proof_path FROM checkout_payments WHERE id=? LIMIT 1");$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$row||empty($row['proof_path'])){http_response_code(404);exit('Proof not found.');}
$base=realpath(__DIR__.'/uploads/private/payment-proofs');$file=realpath(__DIR__.'/'.$row['proof_path']);
if(!$base||!$file||strpos($file,$base.DIRECTORY_SEPARATOR)!==0||!is_file($file)){http_response_code(404);exit('Proof not found.');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);if(!in_array($mime,['image/jpeg','image/png','image/webp','application/pdf'],true)){http_response_code(415);exit('Unsupported file.');}
header('Content-Type: '.$mime);header('Content-Length: '.filesize($file));header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline; filename="payment-proof-'.$id.'"');readfile($file);