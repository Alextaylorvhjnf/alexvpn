<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) { header('Location: '.SITE_URL.'/login.php'); exit; }
if (!isset($_POST['plan_id']) || !isset($_FILES['receipt'])) { header('Location: '.SITE_URL); exit; }

$plans = loadPlans(); $planId = $_POST['plan_id']; $selectedPlan = null;
foreach ($plans as $p) { if ($p['id']===$planId && ($p['active']??true)) { $selectedPlan=$p; break; } }
if (!$selectedPlan) { header('Location: '.SITE_URL); exit; }

$uploadDir = __DIR__.'/data/receipts/';
if (!is_dir($uploadDir)) mkdir($uploadDir,0775,true);
$ext = strtolower(pathinfo($_FILES['receipt']['name'],PATHINFO_EXTENSION));
$receiptFile = $uploadDir.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
move_uploaded_file($_FILES['receipt']['tmp_name'],$receiptFile);

$location = $selectedPlan['location']??'';
$inbounds = getInboundsForLocation($location); if(empty($inbounds)) $inbounds=ALL_INBOUNDS;

$pendingId = 'card_'.time().'_'.bin2hex(random_bytes(4));
$user = getCurrentUser();
$pending = [
    'id'=>$pendingId, 'plan_id'=>$planId, 'plan_name'=>$selectedPlan['name'],
    'volume'=>$selectedPlan['volume'], 'days'=>$selectedPlan['days'], 'price'=>$selectedPlan['price'],
    'inbounds'=>$inbounds, 'location'=>$location, 'location_name'=>$selectedPlan['location_name']??'',
    'receipt'=>$receiptFile, 'status'=>'pending', 'created_at'=>date('Y-m-d H:i:s'),
    'user_id'=>$user['id'], 'user_email'=>$user['email'], 'user_name'=>$user['name'],
    'auto_approve_time' => time() + 60 // Auto-approve after 60 seconds
];

$pendingPayments = loadPending(); $pendingPayments[] = $pending; savePending($pendingPayments);

$settings = loadSettings();

// Send receipt photo
if(file_exists($receiptFile)) {
    $ch=curl_init('https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/sendPhoto');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>['chat_id'=>TELEGRAM_ADMIN_ID,'photo'=>curl_file_create($receiptFile),'caption'=>'📸 Receipt - '.$pendingId],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]);
    curl_exec($ch); curl_close($ch);
}

// Send payment details
$cardInfo = "💳 Card: {$settings['card_number']}\n👤 Holder: {$settings['card_holder']}\n🏦 Bank: {$settings['card_bank']}";
$text = "🔔 <b>New Card Payment</b>\n\n";
$text .= "🆔 ID: <code>$pendingId</code>\n";
$text .= "👤 User: {$user['name']}\n📧 Email: {$user['email']}\n";
$text .= "📦 Plan: {$selectedPlan['name']}\n📍 Location: {$selectedPlan['location_name']}\n";
$text .= "📊 Volume: {$selectedPlan['volume']} GB\n📅 Days: {$selectedPlan['days']}\n";
$text .= "💰 Amount: ".number_format($selectedPlan['price'])." Toman\n";
$text .= "🕐 Time: ".date('Y-m-d H:i:s')."\n\n";
$text .= $cardInfo . "\n\n";
$text .= "⏰ <b>Auto-approve in 60 seconds if no action taken!</b>";

