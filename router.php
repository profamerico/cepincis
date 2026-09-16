<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$file = __DIR__ . $path;

if (
    $path !== '/' &&
    file_exists($file) &&
    !is_dir($file)
) {
    return false;
}

if ($path !== '/') {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

return false;