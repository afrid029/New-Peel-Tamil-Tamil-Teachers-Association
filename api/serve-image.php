<?php

/**
 * Serve uploaded images securely (posters).
 * Usage: api/serve-image.php?f=posters/filename.jpg
 */
$baseDir = realpath(__DIR__ . '/../assets/uploads');
$file    = $_GET['f'] ?? '';

if ($file === '' || strpos($file, '..') !== false) {
    http_response_code(400);
    exit;
}

$path = realpath($baseDir . '/' . $file);
if (!$path || strpos($path, $baseDir) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit;
}

$allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if (!isset($allowed[$ext])) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $allowed[$ext]);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
readfile($path);
exit;
