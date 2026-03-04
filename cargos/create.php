<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Novo Cargo";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT id, nome, sigla FROM orgaos ORDER BY nome ASC");
$orgaos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orgao = sanitizeInput($_POST['orgao'] ?? '');
    $sigla = sanitizeInput($_POST['sigla'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    
    if (empty($orgao)) {
        $error = "Selecione um órgão.";
    } elseif (empty($sigla)) {
        $error = "O campo sigla é obrigatório.";
    } elseif (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO cargos (id, orgao, sigla, nome) VALUES (UUID(), :orgao, :sigla, :nome)");
            $stmt->bindParam(':orgao', $orgao);
            $stmt->bindParam(':sigla', $sigla);
            $stmt->bindParam(':nome', $nome);
            
            if ($stmt->execute()) {
                header('Location: index.php?success=created');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Novo Cargo
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
            <label for="orgao">Órgão *</label>
            <select id="orgao" name="orgao" class="form-control" required>
                <option value="">-- Selecione o órgão --</option>
                <?php foreach ($orgaos as $org): ?>
                <option value="<?php echo htmlspecialchars($org['id']); ?>" <?php echo (isset($_POST['orgao']) && $_POST['orgao'] === $org['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($org['nome']); ?>
                    <?php if (!empty($org['sigla'])): ?> (<?php echo htmlspecialchars($org['sigla']); ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="sigla">Sigla *</label>
                <input type="text" id="sigla" name="sigla" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($_POST['sigla'] ?? ''); ?>" required placeholder="Ex: FEA, AFFA">
            </div>
            <div class="form-group">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>
        </div>
        
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
