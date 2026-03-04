<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Editar Tipo de Órgão";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM orgaos_tipos WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$tipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tipo) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_val = sanitizeInput($_POST['tipo'] ?? '');
    
    if (empty($tipo_val)) {
        $error = "O campo tipo é obrigatório.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE orgaos_tipos SET tipo = :tipo WHERE id = :id");
            $stmt->bindParam(':tipo', $tipo_val);
            $stmt->bindParam(':id', $id);
            
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
            <i class="fas fa-edit mr-2"></i>Editar Tipo de Órgão
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
            <label for="tipo">Tipo *</label>
            <input type="text" id="tipo" name="tipo" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['tipo'] ?? $tipo['tipo']); ?>" required>
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
