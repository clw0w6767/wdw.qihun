<?php
// ===== 安全防线 1：WAF 入口检测 =====
require_once __DIR__ . '/includes/WAF.class.php';
WAF::inspect();
// ===== WAF 检测结束 =====

ini_set('display_errors', 0);
error_reporting(E_ALL);

// 数据库配置（部署时改成 InfinityFree 给你的信息）
define('DB_HOST', 'localhost');
define('DB_NAME', 'weida');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BACKUP_DIR', __DIR__ . '/backups/');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) { die('数据库连接失败'); }

// 输入过滤
function safe_input($data) {
    if (is_array($data)) {
        foreach ($data as $k => $v) $data[$k] = safe_input($v);
    } else {
        $data = trim($data);
        $data = strip_tags($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}
$_GET = safe_input($_GET);
$_POST = safe_input($_POST);
$_COOKIE = safe_input($_COOKIE);

// 日志函数
function write_log($user_id, $action, $detail = '') {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, detail, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $detail, $ip]);
}

// Session 安全启动
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();

// 会话绑定校验
function validateSession() {
    if (!isset($_SESSION['user_id'])) return false;
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!isset($_SESSION['_ua'])) { $_SESSION['_ua'] = $currentUa; return true; }
    if ($_SESSION['_ua'] !== $currentUa) { session_destroy(); return false; }
    return true;
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?error=请先登录');
        exit;
    }
    if (!validateSession()) {
        header('Location: index.php?error=会话异常');
        exit;
    }
}
?>
