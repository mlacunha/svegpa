<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Produtores";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT * FROM produtores ORDER BY nome ASC");
$produtores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Produtores</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Produtor
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Produtor criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Produtor atualizado com sucesso!' : 'Produtor excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar produtor..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município / UF</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Telefone</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">N. Cadastro</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="produtores-table">
                <?php foreach ($produtores as $prod): ?>
                <tr class="border-b hover:bg-gray-50 produtor-row" data-search="<?php echo strtolower(htmlspecialchars($prod['nome'] . ' ' . ($prod['municipio'] ?? '') . ' ' . ($prod['uf'] ?? '') . ' ' . ($prod['n_cadastro'] ?? '') . ' ' . ($prod['email'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($prod['nome']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars(($prod['municipio'] ?? '-') . ' / ' . ($prod['uf'] ?? '-')); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($prod['telefone'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($prod['n_cadastro'] ?? '-'); ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?id=<?php echo htmlspecialchars($prod['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($prod['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este produtor? Propriedades vinculadas podem ser afetadas.')">
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
    const rows = document.querySelectorAll('.produtor-row');
    
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
