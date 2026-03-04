<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$base = getBasePath();

if (getLoggedUser()) {
    header('Location: ' . $base . 'dashboard.php');
    exit;
}

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$error = '';

if (empty($code) || strlen($code) < 32) {
    header('Location: ' . $base . 'login.php?msg=link_invalido');
    exit;
}

$db = (new Database())->getConnection();
if (!$db) {
    $error = 'Erro de conexão com o banco.';
} else {
    $stmt = $db->prepare("SELECT login, pswd_reset_expires FROM sec_users WHERE pswd_reset_code = ?");
    $stmt->execute([$code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || strtotime($user['pswd_reset_expires']) < time()) {
        header('Location: ' . $base . 'login.php?msg=link_expirado');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $senha = $_POST['senha'] ?? '';
        $confirma = $_POST['confirma'] ?? '';

        $senha = trim($senha);
        $confirma = trim($confirma);
        if (strlen($senha) < 6) {
            $error = 'A senha deve ter no mínimo 6 caracteres.';
        } elseif ($senha !== $confirma) {
            $error = 'As senhas informadas não coincidem.';
        } else {
            $hash = md5($senha);
            $stmt = $db->prepare("UPDATE sec_users SET pswd = ?, pswd_reset_code = NULL, pswd_reset_expires = NULL WHERE pswd_reset_code = ?");
            $stmt->execute([$hash, $code]);

            if ($stmt->rowCount() > 0) {
                header('Location: ' . $base . 'login.php?msg=senha_alterada');
                exit;
            }
            $error = 'Erro ao alterar a senha. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha - SVEG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>css/style.css">
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#1e40af', secondary: '#0f172a' } } } };</script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-block bg-primary p-4 rounded-lg mb-3">
                    <i class="fas fa-lock text-white text-3xl"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-800">Nova senha</h1>
                <p class="text-sm text-gray-500">Defina uma nova senha para sua conta</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="form-group">
                    <label for="senha">Nova senha *</label>
                    <input type="password" id="senha" name="senha" class="form-control w-full" required
                           minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirma">Confirmar senha *</label>
                    <input type="password" id="confirma" name="confirma" class="form-control w-full" required
                           minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-primary w-full py-2">
                    <i class="fas fa-save mr-2"></i>Alterar senha
                </button>
            </form>
            <p class="text-center mt-4">
                <a href="<?php echo htmlspecialchars($base); ?>login.php" class="text-sm text-primary hover:underline">Voltar ao login</a>
            </p>
        </div>
    </div>
</body>
</html>
