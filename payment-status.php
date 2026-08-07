<?php
require_once __DIR__ . '/config.php';

$pendingId = $_GET['id'] ?? '';
$result = null;

if ($pendingId) {
    $pendingFile = __DIR__ . '/data/pending_payments.json';
    if (file_exists($pendingFile)) {
        $pp = json_decode(file_get_contents($pendingFile), true);
        foreach ($pp as $p) { if ($p['id'] === $pendingId) { $result = $p; break; } }
    }
    if (!$result) {
        $orders = loadOrders();
        foreach ($orders as $o) {
            if (($o['ref_id'] ?? '') === $pendingId) {
                $result = [
                    'status'=>'approved', 'username'=>$o['username']??'', 'subscription_url'=>$o['subscription_url']??'',
                    'plan_name'=>$o['product']??'', 'location_name'=>$o['location']??'', 'volume'=>$o['volume']??'', 'days'=>$o['days']??'', 'price'=>$o['amount']??''
                ];
                break;
            }
        }
    }
}
if (!$result) { header('Location: '.SITE_URL); exit; }
$subUrl = $result['subscription_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وضعیت پرداخت | AlexVPN</title>
    <link rel="icon" type="image/png" href="/alexvpn.png">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Vazirmatn',Tahoma,sans-serif;background:#060a1a;color:#eaeaf2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0}
        .bg .o{position:absolute;border-radius:50%;filter:blur(120px);opacity:.07}
        .bg .o:nth-child(1){width:500px;height:500px;background:radial-gradient(circle,#7c6ff7,transparent 70%);top:-150px;right:-100px;animation:f 20s infinite}
        .bg .o:nth-child(2){width:400px;height:400px;background:radial-gradient(circle,#00d4cc,transparent 70%);bottom:-100px;left:-80px;animation:f 25s infinite;animation-delay:-8s}
        @keyframes f{0%,100%{transform:translate(0,0)}25%{transform:translate(80px,-60px)}50%{transform:translate(-40px,70px)}75%{transform:translate(-60px,-40px)}}
        .card{position:relative;z-index:1;background:rgba(12,16,48,0.85);border:1px solid rgba(255,255,255,0.08);border-radius:28px;padding:40px 28px;text-align:center;backdrop-filter:blur(30px);box-shadow:0 20px 60px rgba(0,0,0,0.5);max-width:550px;width:100%}
        .icon{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem}
        .icon.success{background:rgba(0,212,204,0.12);color:#00d4cc;box-shadow:0 0 40px rgba(0,212,204,0.2)}
        .icon.pending{background:rgba(253,203,110,0.12);color:#fdcb6e;box-shadow:0 0 40px rgba(253,203,110,0.2)}
        .icon.rejected{background:rgba(255,107,157,0.12);color:#ff6b9d;box-shadow:0 0 40px rgba(255,107,157,0.2)}
        h2{font-weight:900;font-size:1.5rem;margin-bottom:8px}
        p{color:#b8b8d0;margin-bottom:12px;font-size:.9rem}
        .info-box{background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;margin:12px 0;text-align:center}
        .info-box .lbl{color:#8080a0;font-size:.75rem;display:block;margin-bottom:4px}
        .info-box .val{color:#00d4cc;font-size:.85rem;word-break:break-all;font-family:monospace;direction:ltr}
        .info-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:.84rem}
        .info-row .lbl{color:#8080a0}.info-row .val{color:#fff;font-weight:600}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:36px;font-weight:700;font-size:.9rem;text-decoration:none;transition:all .3s;font-family:inherit;margin:6px 4px;border:none;cursor:pointer}
        .btn-primary{background:linear-gradient(135deg,#7c6ff7,#5b4fcf);color:#fff}
        .btn-copy{background:rgba(0,212,204,0.12);color:#00d4cc;border:1px solid rgba(0,212,204,0.25)}
        .tutorial{text-align:right;background:rgba(0,0,0,0.2);border-radius:14px;padding:16px;margin:12px 0}
        .tutorial h4{color:#fff;margin-bottom:8px;font-size:.95rem}
        .tutorial ol{padding-right:18px;color:#b8b8d0;font-size:.82rem;line-height:2.2}
    </style>
</head>
<body>
    <div class="bg"><div class="o"></div><div class="o"></div></div>
    <div class="card">
        <?php if ($result['status'] === 'approved'): ?>
        <div class="icon success">✅</div>
        <h2>پرداخت تایید و تحویل داده شد!</h2>
        <div class="info-row"><span class="lbl">نام کاربری:</span><span class="val" style="font-family:monospace;direction:ltr;"><?php echo htmlspecialchars($result['username']??''); ?></span></div>
        <div class="info-row"><span class="lbl">پلن:</span><span class="val"><?php echo htmlspecialchars($result['plan_name']??''); ?></span></div>
        <div class="info-row"><span class="lbl">حجم:</span><span class="val"><?php echo (int)($result['volume']??0); ?> GB | <?php echo (int)($result['days']??0); ?> روز</span></div>
        <?php if (!empty($subUrl)): ?>
        <div class="info-box">
            <span class="lbl">🔗 لینک اشتراک شما</span>
            <span class="val" id="subUrl"><?php echo htmlspecialchars($subUrl); ?></span>
        </div>
        <button class="btn btn-copy" onclick="copyUrl()">📋 کپی لینک</button>
        <?php endif; ?>
        <div class="tutorial">
            <h4>🎓 نحوه اتصال:</h4>
            <ol><li>لینک را کپی کنید</li><li>برنامه v2rayNG یا Hiddify را باز کنید</li><li>بخش Subscription → Add</li><li>لینک را Paste و ذخیره کنید</li><li>کانفیگ را انتخاب و Connect شوید</li></ol>
        </div>
        <?php elseif ($result['status'] === 'rejected'): ?>
        <div class="icon rejected">❌</div>
        <h2>پرداخت رد شد</h2>
        <p>دلیل: <?php echo htmlspecialchars($result['reject_reason']??'نامشخص'); ?></p>
        <a href="https://t.me/AlexVPN98Bot" target="_blank" class="btn btn-primary">پشتیبانی</a>
        <?php else: ?>
        <div class="icon pending">⏳</div>
        <h2>در انتظار بررسی</h2>
        <p>این صفحه خودکار بروزرسانی می‌شود</p>
        <?php endif; ?>
        <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">بازگشت به سایت</a>
    </div>
    <script>
    function copyUrl(){var t=document.getElementById('subUrl');if(t){navigator.clipboard.writeText(t.textContent).then(function(){var b=document.querySelector('.btn-copy');b.textContent='✅ کپی شد!';setTimeout(function(){b.textContent='📋 کپی لینک';},2500);});}}
    <?php if (in_array($result['status'], ['pending','reject_pending_reason'])): ?>
    setTimeout(function(){ location.reload(); }, 10000);
    <?php endif; ?>
    </script>
</body>
</html>
