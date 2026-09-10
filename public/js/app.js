function login() {
    const user = document.getElementById('loginUser').value;
    const pass = document.getElementById('loginPass').value;
    fetch('api.php?action=login', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `username=${encodeURIComponent(user)}&password=${encodeURIComponent(pass)}`
    }).then(r => r.json()).then(data => {
        if (data.code === 1) location.reload();
        else document.getElementById('msg').textContent = data.msg;
    });
}

function register() {
    const user = document.getElementById('loginUser').value;
    const pass = document.getElementById('loginPass').value;
    fetch('api.php?action=register', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `username=${encodeURIComponent(user)}&password=${encodeURIComponent(pass)}`
    }).then(r => r.json()).then(data => {
        document.getElementById('msg').textContent = data.msg;
    });
}

// --- 自动匹配核心逻辑 ---
let matchTimer = null;

function startMatch() {
    const statusText = document.getElementById('matchStatus');
    statusText.textContent = '正在寻找对手...';
    statusText.style.color = '#f0ad4e';

    // 先请求一次匹配
    fetch('api.php?action=matchmake', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
        if (data.code === 1) {
            if (data.status === 'matched') {
                // 直接匹配到人了，跳转房间
                statusText.textContent = '匹配成功！正在进入对局室...';
                statusText.style.color = '#5cb85c';
                setTimeout(() => { window.location.href = 'room.php?room_id=' + data.room_id; }, 1000);
            } else if (data.status === 'waiting') {
                // 自己在等待，开始轮询检查有没有人来
                statusText.textContent = '已创建等待房间，正在等待对手加入...';
                pollMatch(data.room_id);
            }
        } else {
            statusText.textContent = data.msg || '匹配失败';
            statusText.style.color = '#ff6b6b';
        }
    })
    .catch(e => {
        statusText.textContent = '网络错误，请重试';
        statusText.style.color = '#ff6b6b';
    });
}

// 轮询检查等待房间的状态
function pollMatch(roomId) {
    if (matchTimer) clearInterval(matchTimer);
    matchTimer = setInterval(() => {
        fetch('api.php?action=get_room_state&room_id=' + roomId)
        .then(r => r.json())
        .then(data => {
            if (data.code === 1 && data.white_id && data.white_id > 0) {
                // 有人加入了！
                clearInterval(matchTimer);
                document.getElementById('matchStatus').textContent = '对手已加入！正在进入对局室...';
                document.getElementById('matchStatus').style.color = '#5cb85c';
                setTimeout(() => { window.location.href = 'room.php?room_id=' + roomId; }, 1000);
            }
        });
    }, 2000); // 每2秒检查一次
}
