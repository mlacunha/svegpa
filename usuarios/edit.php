<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Editar Usuário";
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$login = isset($_GET['login']) ? sanitizeInput($_GET['login']) : null;
if (!$login) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM sec_users WHERE login = ?");
$stmt->execute([$login]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: index.php');
    exit;
}

$orgaos = $db->query("SELECT id, nome, sigla FROM orgaos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$cargos = $db->query("SELECT id, orgao, sigla, nome FROM cargos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$unidades = $db->query("SELECT id, orgao, nome FROM unidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$formacoes = $db->query("SELECT id, nome FROM formacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$val = function($k, $d = '') use ($usuario) {
    return isset($_POST[$k]) ? $_POST[$k] : ($usuario[$k] ?? $d);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim(sanitizeInput($_POST['name'] ?? ''));
    $email = trim(sanitizeInput($_POST['email'] ?? ''));
    $active = !empty($_POST['active']) ? 'Y' : '';
    $priv_admin = !empty($_POST['priv_admin']) ? 'Y' : '';
    $orgao = !empty($_POST['orgao']) ? sanitizeInput($_POST['orgao']) : null;
    $role = !empty($_POST['role']) ? sanitizeInput($_POST['role']) : null;
    $unidade = !empty($_POST['unidade']) ? sanitizeInput($_POST['unidade']) : null;
    $matricula = trim(sanitizeInput($_POST['matricula'] ?? ''));
    $phone = trim(sanitizeInput($_POST['phone'] ?? ''));
    $formacao = !empty($_POST['formacao']) ? (int)$_POST['formacao'] : null;
    $nova_senha = trim($_POST['nova_senha'] ?? '');
    
    try {
        $updates = "name=:name, email=:email, active=:active, priv_admin=:priv_admin, orgao=:orgao, role=:role, unidade=:unidade, matricula=:matricula, phone=:phone, formacao=:formacao";
        $binds = [':name' => $name ?: null, ':email' => $email ?: null, ':active' => $active, ':priv_admin' => $priv_admin, ':orgao' => $orgao, ':role' => $role, ':unidade' => $unidade, ':matricula' => $matricula ?: null, ':phone' => $phone ?: null, ':formacao' => $formacao, ':login' => $login];
        if ($nova_senha !== '') {
            if (strlen($nova_senha) < 6) {
                $error = "Nova senha deve ter no mínimo 6 caracteres.";
            } else {
                $updates .= ", pswd=:pswd, pswd_reset_code=NULL, pswd_reset_expires=NULL";
                $binds[':pswd'] = md5($nova_senha);
            }
        }
        if (!isset($error)) {
        $stmt = $db->prepare("UPDATE sec_users SET $updates WHERE login=:login");
        foreach ($binds as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        header('Location: index.php?success=updated' . ($nova_senha !== '' ? '&senha=1' : ''));
        exit;
        }
    } catch (PDOException $e) {
        $error = "Erro ao atualizar: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-edit mr-2"></i>Editar Usuário
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
    
    <form method="POST" class="space-y-6">
        <div class="form-group">
            <label>Login</label>
            <input type="text" class="form-control bg-gray-100" value="<?php echo htmlspecialchars($login); ?>" readonly>
        </div>
        
        <div class="form-group mb-4 p-4 bg-amber-50 border border-amber-200 rounded">
            <label for="nova_senha">Redefinir senha (admin)</label>
            <input type="password" id="nova_senha" name="nova_senha" class="form-control max-w-xs" minlength="6" autocomplete="new-password" placeholder="Deixe em branco para manter a atual">
            <p class="text-sm text-gray-600 mt-1">Preencha para forçar nova senha (6+ caracteres). Usuário poderá usar "Esqueci a senha" depois.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('name')); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('email')); ?>">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label for="orgao">Órgão</label>
                <select id="orgao" name="orgao" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($orgaos as $o): ?>
                    <option value="<?php echo htmlspecialchars($o['id']); ?>" <?php echo $val('orgao') === $o['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($o['nome']); ?><?php if (!empty($o['sigla'])): ?> (<?php echo htmlspecialchars($o['sigla']); ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="role">Cargo</label>
                <select id="role" name="role" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($cargos as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['id']); ?>" data-orgao="<?php echo htmlspecialchars($c['orgao']); ?>" <?php echo $val('role') === $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nome']); ?><?php if (!empty($c['sigla'])): ?> (<?php echo htmlspecialchars($c['sigla']); ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="unidade">Unidade</label>
                <select id="unidade" name="unidade" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($unidades as $un): ?>
                    <option value="<?php echo htmlspecialchars($un['id']); ?>" data-orgao="<?php echo htmlspecialchars($un['orgao']); ?>" <?php echo $val('unidade') === $un['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($un['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label for="matricula">Matrícula</label>
                <input type="text" id="matricula" name="matricula" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($val('matricula')); ?>">
            </div>
            <div class="form-group">
                <label for="phone">Telefone</label>
                <input type="text" id="phone" name="phone" class="form-control" maxlength="64" value="<?php echo htmlspecialchars($val('phone')); ?>">
            </div>
            <div class="form-group">
                <label for="formacao">Formação</label>
                <select id="formacao" name="formacao" class="form-control">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($formacoes as $f): ?>
                    <option value="<?php echo (int)$f['id']; ?>" <?php echo $val('formacao') == $f['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($f['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="flex gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="active" value="Y" <?php echo ($val('active') === 'Y') ? 'checked' : ''; ?>>
                <span>Ativo</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="priv_admin" value="Y" <?php echo ($val('priv_admin') === 'Y') ? 'checked' : ''; ?>>
                <span>Privilégio admin</span>
            </label>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Atualizar
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
                    opt.disabled = false;
                } else {
                    var show = !orgId || opt.dataset.orgao === orgId || opt.selected;
                    opt.style.display = show ? '' : 'none';
                    opt.disabled = false;
                }
            });
        });
    }
    
    orgao.addEventListener('change', filterSelects);
    filterSelects();
})();
</script>

<?php include '../includes/footer.php'; ?>
