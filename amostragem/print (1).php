<?php
/**
 * Impressão do Termo de Coleta de Amostras (TCA) - layout oficial.
 * Recebe id (termo_inspecao). Sem logo no topo (conforme solicitado).
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("
    SELECT t.id, t.termo_coleta, t.data_amostragem, t.id_usuario, t.id_auxiliar, t.id_propriedade,
           p.nome as propriedade_nome, p.municipio as propriedade_municipio, p.UF as propriedade_uf,
           e.nome as estado_nome,
           u.name as usuario_nome, fu.nome as usuario_formacao, cr.nome as usuario_cargo,
           o.nome as usuario_orgao_nome, o.sigla as usuario_orgao_sigla,
           un.nome as usuario_unidade_nome,
           COALESCE(NULLIF(TRIM(u.matricula),''), u.login) as usuario_matricula,
           au.name as auxiliar_nome, fa.nome as auxiliar_formacao, ca.nome as auxiliar_cargo,
           oa.nome as auxiliar_orgao_nome, oa.sigla as auxiliar_orgao_sigla,
           COALESCE(NULLIF(TRIM(au.matricula),''), au.login) as auxiliar_matricula
    FROM termo_inspecao t
    LEFT JOIN propriedades p ON t.id_propriedade = p.id
    LEFT JOIN estados e ON e.sigla = p.UF
    LEFT JOIN sec_users u ON t.id_usuario = u.login
    LEFT JOIN formacao fu ON u.formacao = fu.id
    LEFT JOIN cargos cr ON u.role = cr.id
    LEFT JOIN orgaos o ON u.orgao = o.id
    LEFT JOIN unidades un ON u.unidade = un.id
    LEFT JOIN sec_users au ON t.id_auxiliar = au.login
    LEFT JOIN formacao fa ON au.formacao = fa.id
    LEFT JOIN cargos ca ON au.role = ca.id
    LEFT JOIN orgaos oa ON au.orgao = oa.id
    WHERE t.id = ? AND t.termo_coleta IS NOT NULL AND TRIM(t.termo_coleta) != ''
");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
    header('Location: index.php');
    exit;
}

$stmt_a = $db->prepare("
    SELECT a.*, pr.nome as programa_nome, pr.nome_cientifico as programa_nome_cientifico,
           COALESCE(a.nome_local, a.tipo_area, CONCAT('Área ', a.id)) as area_nome
    FROM area_inspecionada a
    LEFT JOIN termo_inspecao t ON a.id_termo_inspecao = t.id
    LEFT JOIN programas pr ON t.id_programa = pr.id
    WHERE a.id_termo_inspecao = ? AND a.coletar_mostra = 1
    ORDER BY a.id
");
$stmt_a->execute([$id]);
$areas = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

function partesColetadas($a) {
    $partes = ['raiz'=>'Raiz','caule'=>'Caule','peciolo'=>'Peciolos','folha'=>'Folhas','flor'=>'Flores','fruto'=>'Frutos','semente'=>'Sementes'];
    $sel = [];
    foreach ($partes as $k => $label) {
        if (!empty($a[$k])) $sel[] = $label;
    }
    return implode(', ', $sel);
}

$locais = array_map(function($a) { return $a['area_nome'] ?? ''; }, $areas);
$localColeta = implode(', ', array_filter($locais));
$municipioUf = trim(($t['propriedade_municipio'] ?? '') . ($t['propriedade_uf'] ? ' - ' . $t['propriedade_uf'] : ''));
$estadoNome = $t['estado_nome'] ?? $t['propriedade_uf'] ?? '';
$municipioNome = $t['propriedade_municipio'] ?? '';

$dataAmostragem = !empty($t['data_amostragem']) && $t['data_amostragem'] != '0000-00-00' ? $t['data_amostragem'] : null;
$dia = $dataAmostragem ? (int)date('d', strtotime($dataAmostragem)) : '';
$mesNum = $dataAmostragem ? (int)date('n', strtotime($dataAmostragem)) : 0;
$ano = $dataAmostragem ? date('Y', strtotime($dataAmostragem)) : '';
$meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$mes = $mesNum ? ($meses[$mesNum] ?? '') : '';

$partesUnicas = [];
foreach ($areas as $a) {
    $p = partesColetadas($a);
    if ($p) {
        foreach (explode(', ', $p) as $pt) {
            if ($pt && !in_array($pt, $partesUnicas)) $partesUnicas[] = $pt;
        }
    }
}
$partesTexto = count($partesUnicas) > 0 ? implode(', ', $partesUnicas) : 'conforme discriminado';

$programaNome = !empty($areas[0]['programa_nome']) ? $areas[0]['programa_nome'] : 'conforme discriminado a seguir';
$responsavel = trim($t['usuario_nome'] ?? '') ?: ($t['id_usuario'] ?? '');
$formacaoResp = trim($t['usuario_formacao'] ?? '');
$cargoResp = trim($t['usuario_cargo'] ?? '');
$orgaoResp = trim($t['usuario_orgao_nome'] ?? '');
$orgaoSiglaResp = trim($t['usuario_orgao_sigla'] ?? '');
$unidadeResp = trim($t['usuario_unidade_nome'] ?? '');
$matriculaResp = $t['usuario_matricula'] ?? '';

$temAuxiliar = !empty(trim($t['auxiliar_nome'] ?? ''));
$auxiliar = trim($t['auxiliar_nome'] ?? '');
$formacaoAux = trim($t['auxiliar_formacao'] ?? '');
$cargoAux = trim($t['auxiliar_cargo'] ?? '');
$orgaoAux = trim($t['auxiliar_orgao_nome'] ?? '');
$orgaoSiglaAux = trim($t['auxiliar_orgao_sigla'] ?? '');
$matriculaAux = $t['auxiliar_matricula'] ?? '';

$narrativaAux = '';
if ($temAuxiliar) {
    $prefixo = $formacaoAux ? $formacaoAux . ' ' : '';
    $auxCompleto = $prefixo . $auxiliar;
    if ($cargoAux) $auxCompleto .= ', ' . $cargoAux;
    if ($orgaoAux) $auxCompleto .= ' da ' . $orgaoAux . ($orgaoSiglaAux ? ' – ' . $orgaoSiglaAux : '');
    $narrativaAux = ' auxiliado pelo ' . $auxCompleto . ',';
}

$partesResp = array_filter([$formacaoResp, $cargoResp]);
$respCompleto = $responsavel . ($partesResp ? ', ' . implode(', ', $partesResp) : '');
$orgaoCompleto = $orgaoResp . ($orgaoSiglaResp ? ' – ' . $orgaoSiglaResp : '');
$verboColeta = $temAuxiliar ? 'realizamos' : 'realizei';

$narrativa = sprintf(
    'No dia %s de %s do ano de %s no município de %s, estado do %s eu, %s, do(a) %s, Cart. Fiscal %s,%s %s a coleta de amostras de materiais vegetais (%s) em atendimento às normas do %s, conforme discriminado a seguir:',
    $dia ? (($dia === 1) ? '1º' : $dia) : '___',
    $mes ?: '_________',
    $ano ?: '____',
    $municipioNome ?: '_________',
    $estadoNome ?: '_________',
    $respCompleto ?: '_________',
    $orgaoCompleto ?: '_________',
    $matriculaResp ?: '_________',
    $narrativaAux,
    $verboColeta,
    $partesTexto,
    $programaNome
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCA <?php echo htmlspecialchars($t['termo_coleta'] ?? ''); ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.4; color: #000; max-width: 21cm; margin: 0 auto; padding: 1.5cm; }
        .header { text-align: center; margin-bottom: 1.2em; }
        .header h1 { font-size: 12pt; font-weight: bold; margin: 0 0 2px 0; }
        .header h2 { font-size: 11pt; font-weight: bold; margin: 0 0 4px 0; }
        .tca-num { font-size: 12pt; font-weight: bold; margin-bottom: 0.8em; }
        .section { margin-bottom: 0.8em; }
        .section-title { font-weight: bold; margin-bottom: 2px; }
        .narrativa { text-align: justify; margin: 1em 0; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; margin: 1em 0; }
        th, td { border-top: none; border-left: none; border-right: none; border-bottom: 1px solid #000; padding: 4px 6px; text-align: left; vertical-align: top; }
        thead th { border-top: 1px solid #000; }
        th { background: #f0f0f0; font-weight: bold; }
        .assinatura { margin-top: 2em; }
        .assinatura-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 2em; flex-wrap: wrap; }
        .assinatura-block { min-width: 0; }
        .assinatura-nome { font-weight: bold; text-decoration: underline; margin-top: 2em; }
        .assinatura-info { font-size: 9pt; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 1em;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; font-size: 14px;">Imprimir</button>
        <a href="<?php echo htmlspecialchars(getBasePath()); ?>amostragem/index.php" style="margin-left: 8px;">Voltar</a>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($orgaoResp ?: '_________'); ?></h1>
        <h2><?php echo htmlspecialchars($unidadeResp ?: '_________'); ?></h2>
        <div class="tca-num">TERMO DE COLETA DE AMOSTRAS – TCA <?php echo htmlspecialchars($t['termo_coleta'] ?? '-'); ?></div>
    </div>

    <div class="section">
        <div class="section-title">Estabelecimento Fiscalizado:</div>
        <div><strong>Nome:</strong> <?php echo htmlspecialchars($t['propriedade_nome'] ?? '-'); ?></div>
        <div><strong>Local da Coleta:</strong> <?php echo htmlspecialchars($localColeta ?: '-'); ?></div>
        <div><strong>Município:</strong> <?php echo htmlspecialchars($municipioUf ?: '-'); ?></div>
    </div>

    <div class="narrativa"><?php echo htmlspecialchars($narrativa); ?></div>

    <div class="section-title">MATERIAL COLETADO</div>
    <table>
        <thead>
            <tr>
                <th>IDENTIFICAÇÃO DA AMOSTRA</th>
                <th>ESPÉCIE</th>
                <th>TIPO DE MATERIAL</th>
                <th>VARIEDADE</th>
                <th>COORDENADAS GEOGRÁFICAS</th>
                <th>ANÁLISE SOLICITADA</th>
                <th>ASSOCIADO</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($areas)): ?>
            <tr><td colspan="7" style="text-align: center;">Nenhum material coletado</td></tr>
            <?php else: ?>
            <?php foreach ($areas as $a): 
                $lat = $a['latitude'] ?? null;
                $lon = $a['longitude'] ?? null;
                $coords = ($lat !== null && $lon !== null) ? $lat . ', ' . $lon : '-';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($a['identificacao_amostra'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($a['especie'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars(partesColetadas($a) ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($a['variedade'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($coords); ?></td>
                <td><?php echo htmlspecialchars($a['programa_nome_cientifico'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($a['associado'] ?? '-'); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="assinatura">
        <div class="assinatura-row">
            <div class="assinatura-block">
                <div class="assinatura-nome"><?php echo htmlspecialchars($responsavel ?: '_________________________'); ?></div>
                <div class="assinatura-info"><?php echo htmlspecialchars($formacaoResp ?: ''); ?></div>
                <div class="assinatura-info"><?php echo htmlspecialchars($cargoResp ?: ''); ?></div>
                <div class="assinatura-info">Carteira <?php echo htmlspecialchars($matriculaResp ?: '_________'); ?></div>
            </div>
            <?php if ($temAuxiliar): ?>
            <div class="assinatura-block">
                <div class="assinatura-nome"><?php echo htmlspecialchars($auxiliar ?: '_________________________'); ?></div>
                <div class="assinatura-info"><?php echo htmlspecialchars($formacaoAux ?: ''); ?></div>
                <div class="assinatura-info"><?php echo htmlspecialchars($cargoAux ?: ''); ?></div>
                <div class="assinatura-info">Carteira <?php echo htmlspecialchars($matriculaAux ?: '_________'); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
