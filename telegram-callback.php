<?php
require_once __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['callback_query'])) { http_response_code(400); exit; }

$callback = $input['callback_query'];
$data = $callback['data'];
$chatId = $callback['message']['chat']['id'];
$messageId = $callback['message']['message_id'];
$callbackId = $callback['id'];

// Answer callback
$ch = curl_init('https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/answerCallbackQuery');
curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>['callback_query_id'=>$callbackId], CURLOPT_RETURNTRANSFER=>true]);
curl_exec($ch); curl_close($ch);

if (strpos($data, 'approve_') === 0 || strpos($data, 'reject_') === 0) {
    $action = strpos($data, 'approve_') === 0 ? 'approve' : 'reject';
    $pendingId = substr($data, strpos($data, '_') + 1);
    
    $pendingFile = __DIR__ . '/data/pending_payments.json';
    $pendingPayments = file_exists($pendingFile) ? json_decode(file_get_contents($pendingFile), true) : [];
    
    $foundKey = null;
    foreach ($pendingPayments as $key => $p) { if ($p['id'] === $pendingId) { $foundKey = $key; break; } }
    if ($foundKey === null) { sendTelegramMessage($chatId, '❌ Payment not found.'); exit; }
    
    $pending = $pendingPayments[$foundKey];
    
    // Stop auto-approve timer since admin took action
    if (isset($pending['auto_approve_time'])) {
        $pendingPayments[$foundKey]['auto_approve_time'] = 0; // Cancel auto-approve
    }
    
    if ($action === 'approve') {
        $username = $userEmail ?? 'alexvpn_' . bin2hex(random_bytes(6));
        $totalBytes = $pending['volume'] * 1073741824;
        $inbounds = !empty($pending['inbounds']) ? $pending['inbounds'] : ALL_INBOUNDS;
        
        $acc = createVpnAccount($username, $totalBytes, $pending['days'], $inbounds);
        $subUrl = $acc['subscription_url'] ?? '';
        
        $pendingPayments[$foundKey]['status'] = 'approved';
        $pendingPayments[$foundKey]['username'] = $username;
        $pendingPayments[$foundKey]['subscription_url'] = $subUrl;
        $pendingPayments[$foundKey]['approved_at'] = date('Y-m-d H:i:s');
        $pendingPayments[$foundKey]['auto_approve_time'] = 0; // Cancel timer
        
        $orders = loadOrders();
        $orders[] = [
            'ref_id' => $pendingId, 'username' => $username, 'amount' => $pending['price'],
            'product' => $pending['plan_name'], 'location' => $pending['location_name'] ?? '',
            'volume' => $pending['volume'], 'days' => $pending['days'],
            'date' => date('Y-m-d H:i:s'), 'method' => 'card', 'subscription_url' => $subUrl
        ];
        saveOrders($orders);
        
        $configLink = !empty($subUrl) ? $subUrl : 'https://alexvpn.pradashops.ir/payment-status.php?id=' . $pendingId;
        
        $text = "✅ <b>Payment Approved - Account Created</b>\n\n";
        $text .= "👤 Username: <code>$username</code>\n";
        $text .= "📦 Plan: {$pending['plan_name']}\n";
        $text .= "📍 Location: {$pending['location_name']}\n";
        $text .= "📊 Volume: {$pending['volume']} GB | {$pending['days']} Days\n";
        $text .= "🔗 <b>Subscription Link:</b>\n<code>{$configLink}</code>\n\n";
        $text .= "━━━━━━━━━━━━━━━\n";
        $text .= "📱 How to use:\n";
        $text .= "1️⃣ Copy the link above\n";
        $text .= "2️⃣ Open v2rayNG / Hiddify\n";
        $text .= "3️⃣ Add Subscription → Paste link\n";
        $text .= "4️⃣ Connect and enjoy!";
        
        sendTelegramMessage($chatId, $text);
        
        // Update keyboard
        $editUrl = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/editMessageReplyMarkup';
        $ch = curl_init($editUrl);
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode(['chat_id'=>$chatId,'message_id'=>$messageId,'reply_markup'=>['inline_keyboard'=>[[['text'=>'✅ Approved & Delivered','callback_data'=>'done']]]]]), CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
        curl_exec($ch); curl_close($ch);
        
    } else {
        // Reject
        $pendingPayments[$foundKey]['status'] = 'reject_pending_reason';
        $pendingPayments[$foundKey]['auto_approve_time'] = 0; // Cancel timer
        $rejectFile = __DIR__ . '/data/reject_state.json';
        file_put_contents($rejectFile, json_encode(['pending_id'=>$pendingId,'chat_id'=>$chatId,'message_id'=>$messageId]));
        sendTelegramMessage($chatId, '📝 Please enter the reason for rejection (reply to this message):');
    }
    
    file_put_contents($pendingFile, json_encode($pendingPayments, JSON_UNESCAPED_UNICODE));
}

echo 'OK';
