<?php
require_once 'config.php';
require_once 'includes/GoBoard.class.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if (!in_array($action, ['login', 'register']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['code'=>0, 'msg'=>'请先登录']); exit;
}

// --- 注册 ---
if ($action == 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (strlen($username)<3 || strlen($password)<6) {
        echo json_encode(['code'=>0, 'msg'=>'用户名至少3位，密码至少6位']); exit;
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashed]);
        write_log(0, 'register', "用户{$username}注册");
        echo json_encode(['code'=>1, 'msg'=>'注册成功，请登录']);
    } catch(PDOException $e) {
        echo json_encode(['code'=>0, 'msg'=>'用户名已存在']);
    }
    exit;
}

// --- 登录 ---
if ($action == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        write_log($user['id'], 'login', '登录成功');
        echo json_encode(['code'=>1, 'msg'=>'登录成功']);
    } else {
        write_log(0, 'login_fail', "用户{$username}登录失败");
        echo json_encode(['code'=>0, 'msg'=>'用户名或密码错误']);
    }
    exit;
}

// --- 自动匹配 ---
if ($action == 'matchmake') {
    $user_id = $_SESSION['user_id'];
    // 1. 先查自己是不是已经在等待队列里了（防止点两次）
    $stmt = $pdo->prepare("SELECT room_id FROM active_rooms WHERE black_id = ? AND white_id IS NULL");
    $stmt->execute([$user_id]);
    $myWait = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($myWait) {
        echo json_encode(['code'=>1, 'room_id'=>$myWait['room_id'], 'status'=>'waiting']); exit;
    }

    // 2. 查数据库里有没有别人在等待的房间
    $stmt = $pdo->prepare("SELECT room_id FROM active_rooms WHERE white_id IS NULL AND black_id != ? ORDER BY last_update ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $waitRoom = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($waitRoom) {
        // 找到等待中的房间，加入进去
        $upd = $pdo->prepare("UPDATE active_rooms SET white_id = ? WHERE room_id = ?");
        $upd->execute([$user_id, $waitRoom['room_id']]);
        write_log($user_id, 'match_join', "加入房间 {$waitRoom['room_id']}");
        echo json_encode(['code'=>1, 'room_id'=>$waitRoom['room_id'], 'status'=>'matched']); exit;
    } else {
        // 3. 没找到，自己创建一个房间等待
        $room_id = substr(md5(uniqid().mt_rand()), 0, 6);
        $board = new GoBoard();
        $stmt = $pdo->prepare("INSERT INTO active_rooms (room_id, black_id, board_data, current_turn, move_history, ko) VALUES (?, ?, ?, 1, ?, ?)");
        $stmt->execute([$room_id, $user_id, json_encode($board->board), json_encode([]), json_encode(null)]);
        write_log($user_id, 'match_create', "创建等待房间 {$room_id}");
        echo json_encode(['code'=>1, 'room_id'=>$room_id, 'status'=>'waiting']); exit;
    }
}

