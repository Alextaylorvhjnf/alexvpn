<?php
session_start();

// ===== MAIN SETTINGS =====
define('MERCHANT_ID', '094d65c2-38d6-4058-bc85-6ff0f4215158');
define('PANEL_URL', 'https://3xi4ib1t8cfa.dop1000.com:3587/3HS52Y6NvNmU8xbjLR/');
define('PANEL_TOKEN', '96awU4vHK8jXhis7EWVba8fvllIEfntnV81wmM3TCpE9FzDh');
define('SITE_URL', 'https://alexvpn.pradashops.ir');
define('DB_PATH', __DIR__ . '/data/plans.json');
define('ORDERS_PATH', __DIR__ . '/data/orders.json');
define('SETTINGS_PATH', __DIR__ . '/data/settings.json');
define('SERVERS_PATH', __DIR__ . '/data/servers.json');
define('USERS_PATH', __DIR__ . '/data/users.json');
define('PENDING_PATH', __DIR__ . '/data/pending_payments.json');
define('ALL_INBOUNDS', [336,361,385,414,419,420,421,423,425,427,428,429,438,439,440,441,444,445,446,447,448,449,454,455,456,457,458,459,462]);
define('ADMIN_EMAIL', 'alextaylor98ap@gmail.com');
define('CALLBACK_URL', SITE_URL . '/thank-you.php');
define('SUBSCRIPTION_PORT', '2096');
define('TELEGRAM_BOT_TOKEN', '8725530012:AAGMmPXJPMlr6z3E6qu07p-qJS7ga5_xpak');
define('TELEGRAM_ADMIN_ID', '8002207688');

$GLOBALS['COUNTRIES'] = [
    'germany' => ['name' => 'آلمان', 'emoji' => '🇩🇪'],
    'usa' => ['name' => 'آمریکا', 'emoji' => '🇺🇸'],
    'turkey' => ['name' => 'ترکیه', 'emoji' => '🇹🇷'],
    'sweden' => ['name' => 'سوئد', 'emoji' => '🇸🇪'],
    'azerbaijan' => ['name' => 'آذربایجان', 'emoji' => '🇦🇿'],
];

define('LOCATION_FLAGS', [
    'germany' => '<svg viewBox="0 0 48 48" width="48" height="48" style="border-radius:12px;"><rect x="4" y="10" width="40" height="9" rx="4" fill="#000"/><rect x="4" y="19" width="40" height="10" fill="#DD0000"/><rect x="4" y="29" width="40" height="9" rx="4" fill="#FFCE00"/></svg>',
    'usa' => '<svg viewBox="0 0 48 48" width="48" height="48" style="border-radius:12px;"><rect x="4" y="4" width="40" height="5" rx="2" fill="#B22234"/><rect x="4" y="9" width="40" height="6" fill="#fff"/><rect x="4" y="15" width="40" height="5" rx="2" fill="#B22234"/><rect x="4" y="20" width="40" height="6" fill="#fff"/><rect x="4" y="26" width="40" height="5" rx="2" fill="#B22234"/><rect x="4" y="31" width="40" height="6" fill="#fff"/><rect x="4" y="37" width="40" height="5" rx="2" fill="#B22234"/><rect x="4" y="4" width="18" height="18" rx="3" fill="#3C3B6E"/></svg>',
    'turkey' => '<svg viewBox="0 0 48 48" width="48" height="48" style="border-radius:12px;"><rect x="4" y="4" width="40" height="40" rx="8" fill="#E30A17"/><circle cx="22" cy="24" r="8" fill="#fff"/><circle cx="24" cy="24" r="6" fill="#E30A17"/><polygon points="29,20 32,24 29,28 36,24" fill="#fff"/></svg>',
    'sweden' => '<svg viewBox="0 0 48 48" width="48" height="48" style="border-radius:12px;"><rect x="4" y="4" width="40" height="40" rx="8" fill="#006AA7"/><rect x="16" y="4" width="8" height="40" fill="#FECC00"/><rect x="4" y="18" width="40" height="8" fill="#FECC00"/></svg>',
    'azerbaijan' => '<svg viewBox="0 0 48 48" width="48" height="48" style="border-radius:12px;"><rect x="4" y="6" width="40" height="12" rx="3" fill="#00B5E2"/><rect x="4" y="18" width="40" height="12" fill="#E4002B"/><rect x="4" y="30" width="40" height="12" rx="3" fill="#00B85C"/><circle cx="24" cy="24" r="6" fill="#fff"/><path d="M24 18l1.5 4.5h4.5l-3.5 2.5 1.5 4.5-3.5-2.5-3.5 2.5 1.5-4.5-3.5-2.5h4.5z" fill="#E4002B"/></svg>',
]);

