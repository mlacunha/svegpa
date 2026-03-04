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
        $stmt = $db->prepare("DELETE FROM unidades WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Erro silencioso
    }
}

header('Location: index.php?success=deleted');
exit;
