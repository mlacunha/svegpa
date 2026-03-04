<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if ($id) {
    try {
        $stmt = $db->prepare("SELECT logo FROM orgaos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $logo = $stmt->fetchColumn();
        if ($logo && str_starts_with($logo, 'uploads/orgaos_logos/')) {
            $logoPath = dirname(__DIR__) . '/' . $logo;
            if (file_exists($logoPath)) @unlink($logoPath);
        }
        $stmt = $db->prepare("DELETE FROM orgaos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    } catch (PDOException $e) {
        // Erro silencioso - redireciona de qualquer forma
    }
}

header('Location: index.php?success=deleted');
exit;
