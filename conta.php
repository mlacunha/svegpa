<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Minha Conta";
include 'includes/header.php';

$user = getLoggedUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Atualizar foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'foto') {
    if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['foto']['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            $error = 'Formato inválido. Use JPG, PNG, GIF ou WebP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) { // 2MB
            $error = 'Imagem muito grande. Máximo 2 MB.';
        } else {
            $blob = file_get_contents($_FILES['foto']['tmp_name']);
            $stmt = $db->prepare("UPDATE sec_users SET picture = ? WHERE login = ?");
            $stmt->execute([$blob, $user['login']]);
            $success = 'Foto atualizada com sucesso.';
        }
    } else {
        $error = 'Selecione uma imagem.';
    }
}

// Atualizar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_conf = $_POST['senha_confirma'] ?? '';
    
    if (empty($senha_atual) || empty($senha_nova) || empty($senha_conf)) {
        $error = 'Preencha todos os campos de senha.';
    } elseif ($senha_nova !== $senha_conf) {
        $error = 'A nova senha e a confirmação não conferem.';
    } elseif (strlen($senha_nova) < 4) {
        $error = 'A nova senha deve ter no mínimo 4 caracteres.';
    } else {
        $stmt = $db->prepare("SELECT pswd FROM sec_users WHERE login = ?");
        $stmt->execute([$user['login']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['pswd'] !== md5($senha_atual)) {
            $error = 'Senha atual incorreta.';
        } else {
            $stmt = $db->prepare("UPDATE sec_users SET pswd = ? WHERE login = ?");
            $stmt->execute([md5($senha_nova), $user['login']]);
            $success = 'Senha alterada com sucesso.';
        }
    }
}

// Carregar foto atual
$fotoSrc = getUserPhotoDataUrl($user['login']);
?>

<div class="card max-w-2xl">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-user-cog mr-2"></i>Minha Conta
    </h2>
    
    <?php if ($error): ?>
    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Foto -->
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Foto</h3>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                    <?php if ($fotoSrc): ?>
                    <img src="<?php echo htmlspecialchars($fotoSrc); ?>" alt="Foto" class="w-full h-full object-cover">
                    <?php else: ?>
                    <i class="fas fa-user text-4xl text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="foto">
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control mb-2" required>
                        <button type="submit" class="btn-primary text-sm py-1">
                            <i class="fas fa-upload mr-1"></i>Enviar foto
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-xs text-gray-500">JPG, PNG, GIF ou WebP. Máximo 2 MB.</p>
        </div>
        
        <!-- Senha -->
        <div id="senha">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Alterar senha</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="senha">
                <div class="form-group">
                    <label for="senha_atual">Senha atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="senha_nova">Nova senha</label>
                    <input type="password" id="senha_nova" name="senha_nova" class="form-control" minlength="4" required>
                </div>
                <div class="form-group">
                    <label for="senha_confirma">Confirmar nova senha</label>
                    <input type="password" id="senha_confirma" name="senha_confirma" class="form-control" minlength="4" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-key mr-2"></i>Alterar senha
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
