<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$id_termo = isset($_GET['id_termo']) ? sanitizeInput($_GET['id_termo']) : null;
$area_id = isset($_GET['area_id']) ? trim(sanitizeInput($_GET['area_id'])) : null;

if (!$id_termo || $area_id === null || $area_id === '') {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM area_inspecionada WHERE id_termo_inspecao = :id_termo AND id = :aid");
    $stmt->bindValue(':id_termo', $id_termo);
    $stmt->bindValue(':aid', $area_id);
    $stmt->execute();
} catch (PDOException $e) {}

header('Location: edit.php?id=' . urlencode($id_termo) . '&success=area_deleted');
exit;
