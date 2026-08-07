<?php require_once __DIR__.'/config.php';
if(!isLoggedIn()){header('Location: '.SITE_URL.'/login.php');exit;}
$user=getCurrentUser();$orders=getUserOrders($user['id']);$pp=loadPending();
$myPending=array_filter($pp,fn($p)=>($p['user_id']??'')===$user['id']);
if(isset($_GET['logout'])){session_destroy();header('Location: '.SITE_URL);exit;}
?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پنل کاربری | AlexVPN</title><link rel="icon" href="/alexvpn.png">
<style>
@import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Vazirmatn',Tahoma,sans-serif;background:#060a1a;color:#e8e8f2;min-height:100vh}
.bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}
.bg .o{position:absolute;border-radius:50%;filter:blur(130px);opacity:.05;animation:float 20s infinite}
.bg .o:nth-child(1){width:500px;height:500px;background:radial-gradient(circle,#7c6ff7,transparent 70%);top:-100px;right:-100px}
.bg .o:nth-child(2){width:400px;height:400px;background:radial-gradient(circle,#00d4cc,transparent 70%);bottom:-100px;left:-80px;animation-delay:-8s}
@keyframes float{0%,100%{transform:translate(0,0)}25%{transform:translate(80px,-60px)}50%{transform:translate(-40px,70px)}75%{transform:translate(-60px,-40px)}}
.header{position:sticky;top:0;z-index:100;backdrop-filter:blur(30px);background:rgba(6,10,26,0.8);border-bottom:1px solid rgba(255,255,255,0.06);padding:12px 20px}
.header-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.logo{display:flex;align-items:center;gap:8px;text-decoration:none;color:#e8e8f2;font-weight:900}.logo img{width:36px;height:36px}
.logo span{background:linear-gradient(135deg,#7c6ff7,#00d4cc);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:36px;font-weight:700;font-size:.82rem;text-decoration:none;border:none;cursor:pointer;transition:all .3s;font-family:inherit}
.btn-primary{background:linear-gradient(135deg,#7c6ff7,#5b4fcf);color:#fff}.btn-danger{background:linear-gradient(135deg,#ff6b9d,#e85d75);color:#fff}
.container{max-width:1100px;margin:0 auto;padding:24px 20px;position:relative;z-index:1}
.profile-card{background:rgba(12,16,48,0.8);border:1px solid rgba(255,255,255,0.06);border-radius:24px;padding:28px;backdrop-filter:blur(20px);margin-bottom:18px;text-align:center}
.profile-card .avatar{width:64px;height:64px;border-radius:50%;background:rgba(124,111,247,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.8rem}.profile-card h2{font-weight:900}
.card{background:rgba(12,16,48,0.8);border:1px solid rgba(255,255,255,0.06);border-radius:22px;padding:20px;backdrop-filter:blur(20px);margin-bottom:16px}.card h3{font-weight:900;margin-bottom:12px}
table{width:100%;border-collapse:collapse;font-size:.8rem}th{color:#00d4cc;font-weight:700;padding:10px;text-align:right;border-bottom:2px solid rgba(255,255,255,0.06)}td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.04)}
.badge{padding:4px 10px;border-radius:16px;font-size:.68rem;font-weight:700}.badge-success{background:rgba(0,212,204,0.1);color:#00d4cc}.badge-warning{background:rgba(253,203,110,0.1);color:#fdcb6e}.badge-danger{background:rgba(255,107,157,0.1);color:#ff6b9d}
.sub-link{color:#00d4cc;font-family:monospace;font-size:.75rem;word-break:break-all;direction:ltr;display:inline-block;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.copy-btn{background:none;border:none;color:#00d4cc;cursor:pointer;font-size:.8rem}.toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:rgba(0,212,204,0.15);border:1px solid rgba(0,212,204,0.3);color:#00d4cc;padding:12px 24px;border-radius:36px;font-weight:700;z-index:999;animation:toastIn .4s ease,toastOut .4s ease 2s forwards;display:flex;align-items:center;gap:8px}
@keyframes toastIn{from{opacity:0;transform:translateX(-50%) translateY(20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}@keyframes toastOut{from{opacity:1}to{opacity:0;transform:translateX(-50%) translateY(-10px)}}
@media(max-width:480px){.container{padding:14px 10px}table{font-size:.72rem}}
</style></head><body>
<div class="bg"><div class="o"></div><div class="o"></div></div>
<div class="header"><div class="header-inner"><a href="/" class="logo"><img src="/alexvpn.png"><span>AlexVPN</span></a><div style="display:flex;gap:8px;"><a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">خرید</a><a href="?logout=1" class="btn btn-danger">خروج</a></div></div></div>
<div class="container">
    <div class="profile-card"><div class="avatar">👤</div><h2><?php echo htmlspecialchars($user['name']); ?></h2><p style="color:#8080a0;"><?php echo htmlspecialchars($user['email']); ?></p></div>
    
    <?php if(!empty($myPending)): ?><div class="card"><h3>⏳ پرداخت‌های در انتظار</h3><div style="overflow-x:auto;"><table><thead><tr><th>پلن</th><th>مبلغ</th><th>تاریخ</th><th>وضعیت</th><th>جزئیات</th></tr></thead><tbody>
    <?php foreach(array_reverse($myPending) as $p): $status=$p['status']??'pending'; ?>
    <tr><td><?php echo htmlspecialchars($p['plan_name']??''); ?></td><td><strong><?php echo number_format((int)($p['price']??0)); ?></strong> ت</td><td><?php echo htmlspecialchars($p['created_at']??''); ?></td>
    <td><?php if($status==='approved'): ?><span class="badge badge-success">✅ تایید شد</span><?php elseif($status==='rejected'): ?><span class="badge badge-danger">❌ رد شد</span><?php else: ?><span class="badge badge-warning">⏳ منتظر</span><?php endif; ?></td>
    <td><?php if($status==='approved'&&!empty($p['subscription_url'])): ?><span class="sub-link"><?php echo htmlspecialchars($p['subscription_url']); ?></span> <button class="copy-btn" onclick="copyUrl('<?php echo htmlspecialchars(addslashes($p['subscription_url'])); ?>')">📋</button><?php elseif($status==='rejected'&&!empty($p['reject_reason'])): ?><span style="color:#ff6b9d;font-size:.75rem;"><?php echo htmlspecialchars($p['reject_reason']); ?></span><?php else: ?>-<?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div><?php endif; ?>
    
    <div class="card"><h3>📋 تاریخچه خریدها</h3>
    <?php if(empty($orders)): ?><p style="text-align:center;color:#8080a0;padding:20px;">خریدی ثبت نشده</p>
    <?php else: ?><div style="overflow-x:auto;"><table><thead><tr><th>تاریخ</th><th>پلن</th><th>لوکیشن</th><th>حجم</th><th>مبلغ</th><th>لینک</th></tr></thead><tbody>
    <?php foreach(array_reverse($orders) as $o): ?><tr><td><?php echo htmlspecialchars($o['date']??''); ?></td><td><?php echo htmlspecialchars($o['product']??''); ?></td><td><?php echo htmlspecialchars($o['location']??''); ?></td><td><?php echo (int)($o['volume']??0); ?> GB</td><td><strong><?php echo number_format((int)($o['amount']??0)); ?></strong> ت</td><td><?php if(!empty($o['subscription_url'])): ?><span class="sub-link"><?php echo htmlspecialchars($o['subscription_url']); ?></span> <button class="copy-btn" onclick="copyUrl('<?php echo htmlspecialchars(addslashes($o['subscription_url'])); ?>')">📋</button><?php else: ?>-<?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?></div>
</div>
<script>function copyUrl(u){navigator.clipboard.writeText(u).then(function(){var t=document.createElement('div');t.className='toast';t.innerHTML='✅ لینک کپی شد!';document.body.appendChild(t);setTimeout(function(){t.remove();},2500);});}</script>
</body></html>
