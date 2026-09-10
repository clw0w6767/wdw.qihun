<?php require_once 'config.php'; check_login(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>围达网 · 对局室</title>
    <link rel="stylesheet" href="assets.php?type=css&f=style.css">
</head>
<body>
<div class="room-container">
    <!-- 左侧：棋盘 -->
    <div class="board-wrapper">
        <canvas id="goban" width="600" height="600"></canvas>
        <div class="board-info">
            <span>黑方: <span id="blackName">-</span></span>
            <span>白方: <span id="whiteName">-</span></span>
            <span>时间: <span id="timer">00:00</span></span>
        </div>
    </div>
    <!-- 右侧：棋谱 + 聊天 -->
    <div class="side-panel">
        <div class="move-history">
            <h3>📜 棋谱</h3>
            <div id="historyList">—— 对局开始 ——</div>
        </div>
        <div class="chat-box">
            <h3>💬 聊天</h3>
            <div id="chatMessages"></div>
            <div class="chat-input">
                <input type="text" id="chatInput" placeholder="输入消息...">
                <button id="sendChatBtn">发送</button>
            </div>
        </div>
    </div>
</div>

<script>
    // 前端全局变量，传给 room.js 使用
    const roomId = new URLSearchParams(location.search).get('room_id');
    const userId = <?= json_encode($_SESSION['user_id']) ?>;
</script>
<script src="assets.php?type=js&f=room.js"></script>
</body>
</html>
