<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Programa";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Buscar programa
$stmt = $db->prepare("SELECT * FROM programas WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$programa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$programa) {
    header('Location: index.php');
    exit;
}

// Normas vinculadas
$stmt = $db->prepare("SELECT * FROM normas WHERE id_programa = :id ORDER BY criado_em DESC");
$stmt->bindParam(':id', $id);
$stmt->execute();
$normas_programa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hospedeiros vinculados
$stmt = $db->prepare("SELECT * FROM hospedeiros WHERE id_programa = :id ORDER BY nomes_comuns ASC");
$stmt->bindParam(':id', $id);
$stmt->execute();
$hospedeiros_programa = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = sanitizeInput($_POST['codigo'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $nomes_comuns = sanitizeInput($_POST['nomes_comuns'] ?? '');
    $nome_cientifico = sanitizeInput($_POST['nome_cientifico'] ?? '');
    
    try {
        $stmt = $db->prepare("UPDATE programas SET codigo = :codigo, nome = :nome, nomes_comuns = :nomes_comuns, nome_cientifico = :nome_cientifico WHERE id = :id");
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':nomes_comuns', $nomes_comuns);
        $stmt->bindParam(':nome_cientifico', $nome_cientifico);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=updated');
            exit;
        }
    } catch(PDOException $e) {
        $error = "Erro ao atualizar: " . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>Editar Programa
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
    <?php if (isset($_GET['success']) && $_GET['success'] === 'norma_created'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Norma criada com sucesso!
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'hospedeiro_created'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Espécie hospedeira criada com sucesso!
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($programa['codigo'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($programa['nome']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="nomes_comuns">Nomes Comuns</label>
            <input type="text" id="nomes_comuns" name="nomes_comuns" class="form-control" value="<?php echo htmlspecialchars($programa['nomes_comuns'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="nome_cientifico">Nome Científico</label>
            <input type="text" id="nome_cientifico" name="nome_cientifico" class="form-control" value="<?php echo htmlspecialchars($programa['nome_cientifico'] ?? ''); ?>">
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

    <hr class="my-8 border-gray-200">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center justify-between">
                <span><i class="fas fa-balance-scale mr-2"></i>Normas vinculadas</span>
                <a href="../normas/create.php?id_programa=<?php echo htmlspecialchars($id); ?>" class="text-sm btn-primary py-1 px-2">
                    <i class="fas fa-plus mr-1"></i>Nova
                </a>
            </h3>
            <?php if (empty($normas_programa)): ?>
            <p class="text-gray-500 text-sm">Nenhuma norma vinculada.</p>
            <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($normas_programa as $n): ?>
                <li class="flex items-start justify-between gap-2 p-3 bg-gray-50 rounded-lg">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($n['nome_norma']); ?></p>
                        <p class="text-gray-600 text-xs mt-0.5"><?php echo htmlspecialchars(mb_substr($n['ementa'] ?? '', 0, 100)) . (mb_strlen($n['ementa'] ?? '') > 100 ? '...' : ''); ?></p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <?php if (!empty($n['url_publicacao'])): ?>
                        <a href="<?php echo htmlspecialchars($n['url_publicacao']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Abrir"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <a href="../normas/edit.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="text-blue-600 hover:text-blue-800" title="Editar"><i class="fas fa-edit"></i></a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center justify-between">
                <span><i class="fas fa-tree mr-2"></i>Espécies hospedeiras</span>
                <a href="../hospedeiros/create.php?id_programa=<?php echo htmlspecialchars($id); ?>" class="text-sm btn-primary py-1 px-2">
                    <i class="fas fa-plus mr-1"></i>Nova
                </a>
            </h3>
            <?php if (empty($hospedeiros_programa)): ?>
            <p class="text-gray-500 text-sm">Nenhuma espécie hospedeira vinculada.</p>
            <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($hospedeiros_programa as $h): ?>
                <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($h['nomes_comuns'] ?? '-'); ?></p>
                        <?php if (!empty($h['nome_cientifico'])): ?>
                        <p class="text-gray-600 text-xs italic"><?php echo htmlspecialchars($h['nome_cientifico']); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="../hospedeiros/edit.php?id=<?php echo htmlspecialchars($h['id']); ?>" class="text-blue-600 hover:text-blue-800 shrink-0" title="Editar"><i class="fas fa-edit"></i></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>