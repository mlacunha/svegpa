<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Termo de Inspeção";

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM termo_inspecao WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$termo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$termo) {
    header('Location: index.php');
    exit;
}

$propriedades = $db->query("SELECT id, n_cadastro, nome, municipio FROM propriedades ORDER BY municipio ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $db->query("SELECT login, name, COALESCE(NULLIF(TRIM(matricula),''), login) as matricula FROM sec_users ORDER BY COALESCE(NULLIF(name,''), login) ASC")->fetchAll(PDO::FETCH_ASSOC);
$programas = $db->query("SELECT id, nome FROM programas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$hospedeiros_raw = $db->query("SELECT id_programa, nome_cientifico FROM hospedeiros ORDER BY nome_cientifico")->fetchAll(PDO::FETCH_ASSOC);
$hospedeirosPorPrograma = [];
foreach ($hospedeiros_raw as $h) {
    $pid = $h['id_programa'] ?? '';
    if (!isset($hospedeirosPorPrograma[$pid])) $hospedeirosPorPrograma[$pid] = [];
    $hospedeirosPorPrograma[$pid][] = $h['nome_cientifico'];
}

$stmt_areas = $db->prepare("SELECT a.* FROM area_inspecionada a WHERE a.id_termo_inspecao = :id ORDER BY a.id");
$stmt_areas->bindParam(':id', $id);
$stmt_areas->execute();
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);
$programasById = [];
foreach ($programas as $p) $programasById[$p['id']] = $p['nome'];

$proprietario_nome = '';
if (!empty($termo['id_propriedade'])) {
    $stmt_prop = $db->prepare("SELECT pr.nome FROM propriedades p LEFT JOIN produtores pr ON p.id_proprietario = pr.id WHERE p.id = ?");
    $stmt_prop->execute([$termo['id_propriedade']]);
    $proprietario_nome = trim($stmt_prop->fetchColumn() ?: '');
}

$expand_area = isset($_GET['expand']) && $_GET['expand'] === '1';
$expand_area_id = isset($_GET['area_id']) ? trim(sanitizeInput($_GET['area_id'])) : null;

$termo_tem_coleta = !empty(trim($termo['termo_coleta'] ?? ''));
if (!$termo_tem_coleta) {
    foreach ($areas as $a) {
        if (!empty($a['coletar_mostra']) || !empty($a['coleta_amostra'])) {
            $termo_tem_coleta = true;
            break;
        }
    }
}

$val = function($key, $default = '') use ($termo) {
    return isset($_POST[$key]) ? $_POST[$key] : ($termo[$key] ?? $default);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $municipio = !empty($_POST['municipio']) ? sanitizeInput($_POST['municipio']) : null;
    $data_inspecao = !empty($_POST['data_inspecao']) ? sanitizeInput($_POST['data_inspecao']) : null;
    $data_amostragem = !empty($_POST['data_amostragem']) ? sanitizeInput($_POST['data_amostragem']) : null;
    $id_usuario = !empty($_POST['id_usuario']) ? sanitizeInput($_POST['id_usuario']) : null;
    $id_propriedade = !empty($_POST['id_propriedade']) ? sanitizeInput($_POST['id_propriedade']) : null;
    $id_auxiliar = !empty($_POST['id_auxiliar']) ? sanitizeInput($_POST['id_auxiliar']) : null;
    $id_programa = !empty($_POST['id_programa']) ? sanitizeInput($_POST['id_programa']) : null;

    if (empty($municipio)) {
        $error = "O Município é obrigatório.";
    } else {
    try {
        $id_usuario_antigo = trim($termo['id_usuario'] ?? '');
        $mudou_responsavel = $id_usuario && $id_usuario !== $id_usuario_antigo;
        $termo_inspecao_val = null;
        $termo_coleta_val = null;

        if ($mudou_responsavel) {
            $ano = (int) date('Y');
            if ($data_inspecao) {
                $d = DateTime::createFromFormat('Y-m-d', $data_inspecao);
                if ($d) $ano = (int) $d->format('Y');
            }
            $ano_str = (string) $ano;
            $matricula = $id_usuario;
            foreach ($usuarios as $u) {
                if (($u['login'] ?? '') === $id_usuario) {
                    $matricula = $u['matricula'] ?? $id_usuario;
                    break;
                }
            }
            $db->prepare("INSERT INTO controle_sequencial (login, ano, seq_ti, seq_tc) VALUES (?, ?, 0, 0) ON DUPLICATE KEY UPDATE login = login")
                ->execute([$id_usuario, $ano_str]);
            $db->prepare("UPDATE controle_sequencial SET seq_ti = seq_ti + 1 WHERE login = ? AND ano = ?")
                ->execute([$id_usuario, $ano_str]);
            $stmt_ti = $db->prepare("SELECT seq_ti FROM controle_sequencial WHERE login = ? AND ano = ?");
            $stmt_ti->execute([$id_usuario, $ano_str]);
            $seq_ti = (int) ($stmt_ti->fetchColumn() ?: 0);
            $termo_inspecao_val = $seq_ti . '/' . $matricula . '/' . $ano_str;

            $tem_coleta = !empty(trim($termo['termo_coleta'] ?? ''));
            if (!$tem_coleta) {
                foreach ($areas as $a) {
                    if (!empty($a['coletar_mostra']) || !empty($a['coleta_amostra'])) {
                        $tem_coleta = true;
                        break;
                    }
                }
            }
            if ($tem_coleta) {
                $db->prepare("UPDATE controle_sequencial SET seq_tc = seq_tc + 1 WHERE login = ? AND ano = ?")
                    ->execute([$id_usuario, $ano_str]);
                $stmt_tc = $db->prepare("SELECT seq_tc FROM controle_sequencial WHERE login = ? AND ano = ?");
                $stmt_tc->execute([$id_usuario, $ano_str]);
                $seq_tc = (int) ($stmt_tc->fetchColumn() ?: 0);
                $termo_coleta_val = $seq_tc . '/' . $matricula . '/' . $ano_str;
            }
        }

        $updates = ["data_inspecao = :data_inspecao", "data_amostragem = :data_amostragem", "id_usuario = :id_usuario", "id_auxiliar = :id_auxiliar", "id_propriedade = :id_propriedade", "id_programa = :id_programa"];
        $params = [':data_inspecao' => $data_inspecao, ':data_amostragem' => $data_amostragem ?: null, ':id_usuario' => $id_usuario, ':id_auxiliar' => $id_auxiliar, ':id_propriedade' => $id_propriedade, ':id_programa' => $id_programa];
        if ($termo_inspecao_val !== null) {
            $updates[] = "termo_inspecao = :termo_inspecao";
            $params[':termo_inspecao'] = $termo_inspecao_val;
        }
        if ($termo_coleta_val !== null) {
            $updates[] = "termo_coleta = :termo_coleta";
            $params[':termo_coleta'] = $termo_coleta_val;
        }
        $params[':id'] = $id;
        $stmt = $db->prepare("UPDATE termo_inspecao SET " . implode(', ', $updates) . " WHERE id = :id");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        if ($stmt->execute()) {
            header('Location: index.php?success=updated');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erro ao atualizar: " . $e->getMessage();
    }
    }
}

$dataInspecaoVal = $val('data_inspecao');
$dataInspecaoInput = '';
if ($dataInspecaoVal) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataInspecaoVal);
    if ($dt) $dataInspecaoInput = $dt->format('Y-m-d');
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$dataInspecaoVal)) $dataInspecaoInput = substr((string)$dataInspecaoVal, 0, 10);
}
$dataAmostragemVal = $val('data_amostragem');
$dataAmostragemInput = '';
if ($dataAmostragemVal) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataAmostragemVal);
    if ($dt) $dataAmostragemInput = $dt->format('Y-m-d');
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$dataAmostragemVal)) $dataAmostragemInput = substr((string)$dataAmostragemVal, 0, 10);
}

