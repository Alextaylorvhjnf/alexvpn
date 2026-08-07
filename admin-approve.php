<?php
require_once __DIR__ . '/config.php';

// Check admin is logged in
if (!isset($_SESSION['admin_logged_in'])) { header('Location: /admin'); exit; }
if (!isset($_POST['pending_id']) || !isset($_POST['action'])) { header('Location: /admin'); exit; }

$pendingId = $_POST['pending_id'];
$action = $_POST['action'];

$pendingPayments = loadPending();
$foundKey = null;
foreach ($pendingPayments as $key => $p) { if ($p['id'] === $pendingId) { $foundKey = $key; break; } }
if ($foundKey === null) { header('Location: /admin'); exit; }

$pending = $pendingPayments[$foundKey];

if ($action === 'approve') {
    $username = 'u' . substr(md5($pending['user_email'] ?? time()), 0, 10);
    $totalBytes = $pending['volume'] * 1073741824;
    $inbounds = !empty($pending['inbounds']) ? $pending['inbounds'] : ALL_INBOUNDS;
    
    $acc = createVpnAccount($username, $totalBytes, $pending['days'], $inbounds);
    
    $subUrl = $acc['subscription_url'] ?? '';
    
    $pendingPayments[$foundKey]['status'] = 'approved';
    $pendingPayments[$foundKey]['username'] = $username;
    $pendingPayments[$foundKey]['subscription_url'] = $subUrl;
    $pendingPayments[$foundKey]['approved_at'] = date('Y-m-d H:i:s');
    
    $orders = loadOrders();
    $orders[] = [
        'ref_id' => $pendingId, 'username' => $username, 'amount' => $pending['price'],
        'product' => $pending['plan_name'], 'location' => $pending['location_name'] ?? '',
        'volume' => $pending['volume'], 'days' => $pending['days'],
        'date' => date('Y-m-d H:i:s'), 'method' => 'card', 'subscription_url' => $subUrl,
        'user_id' => $pending['user_id'] ?? ''
    ];
    saveOrders($orders);
    
    // Send to Telegram
    $adminText = "✅ تایید از پنل مدیریت\n🆔 {$pendingId}\n👤 {$username}\n🔗 {$subUrl}";
    sendTelegramMessage(TELEGRAM_ADMIN_ID, $adminText);
    
} elseif ($action === 'reject') {
    $reason = $_POST['reject_reason'] ?? 'بدون دلیل';
    $pendingPayments[$foundKey]['status'] = 'rejected';
    $pendingPayments[$foundKey]['reject_reason'] = $reason;
    $pendingPayments[$foundKey]['rejected_at'] = date('Y-m-d H:i:s');
}

savePending($pendingPayments);
header('Location: /admin?tab=pending&msg=' . ($action === 'approve' ? 'approved' : 'rejected'));
