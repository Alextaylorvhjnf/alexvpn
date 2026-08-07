<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) { echo json_encode(['status'=>'error']); exit; }

$pending = loadPending();
foreach ($pending as $p) {
    if ($p['id'] === $id) {
        echo json_encode([
            'status' => $p['status'] === 'approved' ? 'approved' : ($p['status'] === 'rejected' ? 'rejected' : 'pending'),
            'subscription_url' => $p['subscription_url'] ?? '',
            'username' => $p['username'] ?? '',
            'reject_reason' => $p['reject_reason'] ?? ''
        ]);
        exit;
    }
}
echo json_encode(['status'=>'pending']);
