<?php
/**
 * Migration: adiciona colunas de reset de senha em sec_users.
 * Execute uma vez (navegador ou CLI) e depois remova ou ignore.
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

$db = (new Database())->getConnection();
if (!$db) {
    die("Erro: não foi possível conectar ao banco.\n");
}

$cols = $db->query("SHOW COLUMNS FROM sec_users LIKE 'pswd_reset%'")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('pswd_reset_code', $cols) && in_array('pswd_reset_expires', $cols)) {
    die("OK: Colunas pswd_reset_code e pswd_reset_expires já existem.\n");
}

try {
    if (!in_array('pswd_reset_code', $cols)) {
        $db->exec("ALTER TABLE sec_users ADD COLUMN pswd_reset_code VARCHAR(64) NULL");
        echo "Coluna pswd_reset_code adicionada.\n";
    }
    if (!in_array('pswd_reset_expires', $cols)) {
        $db->exec("ALTER TABLE sec_users ADD COLUMN pswd_reset_expires DATETIME NULL");
        echo "Coluna pswd_reset_expires adicionada.\n";
    }
    echo "Migration concluída. Esqueci a senha pronto.\n";
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage() . "\n");
}
