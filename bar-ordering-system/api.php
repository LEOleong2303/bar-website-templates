<?php
/**
 * Bar Ordering System API (shared-hosting PHP version)
 *
 * 用法：api.php?action=<動作>
 *   get_menu        GET   公開：攞酒單
 *   submit_order    POST  公開：客人落單（JSON：items、spot、note）
 *   login           POST  公開：吧台 / 管理頁登入（JSON：pin）
 *   logout          POST  已登入：登出
 *   get_orders      GET   已登入：攞訂單（最新先）
 *   update_order    POST  已登入：改訂單狀態（JSON：id、status）
 *   admin_save_menu POST  已登入：儲存成份酒單（JSON：menu）
 *
 * 訂單同酒單存喺 data/（.htaccess 已鎖住，唔可以直接經網頁開）。
 * 錢照舊喺吧枱俾，呢度冇接任何網上付款。
 */

// 時區：伺服器 PHP 預設係 UTC，同廣州（Asia/Shanghai）差 8 個鐘；
// 訂單時間用返本地時區，Telegram 通知同吧台顯示先會啱。
date_default_timezone_set('Asia/Shanghai');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';

$DATA_DIR    = __DIR__ . '/data';
$MENU_FILE   = $DATA_DIR . '/menu.json';
$ORDERS_FILE = $DATA_DIR . '/orders.json';

// 分類（同 index.html / admin.html 嘅 CATS 一致；次序 = 酒單頁分類次序）
$CATEGORIES = array(
    'cocktail' => '雞尾酒',
    'draft'    => '精釀啤酒',
    'whisky'   => '威士忌',
    'special'  => '基酒特調',
    'zero'     => '無酒精特飲',
    'sparkling' => '氣泡特飲',
    'cans'     => '環球特選啤酒',
    'wine'     => '紅酒',
    'shisha'   => 'Shisha',
);
$ORDER_STATUSES = array('待製作', '製作中', '完成');

// ---------- session ----------
$cookie_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params(array(
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $cookie_secure,
));
session_start();

// ---------- helpers ----------
function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error($msg, $code = 400) {
    json_out(array('ok' => false, 'error' => $msg), $code);
}

function get_body() {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return array();
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('請求格式唔啱', 400);
    }
    return $data;
}

function require_auth() {
    if (empty($_SESSION['bar_auth'])) {
        json_error('未登入', 401);
    }
}

function read_menu() {
    global $MENU_FILE;
    $raw = @file_get_contents($MENU_FILE);
    if ($raw === false) {
        json_error('酒單讀唔到', 500);
    }
    $menu = json_decode($raw, true);
    if (!is_array($menu)) {
        json_error('酒單格式唔啱', 500);
    }
    return $menu;
}

function read_orders() {
    global $ORDERS_FILE;
    $raw = @file_get_contents($ORDERS_FILE);
    if ($raw === false || trim($raw) === '') {
        return array();
    }
    $orders = json_decode($raw, true);
    return is_array($orders) ? $orders : array();
}

