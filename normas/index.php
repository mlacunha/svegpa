<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Normas";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT n.*, p.nome as programa_nome FROM normas n LEFT JOIN programas p ON n.id_programa = p.id ORDER BY n.criado_em DESC");
$normas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-balance-scale mr-2"></i>Normas
        </h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Nova Norma
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Norma criada com sucesso!' : ($_GET['success'] == 'updated' ? 'Norma atualizada com sucesso!' : 'Norma excluída com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar norma ou programa..." class="form-control max-w-md">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome da Norma</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Programa</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ementa</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="normas-table">
                <?php foreach ($normas as $n): ?>
                <tr class="border-b hover:bg-gray-50 norma-row" data-search="<?php echo strtolower(htmlspecialchars($n['nome_norma'] . ' ' . ($n['programa_nome'] ?? '') . ' ' . ($n['ementa'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($n['nome_norma']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($n['programa_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($n['ementa'] ?? ''); ?>"><?php echo htmlspecialchars(mb_substr($n['ementa'] ?? '-', 0, 80)) . (mb_strlen($n['ementa'] ?? '') > 80 ? '...' : ''); ?></td>
                    <td class="py-3 px-4">
                        <?php if (!empty($n['url_publicacao'])): ?>
                        <a href="<?php echo htmlspecialchars($n['url_publicacao']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 mr-3" title="Abrir publicação"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <a href="edit.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta norma?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function() {
    var t = this.value.toLowerCase();
    document.querySelectorAll('.norma-row').forEach(function(row) {
        row.style.display = (row.getAttribute('data-search') || '').indexOf(t) >= 0 ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
