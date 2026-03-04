<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Novo Programa";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = sanitizeInput($_POST['codigo'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $nomes_comuns = sanitizeInput($_POST['nomes_comuns'] ?? '');
    $nome_cientifico = sanitizeInput($_POST['nome_cientifico'] ?? '');
    
    try {
        $stmt = $db->prepare("INSERT INTO programas (id, codigo, nome, nomes_comuns, nome_cientifico) VALUES (UUID(), :codigo, :nome, :nomes_comuns, :nome_cientifico)");
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':nomes_comuns', $nomes_comuns);
        $stmt->bindParam(':nome_cientifico', $nome_cientifico);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=created');
            exit;
        }
    } catch(PDOException $e) {
        $error = "Erro ao salvar: " . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Novo Programa
        </h2>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="nomes_comuns">Nomes Comuns</label>
            <input type="text" id="nomes_comuns" name="nomes_comuns" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="nome_cientifico">Nome Científico</label>
            <input type="text" id="nome_cientifico" name="nome_cientifico" class="form-control">
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