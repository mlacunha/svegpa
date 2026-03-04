<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if ($id) {
    try {
        $db->prepare("DELETE FROM area_inspecionada WHERE id_termo_inspecao = ?")->execute([$id]);
        $stmt = $db->prepare("DELETE FROM termo_inspecao WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    } catch (PDOException $e) {
        // Erro silencioso
    }
}

header('Location: index.php?success=deleted');
exit;
