<?php
/**
 * Emergência: redefine senha via CLI quando sem acesso admin.
 * Uso: php run_force_reset.php LOGIN NOVA_SENHA
 * Exemplo: php run_force_reset.php admin 123456
 * EXCLUA este arquivo após usar (segurança).
 */
if (php_sapi_name() !== 'cli') {
    die("Execute apenas via linha de comando: php run_force_reset.php LOGIN SENHA\n");
}
$login = $argv[1] ?? '';
$senha = $argv[2] ?? '';
if (strlen($login) < 2 || strlen($senha) < 6) {
    die("Uso: php run_force_reset.php LOGIN SENHA (senha min 6 chars)\nEx: php run_force_reset.php admin 123456\n");
}
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
if (!$db) die("Erro: conexão com banco falhou.\n");
$stmt = $db->prepare("UPDATE sec_users SET pswd = ?, pswd_reset_code = NULL, pswd_reset_expires = NULL WHERE LOWER(TRIM(login)) = LOWER(?)");
$stmt->execute([md5(trim($senha)), $login]);
if ($stmt->rowCount() > 0) {
    echo "OK: Senha do usuário '$login' redefinida. Faça login.\n";
} else {
    echo "ERRO: Usuário '$login' não encontrado.\n";
}
