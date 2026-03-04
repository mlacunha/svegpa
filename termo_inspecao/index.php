<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Termos de Inspeção";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$filtro_programa = isset($_GET['programa']) && $_GET['programa'] !== '' ? sanitizeInput($_GET['programa']) : null;
$filtro_ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int)$_GET['ano'] : null;
$filtro_trimestre = isset($_GET['trimestre']) && $_GET['trimestre'] !== '' ? (int)$_GET['trimestre'] : null;

$programas = $db->query("SELECT id, nome FROM programas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$anos_distintos = $db->query("
    SELECT DISTINCT YEAR(COALESCE(data_inspecao, criado_em)) as ano
    FROM termo_inspecao
    WHERE data_inspecao IS NOT NULL OR criado_em IS NOT NULL
    ORDER BY ano DESC
")->fetchAll(PDO::FETCH_COLUMN);

$params = [];
$where = [];
if ($filtro_programa) {
    $where[] = "t.id_programa = :filtro_programa";
    $params[':filtro_programa'] = $filtro_programa;
}
if ($filtro_ano) {
    $where[] = "YEAR(COALESCE(t.data_inspecao, t.criado_em)) = :filtro_ano";
    $params[':filtro_ano'] = $filtro_ano;
}
if ($filtro_trimestre) {
    $where[] = "CEIL(MONTH(COALESCE(t.data_inspecao, t.criado_em)) / 3) = :filtro_trimestre";
    $params[':filtro_trimestre'] = $filtro_trimestre;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT t.*, p.nome as propriedade_nome, u.name as usuario_nome,
           au.name as auxiliar_nome, pr.nome as programa_nome,
           YEAR(COALESCE(t.data_inspecao, t.criado_em)) as ano,
           CEIL(MONTH(COALESCE(t.data_inspecao, t.criado_em)) / 3) as trimestre
    FROM termo_inspecao t
    LEFT JOIN propriedades p ON t.id_propriedade = p.id
    LEFT JOIN sec_users u ON t.id_usuario = u.login
    LEFT JOIN sec_users au ON t.id_auxiliar = au.login
    LEFT JOIN programas pr ON t.id_programa = pr.id
    $sql_where
    ORDER BY COALESCE(pr.nome, ''), ano DESC, trimestre DESC, COALESCE(t.data_inspecao, t.criado_em) DESC, t.criado_em DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$termos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-search mr-2"></i>Inspeção
        </h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Termo de Inspeção
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Termo criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Termo atualizado com sucesso!' : 'Termo excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-4">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="filtro_programa" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Programa</label>
                <select id="filtro_programa" name="programa" class="form-control w-48">
                    <option value="">Todos</option>
                    <?php foreach ($programas as $prog): ?>
                    <option value="<?php echo htmlspecialchars($prog['id']); ?>" <?php echo $filtro_programa === $prog['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($prog['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro_ano" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ano</label>
                <select id="filtro_ano" name="ano" class="form-control w-24">
                    <option value="">Todos</option>
                    <?php foreach ($anos_distintos as $a): ?>
                    <option value="<?php echo (int)$a; ?>" <?php echo $filtro_ano === (int)$a ? 'selected' : ''; ?>><?php echo (int)$a; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro_trimestre" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trimestre</label>
                <select id="filtro_trimestre" name="trimestre" class="form-control w-24">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $filtro_trimestre === 1 ? 'selected' : ''; ?>>1º</option>
                    <option value="2" <?php echo $filtro_trimestre === 2 ? 'selected' : ''; ?>>2º</option>
                    <option value="3" <?php echo $filtro_trimestre === 3 ? 'selected' : ''; ?>>3º</option>
                    <option value="4" <?php echo $filtro_trimestre === 4 ? 'selected' : ''; ?>>4º</option>
                </select>
            </div>
            <button type="submit" class="btn-secondary">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
            <?php if ($filtro_programa || $filtro_ano || $filtro_trimestre): ?>
            <a href="index.php" class="btn-secondary">Limpar</a>
            <?php endif; ?>
        </div>
    </form>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar por propriedade, técnico..." class="form-control max-w-md">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Data Inspeção</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600" style="width: 12%">Propriedade</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Termo Inspeção</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600" style="width: 10%">Termo Coleta</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Técnico</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600" style="width: 12%">Auxiliar</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="termos-table">
                <?php
                $ult_grupo = '';
                foreach ($termos as $t):
                    $grupo = ($t['programa_nome'] ?? '-') . '|' . ($t['ano'] ?? '-') . '|' . ($t['trimestre'] ?? '-');
                    if ($grupo !== $ult_grupo):
                        $ult_grupo = $grupo;
                ?>
                <tr class="bg-gray-100 border-b border-gray-300 grupo-row cursor-pointer hover:bg-gray-200" data-grupo="<?php echo htmlspecialchars($grupo); ?>" onclick="toggleGrupo(this)">
                    <td colspan="7" class="py-2 px-4 font-semibold text-gray-700">
                        <i class="fas fa-chevron-down grupo-chevron mr-2 transition-transform inline-block text-sm" style="width: 1em;"></i>
                        <span class="text-primary"><?php echo htmlspecialchars($t['programa_nome'] ?? 'Sem programa'); ?></span>
                        — Ano <?php echo htmlspecialchars($t['ano'] ?? '-'); ?>
                        — <?php echo (int)($t['trimestre'] ?? 0); ?>º Trimestre
                    </td>
                </tr>
                <?php endif; ?>
                <tr class="border-b hover:bg-gray-50 termo-row" data-search="<?php echo strtolower(htmlspecialchars(($t['propriedade_nome'] ?? '') . ' ' . ($t['usuario_nome'] ?? $t['id_usuario'] ?? '') . ' ' . ($t['auxiliar_nome'] ?? '') . ' ' . ($t['id_usuario'] ?? '') . ' ' . ($t['id'] ?? ''))); ?>" data-grupo="<?php echo htmlspecialchars($grupo); ?>">
                    <td class="py-3 px-4"><?php echo !empty($t['data_inspecao']) && $t['data_inspecao'] != '0000-00-00' ? date('d/m/Y', strtotime($t['data_inspecao'])) : '-'; ?></td>
                    <td class="py-3 px-4 font-medium truncate" style="max-width: 150px" title="<?php echo htmlspecialchars($t['propriedade_nome'] ?? ''); ?>"><?php echo htmlspecialchars($t['propriedade_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($t['termo_inspecao'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($t['termo_coleta'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars(trim($t['usuario_nome'] ?? '') ?: ($t['id_usuario'] ?? '-')); ?></td>
                    <td class="py-3 px-4 truncate" style="max-width: 120px" title="<?php echo htmlspecialchars($t['auxiliar_nome'] ?? ''); ?>"><?php echo htmlspecialchars(trim($t['auxiliar_nome'] ?? '') ?: '-'); ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?id=<?php echo htmlspecialchars($t['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($t['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este termo de inspeção?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleGrupo(grupoRow) {
    var collapsed = grupoRow.classList.toggle('grupo-collapsed');
    var next = grupoRow.nextElementSibling;
    while (next && !next.classList.contains('grupo-row')) {
        if (next.classList.contains('termo-row')) next.style.display = collapsed ? 'none' : '';
        next = next.nextElementSibling;
    }
    var chevron = grupoRow.querySelector('.grupo-chevron');
    if (chevron) chevron.style.transform = collapsed ? 'rotate(-90deg)' : '';
}
document.getElementById('search').addEventListener('keyup', function() {
    var t = this.value.toLowerCase();
    document.querySelectorAll('.termo-row').forEach(function(row) {
        var match = (row.getAttribute('data-search') || '').indexOf(t) >= 0;
        row.setAttribute('data-search-match', match ? '1' : '0');
        var grupoRow = row.previousElementSibling;
        while (grupoRow && !grupoRow.classList.contains('grupo-row')) grupoRow = grupoRow.previousElementSibling;
        var collapsed = grupoRow && grupoRow.classList.contains('grupo-collapsed');
        row.style.display = (match && !collapsed) ? '' : 'none';
    });
    document.querySelectorAll('.grupo-row').forEach(function(grupoRow) {
        var next = grupoRow.nextElementSibling;
        var hasMatch = false;
        while (next && !next.classList.contains('grupo-row')) {
            if (next.classList.contains('termo-row') && next.getAttribute('data-search-match') === '1') hasMatch = true;
            next = next.nextElementSibling;
        }
        grupoRow.style.display = hasMatch ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
