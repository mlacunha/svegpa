<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Editar Município";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM municipios WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$mun = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mun) {
    header('Location: index.php');
    exit;
}

$stmt = $db->query("SELECT id, nome, sigla FROM estados ORDER BY nome ASC");
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $estado_id = isset($_POST['estado_id']) && $_POST['estado_id'] !== '' ? intval($_POST['estado_id']) : null;
    
    if (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } elseif ($estado_id === null) {
        $error = "Selecione um estado.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE municipios SET nome = :nome, estado_id = :estado_id WHERE id = :id");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':estado_id', $estado_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                header('Location: index.php?success=updated');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>Editar Município
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
            <label>Código IBGE</label>
            <input type="text" class="form-control bg-gray-100" value="<?php echo htmlspecialchars($mun['id']); ?>" readonly>
            <p class="text-sm text-gray-500 mt-1">O código IBGE não pode ser alterado.</p>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['nome'] ?? $mun['nome']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="estado_id">Estado *</label>
            <select id="estado_id" name="estado_id" class="form-control" required>
                <option value="">-- Selecione o estado --</option>
                <?php foreach ($estados as $est): ?>
                <option value="<?php echo htmlspecialchars($est['id']); ?>" <?php echo (isset($_POST['estado_id']) ? $_POST['estado_id'] : $mun['estado_id']) == $est['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($est['nome']); ?> (<?php echo htmlspecialchars($est['sigla']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
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

<?php include '../includes/footer.php'; ?>
