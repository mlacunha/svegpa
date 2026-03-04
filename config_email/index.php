<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/email_helper.php';
$page_title = "Configuração de E-mail";
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar_envio'])) {
    $testEmail = trim(sanitizeInput($_POST['test_email'] ?? ''));
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $testResult = ['ok' => false, 'msg' => 'Informe um e-mail válido para teste.'];
    } else {
        $err = '';
        $ok = sendEmailSmtp($testEmail, 'Teste SVEG - Configuração de E-mail', '<p>Se você recebeu este e-mail, a configuração SMTP está funcionando.</p>', '', $err);
        $testResult = ['ok' => $ok, 'msg' => $ok ? 'E-mail de teste enviado com sucesso!' : $err];
    }
}

$config = null;
try {
    $stmt = $db->query("SELECT * FROM config_email WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $table_missing = (strpos($e->getMessage(), 'config_email') !== false || strpos($e->getMessage(), "doesn't exist") !== false);
}

if (!$config) {
    $config = [
        'smtp_host' => 'smtp.exemplo.com',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'from_email' => '',
        'from_name' => 'SVEG',
    ];
}

$val = function($k, $d = '') use ($config) {
    return isset($_POST[$k]) ? $_POST[$k] : ($config[$k] ?? $d);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['testar_envio'])) {
    $smtp_host = trim(sanitizeInput($_POST['smtp_host'] ?? ''));
    $smtp_port = isset($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
    $smtp_user = trim(sanitizeInput($_POST['smtp_user'] ?? ''));
    $smtp_pass_new = $_POST['smtp_pass'] ?? '';
    $smtp_secure = in_array($_POST['smtp_secure'] ?? '', ['ssl', 'tls', 'none']) ? $_POST['smtp_secure'] : 'tls';
    $from_email = trim(sanitizeInput($_POST['from_email'] ?? ''));
    $from_name = trim(sanitizeInput($_POST['from_name'] ?? ''));
    
    if (empty($smtp_host) || empty($smtp_user) || empty($from_email) || empty($from_name)) {
        $error = "Host SMTP, usuário SMTP, e-mail de origem e nome de origem são obrigatórios.";
    } elseif ($smtp_port < 1 || $smtp_port > 65535) {
        $error = "Porta SMTP inválida.";
    } else {
        try {
            if ($config) {
                if (!empty($smtp_pass_new)) {
                    $stmt = $db->prepare("UPDATE config_email SET smtp_host=:smtp_host, smtp_port=:smtp_port, smtp_user=:smtp_user, smtp_pass=:smtp_pass, smtp_secure=:smtp_secure, from_email=:from_email, from_name=:from_name WHERE id=1");
                    $stmt->bindValue(':smtp_pass', $smtp_pass_new);
                } else {
                    $stmt = $db->prepare("UPDATE config_email SET smtp_host=:smtp_host, smtp_port=:smtp_port, smtp_user=:smtp_user, smtp_secure=:smtp_secure, from_email=:from_email, from_name=:from_name WHERE id=1");
                }
            } else {
                $stmt = $db->prepare("INSERT INTO config_email (id, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, from_email, from_name) VALUES (1, :smtp_host, :smtp_port, :smtp_user, :smtp_pass, :smtp_secure, :from_email, :from_name)");
                $stmt->bindValue(':smtp_pass', $smtp_pass_new ?: '');
            }
            $stmt->bindValue(':smtp_host', $smtp_host);
            $stmt->bindValue(':smtp_port', $smtp_port);
            $stmt->bindValue(':smtp_user', $smtp_user);
            $stmt->bindValue(':smtp_secure', $smtp_secure);
            $stmt->bindValue(':from_email', $from_email);
            $stmt->bindValue(':from_name', $from_name);
            $stmt->execute();
            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'config_email') !== false) {
                $error = "Tabela config_email não existe. Execute o script sql/config_email.sql no banco de dados.";
            } else {
                $error = "Erro ao salvar: " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($error)) {
    $config = array_merge($config ?: [], [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
        'smtp_user' => $_POST['smtp_user'] ?? '',
        'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? '',
    ]);
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-envelope mr-2"></i>Configuração de E-mail
        </h2>
        <a href="<?php echo $base_path ?? ''; ?>dashboard.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if (isset($table_missing) && $table_missing): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i>A tabela <code>config_email</code> não existe. Execute o script <code>sql/config_email.sql</code> no banco de dados.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Configuração de e-mail salva com sucesso!
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($testResult): ?>
    <div class="alert alert-<?php echo $testResult['ok'] ? 'success' : 'error'; ?> mb-4">
        <i class="fas fa-<?php echo $testResult['ok'] ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($testResult['msg']); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Servidor SMTP</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="smtp_host">Host SMTP *</label>
                <input type="text" id="smtp_host" name="smtp_host" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('smtp_host')); ?>" required placeholder="Ex: smtp.gmail.com">
            </div>
            <div class="form-group">
                <label for="smtp_port">Porta *</label>
                <input type="number" id="smtp_port" name="smtp_port" class="form-control" min="1" max="65535" value="<?php echo htmlspecialchars($val('smtp_port', 587)); ?>" required>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="smtp_user">Usuário SMTP *</label>
                <input type="text" id="smtp_user" name="smtp_user" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('smtp_user')); ?>" required placeholder="E-mail ou usuário de autenticação">
            </div>
            <div class="form-group">
                <label for="smtp_pass">Senha SMTP</label>
                <div class="relative">
                    <input type="password" id="smtp_pass" name="smtp_pass" class="form-control pr-10" placeholder="<?php echo $config && $config['smtp_pass'] ? 'Deixe em branco para manter a atual' : 'Digite a senha'; ?>">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" onclick="var inp=document.getElementById('smtp_pass');var btn=this;inp.type=inp.type==='password'?'text':'password';btn.querySelector('i').className=inp.type==='password'?'fas fa-eye':'fas fa-eye-slash';" title="Mostrar/ocultar senha">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="smtp_secure">Criptografia</label>
            <select id="smtp_secure" name="smtp_secure" class="form-control">
                <option value="tls" <?php echo $val('smtp_secure') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo $val('smtp_secure') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                <option value="none" <?php echo $val('smtp_secure') === 'none' ? 'selected' : ''; ?>>Nenhuma</option>
            </select>
        </div>
        
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mt-8">Remetente padrão</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="from_email">E-mail de origem *</label>
                <input type="email" id="from_email" name="from_email" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('from_email')); ?>" required>
            </div>
            <div class="form-group">
                <label for="from_name">Nome de origem *</label>
                <input type="text" id="from_name" name="from_name" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('from_name')); ?>" required placeholder="Ex: SVEG - Sistema">
            </div>
        </div>
        
        <div class="flex justify-end space-x-3 pt-4">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
            <a href="<?php echo $base_path ?? ''; ?>dashboard.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
    
    <hr class="my-8 border-gray-200">
    
    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4"><i class="fas fa-paper-plane mr-2"></i>Testar envio</h3>
    <p class="text-gray-600 text-sm mb-4">Salve a configuração acima e teste o envio de e-mail antes de criar usuários.</p>
    <form method="POST" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="testar_envio" value="1">
        <div class="form-group mb-0">
            <label for="test_email">E-mail para teste</label>
            <input type="email" id="test_email" name="test_email" class="form-control" placeholder="seu@email.com" required value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn-primary">
            <i class="fas fa-paper-plane mr-2"></i>Enviar teste
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
