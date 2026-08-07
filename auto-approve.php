<?php
require_once __DIR__ . '/config.php';

$pendingFile = __DIR__ . '/data/pending_payments.json';
if (!file_exists($pendingFile)) exit;

$pendingPayments = json_decode(file_get_contents($pendingFile), true);
$changed = false;
$now = time();

foreach ($pendingPayments as $key => &$p) {
    // Check if pending and auto-approve time has passed
    if (($p['status'] ?? '') === 'pending' && isset($p['auto_approve_time']) && $p['auto_approve_time'] > 0 && $now >= $p['auto_approve_time']) {
        
        $username = 'alexvpn_' . bin2hex(random_bytes(6));
        $totalBytes = $p['volume'] * 1073741824;
        $inbounds = !empty($p['inbounds']) ? $p['inbounds'] : ALL_INBOUNDS;
        
        $acc = createVpnAccount($username, $totalBytes, $p['days'], $inbounds);
        $subUrl = $acc['subscription_url'] ?? '';
        
        $p['status'] = 'approved';
        $p['username'] = $username;
        $p['subscription_url'] = $subUrl;
        $p['approved_at'] = date('Y-m-d H:i:s');
        $p['auto_approved'] = true;
        $p['auto_approve_time'] = 0;
        
        // Save to orders
        $orders = loadOrders();
        $orders[] = [
            'ref_id' => $p['id'], 'username' => $username, 'amount' => $p['price'],
            'product' => $p['plan_name'], 'location' => $p['location_name'] ?? '',
            'volume' => $p['volume'], 'days' => $p['days'],
            'date' => date('Y-m-d H:i:s'), 'method' => 'card', 'subscription_url' => $subUrl,
            'auto_approved' => true
        ];
        saveOrders($orders);
        
        // Notify admin
        $configLink = !empty($subUrl) ? $subUrl : 'N/A';
        $text = "⏰ <b>Auto-Approved Payment</b>\n\n";
        $text .= "🆔 ID: <code>{$p['id']}</code>\n";
        $text .= "👤 User: {$p['user_name']}\n";
        $text .= "📦 Plan: {$p['plan_name']}\n";
        $text .= "🔗 Link: <code>{$configLink}</code>\n";
        $text .= "💡 No action was taken within 60 seconds - auto-approved.";
        
        sendTelegramMessage(TELEGRAM_ADMIN_ID, $text);
        
        $changed = true;
    }
}

if ($changed) {
    file_put_contents($pendingFile, json_encode($pendingPayments, JSON_UNESCAPED_UNICODE));
}

echo "Auto-approve check completed at " . date('Y-m-d H:i:s');
