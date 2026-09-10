<?php
class WAF {
    private static $patterns = [
        '/(\bUNION\b.*\bSELECT\b)/i', '/(\bDROP\b.*\bTABLE\b)/i',
        '/(\bOR\b\s+[\'\"]?\d+[\'\"]?\s*=\s*[\'\"]?\d+)/i',
        '/(\bSLEEP\s*\(\s*\d+\s*\))/i', '/(\bLOAD_FILE\s*\()/i',
        '/(<script[^>]*>)/i', '/(javascript\s*:)/i',
        '/(on(load|error|click|mouseover)\s*=)/i', '/(<iframe[^>]*>)/i',
        '/(document\.(cookie|location|write))/i', '/(eval\s*\()/i',
        '/(\.\.\/|\.\.\\\\)/', '/(%2e%2e%2f|%2e%2e\/)/i',
        '/(\/etc\/passwd|\/etc\/shadow)/i', '/(php:\/\/filter)/i',
        '/(;\s*(ls|cat|rm|wget|curl|bash|sh)\b)/i',
        '/(\|\s*(ls|cat|rm|wget|curl|bash|sh)\b)/i',
        '/(<\?php)/i', '/(base64_decode\s*\()/i',
        '/(system\s*\()/i', '/(exec\s*\()/i', '/(passthru\s*\()/i',
        '/(shell_exec\s*\()/i', '/(popen\s*\()/i',
    ];

    public static function inspect() {
        $inputs = array_merge($_GET, $_POST);
        $uri = urldecode($_SERVER['REQUEST_URI'] ?? '');
        if (self::checkString($uri)) { self::block('URL', $uri); return false; }
        foreach ($inputs as $k=>$v) {
            $d1 = urldecode($v); $d2 = urldecode($d1);
            if (self::checkString($d1) || self::checkString($d2)) { self::block("参数[{$k}]", $v); return false; }
        }
        foreach ($_COOKIE as $k=>$v) {
            if (self::checkString(urldecode($v))) { self::block("Cookie[{$k}]", $v); return false; }
        }
        return true;
    }

    private static function checkString($str) {
        if (empty($str)) return false;
        foreach (self::$patterns as $p) { if (preg_match($p, $str)) return true; }
        return false;
    }

    private static function block($src, $payload) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $log = "[".date('Y-m-d H:i:s')."] IP:{$ip} 来源:{$src} 载荷:".substr($payload,0,200).PHP_EOL;
        @file_put_contents(__DIR__.'/../backups/waf.log', $log, FILE_APPEND|LOCK_EX);
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403</title></head>';
        echo '<body style="background:#1a2a3a;color:#d4c9a8;text-align:center;padding-top:20vh;">';
        echo '<h1>403 Forbidden</h1><p>您的请求已被安全系统拦截。</p></body></html>';
        exit;
    }
}
?>
