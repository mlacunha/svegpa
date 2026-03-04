<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Hospedeiros";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Busca todos os hospedeiros com nome do programa
$stmt = $db->query("
    SELECT h.*, p.nome as programa_nome, p.codigo as programa_codigo
    FROM hospedeiros h
    LEFT JOIN programas p ON h.id_programa = p.id
    ORDER BY h.nomes_comuns ASC
");
$hospedeiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Hospedeiros</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Hospedeiro
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Hospedeiro criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Hospedeiro atualizado com sucesso!' : 'Hospedeiro excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar hospedeiro..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Programa</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nomes Comuns</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome Científico</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="hospedeiros-table">
                <?php foreach ($hospedeiros as $hospedeiro): ?>
                <tr class="border-b hover:bg-gray-50 hospedeiro-row" data-search="<?php echo strtolower(htmlspecialchars($hospedeiro['nomes_comuns'] . ' ' . $hospedeiro['nome_cientifico'] . ' ' . ($hospedeiro['programa_nome'] ?? ''))); ?>">
                    <td class="py-3 px-4">
                        <span class="font-medium"><?php echo htmlspecialchars($hospedeiro['programa_nome'] ?? '-'); ?></span>
                        <?php if (!empty($hospedeiro['programa_codigo'])): ?>
                        <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($hospedeiro['programa_codigo']); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($hospedeiro['nomes_comuns']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($hospedeiro['nome_cientifico']); ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?id=<?php echo htmlspecialchars($hospedeiro['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($hospedeiro['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este hospedeiro?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.hospedeiro-row');
    
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search');
        if (searchText && searchText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
