<?php
require_once __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);

// Handle callback queries
if (isset($input['callback_query'])) {
    require __DIR__ . '/telegram-callback.php';
    exit;
}

// Handle messages
if (isset($input['message'])) {
    $message = $input['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    // Check for reject reason (reply to admin's message)
    if (isset($message['reply_to_message']) && $chatId == TELEGRAM_ADMIN_ID) {
        $rejectFile = __DIR__ . '/data/reject_state.json';
        if (file_exists($rejectFile)) {
            $rejectState = json_decode(file_get_contents($rejectFile), true);
            
            $pendingFile = __DIR__ . '/data/pending_payments.json';
            $pendingPayments = file_exists($pendingFile) ? json_decode(file_get_contents($pendingFile), true) : [];
            
            $foundKey = null;
            foreach ($pendingPayments as $key => $p) {
                if ($p['id'] === $rejectState['pending_id']) { $foundKey = $key; break; }
            }
            
            if ($foundKey !== null) {
                $pendingPayments[$foundKey]['status'] = 'rejected';
                $pendingPayments[$foundKey]['reject_reason'] = $text;
                $pendingPayments[$foundKey]['rejected_at'] = date('Y-m-d H:i:s');
                file_put_contents($pendingFile, json_encode($pendingPayments, JSON_UNESCAPED_UNICODE));
                
                sendTelegramMessage($chatId, "❌ پرداخت رد شد.\n📝 دلیل: $text");
                
                // Update original message
                $editUrl = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/editMessageReplyMarkup';
                $ch = curl_init($editUrl);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        'chat_id' => $chatId,
                        'message_id' => $rejectState['message_id'],
                        'reply_markup' => ['inline_keyboard' => [[['text' => "❌ رد شد: $text", 'callback_data' => 'done']]]]
                    ]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
                ]);
                curl_exec($ch); curl_close($ch);
                
                unlink($rejectFile);
            }
        }
    }
    
    // /start command
    if ($text === '/start') {
        sendTelegramMessage($chatId, "🤖 به ربات مدیریت AlexVPN خوش آمدید!\n\nپرداخت‌های کارت به کارت برای تایید یا رد به این ربات ارسال می‌شوند.");
    }
}

echo 'OK';