function loadPlans() { if (!file_exists(DB_PATH)) return []; return json_decode(file_get_contents(DB_PATH), true) ?: []; }
function savePlans($p) { if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0775, true); file_put_contents(DB_PATH, json_encode($p, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function loadOrders() { if (!file_exists(ORDERS_PATH)) return []; return json_decode(file_get_contents(ORDERS_PATH), true) ?: []; }
function saveOrders($o) { file_put_contents(ORDERS_PATH, json_encode($o, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function loadSettings() { if (!file_exists(SETTINGS_PATH)) { $d=['card_number'=>'','card_holder'=>'','card_bank'=>'','zarinpal_enabled'=>true,'card_enabled'=>true]; file_put_contents(SETTINGS_PATH, json_encode($d,JSON_UNESCAPED_UNICODE)); return $d; } return json_decode(file_get_contents(SETTINGS_PATH), true)?:[]; }
function saveSettings($s) { file_put_contents(SETTINGS_PATH, json_encode($s, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function loadServers() { if (!file_exists(SERVERS_PATH)) return []; return json_decode(file_get_contents(SERVERS_PATH), true)?:[]; }
function saveServers($s) { file_put_contents(SERVERS_PATH, json_encode($s, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function loadUsers() { if (!file_exists(USERS_PATH)) return []; return json_decode(file_get_contents(USERS_PATH), true)?:[]; }
function saveUsers($u) { file_put_contents(USERS_PATH, json_encode($u, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function loadPending() { if (!file_exists(PENDING_PATH)) return []; return json_decode(file_get_contents(PENDING_PATH), true)?:[]; }
function savePending($p) { file_put_contents(PENDING_PATH, json_encode($p, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }

function getInboundsForLocation($location) {
    $servers = loadServers();
    foreach ($servers as $s) { if (($s['location']??'')===$location && !empty($s['inbounds'])) return array_map('intval',$s['inbounds']); }
    return [];
}

function panelApi($endpoint, $method='GET', $data=null) {
    $url = rtrim(PANEL_URL,'/').'/panel/api/'.$endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_SSL_VERIFYHOST=>false,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.PANEL_TOKEN,'Accept: application/json','Content-Type: application/json']]);
    if($method==='POST'){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));}
    $res=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    return ['http_code'=>$http,'error'=>$err,'data'=>json_decode($res,true)];
}

function buildSubscriptionUrl($subId) {
    $host = parse_url(PANEL_URL, PHP_URL_HOST);
    return "https://{$host}:" . SUBSCRIPTION_PORT . "/subs/{$subId}";
}

function createVpnAccount($username,$totalBytes,$days,$inbounds) {
    if(empty($inbounds)) $inbounds=ALL_INBOUNDS;
    $expiryTime=(time()+($days*86400))*1000;
    $subId=bin2hex(random_bytes(8));
    $payload=['inboundIds'=>$inbounds,'client'=>['email'=>$username,'totalGB'=>(int)$totalBytes,'expiryTime'=>$expiryTime,'tgId'=>0,'comment'=>'AlexVPN Purchase','enable'=>true,'subId'=>$subId]];
    $result=panelApi('clients/add','POST',$payload);
    if($result['http_code']===200 && isset($result['data']['success']) && $result['data']['success']){
        return ['success'=>true,'username'=>$username,'subscription_url'=>buildSubscriptionUrl($subId),'subId'=>$subId];
    }
    return ['success'=>false,'error'=>$result['data']['msg']??($result['error']??'Connection failed')];
}

function sendTelegramMessage($chatId,$text,$replyMarkup=null) {
    $url='https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/sendMessage';
    $data=['chat_id'=>$chatId,'text'=>$text,'parse_mode'=>'HTML'];
    if($replyMarkup) $data['reply_markup']=json_encode($replyMarkup);
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$data,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]);
    $res=curl_exec($ch);curl_close($ch);
    return json_decode($res,true);
}

// User auth functions
function isLoggedIn() { return isset($_SESSION['user_id']); }
function registerUser($email,$password,$name) {
    $users = loadUsers();
    foreach($users as $u) { if($u['email']===$email) return ['success'=>false,'error'=>'این ایمیل قبلا ثبت شده است.']; }
    if(strlen($password)<8) return ['success'=>false,'error'=>'رمز عبور باید حداقل ۸ کاراکتر باشد.'];
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) return ['success'=>false,'error'=>'ایمیل نامعتبر است.'];
    $users[] = ['id'=>'user_'.bin2hex(random_bytes(8)),'email'=>$email,'password'=>password_hash($password,PASSWORD_BCRYPT),'name'=>$name,'created_at'=>date('Y-m-d H:i:s')];
    saveUsers($users);
    return ['success'=>true];
}
function loginUser($email,$password) {
    $users = loadUsers();
    foreach($users as $u) { if($u['email']===$email && password_verify($password,$u['password'])) { $_SESSION['user_id']=$u['id']; $_SESSION['user_email']=$u['email']; $_SESSION['user_name']=$u['name']; return ['success'=>true]; } }
    return ['success'=>false,'error'=>'ایمیل یا رمز عبور اشتباه است.'];
}
function getCurrentUser() {
    if(!isLoggedIn()) return null;
    $users=loadUsers();
    foreach($users as $u) { if($u['id']===$_SESSION['user_id']) return $u; }
    return null;
}
function getUserOrders($userId) {
    $orders=loadOrders(); $userOrders=[];
    foreach($orders as $o) { if(($o['user_id']??'')===$userId) $userOrders[]=$o; }
    return $userOrders;
}
