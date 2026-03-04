<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$base = getBasePath();

// Já logado? Vai para o dashboard
if (getLoggedUser()) {
    header('Location: ' . $base . 'dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($login) || empty($senha)) {
        $error = 'Login e senha são obrigatórios.';
    } else {
        $user = validateLogin($login, $senha);
        if ($user) {
            $_SESSION['user'] = [
                'login' => $user['login'],
                'name' => $user['name'] ?? $user['login'],
                'priv_admin' => ($user['priv_admin'] ?? '') === 'Y',
                'orgao_nome' => $user['orgao_nome'] ?? null,
            ];
            $redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
            $dest = $base . 'dashboard.php';
            if ($redirect && strpos($redirect, 'login') === false && preg_match('#^/[^/:]+#', $redirect)) {
                $dest = $redirect;
            }
            header('Location: ' . $dest);
            exit;
        }
        $error = 'Login ou senha incorretos. Verifique se a conta está ativa.';
    }
}

$msg = $_GET['msg'] ?? '';
$msgText = '';
if ($msg === 'activated') {
    $msgText = 'Conta ativada! Faça login com a senha enviada por e-mail.';
} elseif ($msg === 'logout') {
    $msgText = 'Você saiu do sistema.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SVEG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>css/style.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1e40af', secondary: '#0f172a' } } } };
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-block bg-primary p-4 rounded-lg mb-3">
                    <i class="fas fa-leaf text-white text-3xl"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-800">SVEG</h1>
                <p class="text-sm text-gray-500">Sistema de Vigilância Epidemiológica</p>
            </div>
            
            <?php if ($msgText): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($msgText); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div class="form-group">
                    <label for="login">Login</label>
                    <input type="text" id="login" name="login" class="form-control w-full" required autofocus value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control w-full" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-primary w-full py-2">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar
                </button>
            </form>
        </div>
        <p class="text-center text-sm text-gray-500 mt-4">Acesso restrito a usuários autorizados</p>
    </div>
</body>
</html>