// --- 获取对局室状态 ---
if ($action == 'get_room_state') {
    $room_id = $_GET['room_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM active_rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) { echo json_encode(['code'=>0, 'msg'=>'房间不存在']); exit; }
    echo json_encode([
        'code'=>1, 'board'=>json_decode($room['board_data'], true),
        'current_turn'=>$room['current_turn'], 'move_history'=>json_decode($room['move_history'], true),
        'ko'=>json_decode($room['ko'], true), 'captured_black'=>$room['captured_black'],
        'captured_white'=>$room['captured_white'], 'black_id'=>$room['black_id'], 'white_id'=>$room['white_id']
    ]); exit;
}

// --- 落子 ---
if ($action == 'place') {
    $room_id = $_POST['room_id'] ?? '';
    $row = intval($_POST['row'] ?? -1);
    $col = intval($_POST['col'] ?? -1);
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM active_rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) { echo json_encode(['code'=>0, 'msg'=>'房间不存在']); exit; }

    $color = 0;
    if ($room['black_id'] == $user_id) $color = 1;
    elseif ($room['white_id'] == $user_id) $color = 2;
    else { echo json_encode(['code'=>0, 'msg'=>'你不是该房间玩家']); exit; }
    if ($room['current_turn'] != $color) { echo json_encode(['code'=>0, 'msg'=>'不是你的回合']); exit; }

    $board = new GoBoard();
    $board->board = json_decode($room['board_data'], true);
    $board->history = json_decode($room['move_history'], true);
    $board->captured = [1=>$room['captured_black'], 2=>$room['captured_white']];
    $board->ko = json_decode($room['ko'], true);

    $result = $board->place($row, $col, $color);
    if (!$result['success']) { echo json_encode(['code'=>0, 'msg'=>$result['error']]); exit; }

    $new_turn = ($color == 1) ? 2 : 1;
    $upd = $pdo->prepare("UPDATE active_rooms SET board_data=?, current_turn=?, move_history=?, ko=?, captured_black=?, captured_white=? WHERE room_id=?");
    $upd->execute([json_encode($board->board), $new_turn, json_encode($board->history), json_encode($board->ko), $board->captured[1], $board->captured[2], $room_id]);
    write_log($user_id, 'place', "房间{$room_id} 落子({$row},{$col})");
    echo json_encode(['code'=>1, 'msg'=>'落子成功']); exit;
}

// --- 聊天 ---
if ($action == 'send_chat') {
    $room_id = $_POST['room_id'] ?? '';
    $message = strip_tags($_POST['message'] ?? '');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    if (empty($message)) { echo json_encode(['code'=>0, 'msg'=>'消息不能为空']); exit; }
    $stmt = $pdo->prepare("INSERT INTO chat_messages (room_id, user_id, username, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$room_id, $_SESSION['user_id'], $_SESSION['username'], $message]);
    echo json_encode(['code'=>1, 'msg'=>'发送成功']); exit;
}

if ($action == 'get_chat') {
    $room_id = $_GET['room_id'] ?? '';
    $stmt = $pdo->prepare("SELECT user_id, username, message, time FROM chat_messages WHERE room_id = ? ORDER BY time ASC LIMIT 50");
    $stmt->execute([$room_id]);
    echo json_encode(['code'=>1, 'messages'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

// --- 认输 ---
if ($action == 'resign') {
    $room_id = $_POST['room_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM active_rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) { echo json_encode(['code'=>0, 'msg'=>'房间不存在']); exit; }
    if ($room['black_id'] == $user_id) { $winner_id = $room['white_id']; $loser_id = $room['black_id']; }
    elseif ($room['white_id'] == $user_id) { $winner_id = $room['black_id']; $loser_id = $room['white_id']; }
    else { echo json_encode(['code'=>0, 'msg'=>'你不是该房间玩家']); exit; }

    $stmt = $pdo->prepare("INSERT INTO games (room_id, black_id, white_id, winner_id, loser_id, win_type, sgf, move_count, end_time, status) VALUES (?, ?, ?, ?, ?, 'resign', ?, ?, NOW(), 2)");
    $stmt->execute([$room_id, $room['black_id'], $room['white_id'], $winner_id, $loser_id, $room['move_history'], count(json_decode($room['move_history'], true))]);
    $pdo->prepare("UPDATE users SET wins=wins+1, rating=rating+10 WHERE id=?")->execute([$winner_id]);
    $pdo->prepare("UPDATE users SET losses=losses+1, rating=rating-5 WHERE id=?")->execute([$loser_id]);
    $pdo->prepare("DELETE FROM active_rooms WHERE room_id=?")->execute([$room_id]);
    write_log($user_id, 'resign', "房间{$room_id} 认输");
    echo json_encode(['code'=>1, 'msg'=>'已认输']); exit;
}
?>
