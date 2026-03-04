<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Propriedades";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("
    SELECT p.*, pr.nome as proprietario_nome
    FROM propriedades p
    LEFT JOIN produtores pr ON p.id_proprietario = pr.id
    ORDER BY p.nome ASC
");
$propriedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Propriedades</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Nova Propriedade
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Propriedade criada com sucesso!' : ($_GET['success'] == 'updated' ? 'Propriedade atualizada com sucesso!' : 'Propriedade excluída com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar propriedade..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município / UF</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Proprietário</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">N. Cadastro</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="propriedades-table">
                <?php foreach ($propriedades as $prop): ?>
                <tr class="border-b hover:bg-gray-50 propriedade-row" data-search="<?php echo strtolower(htmlspecialchars($prop['nome'] . ' ' . ($prop['municipio'] ?? '') . ' ' . ($prop['UF'] ?? '') . ' ' . ($prop['proprietario_nome'] ?? '') . ' ' . ($prop['n_cadastro'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($prop['nome']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars(($prop['municipio'] ?? '-') . ' / ' . ($prop['UF'] ?? '-')); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($prop['proprietario_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($prop['n_cadastro'] ?? '-'); ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?id=<?php echo htmlspecialchars($prop['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($prop['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta propriedade? Produtos vinculados podem ser afetados.')">
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
    const rows = document.querySelectorAll('.propriedade-row');
    
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
