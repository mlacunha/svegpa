<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/email_helper.php';
$page_title = "Novo Usuário";
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$orgaos = $db->query("SELECT id, nome, sigla FROM orgaos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$cargos = $db->query("SELECT id, orgao, sigla, nome FROM cargos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$unidades = $db->query("SELECT id, orgao, nome FROM unidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$formacoes = $db->query("SELECT id, nome FROM formacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim(sanitizeInput($_POST['login'] ?? ''));
    $name = trim(sanitizeInput($_POST['name'] ?? ''));
    $email = trim(sanitizeInput($_POST['email'] ?? ''));
    $active = 'N';
    $priv_admin = !empty($_POST['priv_admin']) ? 'Y' : '';
    $orgao = !empty($_POST['orgao']) ? sanitizeInput($_POST['orgao']) : null;
    $role = !empty($_POST['role']) ? sanitizeInput($_POST['role']) : null;
    $unidade = !empty($_POST['unidade']) ? sanitizeInput($_POST['unidade']) : null;
    $matricula = trim(sanitizeInput($_POST['matricula'] ?? ''));
    $phone = trim(sanitizeInput($_POST['phone'] ?? ''));
    $formacao = !empty($_POST['formacao']) ? (int)$_POST['formacao'] : null;
    
    if (empty($login)) {
        $error = "Login é obrigatório.";
    } elseif (empty($email)) {
        $error = "E-mail é obrigatório para envio do link de ativação.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "E-mail inválido.";
    } else {
        $stmt_check = $db->prepare("SELECT login FROM sec_users WHERE login = ?");
        $stmt_check->execute([$login]);
        if ($stmt_check->fetch()) {
            $error = "Já existe um usuário com este login.";
        } else {
            try {
                $pswd_plain = bin2hex(random_bytes(8));
                $pswd_hash = md5($pswd_plain);
                $activation_code = bin2hex(random_bytes(16));
                
                $stmt = $db->prepare("INSERT INTO sec_users (login, pswd, name, email, active, activation_code, priv_admin, orgao, role, unidade, matricula, phone, formacao) VALUES (:login, :pswd, :name, :email, :active, :activation_code, :priv_admin, :orgao, :role, :unidade, :matricula, :phone, :formacao)");
                $stmt->bindValue(':login', $login);
                $stmt->bindValue(':pswd', $pswd_hash);
                $stmt->bindValue(':name', $name ?: null);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':active', $active);
                $stmt->bindValue(':activation_code', $activation_code);
                $stmt->bindValue(':priv_admin', $priv_admin);
                $stmt->bindValue(':orgao', $orgao);
                $stmt->bindValue(':role', $role);
                $stmt->bindValue(':unidade', $unidade);
                $stmt->bindValue(':matricula', $matricula ?: null);
                $stmt->bindValue(':phone', $phone ?: null);
                $stmt->bindValue(':formacao', $formacao);
                $stmt->execute();
                
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '/usuarios/create.php');
                $basePath = str_replace('/usuarios', '', $scriptPath);
                $ativarUrl = rtrim($baseUrl . $basePath, '/') . '/usuarios/ativar.php?code=' . urlencode($activation_code);
                
                $assunto = "Ativação de conta - SVEG";
                $corpo = "<h2>Bem-vindo ao SVEG</h2>";
                $corpo .= "<p>Uma conta foi criada para você. Para ativar, clique no link abaixo:</p>";
                $corpo .= "<p><a href=\"" . htmlspecialchars($ativarUrl) . "\">" . htmlspecialchars($ativarUrl) . "</a></p>";
                $corpo .= "<p><strong>Login:</strong> " . htmlspecialchars($login) . "</p>";
                $corpo .= "<p><strong>Senha temporária:</strong> " . htmlspecialchars($pswd_plain) . "</p>";
                $corpo .= "<p>Recomendamos que você altere sua senha após o primeiro acesso.</p>";
                
                $emailError = '';
                $enviou = sendEmailSmtp($email, $assunto, $corpo, '', $emailError);
                if (!$enviou) {
                    $db->prepare("DELETE FROM sec_users WHERE login = ?")->execute([$login]);
                    $error = $emailError ?: "E-mail de ativação não enviado. Verifique a configuração em Auxiliares → Config. E-mail.";
                } else {
                    header('Location: index.php?success=created');
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Erro ao salvar: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-plus mr-2"></i>Novo Usuário
        </h2>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <p class="text-gray-600 mb-4">O usuário receberá um e-mail com link de ativação e senha temporária. O acesso será liberado após confirmar o e-mail.</p>
    
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="login">Login *</label>
                <input type="text" id="login" name="login" class="form-control" maxlength="190" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Para envio do link de ativação">
            </div>
        </div>
        
        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label for="orgao">Órgão</label>
                <select id="orgao" name="orgao" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($orgaos as $o): ?>
                    <option value="<?php echo htmlspecialchars($o['id']); ?>" <?php echo (isset($_POST['orgao']) && $_POST['orgao'] === $o['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($o['nome']); ?><?php if (!empty($o['sigla'])): ?> (<?php echo htmlspecialchars($o['sigla']); ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="role">Cargo</label>
                <select id="role" name="role" class="form-control">
                    <option value="">-- Selecione o órgão primeiro --</option>
                    <?php foreach ($cargos as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['id']); ?>" data-orgao="<?php echo htmlspecialchars($c['orgao']); ?>" <?php echo (isset($_POST['role']) && $_POST['role'] === $c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nome']); ?><?php if (!empty($c['sigla'])): ?> (<?php echo htmlspecialchars($c['sigla']); ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="unidade">Unidade</label>
                <select id="unidade" name="unidade" class="form-control">
                    <option value="">-- Selecione o órgão primeiro --</option>
                    <?php foreach ($unidades as $un): ?>
                    <option value="<?php echo htmlspecialchars($un['id']); ?>" data-orgao="<?php echo htmlspecialchars($un['orgao']); ?>" <?php echo (isset($_POST['unidade']) && $_POST['unidade'] === $un['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($un['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label for="matricula">Matrícula</label>
                <input type="text" id="matricula" name="matricula" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($_POST['matricula'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="phone">Telefone</label>
                <input type="text" id="phone" name="phone" class="form-control" maxlength="64" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="formacao">Formação</label>
                <select id="formacao" name="formacao" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($formacoes as $f): ?>
                    <option value="<?php echo (int)$f['id']; ?>" <?php echo (isset($_POST['formacao']) && $_POST['formacao'] == $f['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($f['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="priv_admin" value="Y" <?php echo !empty($_POST['priv_admin']) ? 'checked' : ''; ?>>
                <span>Privilégio admin</span>
            </label>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Salvar e enviar e-mail
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<script>
(function() {
    var orgao = document.getElementById('orgao');
    var role = document.getElementById('role');
    var unidade = document.getElementById('unidade');
    
    function filterSelects() {
        var orgId = orgao.value || '';
        [role, unidade].forEach(function(sel) {
            Array.prototype.forEach.call(sel.options, function(opt) {
                if (opt.value === '') {
                    opt.style.display = '';
                    opt.disabled = !orgId;
                    opt.textContent = orgId ? '-- Selecione --' : '-- Selecione o órgão primeiro --';
                } else {
                    var show = !orgId || opt.dataset.orgao === orgId;
                    opt.style.display = show ? '' : 'none';
                    opt.disabled = false;
                    if (!show && opt.selected) opt.selected = false;
                }
            });
        });
    }
    
    orgao.addEventListener('change', filterSelects);
    filterSelects();
})();
</script>

<?php include '../includes/footer.php'; ?>
