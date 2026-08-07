<?php
define('SMTP_HOST', 'smtp.elasticemail.com');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', 'alexvpnsupport98@gmail.com');
define('SMTP_PASSWORD', '7F59E7B1FCB850F8E24C837B777B986E1E4A');
define('SMTP_FROM_EMAIL', 'alexvpnsupport98@pradashops.ir');
define('SMTP_FROM_NAME', 'AlexVPN');

function sendOTPEmail($toEmail, $otpCode) {
    $subject = "AlexVPN - OTP: " . $otpCode;
    $message = '
    <!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8"><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet"></head>
    <body style="font-family:\'Vazirmatn\',Tahoma,sans-serif;background:#f0f2f5;padding:40px 20px;margin:0">
        <div style="max-width:460px;margin:0 auto;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.08)">
            <div style="background:linear-gradient(135deg,#6c5ce7,#a29bfe);padding:35px 30px;text-align:center">
                <img src="https://alexvpn.pradashops.ir/alexvpn.png" style="width:70px;height:70px;border-radius:18px;margin-bottom:15px;border:3px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.15);padding:8px" alt="AlexVPN">
                <h1 style="color:#fff;font-size:1.6rem;margin:0;font-weight:900;letter-spacing:-0.5px">AlexVPN</h1>
                <p style="color:rgba(255,255,255,0.85);font-size:.82rem;margin:5px 0 0">اینترنت آزاد، سریع و امن</p>
            </div>
            <div style="padding:35px 30px;text-align:center">
                <h2 style="color:#2d3436;font-size:1.15rem;margin:0 0 6px;font-weight:800">کد تایید ثبت‌نام</h2>
                <p style="color:#636e72;font-size:.85rem;margin:0 0 25px;line-height:1.8">کد ۶ رقمی زیر را در صفحه ثبت‌نام وارد کنید</p>
                <div style="background:linear-gradient(135deg,#f8f7ff,#f0edff);border:2px solid #6c5ce7;border-radius:16px;padding:22px;margin:0 0 20px;letter-spacing:12px">
                    <span style="font-size:2.6rem;font-weight:900;color:#5b4fcf;font-family:\'Vazirmatn\',monospace">'.$otpCode.'</span>
                </div>
                <div style="display:inline-block;background:#fff3f3;border-radius:20px;padding:6px 16px;margin-bottom:15px">
                    <p style="color:#d63031;font-size:.7rem;margin:0">⏰ اعتبار: ۵ دقیقه</p>
                </div>
                <p style="color:#b2bec3;font-size:.68rem;margin:0">این کد محرمانه است، با کسی به اشتراک نگذارید</p>
            </div>
            <div style="background:#fafafa;border-top:1px solid #f0f0f0;padding:22px 25px;text-align:center">
                <p style="color:#2d3436;font-size:.75rem;font-weight:700;margin:0 0 14px">📱 AlexVPN در شبکه‌های اجتماعی</p>
                <table align="center" cellpadding="0" cellspacing="0" style="margin-bottom:8px">
                    <tr>
                        <td style="padding:5px">
                            <a href="https://t.me/Alexvpn98bot" style="display:inline-block;text-decoration:none;color:#fff;font-size:.7rem;background:#2AABEE;padding:9px 18px;border-radius:22px;font-weight:700;letter-spacing:0.3px">
                                🤖 Telegram Bot
                            </a>
                        </td>
                        <td style="padding:5px">
                            <a href="https://t.me/Alexvpnsupport" style="display:inline-block;text-decoration:none;color:#fff;font-size:.7rem;background:#2AABEE;padding:9px 18px;border-radius:22px;font-weight:700;letter-spacing:0.3px">
                                💬 Support
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px">
                            <a href="https://instagram.com/alexvpn98" style="display:inline-block;text-decoration:none;color:#fff;font-size:.7rem;background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);padding:9px 18px;border-radius:22px;font-weight:700;letter-spacing:0.3px">
                                📸 Instagram
                            </a>
                        </td>
                        <td style="padding:5px">
                            <a href="https://wa.me/message/ADZ7MLNKH55NG1" style="display:inline-block;text-decoration:none;color:#fff;font-size:.7rem;background:#25D366;padding:9px 18px;border-radius:22px;font-weight:700;letter-spacing:0.3px">
                                💬 WhatsApp
                            </a>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:8px">
                    <a href="https://t.me/Alexvpn98" style="display:inline-block;text-decoration:none;color:#fff;font-size:.68rem;background:#2AABEE;padding:7px 16px;border-radius:20px;font-weight:600">
                        📢 Telegram Channel: @Alexvpn98
                    </a>
                </div>
                <p style="color:#b2bec3;font-size:.62rem;margin:12px 0 0">© 2026 AlexVPN • All Rights Reserved</p>
            </div>
        </div>
    </body></html>';
    
    $s = fsockopen(SMTP_HOST, SMTP_PORT, $e, $err, 10);
    if (!$s) return false;
    
    $r = function() use ($s) { $res=''; while($l=fgets($s,515)){$res.=$l;if(substr($l,3,1)==' ')break;} return $res; };
    $w = function($c) use ($s) { fputs($s,$c."\r\n"); };
    
    $r(); $w('EHLO alex'); $r();
    $w('AUTH LOGIN'); $r();
    $w(base64_encode(SMTP_USERNAME)); $r();
    $w(base64_encode(SMTP_PASSWORD)); $r();
    $w('MAIL FROM:<'.SMTP_FROM_EMAIL.'>'); $r();
    $w('RCPT TO:<'.$toEmail.'>'); $r();
    $w('DATA'); $r();
    
    $email = "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $email .= "From: ".SMTP_FROM_NAME." <".SMTP_FROM_EMAIL.">\r\n";
    $email .= "To: <".$toEmail.">\r\n";
    $email .= "MIME-Version: 1.0\r\n";
    $email .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email .= "\r\n".$message."\r\n.";
    
    $w($email); $r();
    $w('QUIT'); fclose($s);
    return true;
}
