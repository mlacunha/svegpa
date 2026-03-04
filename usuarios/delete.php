<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$login = isset($_GET['login']) ? sanitizeInput($_GET['login']) : null;

if ($login) {
    try {
        $stmt = $db->prepare("DELETE FROM sec_users WHERE login = ?");
        $stmt->execute([$login]);
    } catch (PDOException $e) {
        // Erro silencioso
    }
}

header('Location: index.php?success=deleted');
exit;
