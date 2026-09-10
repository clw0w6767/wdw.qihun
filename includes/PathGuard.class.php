<?php
class PathGuard {
    private static $allowedRoots = [];

    public static function init() {
        self::$allowedRoots = [
            realpath(__DIR__.'/../'),
            realpath(__DIR__.'/../public/'),
            realpath(__DIR__.'/../backups/'),
        ];
        self::$allowedRoots = array_filter(self::$allowedRoots);
    }

    public static function resolve($userInput, $baseDir=null) {
        if ($baseDir===null) $baseDir = realpath(__DIR__.'/../');
        $decoded = urldecode($userInput);
        $filename = basename($decoded);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) return false;
        $realPath = realpath($baseDir . DIRECTORY_SEPARATOR . $filename);
        if ($realPath===false) return false;
        foreach (self::$allowedRoots as $root) {
            if (strpos($realPath, $root)===0) return $realPath;
        }
        return false;
    }

    public static function safeInclude($userInput) {
        $base = realpath(__DIR__.'/../pages/');
        if ($base===false) return false;
        $name = basename(urldecode($userInput));
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) return false;
        $path = realpath($base.'/'.$name.'.php');
        if ($path===false || strpos($path, $base)!==0) return false;
        include $path;
        return true;
    }
}
?>
