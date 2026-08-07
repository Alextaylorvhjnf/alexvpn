<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/smtp_config.php';

$error = '';
$mode = $_GET['mode'] ?? 'login';
$otpSent = false;

if (isset($_POST['send_otp'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'لطفا یک ایمیل معتبر وارد کنید.';
    } elseif ($mode === 'register' && empty($name)) {
        $error = 'لطفا نام خود را وارد کنید.';
    } elseif (strlen($password) < 8) {
        $error = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
    } else {
        $otp = rand(100000, 999999);
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_password'] = $password;
        $_SESSION['otp_name'] = $name;
        $_SESSION['otp_expiry'] = time() + 300;
        sendOTPEmail($email, $otp);
        $otpSent = true;
    }
}

if (isset($_POST['verify_otp'])) {
    $userOtp = trim($_POST['otp_code'] ?? '');
    if ($userOtp == ($_SESSION['otp_code'] ?? '') && time() <= ($_SESSION['otp_expiry'] ?? 0)) {
        $email = $_SESSION['otp_email']; $password = $_SESSION['otp_password']; $name = $_SESSION['otp_name'];
        if ($mode === 'register') {
            $r = registerUser($email, $password, $name);
            if ($r['success']) {
                loginUser($email, $password);
                unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_password'], $_SESSION['otp_name'], $_SESSION['otp_expiry']);
                header('Location: '.SITE_URL.'/panel.php'); exit;
            } else { $error = $r['error']; $otpSent = false; }
        }
    } else { $error = 'کد نامعتبر یا منقضی شده است.'; $otpSent = true; }
}

if (isset($_POST['login']) && !isset($_POST['send_otp']) && !isset($_POST['verify_otp'])) {
    $r = loginUser($_POST['email'], $_POST['password']);
    if ($r['success']) { header('Location: '.SITE_URL.'/panel.php'); exit; }
    $error = $r['error'];
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $mode==='register'?'ثبت نام':'ورود'; ?> | AlexVPN</title><link rel="icon" href="/alexvpn.png">
<style>@import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Vazirmatn',Tahoma,sans-serif;background:#060a1a;color:#eaeaf2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:rgba(12,16,48,0.85);border:1px solid rgba(255,255,255,0.08);border-radius:28px;padding:32px 24px;width:100%;max-width:400px;backdrop-filter:blur(30px);box-shadow:0 20px 60px rgba(0,0,0,0.5);text-align:center}
.logo{width:50px;height:50px;margin:0 auto 12px}h2{font-weight:900;font-size:1.3rem;margin-bottom:16px}
input{width:100%;padding:11px 14px;border-radius:12px;border:2px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.03);color:#fff;font-family:inherit;font-size:.88rem;margin-bottom:8px;transition:all .3s;outline:none}input:focus{border-color:#7c6ff7}
.btn{width:100%;padding:11px;border-radius:32px;font-weight:700;font-size:.9rem;border:none;cursor:pointer;transition:all .3s;font-family:inherit;margin-top:6px}.btn-primary{background:linear-gradient(135deg,#7c6ff7,#5b4fcf);color:#fff}.btn-gold{background:linear-gradient(135deg,#fdcb6e,#f39c12);color:#000}
.otp-input{letter-spacing:8px;text-align:center;font-size:1.4rem;font-weight:900;font-family:monospace;direction:ltr}.error{color:#ff6b9d;font-size:.8rem;margin-bottom:8px;background:rgba(255,107,157,0.08);padding:8px;border-radius:10px}.success{color:#00d4cc;font-size:.8rem;margin-bottom:8px;background:rgba(0,212,204,0.08);padding:8px;border-radius:10px}a{color:#00d4cc;text-decoration:none;font-size:.8rem}
</style></head><body>
<div class="card"><div class="logo"><img src="/alexvpn.png" style="width:100%;height:100%"></div>
<h2><?php echo $mode==='register'?'ثبت نام در AlexVPN':'ورود به AlexVPN'; ?></h2>
<?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
<?php if(!$otpSent): ?><form method="post">
<?php if($mode==='register'): ?><input type="text" name="name" placeholder="نام و نام خانوادگی" required value="<?php echo htmlspecialchars($_POST['name']??''); ?>"><?php endif; ?>
<input type="email" name="email" placeholder="ایمیل" required dir="ltr" value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
<input type="password" name="password" placeholder="رمز عبور (حداقل ۸ کاراکتر)" required minlength="8">
<?php if($mode==='register'): ?><button type="submit" name="send_otp" class="btn btn-gold">📧 ارسال کد تایید</button>
<p style="margin-top:10px;font-size:.8rem;color:#b8b8d0">قبلا ثبت نام کرده‌اید؟ <a href="?mode=login">ورود</a></p>
<?php else: ?><button type="submit" name="login" class="btn btn-primary">ورود</button>
<p style="margin-top:10px;font-size:.8rem;color:#b8b8d0">حساب کاربری ندارید؟ <a href="?mode=register">ثبت نام</a></p><?php endif; ?>
</form>
<?php else: ?><div class="success">📧 کد تایید به <strong><?php echo htmlspecialchars($_SESSION['otp_email']); ?></strong> ارسال شد</div>
<p style="color:#8080a0;font-size:.75rem;margin-bottom:8px">لطفا ایمیل خود را چک کنید (پوشه spam را نیز بررسی کنید)</p>
<form method="post"><input type="text" name="otp_code" placeholder="کد ۶ رقمی" class="otp-input" maxlength="6" required autofocus>
<input type="hidden" name="verify_otp" value="1"><button type="submit" class="btn btn-primary">✅ تایید کد</button></form>
<form method="post" style="margin-top:6px"><input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['otp_email']); ?>">
<input type="hidden" name="password" value="<?php echo htmlspecialchars($_SESSION['otp_password']); ?>">
<?php if($mode==='register'): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($_SESSION['otp_name']); ?>"><?php endif; ?>
<button type="submit" name="send_otp" style="background:transparent;border:1px solid rgba(255,255,255,0.12);color:#b8b8d0;width:100%;padding:8px;border-radius:24px;font-size:.75rem;cursor:pointer">🔄 ارسال مجدد</button></form>
<?php endif; ?></div></body></html>
