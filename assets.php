<?php
require_once 'config.php';
require_once 'includes/PathGuard.class.php';
require_once 'includes/AssetProtector.php';

PathGuard::init();
$type = $_GET['type'] ?? '';
$file = $_GET['f'] ?? '';

if ($type === 'js') {
    AssetProtector::serveJs($file);
} elseif ($type === 'css') {
    AssetProtector::serveCss($file);
} else {
    http_response_code(404);
    exit('Not Found');
}
?>
