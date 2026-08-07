<?php
require_once __DIR__ . '/config.php';

$result = ['status' => 'loading', 'message' => '', 'subscription_url' => '', 'username' => '', 'product_name' => ''];

if (isset($_GET['Status']) && isset($_GET['Authority']) && isset($_SESSION['alexvpn_payment'])) {
    $authority = $_GET['Authority'];
    $status = $_GET['Status'];
    $pending = $_SESSION['alexvpn_payment'];
    
    if ($status === 'OK') {
        $verifyData = ['merchant_id' => MERCHANT_ID, 'authority' => $authority, 'amount' => $pending['amount']];
        $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($verifyData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $vr = curl_exec($ch); curl_close($ch);
        $vrd = json_decode($vr, true);
        
        if (isset($vrd['data']['code']) && in_array($vrd['data']['code'], [100, 101])) {
            $username = 'alexvpn_' . bin2hex(random_bytes(6));
            $totalBytes = $pending['volume'] * 1073741824;
            $acc = createVpnAccount($username, $totalBytes, $pending['days'], $pending['inbounds']);
            
            if ($acc['success']) {
                $result = [
                    'status' => 'success',
                    'username' => $username,
                    'subscription_url' => $acc['subscription_url'],
                    'product_name' => $pending['product_name'],
                    'ref_id' => $vrd['data']['ref_id'] ?? '',
                    'amount' => $pending['price'] ?? 0
                ];
                // Save order
                $orders = loadOrders();
                $orders[] = ['ref_id' => $result['ref_id'], 'username' => $username, 'amount' => $result['amount'], 'product' => $pending['product_name'], 'date' => date('Y-m-d H:i:s')];
                saveOrders($orders);
            } else {
                $result = ['status' => 'panel_error', 'message' => $acc['error'], 'ref_id' => $vrd['data']['ref_id'] ?? ''];
            }
        } else {
            $result = ['status' => 'payment_failed', 'message' => 'پرداخت توسط بانک تایید نشد.'];
        }
    } else {
        $result = ['status' => 'cancelled', 'message' => 'پرداخت لغو شد.'];
    }
    unset($_SESSION['alexvpn_payment']);
} else {
    $result = ['status' => 'invalid', 'message' => 'درخواست نامعتبر.'];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $result['status'] === 'success' ? 'پرداخت موفق' : 'نتیجه پرداخت'; ?> | AlexVPN</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        @import url('https://cdn.jsdelivr.net/ghrastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Vazirmatn',Tahoma,sans-serif;background:#060a1a;color:#eaeaf2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .bg{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none}
        .bg .orb{position:absolute;border-radius:50%;filter:blur(120px);opacity:0.08}
        .bg .orb:nth-child(1){width:500px;height:500px;background:radial-gradient(circle,#7c6ff7,transparent 70%);top:-150px;right:-100px;animation:float 20s infinite}
        .bg .orb:nth-child(2){width:400px;height:400px;background:radial-gradient(circle,#00d4cc,transparent 70%);bottom:-100px;left:-80px;animation:float 25s infinite;animation-delay:-8s}
        @keyframes float{0%,100%{transform:translate(0,0)}25%{transform:translate(80px,-60px)}50%{transform:translate(-40px,70px)}75%{transform:translate(-60px,-40px)}}
        .container{position:relative;z-index:1;max-width:600px;width:100%}
        .card{background:rgba(12,16,48,0.85);border:1px solid rgba(255,255,255,0.08);border-radius:28px;padding:40px 28px;text-align:center;backdrop-filter:blur(30px);box-shadow:0 20px 60px rgba(0,0,0,0.5)}
        .icon{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem}
        .icon.success{background:rgba(0,212,204,0.12);color:#00d4cc;box-shadow:0 0 40px rgba(0,212,204,0.2)}
        .icon.error{background:rgba(255,107,157,0.12);color:#ff6b9d;box-shadow:0 0 40px rgba(255,107,157,0.2)}
        h2{font-weight:900;font-size:1.6rem;margin-bottom:8px}
        .sub{color:#8888a8;margin-bottom:16px;font-size:0.9rem}
        .info-box{background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;margin:12px 0;text-align:right;direction:ltr}
        .info-box .label{color:#8888a8;font-size:0.75rem;display:block;margin-bottom:4px}
        .info-box .value{color:#00d4cc;font-size:0.85rem;word-break:break-all;font-family:monospace}
        .url-box{background:rgba(0,212,204,0.05);border:1px solid rgba(0,212,204,0.2);border-radius:14px;padding:14px;margin:16px 0;direction:ltr;text-align:center;position:relative}
        .url-box .url-text{color:#00d4cc;font-size:0.8rem;word-break:break-all;font-family:monospace;display:block}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:36px;font-weight:700;font-size:0.9rem;text-decoration:none;border:none;cursor:pointer;transition:all 0.3s;font-family:inherit;margin:6px}
        .btn-primary{background:linear-gradient(135deg,#7c6ff7,#5b4fcf);color:#fff}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 30px rgba(124,111,247,0.5)}
        .btn-copy{background:rgba(0,212,204,0.15);color:#00d4cc;border:1px solid rgba(0,212,204,0.3)}
        .btn-copy:hover{background:rgba(0,212,204,0.25)}
        .btn-outline{background:transparent;color:#c0c0d4;border:1px solid rgba(255,255,255,0.15)}
        .divider{border:none;border-top:1px solid rgba(255,255,255,0.06);margin:20px 0}
        .tutorial{text-align:right;font-size:0.85rem;color:#c0c0d4;line-height:2}
        .tutorial h4{color:#fff;margin-bottom:8px;font-size:1rem}
        .tutorial ol{padding-right:20px}
        .tutorial li{margin-bottom:8px}
        .tutorial code{background:rgba(124,111,247,0.15);padding:2px 8px;border-radius:6px;font-size:0.8rem;color:#a29bfe}
        .apps-grid{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin:12px 0}
        .app-badge{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);padding:6px 14px;border-radius:20px;font-size:0.78rem;color:#c0c0d4}
        @media(max-width:480px){.card{padding:28px 16px}h2{font-size:1.3rem}}
    </style>
</head>
<body>
    <div class="bg"><div class="orb"></div><div class="orb"></div></div>
    <div class="container">
        <div class="card">
            <?php if ($result['status'] === 'success'): ?>
            <div class="icon success">✓</div>
            <h2>پرداخت موفقیت‌آمیز بود!</h2>
            <p class="sub">اکانت VPN شما با موفقیت ساخته شد</p>
            
            <div class="info-box">
                <span class="label">نام کاربری:</span>
                <span class="value"><?php echo htmlspecialchars($result['username']); ?></span>
            </div>
            
            <?php if (!empty($result['subscription_url'])): ?>
            <p style="color:#c0c0d4;font-size:0.85rem;margin-top:12px;">لینک سابسکریپشن شما:</p>
            <div class="url-box">
                <span class="url-text" id="subUrl"><?php echo htmlspecialchars($result['subscription_url']); ?></span>
            </div>
            <button class="btn btn-copy" onclick="copyUrl()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                کپی لینک
            </button>
            <?php else: ?>
            <p style="color:#fdcb6e;margin:12px 0;font-size:0.85rem;">برای دریافت لینک订阅 به پشتیبانی تلگرام پیام دهید</p>
            <?php endif; ?>
            
            <hr class="divider">
            
            <div class="tutorial">
                <h4>📱 نحوه استفاده از کانفیگ</h4>
                <ol>
                    <li>لینک订阅 را کپی کنید</li>
                    <li>یکی از برنامه‌های زیر را نصب کنید:</li>
                </ol>
                <div class="apps-grid">
                    <span class="app-badge">v2rayNG</span>
                    <span class="app-badge">Hiddify Next</span>
                    <span class="app-badge">V2Box</span>
                    <span class="app-badge">Streisand</span>
                    <span class="app-badge">Sing-box</span>
                    <span class="app-badge">NekoBox</span>
                </div>
                <ol start="3">
                    <li>برنامه را باز کنید و گزینه <code>Add Subscription</code> یا <code>افزودن اشتراک</code> را انتخاب کنید</li>
                    <li>لینک کپی شده را Paste کنید و ذخیره نمایید</li>
                    <li>کانفیگ‌ها به صورت خودکار اضافه می‌شوند</li>
                    <li>بهترین کانفیگ را انتخاب و متصل شوید</li>
                </ol>
            </div>
            
            <?php elseif ($result['status'] === 'panel_error'): ?>
            <div class="icon error">⚠</div>
            <h2>نیاز به بررسی</h2>
            <p class="sub">پرداخت موفق بود اما خطا در ساخت اکانت: <?php echo htmlspecialchars($result['message']); ?></p>
            <p style="color:#fdcb6e;font-size:0.85rem;">با پشتیبانی تماس بگیرید: @AlexVPN98Bot</p>
            
            <?php else: ?>
            <div class="icon error">✕</div>
            <h2>پرداخت ناموفق</h2>
            <p class="sub"><?php echo htmlspecialchars($result['message']); ?></p>
            <?php endif; ?>
            
            <div style="margin-top:20px;">
                <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">بازگشت به سایت</a>
                <a href="https://t.me/AlexVPN98Bot" target="_blank" rel="noopener" class="btn btn-outline">پشتیبانی تلگرام</a>
            </div>
        </div>
    </div>
    
    <script>
        function copyUrl() {
            var t = document.getElementById('subUrl');
            if (t) {
                navigator.clipboard.writeText(t.textContent).then(function() {
                    var btn = event.target.closest('button');
                    btn.textContent = '✓ کپی شد!';
                    setTimeout(function(){ btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> کپی لینک'; }, 2500);
                });
            }
        }
    </script>
</body>
</html>
