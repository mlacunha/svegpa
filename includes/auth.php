<?php
/**
 * Autenticação e sessão
 */
if (!function_exists('getBasePath')) {
    require_once __DIR__ . '/functions.php';
}

// Carrega config de deploy (cookie seguro, base path override)
$appConfig = dirname(__DIR__) . '/config/app.php';
if (file_exists($appConfig)) {
    require_once $appConfig;
}

if (session_status() === PHP_SESSION_NONE) {
    if (defined('SANVEG_FORCE_SECURE_COOKIE') && SANVEG_FORCE_SECURE_COOKIE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
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
 * Se $dbError (passado por referência) for true, indica falha de conexão.
 */
function validateLogin($login, $password, &$dbError = null) {
    $dbError = false;
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    if (!$db) {
        $dbError = true;
        return null;
    }

    $login = trim($login ?? '');
    $hash = md5(trim($password ?? ''));
    if ($login === '' || $hash === md5('')) return null;

    $stmt = $db->prepare("SELECT u.login, u.name, u.priv_admin, u.orgao, o.nome as orgao_nome FROM sec_users u LEFT JOIN orgaos o ON u.orgao = o.id WHERE LOWER(TRIM(u.login)) = LOWER(?) AND u.pswd = ? AND TRIM(COALESCE(u.active,'')) IN ('Y','1','S')");
    $stmt->execute([$login, $hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
