<?php
/**
 * Upload de logo para órgão.
 * Aceita arquivo via POST (logo_file).
 * Redimensiona se necessário (max 400x120px para cabeçalho de documento).
 * Salva em uploads/orgaos_logos/ e retorna caminho relativo.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acesso negado']);
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/orgaos_logos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$maxW = 400;
$maxH = 120;
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function resizeImage($srcPath, $destPath, $maxW, $maxH) {
    $info = getimagesize($srcPath);
    if (!$info) return false;
    $w = $info[0];
    $h = $info[1];
    $mime = $info['mime'];
    if ($w <= $maxW && $h <= $maxH) {
        return copy($srcPath, $destPath);
    }
    $ratio = min($maxW / $w, $maxH / $h);
    $nw = (int) round($w * $ratio);
    $nh = (int) round($h * $ratio);
    $src = match ($mime) {
        'image/jpeg', 'image/jpg' => imagecreatefromjpeg($srcPath),
        'image/png' => imagecreatefrompng($srcPath),
        'image/gif' => imagecreatefromgif($srcPath),
        'image/webp' => imagecreatefromwebp($srcPath),
        default => null,
    };
    if (!$src) return false;
    $dst = imagecreatetruecolor($nw, $nh);
    if (!$dst) { $src = null; return false; }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $src = null; // imagedestroy deprecado em PHP 8.5+
    $ok = false;
    $ext = pathinfo($destPath, PATHINFO_EXTENSION);
    if ($ext === 'png') $ok = imagepng($dst, $destPath);
    elseif ($ext === 'gif') $ok = imagegif($dst, $destPath);
    elseif ($ext === 'webp') $ok = function_exists('imagewebp') ? imagewebp($dst, $destPath) : imagepng($dst, $destPath);
    else $ok = imagejpeg($dst, $destPath, 90);
    $dst = null;
    return $ok;
}

$relativePath = '';
$oldPath = isset($_POST['old_logo']) ? trim($_POST['old_logo']) : '';

if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['logo_file'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedTypes)) {
        echo json_encode(['ok' => false, 'error' => 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.']);
        exit;
    }
    $ext = match ($mime) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $fullPath = $uploadDir . $filename;
    if (resizeImage($file['tmp_name'], $fullPath, $maxW, $maxH)) {
        $relativePath = 'uploads/orgaos_logos/' . $filename;
        if ($oldPath && str_starts_with($oldPath, 'uploads/orgaos_logos/') && file_exists(dirname(__DIR__) . '/' . $oldPath)) {
            @unlink(dirname(__DIR__) . '/' . $oldPath);
        }
        echo json_encode(['ok' => true, 'path' => $relativePath]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Falha ao processar a imagem.']);
    }
} elseif (isset($_POST['remove']) && $_POST['remove'] === '1' && $oldPath) {
    $fullPath = dirname(__DIR__) . '/' . $oldPath;
    if (str_starts_with($oldPath, 'uploads/orgaos_logos/') && file_exists($fullPath)) {
        @unlink($fullPath);
    }
    echo json_encode(['ok' => true, 'path' => '']);
} else {
    echo json_encode(['ok' => false, 'error' => 'Nenhum arquivo recebido.']);
}
