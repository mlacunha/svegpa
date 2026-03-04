<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    try {
        $stmt = $db->prepare("DELETE FROM municipios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        // Erro silencioso - redireciona de qualquer forma
    }
}

header('Location: index.php?success=deleted');
exit;
