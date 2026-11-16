<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$reviewerId=require_roles(['Staff','Administrator']);
require_once __DIR__.'/../config/db.php';
$isAdmin=(($_SESSION['role']??'')==='Administrator');
function sr_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
$error='';$notice='';
if(isset($_GET['document'])){
 $id=filter_var($_GET['id']??0,FILTER_VALIDATE_INT);$which=(string)$_GET['document'];
 if(!$id||!in_array($which,['id_front_path','selfie_with_id_path'],true)){http_response_code(404);exit('Document not found.');}
 $app=hb_fetch_one(hb_query($conn,'SELECT id_front_path,selfie_with_id_path FROM seller_applications WHERE id=? LIMIT 1',[$id]));
 if(!$app){http_response_code(404);exit('Document not found.');}
 $path=realpath(__DIR__.'/../'.$app[$which]);$base=realpath(__DIR__.'/../uploads/private/seller-verification');
 if(!$path||!$base||!str_starts_with($path,$base.DIRECTORY_SEPARATOR)||!is_file($path)){http_response_code(404);exit('Document not found.');}
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(415);exit('Unsupported document.');}
 header('Content-Type: '.$mime);header('Content-Disposition: inline; filename="seller-verification"');header('X-Content-Type-Options: nosniff');readfile($path);exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);$decision=(string)($_POST['decision']??'');$note=trim((string)($_POST['reviewer_note']??''));try{
 if(!$id||!in_array($decision,['approved','rejected','needs_revision'],true))throw new RuntimeException('Invalid review decision.');
 if($decision!=='approved'&&$note==='')throw new RuntimeException('Enter a reason or instructions for the applicant.');
 $app=hb_fetch_one(hb_query($conn,"SELECT * FROM seller_applications WHERE id=? AND status='pending' LIMIT 1",[$id]));if(!$app)throw new RuntimeException('This application has already been reviewed.');
 hb_query($conn,'UPDATE seller_applications SET status=?,reviewer_id=?,reviewer_note=?,reviewed_at=NOW() WHERE id=?',[$decision,$reviewerId,$note?:null,$id]);
 if($decision==='approved'){hb_query($conn,"UPDATE users SET role='Client',is_verified=1 WHERE id=? AND role='Buyer'",[(int)$app['user_id']]);}
 hb_query($conn,'INSERT INTO activity_logs(user_id,action,entity_type,record_id,ip_address) VALUES(?,?,?,?,?)',[$reviewerId,'Seller application '.$decision,'seller_application',$id,$_SERVER['REMOTE_ADDR']??null]);
 hb_query($conn,'INSERT INTO notifications(user_id,title,message,related_entity,related_id) VALUES(?,?,?,?,?)',[(int)$app['user_id'],'Seller application '.ucwords(str_replace('_',' ',$decision)),$decision==='approved'?'Your seller application was approved. Sign in again to access Seller features.':'Your seller application needs attention: '.$note,'seller_application',$id]);
 $notice='Application review saved.';
 }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Could not save the review. Check the seller application database migration.';if(!$e instanceof RuntimeException)error_log('Seller review: '.$e->getMessage());}}
