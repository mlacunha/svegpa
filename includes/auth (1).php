<?php
/**
 * Autenticação e sessão
 */
if (!function_exists('getBasePath')) {
    require_once __DIR__ . '/functions.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna o usuário logado ou null
 */
function getLoggedUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Retorna se o usuário logado é admin
 */
function isAdmin() {
    $u = getLoggedUser();
    return $u && !empty($u['priv_admin']);
}

/**
 * Exige que o usuário seja admin. Redireciona para dashboard se não for.
 */
function requireAdmin() {
    if (!isAdmin()) {
        $base = getBasePath();
        header('Location: ' . $base . 'dashboard.php?msg=acesso_negado');
        exit;
    }
}

/**
 * Exige que o usuário esteja logado. Redireciona para login se não estiver.
 */
function requireLogin() {
    if (getLoggedUser()) {
        return;
    }
    $base = getBasePath();
    $redirect = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : '';
    $url = $base . 'login.php' . ($redirect ? '?redirect=' . $redirect : '');
    header('Location: ' . $url);
    exit;
}

/**
 * Valida login e senha contra sec_users. Retorna dados do usuário ou null.
 */
function validateLogin($login, $password) {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT u.login, u.name, u.priv_admin, u.orgao, o.nome as orgao_nome FROM sec_users u LEFT JOIN orgaos o ON u.orgao = o.id WHERE u.login = ? AND u.pswd = ? AND u.active = 'Y'");
    $stmt->execute([$login, md5($password)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
