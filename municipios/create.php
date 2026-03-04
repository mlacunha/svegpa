<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Novo Município";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT id, nome, sigla FROM estados ORDER BY nome ASC");
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $estado_id = isset($_POST['estado_id']) && $_POST['estado_id'] !== '' ? intval($_POST['estado_id']) : null;
    
    if ($id === null) {
        $error = "O código IBGE é obrigatório.";
    } elseif (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } elseif ($estado_id === null) {
        $error = "Selecione um estado.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO municipios (id, nome, estado_id) VALUES (:id, :nome, :estado_id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':estado_id', $estado_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                header('Location: index.php?success=created');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Já existe um município com este código IBGE.";
            } else {
                $error = "Erro ao salvar: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Novo Município
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="id">Código IBGE *</label>
                <input type="number" id="id" name="id" class="form-control" min="1" value="<?php echo htmlspecialchars($_POST['id'] ?? ''); ?>" required placeholder="Ex: 1501402">
            </div>
            
            <div class="form-group">
                <label for="estado_id">Estado *</label>
                <select id="estado_id" name="estado_id" class="form-control" required>
                    <option value="">-- Selecione o estado --</option>
                    <?php foreach ($estados as $est): ?>
                    <option value="<?php echo htmlspecialchars($est['id']); ?>" <?php echo (isset($_POST['estado_id']) && $_POST['estado_id'] == $est['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($est['nome']); ?> (<?php echo htmlspecialchars($est['sigla']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
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