include '../includes/header.php';
?>

<?php
$sel_municipio = '';
foreach ($propriedades as $p) {
    if ($p['id'] === $val('id_propriedade')) { $sel_municipio = $p['municipio'] ?? ''; break; }
}
?>
<div class="card">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>Editar Termo de Inspeção
        </h2>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
            <button type="submit" form="formTermo" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Atualizar
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'area'): ?>
    <?php
    $missing_area = isset($_GET['missing']) ? array_map('trim', explode(',', (string)$_GET['missing'])) : [];
    $msg = 'Alguns campos obrigatórios não foram preenchidos para a área.';
    if (!empty($missing_area)) {
        $labels = ['programa'=>'Programa', 'numero_plantas'=>'Nº plantas', 'numero_inspecionadas'=>'Nº inspecionadas', 'coordenadas'=>'Coordenadas (latitude/longitude)', 'identificacao_amostra'=>'Identificação da amostra', 'partes_coletadas'=>'Partes coletadas (ao menos uma)', 'data_amostragem'=>'Data Amostragem', 'especie'=>'Espécie (deve estar cadastrada nos hospedeiros do programa)'];
        $missing_labels = array_map(function($m) use ($labels) { return $labels[$m] ?? $m; }, $missing_area);
        $msg .= ' Campo(s) não recebidos: ' . implode(', ', $missing_labels) . '.';
    }
    ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Termo criado!
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'area_saved'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Área inspecionada salva com sucesso!
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'area_deleted'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>Área inspecionada excluída!
    </div>
    <?php endif; ?>
    <form id="formTermo" method="POST" class="space-y-6">
        <div class="form-row-horizontal grid grid-cols-1 md:grid md:grid-cols-7 w-full gap-4 min-w-0 items-end">
            <?php $selected_prop = $val('id_propriedade'); $selected_municipio = $sel_municipio; include '../includes/termo_inspecao_propriedade_select.php'; ?>
            <div class="form-group min-w-0">
                <label for="id_programa">Programa *</label>
                <select id="id_programa" name="id_programa" class="form-control w-full" required>
                    <option value="">-- Selecione --</option>
                    <?php foreach ($programas as $prog): ?>
                    <option value="<?php echo htmlspecialchars($prog['id']); ?>" <?php echo $val('id_programa') === $prog['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($prog['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group min-w-0">
                <label for="data_inspecao">Data Inspeção</label>
                <input type="date" id="data_inspecao" name="data_inspecao" class="form-control w-full" value="<?php echo htmlspecialchars($dataInspecaoInput ?: $val('data_inspecao')); ?>">
            </div>
            <input type="hidden" id="data_amostragem" name="data_amostragem" value="<?php echo htmlspecialchars($dataAmostragemInput ?: $val('data_amostragem')); ?>">
            <div class="form-group min-w-0">
                <label>Termo Inspeção</label>
                <input type="text" id="display_termo_inspecao" class="form-control bg-gray-50 w-full" value="<?php echo htmlspecialchars($val('termo_inspecao') ?: '-'); ?>" readonly tabindex="-1">
            </div>
            <input type="hidden" id="display_termo_coleta" value="<?php echo htmlspecialchars($val('termo_coleta') ?: '-'); ?>">
            <div class="form-group min-w-0">
                <label for="id_usuario">Técnico Responsável</label>
                <select id="id_usuario" name="id_usuario" class="form-control w-full">
                    <option value="">-- Selecione o técnico --</option>
                    <?php foreach ($usuarios as $u): $disp = trim($u['name'] ?? '') ?: $u['login']; ?>
                    <option value="<?php echo htmlspecialchars($u['login']); ?>" <?php echo $val('id_usuario') === $u['login'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($disp); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group min-w-0">
                <label for="id_auxiliar">Auxiliar (opcional)</label>
                <select id="id_auxiliar" name="id_auxiliar" class="form-control w-full">
                    <option value="">-- Nenhum --</option>
                    <?php foreach ($usuarios as $u): $disp = trim($u['name'] ?? '') ?: $u['login']; ?>
                    <option value="<?php echo htmlspecialchars($u['login']); ?>" <?php echo $val('id_auxiliar') === $u['login'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($disp); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <hr class="my-8 border-gray-200">

    <div class="mb-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-map mr-2"></i>Áreas Inspecionadas <span class="text-gray-600 font-normal">(<?php echo count($areas); ?> área<?php echo count($areas) !== 1 ? 's' : ''; ?>)</span>
        </h3>
        <div class="flex items-center gap-2">
            <button type="button" id="btnExibirAreaForm" class="btn-secondary" title="Exibir formulário">
                <i class="fas fa-eye mr-2"></i>Exibir
            </button>
            <button type="button" id="btnNovaArea" class="btn-primary text-sm">
                <i class="fas fa-plus mr-2"></i>Nova Área
            </button>
        </div>
    </div>

    <div id="areaFormExpand" class="<?php echo $expand_area ? '' : 'hidden'; ?> mb-6">
        <div class="card p-6">
            <h4 class="text-md font-semibold text-gray-700 mb-4" id="areaFormTitle">Nova Área Inspecionada</h4>
            <form method="POST" action="area_save.php" id="formArea">
                <input type="hidden" name="id_termo_inspecao" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="area_id" id="area_id" value="">
                <div class="space-y-4 mb-4">
                    <div class="grid grid-cols-1 md:grid gap-4 md:[grid-template-columns:20%_50%] min-w-0">
                        <input type="hidden" id="area_id_programa" name="id_programa" value="<?php echo htmlspecialchars($val('id_programa')); ?>">
                        <div class="form-group min-w-0">
                            <label for="area_tipo_area">Tipo área</label>
                            <select id="area_tipo_area" name="tipo_area" class="form-control">
                                <option value="">-- Selecione --</option>
                                <option value="Talhão">Talhão</option>
                                <option value="Plantio">Plantio</option>
                                <option value="Sítios e Similares">Sítios e Similares</option>
                                <option value="Áreas Urbanas">Áreas Urbanas</option>
                                <option value="Residências">Residências</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="form-group min-w-0">
                            <label for="area_nome_local">Nome local</label>
                            <input type="text" id="area_nome_local" name="nome_local" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="form-group">
                            <label for="area_especie">Espécie</label>
                            <select id="area_especie" name="especie" class="form-control">
                                <option value="">-- Selecione o programa primeiro --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="area_variedade">Variedade</label>
                            <input type="text" id="area_variedade" name="variedade" class="form-control" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="area_material_multiplicacao">Material multiplicação</label>
                            <input type="text" id="area_material_multiplicacao" name="material_multiplicacao" class="form-control" maxlength="120">
                        </div>
                        <div class="form-group">
                            <label for="area_origem">Origem</label>
                            <input type="text" id="area_origem" name="origem" class="form-control" maxlength="120">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="form-group">
                            <label for="area_idade_plantio">Idade do plantio</label>
                            <input type="text" id="area_idade_plantio" name="idade_plantio" class="form-control" placeholder="Ex: 2.5">
                        </div>
                        <div class="form-group">
                            <label for="area_area_plantada">Área plantada (ha)</label>
                            <input type="text" id="area_area_plantada" name="area_plantada" class="form-control" placeholder="Ex: 1.5">
                        </div>
                        <div class="form-group">
                            <label for="area_numero_plantas">Nº Plantas *</label>
                            <input type="number" id="area_numero_plantas" name="numero_plantas" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="area_numero_inspecionadas">Nº Inspecionadas *</label>
                            <input type="number" id="area_numero_inspecionadas" name="numero_inspecionadas" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="area_numero_suspeitas">Nº Suspeitas</label>
                            <input type="number" id="area_numero_suspeitas" name="numero_suspeitas" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group" id="areaColetaAmostraWrap" style="display:none">
                            <div class="flex items-end gap-4 flex-wrap">
                                <div>
                                    <label>Coletar amostra?</label>
                                    <div class="flex items-center h-[38px]">
                                        <input type="checkbox" id="area_coletar_mostra" name="coletar_mostra" value="1" class="rounded border-gray-300">
                                    </div>
                                </div>
                                <div id="areaDataAmostragemWrap" class="hidden">
                                    <label for="area_data_amostragem">Data Amostragem *</label>
                                    <input type="date" id="area_data_amostragem" name="data_amostragem" class="form-control" value="<?php echo htmlspecialchars(substr($val('data_amostragem'), 0, 10) ?: date('Y-m-d')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="areaColetaCamposWrap" class="hidden mt-4">
                        <div class="flex flex-col md:flex-row gap-4 items-stretch">
                            <div class="flex flex-col md:flex-row gap-4 flex-1">
                                <div class="form-group flex-1">
                                    <label for="area_identificacao_amostra">Identificação da Amostra</label>
                                    <input type="text" id="area_identificacao_amostra" name="identificacao_amostra" class="form-control" maxlength="120" placeholder="Ex: A-01">
                                </div>
                                <div class="form-group flex-1">
                                    <label for="area_associado">Associado</label>
                                    <input type="text" id="area_associado" name="associado" class="form-control" maxlength="120">
                                </div>
                            </div>
                            <div class="form-group md:w-auto md:min-w-[320px] border border-gray-300 rounded-lg p-3 bg-gray-50/50">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Partes Coletadas</label>
                                <div class="flex flex-wrap gap-x-4 gap-y-2">
                                    <?php foreach (['raiz'=>'Raiz','caule'=>'Caule','peciolo'=>'Peciolos','folha'=>'Folhas','flor'=>'Flores','fruto'=>'Frutos','semente'=>'Sementes'] as $fn => $label): ?>
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm">
                                        <input type="checkbox" name="<?php echo $fn; ?>" value="1" class="partes-check rounded border-gray-300 text-primary">
                                        <span><?php echo $label; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="space-y-2">
                            <div class="flex gap-2 items-end flex-wrap">
                                <div class="form-group flex-1 min-w-0">
                                    <label for="area_latitude">Latitude *</label>
                                    <input type="text" id="area_latitude" name="latitude" class="form-control" placeholder="Ex: -1.455456" required>
                                </div>
                                <div class="form-group flex-1 min-w-0">
                                    <label for="area_longitude">Longitude *</label>
                                    <input type="text" id="area_longitude" name="longitude" class="form-control" placeholder="Ex: -48.677678" required>
                                </div>
                            </div>
                            <button type="button" id="areaCoordConverterBtn" class="btn-secondary whitespace-nowrap text-sm">
                                <i class="fas fa-exchange-alt mr-2"></i>Converter GMS→Decimal
                            </button>
                        </div>
                        <div class="form-group">
                            <label for="area_obs">Observações</label>
                            <textarea id="area_obs" name="obs" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="btnCancelarArea" class="btn-secondary">Cancelar</button>
                    <button type="submit" form="formArea" class="btn-primary"><i class="fas fa-save mr-2"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Programa</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Tipo/Nome local</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Espécie</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Nº plantas</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Nº inspec.</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Coleta</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Amostragem</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Resultado</th>
                    <th class="py-2 px-3 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($areas)): ?>
                <tr><td colspan="9" class="py-4 px-3 text-center text-gray-500">Nenhuma área cadastrada. Clique em "Nova Área" ou "Exibir" para adicionar.</td></tr>
                <?php else: ?>
                <?php foreach ($areas as $a): 
                    $aColeta = !empty($a['coletar_mostra']) || !empty($a['coleta_amostra']);
                    $aData = array_merge($a, ['id_programa' => $termo['id_programa'] ?? '']);
                ?>
                <tr class="border-b hover:bg-gray-50 cursor-pointer area-row" data-area="<?php echo htmlspecialchars(json_encode($aData)); ?>">
                    <td class="py-2 px-3"><?php echo htmlspecialchars($programasById[$termo['id_programa'] ?? ''] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars(trim(($a['tipo_area'] ?? '') . ' ' . ($a['nome_local'] ?? '')) ?: '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['especie'] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['numero_plantas'] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['numero_inspecionadas'] ?? '-'); ?></td>
                    <td class="py-2 px-3">
                        <?php if ($aColeta): ?><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" title="<?php echo htmlspecialchars($a['identificacao_amostra'] ?? ''); ?>"><?php echo htmlspecialchars($a['identificacao_amostra'] ?: '-'); ?></span><?php else: ?>-<?php endif; ?>
                    </td>
                    <td class="py-2 px-3"><?php echo $aColeta ? 'Sim' : '-'; ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['resultado'] ?? ($aColeta ? 'Suspeita' : 'Normal')); ?></td>
                    <td class="py-2 px-3" onclick="event.stopPropagation();">
                        <a href="area_delete.php?id_termo=<?php echo urlencode($id); ?>&area_id=<?php echo urlencode($a['id']); ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Excluir esta área?');" title="Excluir"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    var proprietarioNome = <?php echo json_encode($proprietario_nome); ?>;
    var termoDataAmostragem = <?php echo json_encode(substr($val('data_amostragem'), 0, 10)); ?>;
    var expand = document.getElementById('areaFormExpand');
    var btnExibir = document.getElementById('btnExibirAreaForm');
    var btnNova = document.getElementById('btnNovaArea');
    var btnCancelar = document.getElementById('btnCancelarArea');
    var form = document.getElementById('formArea');
    var areaId = document.getElementById('area_id');
    var areaTitle = document.getElementById('areaFormTitle');
    
    function showForm() { expand.classList.remove('hidden'); if (btnExibir) btnExibir.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Ocultar'; }
    function hideForm() { expand.classList.add('hidden'); if (btnExibir) btnExibir.innerHTML = '<i class="fas fa-eye mr-2"></i>Exibir'; }
    var termoIdPrograma = <?php echo json_encode($termo['id_programa'] ?? ''); ?>;
    function clearForm() {
        areaId.value = '';
        areaTitle.textContent = 'Nova Área Inspecionada';
        var progEl = document.getElementById('area_id_programa');
        var progVal = (mainProgramaSelect && mainProgramaSelect.value) || termoIdPrograma || '';
        if (progEl) progEl.value = progVal;
        populateEspecie(progVal);
        ['area_tipo_area','area_nome_local','area_latitude','area_longitude','area_variedade','area_material_multiplicacao','area_origem','area_idade_plantio','area_area_plantada','area_numero_plantas','area_numero_inspecionadas','area_numero_suspeitas','area_obs','area_identificacao_amostra'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        var elAssoc = document.getElementById('area_associado');
        if (elAssoc) elAssoc.value = proprietarioNome || '';
        var cm = document.getElementById('area_coletar_mostra');
        if (cm) cm.checked = false;
        toggleColetaAmostraWrap();
        document.querySelectorAll('.partes-check').forEach(function(cb) { cb.checked = false; });
    }
    function toggleColetaAmostraWrap() {
        var n = parseInt(document.getElementById('area_numero_suspeitas').value, 10) || 0;
        var wrap = document.getElementById('areaColetaAmostraWrap');
        if (wrap) wrap.style.display = n > 0 ? '' : 'none';
        var cm = document.getElementById('area_coletar_mostra');
        var camposWrap = document.getElementById('areaColetaCamposWrap');
        if (camposWrap) camposWrap.classList.toggle('hidden', !(cm && cm.checked));
        var dataWrap = document.getElementById('areaDataAmostragemWrap');
        var dataInput = document.getElementById('area_data_amostragem');
        if (dataWrap) dataWrap.classList.toggle('hidden', !(cm && cm.checked));
        if (dataInput) {
            dataInput.required = !!(cm && cm.checked);
            if (cm && cm.checked && !dataInput.value) dataInput.value = new Date().toISOString().substring(0, 10);
        }
    }
    var hospedeirosPorPrograma = <?php echo json_encode($hospedeirosPorPrograma); ?>;
    function populateEspecie(programaId, valorAtual) {
        var sel = document.getElementById('area_especie');
        if (!sel) return;
        sel.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = programaId ? '-- Selecione --' : '-- Selecione o programa primeiro --';
        sel.appendChild(opt);
        if (programaId && hospedeirosPorPrograma[programaId]) {
            hospedeirosPorPrograma[programaId].forEach(function(nc) {
                var o = document.createElement('option');
                o.value = nc;
                o.textContent = nc;
                sel.appendChild(o);
            });
            if (valorAtual && valorAtual.trim() && !hospedeirosPorPrograma[programaId].includes(valorAtual)) {
                var leg = document.createElement('option');
                leg.value = valorAtual;
                leg.textContent = valorAtual + ' (registro anterior)';
                sel.appendChild(leg);
            }
        }
        if (valorAtual) sel.value = valorAtual;
    }
    function loadArea(data) {
        areaId.value = data.id || '';
        areaTitle.textContent = data.id ? 'Editar Área Inspecionada' : 'Nova Área Inspecionada';
        var map = {id_programa:'area_id_programa',tipo_area:'area_tipo_area',nome_local:'area_nome_local',especie:'area_especie',latitude:'area_latitude',longitude:'area_longitude',variedade:'area_variedade',material_multiplicacao:'area_material_multiplicacao',origem:'area_origem',idade_plantio:'area_idade_plantio',area_plantada:'area_area_plantada',numero_plantas:'area_numero_plantas',numero_inspecionadas:'area_numero_inspecionadas',numero_suspeitas:'area_numero_suspeitas',obs:'area_obs',identificacao_amostra:'area_identificacao_amostra',associado:'area_associado'};
        for (var k in map) {
            var el = document.getElementById(map[k]);
            if (el && data[k] !== undefined && data[k] !== null) el.value = data[k];
        }
        var elAssoc = document.getElementById('area_associado');
        if (elAssoc) elAssoc.value = (data.associado !== undefined && data.associado !== null && String(data.associado).trim() !== '') ? data.associado : (proprietarioNome || '');
        var cm = document.getElementById('area_coletar_mostra');
        if (cm) cm.checked = (data.coletar_mostra == 1 || data.coletar_mostra === true || data.coleta_amostra == 1 || data.coleta_amostra === true);
        var daEl = document.getElementById('area_data_amostragem');
        if (daEl && cm && cm.checked) daEl.value = (termoDataAmostragem || '').substring(0, 10) || new Date().toISOString().substring(0, 10);
        toggleColetaAmostraWrap();
        ['raiz','caule','peciolo','folha','flor','fruto','semente'].forEach(function(fn) {
            var cb = form ? form.querySelector('input[name="'+fn+'"]') : document.querySelector('input[name="'+fn+'"]');
            if (cb) cb.checked = (data[fn] == 1 || data[fn] === true);
        });
        var progVal = (mainProgramaSelect && mainProgramaSelect.value) || termoIdPrograma || '';
        if (progEl) progEl.value = progVal;
        populateEspecie(progVal, data.especie || '');
    }

    var progEl = document.getElementById('area_id_programa');
    var mainProgramaSelect = document.getElementById('id_programa');
    var progInit = (mainProgramaSelect && mainProgramaSelect.value) || termoIdPrograma || '';
    if (progEl) progEl.value = progInit;
    if (progInit) populateEspecie(progInit);
    if (mainProgramaSelect) {
        mainProgramaSelect.addEventListener('change', function() {
            var novoProg = (this.value || '').trim();
            if (progEl) progEl.value = novoProg;
            populateEspecie(novoProg, document.getElementById('area_especie') ? document.getElementById('area_especie').value : '');
        });
    }

    document.getElementById('area_numero_suspeitas').addEventListener('input', toggleColetaAmostraWrap);
    document.getElementById('area_numero_suspeitas').addEventListener('change', toggleColetaAmostraWrap);
    var coletaCheck = document.getElementById('area_coletar_mostra');
    var idTermo = '<?php echo addslashes($id); ?>';
    if (coletaCheck) {
        coletaCheck.addEventListener('change', function() {
            toggleColetaAmostraWrap();
            if (this.checked) {
                fetch('termo_atualizar_amostragem.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id_termo_inspecao: idTermo})
                }).then(function(r) { return r.json(); }).then(function(res) {
                    if (res.ok) {
                        if (res.data_amostragem) {
                            var da = document.getElementById('area_data_amostragem');
                            if (da) da.value = (res.data_amostragem + '').substring(0,10);
                            var daMain = document.querySelector('form#formTermo input[name="data_amostragem"]');
                            if (daMain) daMain.value = (res.data_amostragem + '').substring(0,10);
                        }
                        var tc = document.getElementById('display_termo_coleta');
                        if (tc && res.termo_coleta) tc.value = res.termo_coleta;
                    }
                }).catch(function() {});
            }
        });
    }
    toggleColetaAmostraWrap();
    
    if (btnExibir) btnExibir.addEventListener('click', function() {
        if (expand.classList.contains('hidden')) { clearForm(); showForm(); }
        else hideForm();
    });
    if (btnNova) btnNova.addEventListener('click', function() { clearForm(); showForm(); });
    if (btnCancelar) btnCancelar.addEventListener('click', hideForm);

    form.addEventListener('submit', function(e) {
        var cm = document.getElementById('area_coletar_mostra');
        if (cm && cm.checked) {
            var ident = document.getElementById('area_identificacao_amostra');
            var dataAmostragem = document.getElementById('area_data_amostragem');
            var partes = ['raiz','caule','peciolo','folha','flor','fruto','semente'];
            var hasParte = partes.some(function(fn) { var cb = form.querySelector('input[name="'+fn+'"]'); return cb && cb.checked; });
            if (!ident || !ident.value.trim()) {
                e.preventDefault();
                alert('Quando "Coletar amostra" está marcado, o campo "Identificação da Amostra" é obrigatório.');
                if (ident) ident.focus();
                return false;
            }
            if (!hasParte) {
                e.preventDefault();
                alert('Quando "Coletar amostra" está marcado, selecione ao menos uma opção em Partes Coletadas (raiz, caule, peciolo, folha, flor, fruto ou semente).');
                return false;
            }
            if (!dataAmostragem || !dataAmostragem.value.trim()) {
                e.preventDefault();
                alert('Quando "Coletar amostra" está marcado, o campo "Data Amostragem" é obrigatório.');
                if (dataAmostragem) dataAmostragem.focus();
                return false;
            }
        }
    });

    var formTermo = document.getElementById('formTermo');
    var idUsuarioOriginal = <?php echo json_encode($termo['id_usuario'] ?? ''); ?>;
    var termoTemColeta = <?php echo json_encode($termo_tem_coleta); ?>;
    if (formTermo) {
        formTermo.addEventListener('submit', function(e) {
            var idUsuarioAtual = (document.getElementById('id_usuario') && document.getElementById('id_usuario').value) || '';
            if (idUsuarioOriginal !== idUsuarioAtual && idUsuarioAtual.trim() !== '') {
                var msg = 'Ao alterar o responsável pela ação:\n\n' +
                    '• O número do Termo de Inspeção será atualizado.\n';
                if (termoTemColeta) {
                    msg += '• Como há coleta de amostra, o número do Termo de Coleta também será atualizado.\n';
                }
                msg += '\nConfirma a alteração do responsável?';
                if (!confirm(msg)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    document.querySelectorAll('.area-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('a')) return;
            var d = this.getAttribute('data-area');
            if (d) { loadArea(JSON.parse(d)); showForm(); }
        });
    });
    
    var expandAreaId = <?php echo $expand_area_id ? json_encode($expand_area_id) : 'null'; ?>;
    if (expandAreaId) {
        document.querySelectorAll('.area-row').forEach(function(row) {
            var d = row.getAttribute('data-area');
            if (d) try {
                var data = JSON.parse(d);
                if (String(data.id) === String(expandAreaId)) { loadArea(data); showForm(); }
            } catch(_) {}
        });
    }
})();
</script>

<?php include '../includes/propriedade_create_modal.php'; ?>
<?php include '../includes/coord_converter_area.php'; ?>
<?php include '../includes/footer.php'; ?>