$keyboard = ['inline_keyboard' => [[
    ['text'=>'✅ Approve Now','callback_data'=>'approve_'.$pendingId],
    ['text'=>'❌ Reject','callback_data'=>'reject_'.$pendingId]
]]];
sendTelegramMessage(TELEGRAM_ADMIN_ID, $text, $keyboard);
?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>در انتظار تایید | AlexVPN</title><link rel="icon" href="/alexvpn.png">
<style>@import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Vazirmatn',Tahoma,sans-serif;background:#060a1a;color:#eaeaf2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0}.bg .o{position:absolute;border-radius:50%;filter:blur(120px);opacity:.07}.bg .o:nth-child(1){width:500px;height:500px;background:radial-gradient(circle,#7c6ff7,transparent 70%);top:-150px;right:-100px;animation:f 20s infinite}.bg .o:nth-child(2){width:400px;height:400px;background:radial-gradient(circle,#fdcb6e,transparent 70%);bottom:-100px;left:-80px;animation:f 25s infinite;animation-delay:-8s}@keyframes f{0%,100%{transform:translate(0,0)}25%{transform:translate(80px,-60px)}50%{transform:translate(-40px,70px)}75%{transform:translate(-60px,-40px)}}
.card{position:relative;z-index:1;background:rgba(12,16,48,0.85);border:1px solid rgba(255,255,255,0.08);border-radius:28px;padding:40px 28px;text-align:center;backdrop-filter:blur(30px);box-shadow:0 20px 60px rgba(0,0,0,0.5);max-width:520px;width:100%}
.spinner{width:60px;height:60px;margin:0 auto 16px;border:4px solid rgba(253,203,110,0.2);border-top-color:#fdcb6e;border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
@keyframes toastIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}@keyframes toastOut{from{opacity:1}to{opacity:0;transform:translateY(-10px)}}
h2{font-weight:900;font-size:1.4rem;margin-bottom:8px}p{color:#b8b8d0;font-size:.9rem}.status-badge{padding:8px 20px;border-radius:24px;font-weight:700;font-size:.85rem;background:rgba(253,203,110,0.12);color:#fdcb6e;border:1px solid rgba(253,203,110,0.3);margin:12px 0;display:inline-block}
.success-box{display:none;background:rgba(0,212,204,0.08);border:1px solid rgba(0,212,204,0.2);border-radius:14px;padding:16px;margin:12px 0;text-align:center}.success-box .sub-link{color:#00d4cc;font-family:monospace;font-size:.85rem;word-break:break-all;direction:ltr;display:block;margin:8px 0}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:36px;font-weight:700;font-size:.9rem;text-decoration:none;transition:all .3s;font-family:inherit;margin:6px 4px;border:none;cursor:pointer}.btn-primary{background:linear-gradient(135deg,#7c6ff7,#5b4fcf);color:#fff}.btn-copy{background:rgba(0,212,204,0.12);color:#00d4cc;border:1px solid rgba(0,212,204,0.25)}.btn-telegram{background:rgba(0,212,204,0.12);color:#00d4cc;border:1px solid rgba(0,212,204,0.25)}</style></head><body>
<div class="bg"><div class="o"></div><div class="o"></div></div>
<div class="card">
    <div class="spinner" id="spinner"></div>
    <h2 id="statusTitle">در انتظار تایید</h2>
    <p id="statusText">رسید شما دریافت شد. حداکثر تا ۱ دقیقه دیگر توسط ادمین بررسی و تایید یا رد میگردد. دلیل رد شدن نیز به شما اطلاع داده خواهد شد.</p>
    <div class="status-badge" id="statusBadge">⏳ در حال بررسی</div>
    <div class="success-box" id="successBox"><h3 style="color:#00d4cc;">✅ پرداخت تایید شد!</h3><p style="font-size:.85rem;margin:8px 0;">نام کاربری: <strong id="usernameDisplay"></strong></p><p style="font-size:.85rem;">لینک اشتراک:</p><span class="sub-link" id="subLink"></span><button class="btn btn-copy" onclick="copyUrl()" style="margin-top:8px;">📋 کپی لینک</button></div>
    <div id="errorBox" style="display:none;color:#ff6b9d;"></div>
    <a href="<?php echo SITE_URL; ?>/panel.php" class="btn btn-primary">پنل کاربری</a>
    <a href="https://t.me/Alexvpnsupport" target="_blank" class="btn btn-telegram">پشتیبانی تلگرام</a>
</div>
<script>
var pendingId='<?php echo $pendingId; ?>';var checkCount=0;
function checkStatus(){fetch('/ajax-check.php?id='+pendingId).then(r=>r.json()).then(data=>{checkCount++;if(data.status==='approved'){document.getElementById('spinner').style.display='none';document.getElementById('statusTitle').textContent='تحویل داده شد!';document.getElementById('statusBadge').innerHTML='✅ تایید شد';document.getElementById('statusBadge').style.background='rgba(0,212,204,0.12)';document.getElementById('statusBadge').style.color='#00d4cc';document.getElementById('successBox').style.display='block';document.getElementById('subLink').textContent=data.subscription_url||'';document.getElementById('usernameDisplay').textContent=data.username||'';}else if(data.status==='rejected'){document.getElementById('spinner').style.display='none';document.getElementById('statusTitle').textContent='پرداخت رد شد';document.getElementById('statusBadge').innerHTML='❌ رد شد';document.getElementById('statusBadge').style.background='rgba(255,107,157,0.12)';document.getElementById('statusBadge').style.color='#ff6b9d';document.getElementById('errorBox').style.display='block';document.getElementById('errorBox').textContent=data.reject_reason?'دلیل: '+data.reject_reason:'';}else if(checkCount<60){setTimeout(checkStatus,5000);}else{document.getElementById('statusText').textContent='با پشتیبانی تماس بگیرید.';}}).catch(function(){if(checkCount<60)setTimeout(checkStatus,5000);});}
function copyUrl(){var t=document.getElementById('subLink');if(t){navigator.clipboard.writeText(t.textContent).then(function(){var toast=document.createElement('div');toast.style.cssText='position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:rgba(0,212,204,0.15);border:1px solid rgba(0,212,204,0.3);color:#00d4cc;padding:14px 28px;border-radius:36px;font-weight:700;z-index:9999;backdrop-filter:blur(20px);display:flex;align-items:center;gap:10px;animation:toastIn .4s ease,toastOut .4s ease 2s forwards';toast.innerHTML='<img src="/alexvpn.png" style="width:28px;height:28px;border-radius:8px;"> ✅ لینک ساب AlexVPN کپی شد!';document.body.appendChild(toast);setTimeout(function(){toast.remove()},2500)});}}
setTimeout(checkStatus,3000);
</script>
</body></html>
