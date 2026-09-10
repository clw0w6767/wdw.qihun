<?php
class AssetProtector {
    public static function serveJs($file) {
        self::checkReferer();
        header('Content-Type: application/javascript; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        $path = PathGuard::resolve($file, realpath(__DIR__.'/../public/js/'));
        if ($path===false) { http_response_code(404); exit('// Not Found'); }
        echo file_get_contents($path);
        exit;
    }

    public static function serveCss($file) {
        self::checkReferer();
        header('Content-Type: text/css; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        $path = PathGuard::resolve($file, realpath(__DIR__.'/../public/css/'));
        if ($path===false) { http_response_code(404); exit('/* Not Found */'); }
        echo file_get_contents($path);
        exit;
    }

    private static function checkReferer() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($referer)) { http_response_code(403); exit('/* 403: 禁止直接访问 */'); }
        $refHost = parse_url($referer, PHP_URL_HOST);
        if ($refHost !== $host && $refHost !== 'localhost') {
            http_response_code(403); exit('/* 403: 禁止外站引用 */');
        }
    }
}
?>
