<?php require_once 'config.php'; ?>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>围达网 · 大厅</title>
    <link rel="stylesheet" href="assets.php?type=css&f=style.css">
</head>
<body>
<div class="container">
    <h1>⚫ 围达网 ⚪</h1>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div id="loginBox" class="login-box">
            <h2>登录 / 注册</h2>
            <?php if (!empty($error_msg)): ?>
                <p style="color:#ff6b6b; text-align:center;"><?= $error_msg ?></p>
            <?php endif; ?>
            <div class="form-group">
                <input type="text" id="loginUser" placeholder="用户名（字母数字3-20位）" autocomplete="off">
            </div>
            <div class="form-group">
                <input type="password" id="loginPass" placeholder="密码（至少6位）">
            </div>
            <div class="form-actions">
                <button onclick="login()" class="btn-primary">登录</button>
                <button onclick="register()" class="btn-secondary">注册</button>
            </div>
            <div id="msg" class="message"></div>
        </div>
    <?php else: ?>
        <div class="lobby">
            <p class="welcome">欢迎，<strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong> 
                [<a href="?logout=1" class="logout-link">退出</a>]</p>
            
            <div class="match-box" style="text-align:center; margin: 40px 0;">
                <button onclick="startMatch()" class="btn-primary" style="font-size:1.5rem; padding:20px 60px;">
                    ⚔️ 发起对战（自动匹配）
                </button>
                <p id="matchStatus" style="color:#d4c9a8; margin-top:15px; font-size:1.2rem;"></p>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="assets.php?type=js&f=app.js"></script>
</body>
</html>
