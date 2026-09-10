const canvas = document.getElementById('goban');
const ctx = canvas.getContext('2d');
const SIZE = 19, MARGIN = 30;
const CELL = (canvas.width - 2*MARGIN) / (SIZE-1);
let board = [], currentTurn = 1, myColor = 0, history = [];

function fetchState() {
    fetch('api.php?action=get_room_state&room_id=' + roomId)
    .then(r => r.json()).then(data => {
        if (data.code !== 1) return;
        board = data.board; currentTurn = data.current_turn; history = data.move_history || [];
        if (data.black_id == userId) myColor = 1;
        else if (data.white_id == userId) myColor = 2;
        // 更新棋谱
        let html = '';
        history.forEach((m, i) => {
            const col = String.fromCharCode(65+m.c), row = 19-m.r;
            html += `${i+1}. ${m.color==1?'黑':'白'}${col}${row} `;
            if ((i+1)%5===0) html += '<br>';
        });
        document.getElementById('historyList').innerHTML = html || '—— 对局开始 ——';
        drawBoard();
    });
}

function drawBoard() {
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.fillStyle = '#d9b382'; ctx.fillRect(0,0,canvas.width,canvas.height);
    ctx.strokeStyle = '#2c1e0e'; ctx.lineWidth = 1;
    for (let i=0;i<SIZE;i++) {
        ctx.beginPath(); ctx.moveTo(MARGIN, MARGIN+i*CELL); ctx.lineTo(MARGIN+(SIZE-1)*CELL, MARGIN+i*CELL); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(MARGIN+i*CELL, MARGIN); ctx.lineTo(MARGIN+i*CELL, MARGIN+(SIZE-1)*CELL); ctx.stroke();
    }
    for (let r of [3,9,15]) for (let c of [3,9,15]) {
        ctx.beginPath(); ctx.arc(MARGIN+c*CELL, MARGIN+r*CELL, 5, 0, 2*Math.PI);
        ctx.fillStyle = '#2c1e0e'; ctx.fill();
    }
    for (let r=0;r<SIZE;r++) for (let c=0;c<SIZE;c++) {
        if (board[r][c]==0) continue;
        const x = MARGIN+c*CELL, y = MARGIN+r*CELL;
        const g = ctx.createRadialGradient(x-4,y-4,3,x,y,CELL*0.45);
        if (board[r][c]==1) { g.addColorStop(0,'#333'); g.addColorStop(1,'#000'); }
        else { g.addColorStop(0,'#f9f9f9'); g.addColorStop(1,'#bbb'); }
        ctx.beginPath(); ctx.arc(x,y,CELL*0.43,0,2*Math.PI); ctx.fillStyle = g; ctx.fill();
    }
    if (history.length > 0) {
        const last = history[history.length-1];
        ctx.strokeStyle = 'red'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.arc(MARGIN+last.c*CELL, MARGIN+last.r*CELL, 6, 0, 2*Math.PI); ctx.stroke();
    }
}

canvas.addEventListener('click', function(e) {
    if (myColor !== currentTurn) return alert('请等待对方落子');
    if (myColor === 0) return alert('你还在等待对手');
    const rect = canvas.getBoundingClientRect();
    const sx = canvas.width/rect.width, sy = canvas.height/rect.height;
    const mx = (e.clientX-rect.left)*sx, my = (e.clientY-rect.top)*sy;
    let minD = CELL/2, row=-1, col=-1;
    for (let r=0;r<SIZE;r++) for (let c=0;c<SIZE;c++) {
        const d = Math.hypot(mx-(MARGIN+c*CELL), my-(MARGIN+r*CELL));
        if (d<minD) { minD=d; row=r; col=c; }
    }
    if (row==-1) return;
    fetch('api.php?action=place', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`room_id=${roomId}&row=${row}&col=${col}`
    }).then(r=>r.json()).then(data=>{ if (data.code!==1) alert(data.msg); else fetchState(); });
});

function fetchChat() {
    fetch('api.php?action=get_chat&room_id='+roomId).then(r=>r.json()).then(data=>{
        if (data.code===1) {
            const c = document.getElementById('chatMessages');
            c.innerHTML = data.messages.map(m=>`<div><b>${m.username}</b>: ${m.message}</div>`).join('');
            c.scrollTop = c.scrollHeight;
        }
    });
}

document.getElementById('sendChatBtn').addEventListener('click', function() {
    const input = document.getElementById('chatInput');
    if (!input.value.trim()) return;
    fetch('api.php?action=send_chat', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`room_id=${roomId}&message=${encodeURIComponent(input.value)}`
    }).then(()=>{ input.value=''; fetchChat(); });
});

fetchState(); fetchChat();
setInterval(fetchState, 2000);
setInterval(fetchChat, 2000);
