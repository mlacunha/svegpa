<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Novo Termo de Inspeção";

$database = new Database();
$db = $database->getConnection();

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

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$draft = $_SESSION['termo_draft'] ?? null;
$areas = $_SESSION['termo_areas'] ?? [];

$proprietario_nome = '';
if ($step === 2 && !empty($draft['id_propriedade'])) {
    $stmt_prop = $db->prepare("SELECT pr.nome FROM propriedades p LEFT JOIN produtores pr ON p.id_proprietario = pr.id WHERE p.id = ?");
    $stmt_prop->execute([$draft['id_propriedade']]);
    $proprietario_nome = trim($stmt_prop->fetchColumn() ?: '');
}

// Cancelar rascunho
if (isset($_GET['cancel'])) {
    unset($_SESSION['termo_draft'], $_SESSION['termo_areas']);
    header('Location: index.php');
    exit;
}

// --- POST: Etapa 1 - Dados do pai ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST['action'])) {
    $municipio = !empty($_POST['municipio']) ? sanitizeInput($_POST['municipio']) : null;
    $data_inspecao = !empty($_POST['data_inspecao']) ? sanitizeInput($_POST['data_inspecao']) : null;
    $data_amostragem = !empty($_POST['data_amostragem']) ? sanitizeInput($_POST['data_amostragem']) : null;
    $id_usuario = !empty($_POST['id_usuario']) ? sanitizeInput($_POST['id_usuario']) : null;
    $id_propriedade = !empty($_POST['id_propriedade']) ? sanitizeInput($_POST['id_propriedade']) : null;
    $id_auxiliar = !empty($_POST['id_auxiliar']) ? sanitizeInput($_POST['id_auxiliar']) : null;
    $id_programa = !empty($_POST['id_programa']) ? sanitizeInput($_POST['id_programa']) : null;

    if (empty($municipio)) {
        $error = "O Município é obrigatório.";
    } elseif (empty($id_usuario)) {
        $error = "O Técnico Responsável é obrigatório para gerar o número do Termo de Inspeção.";
    } elseif (empty($id_programa)) {
        $error = "O Programa é obrigatório.";
    } else {
        $_SESSION['termo_draft'] = [
            'municipio' => $municipio,
            'data_inspecao' => $data_inspecao,
            'data_amostragem' => $data_amostragem,
            'id_usuario' => $id_usuario,
            'id_propriedade' => $id_propriedade,
            'id_auxiliar' => $id_auxiliar,
            'id_programa' => $id_programa,
        ];
        if (!isset($_SESSION['termo_areas'])) $_SESSION['termo_areas'] = [];
        header('Location: create.php?step=2');
        exit;
    }
}

