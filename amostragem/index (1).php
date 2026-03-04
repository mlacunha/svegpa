<?php
/**
 * Módulo Amostragem - Termos de Coleta de Amostra.
 * Exibe amostras coletadas (termo_inspecao com termo_coleta).
 * Não cria novas amostras; toda amostragem começa com inspeção.
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Termos de Coleta de Amostra";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$filtro_programa = isset($_GET['programa']) && $_GET['programa'] !== '' ? sanitizeInput($_GET['programa']) : null;
$filtro_ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int)$_GET['ano'] : null;
$filtro_trimestre = isset($_GET['trimestre']) && $_GET['trimestre'] !== '' ? (int)$_GET['trimestre'] : null;

$programas = $db->query("SELECT id, nome FROM programas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$anos_amostragem = $db->query("
    SELECT DISTINCT YEAR(COALESCE(data_amostragem, criado_em)) as ano
    FROM termo_inspecao
    WHERE termo_coleta IS NOT NULL AND TRIM(termo_coleta) != ''
    AND (data_amostragem IS NOT NULL OR criado_em IS NOT NULL)
    ORDER BY ano DESC
")->fetchAll(PDO::FETCH_COLUMN);

$params = [];
$where = ["t.termo_coleta IS NOT NULL", "TRIM(t.termo_coleta) != ''"];
if ($filtro_programa) {
    $where[] = "t.id_programa = :filtro_programa";
    $params[':filtro_programa'] = $filtro_programa;
}
if ($filtro_ano) {
    $where[] = "YEAR(COALESCE(t.data_amostragem, t.criado_em)) = :filtro_ano";
    $params[':filtro_ano'] = $filtro_ano;
}
if ($filtro_trimestre) {
    $where[] = "CEIL(MONTH(COALESCE(t.data_amostragem, t.criado_em)) / 3) = :filtro_trimestre";
    $params[':filtro_trimestre'] = $filtro_trimestre;
}
$sql_where = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT t.id, t.termo_coleta, t.data_amostragem, t.id_usuario, t.id_auxiliar, t.id_propriedade, t.id_programa,
           p.nome as propriedade_nome, p.municipio as propriedade_municipio, p.UF as propriedade_uf,
           u.name as usuario_nome, fu.nome as usuario_formacao, cr.nome as usuario_cargo,
           au.name as auxiliar_nome, fa.nome as auxiliar_formacao, ca.nome as auxiliar_cargo,
           pr.nome as programa_nome,
           YEAR(COALESCE(t.data_amostragem, t.criado_em)) as ano,
           CEIL(MONTH(COALESCE(t.data_amostragem, t.criado_em)) / 3) as trimestre
    FROM termo_inspecao t
    LEFT JOIN propriedades p ON t.id_propriedade = p.id
    LEFT JOIN sec_users u ON t.id_usuario = u.login
    LEFT JOIN formacao fu ON u.formacao = fu.id
    LEFT JOIN cargos cr ON u.role = cr.id
    LEFT JOIN sec_users au ON t.id_auxiliar = au.login
    LEFT JOIN formacao fa ON au.formacao = fa.id
    LEFT JOIN cargos ca ON au.role = ca.id
    LEFT JOIN programas pr ON t.id_programa = pr.id
    $sql_where
    ORDER BY COALESCE(pr.nome, ''), ano DESC, trimestre DESC, t.data_amostragem DESC, t.criado_em DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$termos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Carrega áreas com coleta para cada termo
$areasPorTermo = [];
foreach ($termos as $t) {
    $tid = $t['id'];
    $stmt_a = $db->prepare("
        SELECT a.*, pr.nome_cientifico as programa_nome_cientifico,
               COALESCE(a.nome_local, a.tipo_area, CONCAT('Área ', a.id)) as area_nome
        FROM area_inspecionada a
        LEFT JOIN termo_inspecao t ON a.id_termo_inspecao = t.id
        LEFT JOIN programas pr ON t.id_programa = pr.id
        WHERE a.id_termo_inspecao = ? AND a.coletar_mostra = 1
        ORDER BY a.id
    ");
    $stmt_a->execute([$tid]);
    $areasPorTermo[$tid] = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
}

function partesColetadas($a) {
    $partes = ['raiz'=>'Raiz','caule'=>'Caule','peciolo'=>'Peciolos','folha'=>'Folhas','flor'=>'Flores','fruto'=>'Frutos','semente'=>'Sementes'];
    $sel = [];
    foreach ($partes as $k => $label) {
        if (!empty($a[$k])) $sel[] = $label;
    }
    return implode(', ', $sel);
}
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-vial mr-2"></i>Termos de Coleta de Amostra
        </h2>
    </div>
    <p class="text-gray-600 mb-4">Consulta e ajustes das amostras coletadas. As amostras são originadas das inspeções com coleta.</p>

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
                    <?php foreach ($anos_amostragem as $a): ?>
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
        <input type="text" id="search" placeholder="Buscar por propriedade, município, responsável..." class="form-control max-w-md">
    </div>

    <div class="space-y-4">
        <?php
        $ult_grupo = '';
        foreach ($termos as $t):
            $grupo = ($t['programa_nome'] ?? '-') . '|' . ($t['ano'] ?? '-') . '|' . ($t['trimestre'] ?? '-');
            if ($grupo !== $ult_grupo):
                $ult_grupo = $grupo;
        ?>
        <div class="py-2 px-3 rounded-lg font-semibold text-gray-700 bg-gray-100 border border-gray-200 grupo-header cursor-pointer hover:bg-gray-200" data-grupo="<?php echo htmlspecialchars($grupo); ?>" onclick="toggleGrupoAmostragem(this)">
            <i class="fas fa-chevron-down grupo-chevron mr-2 transition-transform inline-block text-sm" style="width: 1em;"></i>
            <span class="text-primary"><?php echo htmlspecialchars($t['programa_nome'] ?? 'Sem programa'); ?></span>
            — Ano <?php echo htmlspecialchars($t['ano'] ?? '-'); ?>
            — <?php echo (int)($t['trimestre'] ?? 0); ?>º Trimestre
        </div>
        <?php endif;
            $tid = $t['id'];
            $areas = $areasPorTermo[$tid] ?? [];
            $locais = array_map(function($a) { return $a['area_nome'] ?? ''; }, $areas);
            $localColeta = implode(', ', array_filter($locais));
            $nomeMunicipioUf = trim(($t['propriedade_municipio'] ?? '') . ($t['propriedade_uf'] ? ' - ' . $t['propriedade_uf'] : ''));
            $dataColetaStr = !empty($t['data_amostragem']) && $t['data_amostragem'] != '0000-00-00' ? date('d/m/Y', strtotime($t['data_amostragem'])) : '-';
            $dataSearch = strtolower(htmlspecialchars(($t['propriedade_nome'] ?? '') . ' ' . ($t['propriedade_municipio'] ?? '') . ' ' . ($t['usuario_nome'] ?? $t['id_usuario'] ?? '') . ' ' . ($t['usuario_formacao'] ?? '') . ' ' . ($t['auxiliar_nome'] ?? '') . ' ' . ($t['auxiliar_formacao'] ?? '') . ' ' . ($t['termo_coleta'] ?? '')));
        ?>
        <div class="border border-gray-200 rounded-lg overflow-hidden termo-amostragem-row" data-search="<?php echo $dataSearch; ?>" data-grupo="<?php echo htmlspecialchars($grupo); ?>">
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-100" onclick="toggleDetalhe('detalhe-<?php echo htmlspecialchars($tid); ?>')">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="font-semibold text-gray-800">TCA: <?php echo htmlspecialchars($t['termo_coleta'] ?? '-'); ?></span>
                    <span class="text-gray-600"><?php echo htmlspecialchars($t['propriedade_nome'] ?? '-'); ?><?php if ($nomeMunicipioUf): ?>, <?php echo htmlspecialchars($nomeMunicipioUf); ?><?php endif; ?></span>
                    <span class="text-sm text-gray-500">Data da Coleta: <?php echo $dataColetaStr; ?></span>
                </div>
                <i class="fas fa-chevron-down transition-transform detalhe-chevron" id="chevron-<?php echo htmlspecialchars($tid); ?>"></i>
            </div>
            <div id="detalhe-<?php echo htmlspecialchars($tid); ?>" class="hidden border-t border-gray-200 bg-white">
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Estabelecimento Fiscalizado</div>
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($t['propriedade_nome'] ?? '-'); ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Local da Coleta</div>
                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($localColeta ?: '-'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($nomeMunicipioUf ?: '-'); ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Data Amostragem</div>
                            <div class="text-gray-700"><?php echo $dataColetaStr; ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Responsável</div>
                            <div class="text-sm text-gray-800"><?php echo htmlspecialchars(trim($t['usuario_nome'] ?? '') ?: ($t['id_usuario'] ?? '-')); ?></div>
                            <?php if (!empty(trim($t['usuario_formacao'] ?? ''))): ?>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($t['usuario_formacao']); ?></div>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($t['usuario_cargo'] ?? '-'); ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Auxiliar</div>
                            <?php if (!empty(trim($t['auxiliar_nome'] ?? ''))): ?>
                            <div class="text-sm text-gray-800"><?php echo htmlspecialchars($t['auxiliar_nome']); ?></div>
                            <?php if (!empty(trim($t['auxiliar_formacao'] ?? ''))): ?>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($t['auxiliar_formacao']); ?></div>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($t['auxiliar_cargo'] ?? '-'); ?></div>
                            <?php else: ?>
                            <div class="text-sm text-gray-500">—</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h3 class="text-sm font-semibold text-gray-700 border-b pb-1">Material Coletado</h3>
                    <div class="table-container overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Identificação da amostra</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Espécie</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Tipo de material</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Variedade</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Coordenadas</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Análise solicitada</th>
                                    <th class="py-2 px-3 text-left font-semibold text-gray-600">Associado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($areas)): ?>
                                <tr><td colspan="7" class="py-3 px-3 text-center text-gray-500">Nenhum material coletado</td></tr>
                                <?php else: ?>
                                <?php foreach ($areas as $a): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['identificacao_amostra'] ?? '-'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['especie'] ?? '-'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars(partesColetadas($a) ?: '-'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['variedade'] ?? '-'); ?></td>
                                    <td class="py-2 px-3"><?php
                                        $lat = $a['latitude'] ?? null;
                                        $lon = $a['longitude'] ?? null;
                                        echo ($lat !== null && $lon !== null) ? htmlspecialchars($lat . ', ' . $lon) : '-';
                                    ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['programa_nome_cientifico'] ?? '-'); ?></td>
                                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['associado'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="print.php?id=<?php echo urlencode($tid); ?>" target="_blank" class="btn-primary text-sm">
                            <i class="fas fa-print mr-2"></i>Imprimir TCA
                        </a>
                        <a href="<?php echo htmlspecialchars(getBasePath()); ?>termo_inspecao/edit.php?id=<?php echo urlencode($tid); ?>" class="btn-secondary text-sm">
                            <i class="fas fa-edit mr-2"></i>Editar no Termo de Inspeção
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($termos)): ?>
    <div class="text-center py-12 text-gray-500">
        <i class="fas fa-vial text-4xl mb-4 opacity-50"></i>
        <p>Nenhum termo de coleta de amostra cadastrado.</p>
        <p class="text-sm mt-2">As amostras são criadas a partir das inspeções, ao marcar "Coletar amostra" em alguma área.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleGrupoAmostragem(grupoEl) {
    var collapsed = grupoEl.classList.toggle('grupo-collapsed');
    var next = grupoEl.nextElementSibling;
    while (next && !next.classList.contains('grupo-header')) {
        if (next.classList.contains('termo-amostragem-row')) next.style.display = collapsed ? 'none' : '';
        next = next.nextElementSibling;
    }
    var chevron = grupoEl.querySelector('.grupo-chevron');
    if (chevron) chevron.style.transform = collapsed ? 'rotate(-90deg)' : '';
}
function toggleDetalhe(id) {
    var el = document.getElementById(id);
    var tid = id.replace('detalhe-', '');
    var chevron = document.getElementById('chevron-' + tid);
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        if (chevron) chevron.classList.add('rotate-180');
    } else {
        el.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
    }
}
document.getElementById('search').addEventListener('keyup', function() {
    var t = this.value.toLowerCase();
    document.querySelectorAll('.termo-amostragem-row').forEach(function(row) {
        var match = (row.getAttribute('data-search') || '').indexOf(t) >= 0;
        row.setAttribute('data-search-match', match ? '1' : '0');
        var grupoEl = row.previousElementSibling;
        while (grupoEl && !grupoEl.classList.contains('grupo-header')) grupoEl = grupoEl.previousElementSibling;
        var collapsed = grupoEl && grupoEl.classList.contains('grupo-collapsed');
        row.style.display = (match && !collapsed) ? '' : 'none';
    });
    document.querySelectorAll('.grupo-header').forEach(function(grupoEl) {
        var grupo = grupoEl.getAttribute('data-grupo');
        var hasMatch = false;
        document.querySelectorAll('.termo-amostragem-row[data-grupo="' + grupo + '"]').forEach(function(row) {
            if (row.getAttribute('data-search-match') === '1') hasMatch = true;
        });
        grupoEl.style.display = hasMatch ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
