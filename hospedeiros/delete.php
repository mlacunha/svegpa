<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if ($id) {
    try {
        $stmt = $db->prepare("DELETE FROM hospedeiros WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    } catch (PDOException $e) {
        // Erro silencioso - redireciona de qualquer forma
    }
}

header('Location: index.php?success=deleted');
exit;
