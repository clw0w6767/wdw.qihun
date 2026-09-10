<?php
// 使用时通过 URL 加 ?token=你的密钥 调用
if (($_GET['token'] ?? '') !== 'CHANGE_THIS_TOKEN_2026') {
    http_response_code(403);
    exit('Forbidden');
}
require_once 'config.php';

if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0755, true);
$file = BACKUP_DIR . 'weida_' . date('Ymd') . '.sql.gz';
$cmd = "mysqldump -u".escapeshellarg(DB_USER)." -p".escapeshellarg(DB_PASS)." ".escapeshellarg(DB_NAME)." | gzip > ".escapeshellarg($file);
exec($cmd, $out, $ret);
exec("find ".escapeshellarg(BACKUP_DIR)." -name '*.sql.gz' -mtime +7 -delete");
write_log(0, 'auto_backup', $ret===0 ? '成功' : '失败');
echo 'Backup done: ' . ($ret===0 ? 'OK' : 'FAIL');
?>
