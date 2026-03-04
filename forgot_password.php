<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email_helper.php';

$base = getBasePath();

if (getLoggedUser()) {
    header('Location: ' . $base . 'dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } else {
        $db = (new Database())->getConnection();
        if (!$db) {
            $error = 'Erro de conexão com o banco.';
        } else {
            $stmt = $db->prepare("SELECT login, name, email FROM sec_users WHERE email = ? AND (active = 'Y' OR active = '1')");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $success = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
            } else {
                $code = bin2hex(random_bytes(24));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                $stmt = $db->prepare("UPDATE sec_users SET pswd_reset_code = ?, pswd_reset_expires = ? WHERE login = ?");
                $stmt->execute([$code, $expires, $user['login']]);

                $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $resetUrl .= getBasePath() . 'reset_senha.php?code=' . urlencode($code);

                $assunto = 'Redefinição de senha - SVEG';
                $corpo = '<h2>Redefinição de senha</h2>';
                $corpo .= '<p>Olá, ' . htmlspecialchars($user['name'] ?? $user['login']) . '.</p>';
                $corpo .= '<p>Foi solicitada a redefinição da senha da sua conta. Clique no link abaixo (válido por 1 hora):</p>';
                $corpo .= '<p><a href="' . htmlspecialchars($resetUrl) . '">' . htmlspecialchars($resetUrl) . '</a></p>';
                $corpo .= '<p>Se você não solicitou esta alteração, ignore este e-mail.</p>';

                $emailErr = '';
                $enviou = sendEmailSmtp($user['email'], $assunto, $corpo, '', $emailErr);

                if ($enviou) {
                    $success = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
                } else {
                    $error = $emailErr ?: 'Falha ao enviar e-mail. Verifique a configuração em Config. E-mail.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a senha - SVEG</title>
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
                    <i class="fas fa-key text-white text-3xl"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-800">Esqueci a senha</h1>
                <p class="text-sm text-gray-500">Informe seu e-mail para receber o link de redefinição</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
            <a href="<?php echo htmlspecialchars($base); ?>login.php" class="btn-primary w-full py-2 block text-center">
                <i class="fas fa-arrow-left mr-2"></i>Voltar ao login
            </a>
            <?php else: ?>
            <?php if ($error): ?>
            <div class="alert alert-error mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control w-full" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="seu@email.com" autocomplete="email">
                </div>
                <button type="submit" class="btn-primary w-full py-2">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar link
                </button>
            </form>
            <p class="text-center mt-4">
                <a href="<?php echo htmlspecialchars($base); ?>login.php" class="text-sm text-primary hover:underline">Voltar ao login</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