// --- POST: Etapa 2 - Ações (adicionar área, remover, salvar tudo) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['action']) && $draft) {
    $action = $_POST['action'];

    if ($action === 'add_area') {
        $id_programa = $draft['id_programa'] ?? null;
        $numero_plantas = isset($_POST['numero_plantas']) && $_POST['numero_plantas'] !== '' ? (int)$_POST['numero_plantas'] : null;
        $numero_inspecionadas = isset($_POST['numero_inspecionadas']) && $_POST['numero_inspecionadas'] !== '' ? (int)$_POST['numero_inspecionadas'] : null;
        $lat_ok = isset($_POST['latitude']) && trim($_POST['latitude']) !== '';
        $lon_ok = isset($_POST['longitude']) && trim($_POST['longitude']) !== '';
        $coletar = isset($_POST['coletar_mostra']) && $_POST['coletar_mostra'] === '1';
        $coleta_ok = true;
        if ($coletar) {
            $ident_ok = !empty(trim($_POST['identificacao_amostra'] ?? ''));
            $partes_ok = false;
            foreach (['raiz','caule','peciolo','folha','flor','fruto','semente'] as $fn) {
                if (isset($_POST[$fn]) && $_POST[$fn] === '1') { $partes_ok = true; break; }
            }
            $coleta_ok = $ident_ok && $partes_ok;
        }
        $data_amostragem_ok = !$coletar || !empty(trim($_POST['data_amostragem'] ?? ''));
        $especie_raw = !empty($_POST['especie']) ? sanitizeInput($_POST['especie']) : null;
        $especie_ok = true;
        if ($especie_raw && $id_programa) {
            $stmt_esp = $db->prepare("SELECT 1 FROM hospedeiros WHERE id_programa = ? AND nome_cientifico = ?");
            $stmt_esp->execute([$id_programa, $especie_raw]);
            $especie_ok = (bool) $stmt_esp->fetch();
        }
        if ($id_programa && $numero_plantas !== null && $numero_inspecionadas !== null && $lat_ok && $lon_ok && $coleta_ok && $data_amostragem_ok && $especie_ok) {
            $area = [
                'tipo_area' => !empty($_POST['tipo_area']) ? sanitizeInput($_POST['tipo_area']) : null,
                'nome_local' => !empty($_POST['nome_local']) ? sanitizeInput($_POST['nome_local']) : null,
                'especie' => !empty($_POST['especie']) ? sanitizeInput($_POST['especie']) : null,
                'latitude' => isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval(str_replace(',', '.', $_POST['latitude'])) : null,
                'longitude' => isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval(str_replace(',', '.', $_POST['longitude'])) : null,
                'variedade' => !empty($_POST['variedade']) ? sanitizeInput($_POST['variedade']) : null,
                'material_multiplicacao' => !empty($_POST['material_multiplicacao']) ? sanitizeInput($_POST['material_multiplicacao']) : null,
                'origem' => !empty($_POST['origem']) ? sanitizeInput($_POST['origem']) : null,
                'idade_plantio' => isset($_POST['idade_plantio']) && $_POST['idade_plantio'] !== '' ? floatval(str_replace(',', '.', $_POST['idade_plantio'])) : null,
                'area_plantada' => isset($_POST['area_plantada']) && $_POST['area_plantada'] !== '' ? floatval(str_replace(',', '.', $_POST['area_plantada'])) : null,
                'numero_plantas' => $numero_plantas,
                'numero_inspecionadas' => $numero_inspecionadas,
                'numero_suspeitas' => isset($_POST['numero_suspeitas']) && $_POST['numero_suspeitas'] !== '' ? (int)$_POST['numero_suspeitas'] : null,
                'coletar_mostra' => isset($_POST['coletar_mostra']) && $_POST['coletar_mostra'] === '1' ? 1 : 0,
                'obs' => !empty($_POST['obs']) ? sanitizeInput($_POST['obs']) : '',
                'identificacao_amostra' => !empty($_POST['identificacao_amostra']) ? sanitizeInput($_POST['identificacao_amostra']) : null,
                'resultado' => (isset($_POST['coletar_mostra']) && $_POST['coletar_mostra'] === '1') ? 'Suspeita' : 'Normal',
                'associado' => !empty($_POST['associado']) ? sanitizeInput($_POST['associado']) : null,
                'raiz' => isset($_POST['raiz']) && $_POST['raiz'] === '1' ? 1 : 0,
                'caule' => isset($_POST['caule']) && $_POST['caule'] === '1' ? 1 : 0,
                'peciolo' => isset($_POST['peciolo']) && $_POST['peciolo'] === '1' ? 1 : 0,
                'folha' => isset($_POST['folha']) && $_POST['folha'] === '1' ? 1 : 0,
                'flor' => isset($_POST['flor']) && $_POST['flor'] === '1' ? 1 : 0,
                'fruto' => isset($_POST['fruto']) && $_POST['fruto'] === '1' ? 1 : 0,
                'semente' => isset($_POST['semente']) && $_POST['semente'] === '1' ? 1 : 0,
            ];
            $edit_i = isset($_POST['area_index']) && $_POST['area_index'] !== '' ? (int)$_POST['area_index'] : null;
            if ($edit_i !== null && isset($areas[$edit_i])) {
                $areas[$edit_i] = $area;
            } else {
                $areas[] = $area;
            }
            if ($coletar && !empty(trim($_POST['data_amostragem'] ?? ''))) {
                $_SESSION['termo_draft']['data_amostragem'] = sanitizeInput($_POST['data_amostragem']);
            }
            $_SESSION['termo_areas'] = $areas;
            header('Location: create.php?step=2');
            exit;
        }
        $error = "Nº plantas, Nº inspecionadas e coordenadas (latitude/longitude) são obrigatórios para a área. O programa é definido na Etapa 1.";
        if (!$especie_ok) {
            $error = "A espécie informada deve estar cadastrada nos hospedeiros do programa selecionado.";
        } elseif ($id_programa && $numero_plantas !== null && $numero_inspecionadas !== null && $lat_ok && $lon_ok && $coletar && !$coleta_ok) {
            $error = "Quando 'Coletar amostra' está marcado, o campo 'Identificação da Amostra' é obrigatório e deve ser selecionada ao menos uma opção em Partes Coletadas.";
        }
        if ($id_programa && $numero_plantas !== null && $numero_inspecionadas !== null && $lat_ok && $lon_ok && $coletar && $coleta_ok && !$data_amostragem_ok) {
            $error = "Quando 'Coletar amostra' está marcado, o campo 'Data Amostragem' é obrigatório.";
        }
    }

    if ($action === 'remove_area') {
        $i = isset($_POST['area_index']) ? (int)$_POST['area_index'] : -1;
        if ($i >= 0 && isset($areas[$i])) {
            array_splice($areas, $i, 1);
            $_SESSION['termo_areas'] = $areas;
        }
        header('Location: create.php?step=2');
        exit;
    }

    if ($action === 'final_save' && $draft) {
        $data_inspecao = $draft['data_inspecao'] ?? null;
        $data_amostragem = $draft['data_amostragem'] ?? null;
        $tem_coleta_amostra = false;
        foreach ($areas as $a) {
            if (!empty($a['coletar_mostra']) || !empty($a['coleta_amostra'])) { $tem_coleta_amostra = true; break; }
        }
        $coord_ok = true;
        foreach ($areas as $a) {
            $lat = $a['latitude'] ?? null;
            $lon = $a['longitude'] ?? null;
            if ($lat === null || $lat === '' || $lon === null || $lon === '') {
                $coord_ok = false;
                break;
            }
        }
        $coleta_ok = true;
        foreach ($areas as $a) {
            if (!empty($a['coletar_mostra']) || !empty($a['coleta_amostra'])) {
                $ident_ok = !empty(trim($a['identificacao_amostra'] ?? ''));
                $partes_ok = !empty($a['raiz']) || !empty($a['caule']) || !empty($a['peciolo']) || !empty($a['folha']) || !empty($a['flor']) || !empty($a['fruto']) || !empty($a['semente']);
                if (!$ident_ok || !$partes_ok) { $coleta_ok = false; break; }
            }
        }
        $id_programa_draft = $draft['id_programa'] ?? null;
        if (!$id_programa_draft) {
            $error = "O Programa é obrigatório. Volte à Etapa 1 para selecionar.";
        } elseif (!$coord_ok) {
            $error = "Todas as áreas devem ter coordenadas (latitude e longitude) preenchidas.";
        } elseif (!$coleta_ok) {
            $error = "Quando 'Coletar amostra' está marcado, o campo 'Identificação da Amostra' é obrigatório e deve ser selecionada ao menos uma opção em Partes Coletadas.";
        } else {
        if ($tem_coleta_amostra && empty($data_amostragem)) {
            $data_amostragem = date('Y-m-d');
        }
        $id_usuario = $draft['id_usuario'] ?? null;
        $id_propriedade = $draft['id_propriedade'] ?? null;
        $id_auxiliar = $draft['id_auxiliar'] ?? null;
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
        try {
            $db->beginTransaction();
            $db->prepare("INSERT INTO controle_sequencial (login, ano, seq_ti, seq_tc) VALUES (:login, :ano, 0, 0) ON DUPLICATE KEY UPDATE login = login")
                ->execute([':login' => $id_usuario, ':ano' => $ano_str]);
            $db->prepare("UPDATE controle_sequencial SET seq_ti = seq_ti + 1, seq_tc = seq_tc + 1 WHERE login = :login AND ano = :ano")
                ->execute([':login' => $id_usuario, ':ano' => $ano_str]);
            $stmt_get = $db->prepare("SELECT seq_ti, seq_tc FROM controle_sequencial WHERE login = :login AND ano = :ano");
            $stmt_get->execute([':login' => $id_usuario, ':ano' => $ano_str]);
            $row = $stmt_get->fetch(PDO::FETCH_ASSOC);
            $seq_ti = (int) ($row['seq_ti'] ?? 0);
            $seq_tc = (int) ($row['seq_tc'] ?? 0);
            if ($seq_ti < 1) throw new PDOException("Falha ao obter número sequencial.");
            $termo_inspecao_val = $seq_ti . '/' . $matricula . '/' . $ano_str;
            $termo_coleta_val = $seq_tc . '/' . $matricula . '/' . $ano_str;
            $id_termo = bin2hex(random_bytes(10));
            $id_programa = $draft['id_programa'] ?? null;
            $stmt = $db->prepare("INSERT INTO termo_inspecao (id, data_inspecao, data_amostragem, termo_inspecao, termo_coleta, id_usuario, id_auxiliar, id_propriedade, id_programa) VALUES (:id, :data_inspecao, :data_amostragem, :termo_inspecao, :termo_coleta, :id_usuario, :id_auxiliar, :id_propriedade, :id_programa)");
            $stmt->bindValue(':id', $id_termo);
            $stmt->bindValue(':data_inspecao', $data_inspecao);
            $stmt->bindValue(':data_amostragem', $data_amostragem ?: null);
            $stmt->bindValue(':termo_inspecao', $termo_inspecao_val);
            $stmt->bindValue(':termo_coleta', $termo_coleta_val);
            $stmt->bindValue(':id_usuario', $id_usuario);
            $stmt->bindValue(':id_auxiliar', $id_auxiliar);
            $stmt->bindValue(':id_propriedade', $id_propriedade);
            $stmt->bindValue(':id_programa', $id_programa);
            $stmt->execute();
            foreach ($areas as $a) {
                $area_uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
                $stmt_a = $db->prepare("INSERT INTO area_inspecionada (id, id_termo_inspecao, tipo_area, nome_local, especie, variedade, material_multiplicacao, origem, idade_plantio, area_plantada, numero_plantas, numero_inspecionadas, numero_suspeitas, coletar_mostra, obs, identificacao_amostra, resultado, associado, latitude, longitude, raiz, caule, peciolo, folha, flor, fruto, semente) VALUES (:id, :id_termo, :tipo_area, :nome_local, :especie, :variedade, :material_multiplicacao, :origem, :idade_plantio, :area_plantada, :numero_plantas, :numero_inspecionadas, :numero_suspeitas, :coletar_mostra, :obs, :identificacao_amostra, :resultado, :associado, :latitude, :longitude, :raiz, :caule, :peciolo, :folha, :flor, :fruto, :semente)");
                $stmt_a->bindValue(':id', $area_uuid);
                $stmt_a->bindValue(':id_termo', $id_termo);
                $stmt_a->bindValue(':tipo_area', $a['tipo_area']);
                $stmt_a->bindValue(':nome_local', $a['nome_local']);
                $stmt_a->bindValue(':especie', $a['especie']);
                $stmt_a->bindValue(':variedade', $a['variedade']);
                $stmt_a->bindValue(':material_multiplicacao', $a['material_multiplicacao']);
                $stmt_a->bindValue(':origem', $a['origem']);
                $stmt_a->bindValue(':idade_plantio', $a['idade_plantio']);
                $stmt_a->bindValue(':area_plantada', $a['area_plantada']);
                $stmt_a->bindValue(':numero_plantas', $a['numero_plantas']);
                $stmt_a->bindValue(':numero_inspecionadas', $a['numero_inspecionadas']);
                $stmt_a->bindValue(':numero_suspeitas', $a['numero_suspeitas'] ?? 0);
                $stmt_a->bindValue(':coletar_mostra', $a['coletar_mostra'] ?? $a['coleta_amostra'] ?? 0);
                $stmt_a->bindValue(':obs', $a['obs'] ?? '');
                $stmt_a->bindValue(':identificacao_amostra', $a['identificacao_amostra'] ?? null);
                $stmt_a->bindValue(':resultado', $a['resultado'] ?? (!empty($a['coletar_mostra']) || !empty($a['coleta_amostra']) ? 'Suspeita' : 'Normal'));
                $stmt_a->bindValue(':associado', $a['associado'] ?? null);
                $stmt_a->bindValue(':latitude', $a['latitude'] ?? null);
                $stmt_a->bindValue(':longitude', $a['longitude'] ?? null);
                $stmt_a->bindValue(':raiz', $a['raiz'] ?? 0);
                $stmt_a->bindValue(':caule', $a['caule'] ?? 0);
                $stmt_a->bindValue(':peciolo', $a['peciolo'] ?? 0);
                $stmt_a->bindValue(':folha', $a['folha'] ?? 0);
                $stmt_a->bindValue(':flor', $a['flor'] ?? 0);
                $stmt_a->bindValue(':fruto', $a['fruto'] ?? 0);
                $stmt_a->bindValue(':semente', $a['semente'] ?? 0);
                $stmt_a->execute();
            }
            $db->commit();
            unset($_SESSION['termo_draft'], $_SESSION['termo_areas']);
            $redirect = $tem_coleta_amostra ? 'edit.php?id=' . urlencode($id_termo) . '&success=created&expand=1' : 'index.php?success=created';
            header('Location: ' . $redirect);
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Erro ao salvar: " . $e->getMessage();
        }
        }
    }
}