// 喺獨佔鎖入面讀 + 改 + 寫 orders.json（防止兩張單同時寫入撞到）
function with_orders_lock($fn) {
    global $ORDERS_FILE;
    $fp = fopen($ORDERS_FILE, 'c+');
    if ($fp === false) {
        json_error('訂單存唔到', 500);
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        json_error('訂單存唔到', 500);
    }
    $raw = stream_get_contents($fp);
    $orders = ($raw === '' || $raw === false) ? array() : json_decode($raw, true);
    if (!is_array($orders)) {
        $orders = array();
    }
    $result = $fn($orders);
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

// 原子寫入（寫去臨時檔再改名，唔會寫到一半斷咗）
function write_file_atomic($path, $contents) {
    $tmp = $path . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

// 登入失敗次數（按 IP 計，存喺 data/，同訂單一樣俾 .htaccess 鎖住）
function login_attempts_file() {
    return $GLOBALS['DATA_DIR'] . '/login_attempts.json';
}

function login_blocked($ip) {
    $raw = @file_get_contents(login_attempts_file());
    if ($raw === false || trim($raw) === '') {
        return false;
    }
    $all = json_decode($raw, true);
    if (!is_array($all) || !isset($all[$ip])) {
        return false;
    }
    return !empty($all[$ip]['block_until']) && $all[$ip]['block_until'] > time();
}

function login_record($ip, $ok) {
    $file = login_attempts_file();
    $fp = fopen($file, 'c+');
    if ($fp === false) {
        return;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return;
    }
    $raw = stream_get_contents($fp);
    $all = ($raw === '' || $raw === false) ? array() : json_decode($raw, true);
    if (!is_array($all)) {
        $all = array();
    }
    $now = time();
    // 清掉超過 10 分鐘冇郁過嘅記錄
    foreach ($all as $k => $v) {
        if (empty($v['seen']) || $v['seen'] < $now - 600) {
            unset($all[$k]);
        }
    }
    if ($ok) {
        unset($all[$ip]);
    } else {
        $fails = isset($all[$ip]['fails']) ? (int) $all[$ip]['fails'] + 1 : 1;
        $all[$ip] = array('fails' => $fails, 'seen' => $now);
        if ($fails >= 10) {
            $all[$ip]['block_until'] = $now + 300;
            $all[$ip]['fails'] = 0;
        }
    }
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function seat_label($spot) {
    if ($spot === 'bar') {
        return array('label' => '吧枱', 'en' => 'BAR');
    }
    if (preg_match('/^seat([1-6])$/', $spot, $m)) {
        return array('label' => $m[1] . ' 號位', 'en' => 'BAR SEAT');
    }
    if (preg_match('/^table([1-3])$/', $spot, $m)) {
        return array('label' => $m[1] . ' 號枱', 'en' => 'TABLE');
    }
    return null;
}

// Telegram 新單通知（可選）：客人落單之後推一條訊息去 Telegram。
// config.php 入面 TELEGRAM_BOT_TOKEN 同 TELEGRAM_CHAT_ID 兩個都填咗先會發送；
// 唔填就乜都唔做。任何失敗（冇網絡、token 錯、逾時、Telegram 報錯）都只會寫入
// error_log，絕對唔會影響落單本身 — 客人嗰邊永遠照樣收到成功回覆。
// 淨係試一次，唔重試。
function send_telegram_order($order) {
    global $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID;
    if (empty($TELEGRAM_BOT_TOKEN) || empty($TELEGRAM_CHAT_ID)) {
        return;
    }
    $lines = array();
    $lines[] = '🔔 新訂單 #' . (isset($order['no']) ? $order['no'] : '');
    $lines[] = isset($order['seat']) ? $order['seat'] : '';
    foreach ($order['items'] as $it) {
        $lines[] = $it['n'] . ' ×' . $it['q'];
    }
    $lines[] = '總數 ¥' . $order['total'];
    $lines[] = '備註：' . (trim($order['note']) !== '' ? $order['note'] : '無');
    $lines[] = trim(($order['date'] ?? '') . ' ' . ($order['time'] ?? ''));
    $text = implode("\n", $lines);
    $url = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN . '/sendMessage';
    $payload = json_encode(array(
        'chat_id' => $TELEGRAM_CHAT_ID,
        'text'    => $text,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($resp === false) {
                error_log('Telegram 通知失敗：' . curl_error($ch));
            } elseif ($code < 200 || $code >= 300) {
                error_log('Telegram 通知失敗：HTTP ' . $code);
            }
            curl_close($ch);
        } else {
            // 冇 curl 嘅話用 stream context；要檢查埋 HTTP 狀態同 Telegram 回覆，
            // 唔可以淨係睇有冇收到嘢（ignore_errors 令 401 都會照樣返 body）。
            $ctx = stream_context_create(array('http' => array(
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n",
                'content'       => $payload,
                'timeout'       => 5,
                'ignore_errors' => true,
            )));
            $resp = @file_get_contents($url, false, $ctx);
            $ok = false;
            if ($resp !== false) {
                $decoded = json_decode($resp, true);
                if (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
                    $ok = true;
                }
            }
            if (!$ok) {
                $code = '';
                if (isset($http_response_header) && is_array($http_response_header)) {
                    foreach ($http_response_header as $h) {
                        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                            $code = 'HTTP ' . $m[1];
                            break;
                        }
                    }
                }
                error_log('Telegram 通知失敗：' . ($code !== '' ? $code : '連唔到 api.telegram.org'));
            }
        }
    } catch (Throwable $e) {
        error_log('Telegram 通知失敗：' . $e->getMessage());
    }
}

// ---------- routing ----------
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET';

switch ($action) {

    // ----- 公開：攞酒單 -----
    case 'get_menu':
        if ($method !== 'GET') {
            json_error('方法唔啱', 405);
        }
        json_out(array('ok' => true, 'menu' => read_menu()));
        break;

    // ----- 公開：客人落單 -----
    case 'submit_order':
        if ($method !== 'POST') {
            json_error('方法唔啱', 405);
        }
        $body = get_body();
        $items = isset($body['items']) ? $body['items'] : null;
        $spot  = isset($body['spot']) ? (string) $body['spot'] : '';
        $note  = isset($body['note']) ? (string) $body['note'] : '';

        if (!is_array($items) || count($items) === 0 || count($items) > 20) {
            json_error('訂單冇嘢', 400);
        }
        $seat = seat_label($spot);
        if ($seat === null) {
            json_error('座位唔啱', 400);
        }
        if (mb_strlen($note, 'UTF-8') > 200) {
            json_error('備註太長', 400);
        }

        $menu = read_menu();
        $by_id = array();
        foreach ($menu as $m) {
            $by_id[$m['id']] = $m;
        }
        $order_items = array();
        $total = 0;
        foreach ($items as $it) {
            if (!is_array($it) || !isset($it['id']) || !isset($it['qty'])) {
                json_error('訂單格式唔啱', 400);
            }
            $id  = (string) $it['id'];
            $qty = $it['qty'];
            if (!isset($by_id[$id])) {
                json_error('有飲品唔存在', 400);
            }
            if (!is_int($qty) && !ctype_digit((string) $qty)) {
                json_error('數量唔啱', 400);
            }
            $qty = (int) $qty;
            if ($qty < 1 || $qty > 20) {
                json_error('數量唔啱', 400);
            }
            $m = $by_id[$id];
            if ($m['price'] === null) {
                json_error('呢款要問吧台，唔可以直接落單', 400);
            }
            $order_items[] = array(
                'id'  => $id,
                'n'   => $m['name'],
                'p'   => (int) $m['price'],
                'q'   => $qty,
                'img' => isset($m['img']) ? (string) $m['img'] : '',
                'ing' => isset($m['ing']) ? (string) $m['ing'] : '',
            );
            $total += (int) $m['price'] * $qty;
        }

        $saved = with_orders_lock(function (&$orders) use ($order_items, $total, $note, $seat) {
            $no = 1;
            foreach ($orders as $o) {
                if (isset($o['no']) && $o['no'] >= $no) {
                    $no = $o['no'] + 1;
                }
            }
            $order = array(
                'id'     => 'ord-' . bin2hex(random_bytes(6)),
                'no'     => $no,
                'date'   => date('Y-m-d'),
                'time'   => date('H:i'),
                'seat'   => $seat['label'],
                'seatEn' => $seat['en'],
                'items'  => $order_items,
                'total'  => $total,
                'note'   => trim($note),
                'status' => '待製作',
            );
            $orders[] = $order;
            return $order;
        });
        send_telegram_order($saved);
        json_out(array('ok' => true, 'order' => $saved));
        break;

    // ----- 公開：吧台 / 管理頁登入 -----
    case 'login':
        if ($method !== 'POST') {
            json_error('方法唔啱', 405);
        }
        global $BAR_PIN_HASH;
        $ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? (string) $_SERVER['HTTP_CF_CONNECTING_IP']
            : (isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown');
        // 阻暴力試 PIN：同一個 IP 錯 10 次就鎖 5 分鐘
        if (login_blocked($ip)) {
            json_error('試得太多，請稍後再試', 429);
        }
        $body = get_body();
        $pin = isset($body['pin']) ? (string) $body['pin'] : '';
        if (!preg_match('/^\d{4}$/', $pin)) {
            login_record($ip, false);
            json_error('PIN 唔啱', 401);
        }
        if (hash_equals($BAR_PIN_HASH, hash('sha256', $pin))) {
            login_record($ip, true);
            session_regenerate_id(true);
            $_SESSION['bar_auth'] = true;
            json_out(array('ok' => true));
        }
        login_record($ip, false);
        usleep(400000);
        json_error('PIN 唔啱', 401);
        break;

    // ----- 已登入：登出 -----
    case 'logout':
        if ($method !== 'POST') {
            json_error('方法唔啱', 405);
        }
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        json_out(array('ok' => true));
        break;

    // ----- 已登入：攞訂單（最新先） -----
    case 'get_orders':
        if ($method !== 'GET') {
            json_error('方法唔啱', 405);
        }
        require_auth();
        $orders = read_orders();
        usort($orders, function ($a, $b) {
            return ($b['no'] ?? 0) - ($a['no'] ?? 0);
        });
        json_out(array('ok' => true, 'orders' => array_slice($orders, 0, 100)));
        break;

    // ----- 已登入：改訂單狀態 -----
    case 'update_order':
        if ($method !== 'POST') {
            json_error('方法唔啱', 405);
        }
        require_auth();
        $body = get_body();
        $id = isset($body['id']) ? (string) $body['id'] : '';
        $status = isset($body['status']) ? (string) $body['status'] : '';
        if ($id === '' || !in_array($status, $GLOBALS['ORDER_STATUSES'], true)) {
            json_error('資料唔啱', 400);
        }
        $updated = with_orders_lock(function (&$orders) use ($id, $status) {
            foreach ($orders as &$o) {
                if (($o['id'] ?? '') === $id) {
                    $o['status'] = $status;
                    return $o;
                }
            }
            return null;
        });
        if ($updated === null) {
            json_error('搵唔到呢張單', 404);
        }
        json_out(array('ok' => true, 'order' => $updated));
        break;

    // ----- 已登入：儲存成份酒單 -----
    case 'admin_save_menu':
        if ($method !== 'POST') {
            json_error('方法唔啱', 405);
        }
        require_auth();
        $body = get_body();
        $menu = isset($body['menu']) ? $body['menu'] : null;
        if (!is_array($menu) || count($menu) === 0 || count($menu) > 200) {
            json_error('酒單格式唔啱', 400);
        }
        $seen = array();
        $clean = array();
        foreach ($menu as $it) {
            if (!is_array($it)) {
                json_error('酒單格式唔啱', 400);
            }
            $id = isset($it['id']) ? (string) $it['id'] : '';
            $name = isset($it['name']) ? trim((string) $it['name']) : '';
            $cat = isset($it['category']) ? (string) $it['category'] : '';
            $price = array_key_exists('price', $it) ? $it['price'] : null;
            $sub = isset($it['sub']) ? trim((string) $it['sub']) : '';
            $ing = isset($it['ing']) ? trim((string) $it['ing']) : '';
            $img = isset($it['img']) ? trim((string) $it['img']) : '';
            if (!preg_match('/^[a-z0-9][a-z0-9\-]*$/', $id)) {
                json_error('飲品編號唔啱', 400);
            }
            if (isset($seen[$id])) {
                json_error('飲品編號重複', 400);
            }
            $seen[$id] = true;
            if ($name === '' || mb_strlen($name, 'UTF-8') > 60) {
                json_error('飲品名唔啱', 400);
            }
            if (!isset($GLOBALS['CATEGORIES'][$cat])) {
                json_error('分類唔啱', 400);
            }
            if ($price !== null) {
                if (!is_int($price) && !ctype_digit((string) $price)) {
                    json_error('價錢唔啱', 400);
                }
                $price = (int) $price;
                if ($price < 0 || $price > 100000) {
                    json_error('價錢唔啱', 400);
                }
            }
            if (mb_strlen($sub, 'UTF-8') > 100) {
                json_error('副標題太長', 400);
            }
            if (mb_strlen($ing, 'UTF-8') > 200) {
                json_error('英文成份太長', 400);
            }
            if ($img !== '' && !preg_match('#^assets/img/[a-z0-9\-]+\.(jpg|jpeg|png|webp)$#', $img)) {
                json_error('相片路徑唔啱', 400);
            }
            $row = array(
                'id'            => $id,
                'name'          => $name,
                'price'         => $price,
                'category'      => $cat,
                'category_name' => $GLOBALS['CATEGORIES'][$cat],
                'img'           => $img,
            );
            if ($sub !== '') {
                $row['sub'] = $sub;
            }
            if ($ing !== '') {
                $row['ing'] = $ing;
            }
            $clean[] = $row;
        }
        $encoded = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
        if (!write_file_atomic($GLOBALS['MENU_FILE'], $encoded)) {
            json_error('酒單存唔到', 500);
        }
        json_out(array('ok' => true, 'count' => count($clean)));
        break;

    default:
        json_error('唔知你想做咩', 400);
        break;
}
