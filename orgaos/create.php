<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Novo Órgão";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }

$database = new Database();
$db = $database->getConnection();

// Buscar tipos de órgão para o select
$stmt = $db->query("SELECT id, tipo FROM orgaos_tipos ORDER BY tipo ASC");
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sigla = sanitizeInput($_POST['sigla'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $logo = !empty(trim($_POST['logo'] ?? '')) ? trim($_POST['logo']) : '';
    if ($logo === '-') $logo = '';
    $UF_sede = sanitizeInput($_POST['UF_sede'] ?? '');
    
    if (empty($sigla) || empty($nome)) {
        $error = "Sigla e nome são obrigatórios.";
    } elseif (empty($tipo)) {
        $error = "Selecione um tipo de órgão.";
    } elseif (empty($UF_sede)) {
        $error = "UF da sede é obrigatória.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO orgaos (id, sigla, nome, tipo, logo, UF_sede) VALUES (UUID(), :sigla, :nome, :tipo, :logo, :UF_sede)");
            
            $stmt->bindParam(':sigla', $sigla);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindValue(':logo', $logo ?: '');
            $stmt->bindParam(':UF_sede', $UF_sede);
            
            if ($stmt->execute()) {
                header('Location: index.php?success=created');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Novo Órgão
        </h2>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="sigla">Sigla *</label>
                <input type="text" id="sigla" name="sigla" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($_POST['sigla'] ?? ''); ?>" required placeholder="Ex: MAPA">
            </div>
            
            <div class="form-group">
                <label for="UF_sede">UF Sede *</label>
                <input type="text" id="UF_sede" name="UF_sede" class="form-control" maxlength="10" value="<?php echo htmlspecialchars($_POST['UF_sede'] ?? ''); ?>" required placeholder="Ex: PA">
            </div>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="tipo">Tipo *</label>
            <select id="tipo" name="tipo" class="form-control" required>
                <option value="">Selecione o tipo</option>
                <?php foreach ($tipos as $t): ?>
                <option value="<?php echo htmlspecialchars($t['id']); ?>" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === $t['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t['tipo']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php $logo_path = $_POST['logo'] ?? ''; include '../includes/orgao_logo_upload.php'; ?>
        
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