$programasById = [];
foreach ($programas as $p) $programasById[$p['id']] = $p['nome'];

if ($step === 2 && !$draft) {
    header('Location: create.php');
    exit;
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
        <div class="flex items-center gap-4 flex-wrap">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-plus-circle mr-2"></i>Novo Termo de Inspeção
                <?php if ($step === 2): ?><span class="text-base font-normal text-gray-500">— Etapa 2 de 2</span><?php endif; ?>
            </h2>
            <?php if ($step === 2): $selected_prop = $draft['id_propriedade'] ?? null; $selected_municipio = $draft['municipio'] ?? ''; ?>
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="font-medium">Município:</span>
                <span><?php echo htmlspecialchars($draft['municipio'] ?? '-'); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <a href="<?php echo $step === 2 ? 'create.php?cancel=1' : 'index.php'; ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i><?php echo $step === 2 ? 'Desistir' : 'Voltar'; ?>
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

<?php if ($step === 1): ?>
    <form method="POST" class="space-y-6">
        <p class="text-gray-600 mb-4">Preencha os dados básicos do termo. Selecione o município primeiro para filtrar as propriedades. Termo Inspeção e Termo Coleta são gerados automaticamente no formato Sequencial/Matrícula/Ano.</p>
        <div class="form-row-horizontal grid grid-cols-1 md:grid md:grid-cols-6 w-full gap-4 min-w-0 items-end">
            <?php $selected_prop = $draft['id_propriedade'] ?? $_POST['id_propriedade'] ?? null; $selected_municipio = $draft['municipio'] ?? $_POST['municipio'] ?? ''; include '../includes/termo_inspecao_propriedade_select.php'; ?>
            <div class="form-group min-w-0">
                <label for="id_programa">Programa *</label>
                <select id="id_programa" name="id_programa" class="form-control w-full" required>
                    <option value="">-- Selecione --</option>
                    <?php foreach ($programas as $prog): $sel = ($draft['id_programa'] ?? $_POST['id_programa'] ?? '') === $prog['id']; ?>
                    <option value="<?php echo htmlspecialchars($prog['id']); ?>" <?php echo $sel ? 'selected' : ''; ?>><?php echo htmlspecialchars($prog['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group min-w-0">
                <label for="data_inspecao">Data Inspeção</label>
                <input type="date" id="data_inspecao" name="data_inspecao" class="form-control w-full" value="<?php echo htmlspecialchars($draft['data_inspecao'] ?? $_POST['data_inspecao'] ?? date('Y-m-d')); ?>">
            </div>
            <input type="hidden" id="data_amostragem" name="data_amostragem" value="<?php echo htmlspecialchars($draft['data_amostragem'] ?? $_POST['data_amostragem'] ?? ''); ?>">
            <div class="form-group min-w-0">
                <label for="id_usuario">Técnico Responsável *</label>
                <select id="id_usuario" name="id_usuario" class="form-control w-full" required>
                    <option value="">-- Selecione o técnico --</option>
                    <?php foreach ($usuarios as $u): $disp = trim($u['name'] ?? '') ?: $u['login']; $sel = ($draft['id_usuario'] ?? $_POST['id_usuario'] ?? '') === $u['login']; ?>
                    <option value="<?php echo htmlspecialchars($u['login']); ?>" <?php echo $sel ? 'selected' : ''; ?>><?php echo htmlspecialchars($disp); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group min-w-0">
                <label for="id_auxiliar">Auxiliar (opcional)</label>
                <select id="id_auxiliar" name="id_auxiliar" class="form-control w-full">
                    <option value="">-- Nenhum --</option>
                    <?php foreach ($usuarios as $u): $disp = trim($u['name'] ?? '') ?: $u['login']; $sel = ($draft['id_auxiliar'] ?? $_POST['id_auxiliar'] ?? '') === $u['login']; ?>
                    <option value="<?php echo htmlspecialchars($u['login']); ?>" <?php echo $sel ? 'selected' : ''; ?>><?php echo htmlspecialchars($disp); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-arrow-right mr-2"></i>Próximo: Adicionar áreas
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>

<?php else: ?>
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Dados do termo (Etapa 1)</h3>
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600">
            <span><strong>Município:</strong> <?php echo htmlspecialchars($draft['municipio'] ?? '-'); ?></span>
            <span><strong>Programa:</strong> <?php
                $pid = $draft['id_programa'] ?? '';
                $pnom = '-';
                foreach ($programas as $p) { if ($p['id'] === $pid) { $pnom = $p['nome']; break; } }
                echo htmlspecialchars($pnom);
            ?></span>
            <span><strong>Data Inspeção:</strong> <?php echo htmlspecialchars($draft['data_inspecao'] ?? '-'); ?></span>
            <span><strong>Propriedade:</strong> <?php 
                $pid = $draft['id_propriedade'] ?? '';
                $pnome = '-';
                foreach ($propriedades as $p) {
                    if ($p['id'] === $pid) {
                        $nc = trim($p['n_cadastro']??'');
                        $pnome = $nc ? ($nc . ' - ' . ($p['nome'] ?? '')) : ($p['nome'] ?? '-'); break;
                    }
                }
                echo htmlspecialchars($pnome);
            ?></span>
            <span><strong>Técnico:</strong> <?php 
                $uid = $draft['id_usuario'] ?? '';
                $unome = $uid;
                foreach ($usuarios as $u) { if ($u['login'] === $uid) { $unome = trim($u['name'] ?? '') ?: $u['login']; break; } }
                echo htmlspecialchars($unome);
            ?></span>
            <?php if (!empty($draft['id_auxiliar'])): $aid = $draft['id_auxiliar']; $anome = $aid; foreach ($usuarios as $u) { if ($u['login'] === $aid) { $anome = trim($u['name'] ?? '') ?: $u['login']; break; } } ?>
            <span><strong>Auxiliar:</strong> <?php echo htmlspecialchars($anome); ?></span>
            <?php endif; ?>
        </div>
        <a href="create.php" class="text-primary hover:underline text-sm mt-2 inline-block">Alterar dados do termo</a>
    </div>

    <hr class="my-6 border-gray-200">

    <div class="mb-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-map mr-2"></i>Áreas Inspecionadas</h3>
        <button type="button" id="btnExibirAreaForm" class="btn-secondary" title="Exibir formulário">
            <i class="fas fa-eye mr-2"></i>Exibir
        </button>
    </div>

    <div id="areaFormExpand" class="hidden mb-6">
        <div class="card p-6">
            <h4 class="text-md font-semibold text-gray-700 mb-4" id="areaFormTitle">Nova Área Inspecionada</h4>
            <form method="POST" id="formArea">
                <input type="hidden" name="action" value="add_area">
                <input type="hidden" name="area_index" id="area_index" value="">
                <div class="space-y-4 mb-4">
                    <div class="grid grid-cols-1 md:grid gap-4 md:[grid-template-columns:20%_50%] min-w-0">
                        <input type="hidden" id="area_id_programa" value="<?php echo htmlspecialchars($draft['id_programa'] ?? ''); ?>">
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
                                    <input type="date" id="area_data_amostragem" name="data_amostragem" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>">
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
                    <button type="submit" class="btn-primary"><i class="fas fa-plus mr-2"></i>Adicionar à lista</button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex justify-between items-center mb-3">
        <span class="text-sm text-gray-600"><?php echo count($areas); ?> área(s) na lista</span>
        <button type="button" id="btnNovaArea" class="btn-primary text-sm">
            <i class="fas fa-plus mr-2"></i>Nova Área
        </button>
    </div>
    <div class="table-container mb-6">
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
                <tr><td colspan="9" class="py-4 px-3 text-center text-gray-500">Nenhuma área adicionada. Clique em "Nova Área" para adicionar. Você pode salvar mesmo sem áreas.</td></tr>
                <?php else: ?>
                <?php foreach ($areas as $i => $a): 
                    $aData = array_merge($a, ['id_programa' => $draft['id_programa'] ?? '']);
                    $progNome = $programasById[$draft['id_programa'] ?? ''] ?? '-'; ?>
                <tr class="border-b hover:bg-gray-50 cursor-pointer area-row" data-index="<?php echo $i; ?>" data-area="<?php echo htmlspecialchars(json_encode($aData)); ?>">
                    <td class="py-2 px-3"><?php echo htmlspecialchars($progNome); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars(trim(($a['tipo_area'] ?? '') . ' ' . ($a['nome_local'] ?? '')) ?: '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['especie'] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['numero_plantas'] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['numero_inspecionadas'] ?? '-'); ?></td>
                    <td class="py-2 px-3"><?php $aColeta = !empty($a['coletar_mostra']) || !empty($a['coleta_amostra']); echo $aColeta ? htmlspecialchars($a['identificacao_amostra'] ?? '-') : '-'; ?></td>
                    <td class="py-2 px-3"><?php echo $aColeta ? 'Sim' : '-'; ?></td>
                    <td class="py-2 px-3"><?php echo htmlspecialchars($a['resultado'] ?? ($aColeta ? 'Suspeita' : 'Normal')); ?></td>
                    <td class="py-2 px-3" onclick="event.stopPropagation();">
                        <form method="POST" class="inline" onsubmit="return confirm('Remover esta área da lista?');">
                            <input type="hidden" name="action" value="remove_area">
                            <input type="hidden" name="area_index" value="<?php echo $i; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Remover"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
        <form method="POST" class="inline">
            <input type="hidden" name="action" value="final_save">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Concluir e Salvar tudo
            </button>
        </form>
        <a href="create.php?cancel=1" class="btn-secondary">
            <i class="fas fa-times mr-2"></i>Desistir
        </a>
    </div>
</div>

<script>
(function() {
    var proprietarioNome = <?php echo json_encode($proprietario_nome); ?>;
    var expand = document.getElementById('areaFormExpand');
    var btnExibir = document.getElementById('btnExibirAreaForm');
    var btnNova = document.getElementById('btnNovaArea');
    var btnCancelar = document.getElementById('btnCancelarArea');
    var form = document.getElementById('formArea');
    var areaIndex = document.getElementById('area_index');
    var areaTitle = document.getElementById('areaFormTitle');

    function showForm() { expand.classList.remove('hidden'); if (btnExibir) btnExibir.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Ocultar'; }
    function hideForm() { expand.classList.add('hidden'); if (btnExibir) btnExibir.innerHTML = '<i class="fas fa-eye mr-2"></i>Exibir'; }
    var draftIdPrograma = <?php echo json_encode($draft['id_programa'] ?? ''); ?>;
    function clearForm() {
        areaIndex.value = '';
        areaTitle.textContent = 'Nova Área Inspecionada';
        var progEl = document.getElementById('area_id_programa');
        if (progEl) progEl.value = draftIdPrograma || '';
        populateEspecie(draftIdPrograma || '');
        ['area_tipo_area','area_nome_local','area_latitude','area_longitude','area_variedade','area_material_multiplicacao','area_origem','area_idade_plantio','area_area_plantada','area_numero_plantas','area_numero_inspecionadas','area_numero_suspeitas','area_obs','area_identificacao_amostra','area_data_amostragem'].forEach(function(id) {
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
            if (valorAtual && valorAtual.trim() && hospedeirosPorPrograma[programaId].indexOf(valorAtual) < 0) {
                var leg = document.createElement('option');
                leg.value = valorAtual;
                leg.textContent = valorAtual + ' (registro anterior)';
                sel.appendChild(leg);
            }
        }
        if (valorAtual) sel.value = valorAtual;
    }
    var draftDataAmostragem = <?php echo json_encode($draft['data_amostragem'] ?? ''); ?>;
    function loadArea(data, index) {
        areaIndex.value = index !== undefined ? index : '';
        areaTitle.textContent = index !== undefined ? 'Editar Área Inspecionada' : 'Nova Área Inspecionada';
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
        if (daEl && (cm && cm.checked)) daEl.value = (draftDataAmostragem || '').substring(0, 10) || new Date().toISOString().substring(0, 10);
        toggleColetaAmostraWrap();
        ['raiz','caule','peciolo','folha','flor','fruto','semente'].forEach(function(fn) {
            var cb = form ? form.querySelector('input[name="'+fn+'"]') : document.querySelector('input[name="'+fn+'"]');
            if (cb) cb.checked = (data[fn] == 1 || data[fn] === true);
        });
        populateEspecie(draftIdPrograma || (data.id_programa || ''), data.especie || '');
    }
    var progEl = document.getElementById('area_id_programa');
    if (progEl) {
        var progVal = progEl.value || (progEl.getAttribute && progEl.getAttribute('value'));
        if (progVal) populateEspecie(progVal);
    }
    var numSuspeitas = document.getElementById('area_numero_suspeitas');
    if (numSuspeitas) { numSuspeitas.addEventListener('input', toggleColetaAmostraWrap); numSuspeitas.addEventListener('change', toggleColetaAmostraWrap); }
    var coletaCheckCreate = document.getElementById('area_coletar_mostra');
    if (coletaCheckCreate) coletaCheckCreate.addEventListener('change', toggleColetaAmostraWrap);
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

    document.querySelectorAll('.area-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('form') || e.target.closest('button')) return;
            var d = this.getAttribute('data-area');
            var i = this.getAttribute('data-index');
            if (d) { loadArea(JSON.parse(d), i); showForm(); }
        });
    });
})();
</script>
<?php endif; ?>
</div>

<?php include '../includes/propriedade_create_modal.php'; ?>
<?php include '../includes/coord_converter_area.php'; ?>
<?php include '../includes/footer.php'; ?>