$apps=hb_fetch_all(hb_query($conn,'SELECT a.*,u.username,u.email FROM seller_applications a JOIN users u ON u.id=a.user_id ORDER BY CASE WHEN a.status="pending" THEN 0 ELSE 1 END,a.created_at DESC LIMIT 150'));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Seller applications | HomeBid</title>
    <style>
    :root {
        --navy: #142b49;
        --ink: #17263b;
        --muted: #718096;
        --line: #e5eaf1;
        --bg: #f4f7fb;
        --gold: #d9a441;
        --green: #16734b;
        --red: #a33a32
    }

    * {
        box-sizing: border-box
    }

    body {
        margin: 0;
        background: var(--bg);
        color: var(--ink);
        font: 14px Inter, Segoe UI, Arial, sans-serif
    }

    .top {
        background: #fff;
        border-bottom: 1px solid var(--line);
        padding: 17px 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        position: sticky;
        top: 0;
        z-index: 2
    }

    .brand {
        font-size: 17px;
        font-weight: 800;
        color: var(--navy)
    }

    .brand small {
        display: block;
        color: var(--muted);
        font-size: 10px;
        letter-spacing: .13em;
        margin-top: 3px
    }

    .top a,
    .back {
        color: var(--navy);
        text-decoration: none;
        font-weight: 700
    }

    .wrap {
        max-width: 1180px;
        margin: 30px auto;
        padding: 0 20px
    }

    .heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        margin-bottom: 22px
    }

    .eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .13em;
        color: #9a7024;
        margin: 0 0 8px
    }

    h1 {
        font-size: 30px;
        margin: 0;
        color: var(--navy)
    }

    .sub {
        color: var(--muted);
        line-height: 1.6;
        margin: 8px 0 0
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
        margin: 22px 0
    }

    .stat,
    .card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(18, 39, 67, .035)
    }

    .stat {
        padding: 17px
    }

    .stat span {
        display: block;
        color: var(--muted);
        font-size: 12px
    }

    .stat strong {
        display: block;
        font-size: 25px;
        color: var(--navy);
        margin-top: 8px
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin: 24px 0 12px
    }

    .toolbar h2 {
        margin: 0;
        font-size: 18px
    }

    .toolbar p {
        color: var(--muted);
        margin: 5px 0 0
    }

    .app {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 15px;
        padding: 22px;
        margin: 14px 0;
        box-shadow: 0 4px 18px rgba(18, 39, 67, .035)
    }

    .app-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        border-bottom: 1px solid var(--line);
        padding-bottom: 15px;
        margin-bottom: 16px
    }

    .app h3 {
        font-size: 18px;
        margin: 0 0 7px;
        color: var(--navy)
    }

    .meta {
        color: var(--muted);
        line-height: 1.8;
        font-size: 13px
    }

    .pill {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 30px;
        background: #fff4dc;
        color: #946311;
        font-size: 11px;
        font-weight: 800;
        text-transform: capitalize;
        white-space: nowrap
    }

    .pill.approved {
        background: #e7f6ee;
        color: var(--green)
    }

    .pill.rejected {
        background: #ffefed;
        color: var(--red)
    }

    .pill.needs_revision {
        background: #fff4dc;
        color: #946311
    }

    .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        gap: 22px
    }

    .docs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px
    }

    .docs a,
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 14px;
        border-radius: 9px;
        text-decoration: none;
        font-weight: 700;
        font-size: 12px;
        border: 1px solid #dce4ee;
        background: #f3f6fa;
        color: var(--navy)
    }

    .docs a:hover,
    .btn:hover {
        filter: brightness(.97)
    }

    .review {
        background: #f8fafc;
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 15px
    }

    label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 8px
    }

    textarea {
        width: 100%;
        min-height: 86px;
        resize: vertical;
        border: 1px solid #ccd6e2;
        border-radius: 9px;
        padding: 11px;
        font: 13px inherit;
        outline-color: #8ba5c4
    }

    .hint {
        font-size: 11px;
        color: var(--muted);
        margin: 7px 0 12px
    }

    .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap
    }

    .actions button {
        border: 0;
        border-radius: 8px;
        padding: 10px 12px;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        font-size: 12px;
        background: var(--green)
    }

    .actions .revision {
        background: #9a6a18
    }

    .actions .reject {
        background: var(--red)
    }

    .flash {
        padding: 13px 15px;
        border-radius: 10px;
        background: #e9f7ef;
        color: #17643b;
        margin: 14px 0
    }

    .flash.err {
        background: #fff0ee;
        color: #a33327
    }

    .empty {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 40px 20px;
        text-align: center;
        color: var(--muted)
    }

    .previous {
        background: #f8fafc;
        border-radius: 9px;
        padding: 10px 12px;
        font-size: 12px;
        line-height: 1.5;
        margin-top: 12px
    }

    @media(max-width:760px) {
        .stats {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .heading,
        .app-head {
            flex-direction: column;
            align-items: flex-start
        }

        .content-grid {
            grid-template-columns: 1fr
        }

        .top {
            padding: 14px 18px
        }

        .wrap {
            margin: 22px auto;
            padding: 0 14px
        }

        h1 {
            font-size: 25px
        }
    }
    </style>
</head>

<body>
    <header class="top">
        <div class="brand">HomeBid <small>SELLER VERIFICATION</small></div><a
            href="<?=$isAdmin?'dashboard.php':'staff_dashboard.php'?>">← Back to dashboard</a>
    </header>
    <main class="wrap">
        <div class="heading">
            <div>
                <p class="eyebrow">IDENTITY & ACCOUNT REVIEW</p>
                <h1>Seller applications</h1>
                <p class="sub">Review buyer information and identity documents before granting seller access.</p>
            </div><a class="btn" href="<?=$isAdmin?'dashboard.php?page=seller-applications':'module.php'?>">Dashboard
                overview</a>
        </div>
        <?php if($notice):?><div class="flash"><?=sr_h($notice)?></div><?php endif;?><?php if($error):?><div
            class="flash err"><?=sr_h($error)?></div><?php endif;?>
        <section class="stats">
            <div class="stat"><span>Total applications</span><strong><?=count($apps)?></strong></div>
            <div class="stat"><span>Pending
                    review</span><strong><?=count(array_filter($apps,fn($x)=>$x['status']==='pending'))?></strong></div>
            <div class="stat">
                <span>Approved</span><strong><?=count(array_filter($apps,fn($x)=>$x['status']==='approved'))?></strong>
            </div>
            <div class="stat"><span>Needs
                    attention</span><strong><?=count(array_filter($apps,fn($x)=>in_array($x['status'],['rejected','needs_revision'],true)))?></strong>
            </div>
        </section>
        <div class="toolbar">
            <div>
                <h2>Application queue</h2>
                <p>Newest pending applications appear first.</p>
            </div>
        </div>
        <?php foreach($apps as $a):?>
        <section class="app">
            <div class="app-head">
                <div>
                    <h3><?=sr_h($a['full_name'])?></h3>
                    <div class="meta">Applicant: <b><?=sr_h($a['username'])?></b> · <?=sr_h($a['email'])?><br>Submitted
                        <?=sr_h($a['created_at'])?></div>
                </div><span class="pill <?=sr_h($a['status'])?>"><?=sr_h(str_replace('_',' ',$a['status']))?></span>
            </div>
            <div class="content-grid">
                <div>
                    <div class="meta"><b>Contact details</b><br>Phone: <?=sr_h($a['phone_number'])?><br>Address:
                        <?=sr_h($a['address'])?><br><br><b>Government ID</b><br><?=sr_h($a['id_type'])?> ·
                        <?=sr_h($a['id_number'])?></div>
                    <div class="docs"><a target="_blank" rel="noopener"
                            href="?document=id_front_path&id=<?=(int)$a['id']?>">View government ID photo ↗</a><a
                            target="_blank" rel="noopener"
                            href="?document=selfie_with_id_path&id=<?=(int)$a['id']?>">View selfie holding ID ↗</a>
                    </div>
                    <?php if($a['reviewer_note']):?><div class="previous"><b>Review
                            note</b><br><?=sr_h($a['reviewer_note'])?></div><?php endif;?>
                </div>
                <div class="review"><?php if($a['status']==='pending'):?><form method="post"><input type="hidden"
                            name="csrf_token" value="<?=sr_h(csrf_token())?>"><input type="hidden" name="id"
                            value="<?=(int)$a['id']?>"><label for="note-<?=(int)$a['id']?>">Reviewer note <span
                                style="font-weight:400;color:#718096">(required for revision or
                                rejection)</span></label><textarea id="note-<?=(int)$a['id']?>" name="reviewer_note"
                            placeholder="Add instructions or reason if changes are needed..."></textarea>
                        <p class="hint">Approve only after checking both uploaded identity images.</p>
                        <div class="actions"><button name="decision" value="approved">Approve seller</button><button
                                class="revision" name="decision" value="needs_revision">Needs revision</button><button
                                class="reject" name="decision" value="rejected">Reject</button></div>
                    </form><?php else:?><div class="meta"><b>Review completed</b><br><?=sr_h($a['reviewed_at']??'—')?>
                    </div><?php endif;?></div>
            </div>
        </section>
        <?php endforeach;?>
        <?php if(!$apps):?><div class="empty">
            <h3>No seller applications yet</h3>
            <p>When a buyer submits the Become a Seller form, the application will appear here.</p>
        </div><?php endif;?>
    </main>
</body>

</html>