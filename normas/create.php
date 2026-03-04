<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Nova Norma";

$database = new Database();
$db = $database->getConnection();

$programas = $db->query("SELECT id, nome FROM programas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_programa = sanitizeInput($_POST['id_programa'] ?? '');
    $nome_norma = sanitizeInput($_POST['nome_norma'] ?? '');
    $ementa = sanitizeInput($_POST['ementa'] ?? '');
    $url_publicacao = !empty($_POST['url_publicacao']) ? sanitizeInput($_POST['url_publicacao']) : null;
    
    if (empty($id_programa) || empty($nome_norma) || empty($ementa)) {
        $error = "Programa, Nome da Norma e Ementa são obrigatórios.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO normas (id, id_programa, nome_norma, ementa, url_publicacao) VALUES (UUID(), :id_programa, :nome_norma, :ementa, :url_publicacao)");
            $stmt->bindParam(':id_programa', $id_programa);
            $stmt->bindParam(':nome_norma', $nome_norma);
            $stmt->bindParam(':ementa', $ementa);
            $stmt->bindValue(':url_publicacao', $url_publicacao);
            if ($stmt->execute()) {
                $return = $_POST['return_to'] ?? $_GET['return_to'] ?? null;
                if ($return && preg_match('#^\.\./programas/edit\.php\?id=[a-zA-Z0-9._-]+$#', $return)) {
                    header('Location: ' . $return . '&success=norma_created');
                } else {
                    header('Location: index.php?success=created');
                }
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
            <i class="fas fa-plus-circle mr-2"></i>Nova Norma
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
    
    <?php $return_to = isset($_GET['id_programa']) ? '../programas/edit.php?id=' . urlencode($_GET['id_programa']) : ''; ?>
    <form method="POST" class="space-y-6">
        <?php if ($return_to): ?><input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to); ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="id_programa">Programa *</label>
                <select id="id_programa" name="id_programa" class="form-control" required>
                    <option value="">-- Selecione o programa --</option>
                    <?php $sel = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null; ?>
                    <?php foreach ($programas as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo ($sel && $sel === $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nome_norma">Nome da Norma *</label>
                <input type="text" id="nome_norma" name="nome_norma" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['nome_norma'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="ementa">Ementa *</label>
            <textarea id="ementa" name="ementa" class="form-control" rows="4" required><?php echo htmlspecialchars($_POST['ementa'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="url_publicacao">URL da Publicação</label>
            <input type="url" id="url_publicacao" name="url_publicacao" class="form-control" maxlength="512" value="<?php echo htmlspecialchars($_POST['url_publicacao'] ?? ''); ?>" placeholder="https://...">
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
