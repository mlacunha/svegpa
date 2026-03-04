<?php
require_once '../config/database.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';

// Código deve ter 32 caracteres hexadecimais (gerado com bin2hex(random_bytes(16)))
if (empty($code) || !preg_match('/^[a-f0-9]{32}$/i', $code)) {
    header('Location: link_invalido.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Atualização atômica: só atualiza se o código existir e o usuário ainda estiver inativo.
// Zera o activation_code para que o link não funcione novamente.
$stmt = $db->prepare("UPDATE sec_users SET active = 'Y', activation_code = NULL WHERE activation_code = ? AND (active IS NULL OR active = '' OR active != 'Y')");
$stmt->execute([$code]);

if ($stmt->rowCount() === 0) {
    // Código inválido, já utilizado ou usuário já ativo - link não concede acesso à aplicação
    header('Location: link_invalido.php');
    exit;
}

header('Location: ../index.php?msg=activated');
