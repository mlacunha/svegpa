<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Dashboard";
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo '<div class="p-6"><div class="alert alert-error"><i class="fas fa-exclamation-circle mr-2"></i>Não foi possível conectar ao banco de dados. Verifique a configuração em config/database.php</div></div>';
    include 'includes/footer.php';
    exit;
}

// Estatísticas gerais
$stmt = $db->query("SELECT COUNT(*) as total FROM programas");
$total_programas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$total_municipios = 0;
$count_normal = 0;
$count_suspeita = 0;
$count_foco = 0;

try {
    $stmt = $db->query("SELECT COUNT(DISTINCT TRIM(municipio)) as total FROM vw_relatorio_mapa_dashboard WHERE municipio IS NOT NULL AND TRIM(municipio) != ''");
    $total_municipios = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->query("
        SELECT UPPER(TRIM(COALESCE(status, 'NORMAL'))) as st, COUNT(*) as c
        FROM vw_relatorio_mapa_dashboard
        GROUP BY st
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $st = $row['st'];
        $c = (int) $row['c'];
        if ($st === 'NORMAL') $count_normal = $c;
        elseif ($st === 'SUSPEITA') $count_suspeita = $c;
        elseif ($st === 'FOCO') $count_foco = $c;
    }
} catch (PDOException $e) {
    try {
        $stmt = $db->query("SELECT COUNT(DISTINCT TRIM(municipio)) as total FROM relatorio_mapa WHERE municipio IS NOT NULL AND TRIM(municipio) != ''");
        $total_municipios = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $db->query("SELECT UPPER(TRIM(COALESCE(status, 'NORMAL'))) as st, COUNT(*) as c FROM relatorio_mapa GROUP BY st");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = $row['st'];
            $c = (int) $row['c'];
            if ($st === 'NORMAL') $count_normal = $c;
            elseif ($st === 'SUSPEITA') $count_suspeita = $c;
            elseif ($st === 'FOCO') $count_foco = $c;
        }
    } catch (PDOException $e2) {
        $stmt = $db->query("
            SELECT COUNT(DISTINCT TRIM(p.municipio)) as total
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao = t.id
            LEFT JOIN propriedades p ON t.id_propriedade = p.id
            WHERE p.municipio IS NOT NULL AND TRIM(p.municipio) != ''
        ");
        $total_municipios = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $db->query("
            SELECT CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END as st, COUNT(*) as c
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao = t.id
            GROUP BY st
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $st = $row['st'];
            $c = (int) $row['c'];
            if ($st === 'NORMAL') $count_normal = $c;
            elseif ($st === 'SUSPEITA') $count_suspeita = $c;
        }
    }
}

// Programas recentes
$stmt = $db->query("SELECT * FROM programas ORDER BY criado_em DESC LIMIT 5");
$programas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Propriedades recentes
$stmt = $db->query("SELECT * FROM propriedades ORDER BY criado_em DESC LIMIT 5");
$propriedades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Produtores recentes
$stmt = $db->query("SELECT * FROM produtores ORDER BY criado_em DESC LIMIT 5");
$produtores_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados do mapa - levantamentos fitossanitários (vw_relatorio_mapa_dashboard ou fallback para tabelas)
$mapa_pontos = [];
try {
    $stmt = $db->query("
        SELECT v.latitude, v.longitude, v.status, v.municipio, v.cultura, v.tipo_imovel, v.data_formatada,
               v.id_programa, COALESCE(p.nome, 'Sem programa') as programa_nome
        FROM vw_relatorio_mapa_dashboard v
        LEFT JOIN programas p ON v.id_programa = p.id
        WHERE v.latitude IS NOT NULL AND v.longitude IS NOT NULL
    ");
    $mapa_pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: view inexistente ou sem permissão - busca direto nas tabelas
    $mapa_pontos = [];
    try {
        $stmt = $db->query("
            SELECT r.latitude, r.longitude, r.status, r.municipio, r.cultura, r.tipo_imovel,
                   CAST(r.data AS DATE) AS data_formatada, r.id_programa,
                   COALESCE(p.nome, 'Sem programa') as programa_nome
            FROM relatorio_mapa r
            LEFT JOIN programas p ON r.id_programa = p.id
            WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
        ");
        $mapa_pontos = array_merge($mapa_pontos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e2) {
        // relatorio_mapa pode não existir
    }
    try {
        $stmt = $db->query("
            SELECT COALESCE(a.latitude, pr.latitude) AS latitude, COALESCE(a.longitude, pr.longitude) AS longitude,
                   CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END AS status,
                   pr.municipio, a.especie AS cultura, COALESCE(a.tipo_area, pr.classificacao) AS tipo_imovel,
                   DATE(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) AS data_formatada,
                   t.id_programa, COALESCE(p.nome, 'Sem programa') as programa_nome
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao = t.id
            LEFT JOIN propriedades pr ON t.id_propriedade = pr.id
            LEFT JOIN programas p ON t.id_programa = p.id
            WHERE (a.latitude IS NOT NULL OR pr.latitude IS NOT NULL)
              AND (a.longitude IS NOT NULL OR pr.longitude IS NOT NULL)
        ");
        $mapa_pontos = array_merge($mapa_pontos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e3) {
        // area_inspecionada pode não ter lat/lon ou tabelas não existirem
    }
}

$msg = $_GET['msg'] ?? '';
$msgText = '';
$msgType = 'info';
if ($msg === 'activated') {
    $msgText = 'Conta ativada com sucesso! Faça login com sua senha enviada por e-mail. Recomendamos alterar sua senha após o primeiro acesso.';
    $msgType = 'success';
} elseif ($msg === 'invalid_code') {
    $msgText = 'Código de ativação inválido ou expirado.';
    $msgType = 'error';
} elseif ($msg === 'already_active') {
    $msgText = 'Esta conta já está ativa. Faça login normalmente.';
    $msgType = 'info';
} elseif ($msg === 'acesso_negado') {
    $msgText = 'Você não tem permissão para acessar essa função.';
    $msgType = 'error';
}
?>

<?php if ($msgText): ?>
<div class="mb-4">
    <div class="alert alert-<?php echo $msgType === 'success' ? 'success' : ($msgType === 'error' ? 'error' : 'info'); ?>">
        <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : ($msgType === 'error' ? 'exclamation-circle' : 'info-circle'); ?> mr-2"></i><?php echo htmlspecialchars($msgText); ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
                <i class="fas fa-seedling text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Programas</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo (int) $total_programas; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="bg-green-100 p-3 rounded-full mr-4">
                <i class="fas fa-map-marker-alt text-green-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Municípios</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $total_municipios; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
                <i class="fas fa-map-marker-alt text-blue-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Levantamentos NORMAL</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $count_normal; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="bg-orange-100 p-3 rounded-full mr-4">
                <i class="fas fa-map-marker-alt text-orange-500 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Levantamentos SUSPEITA</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $count_suspeita; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center">
            <div class="bg-red-100 p-3 rounded-full mr-4">
                <i class="fas fa-map-marker-alt text-red-600 text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-600 text-sm">Levantamentos FOCO</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $count_foco; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Mapa de Levantamentos Fitossanitários -->
<div class="mb-8">
    <div class="card">
        <h2 class="text-xl font-bold mb-4 flex flex-wrap items-center gap-4">
            <span class="flex items-center">
                <i class="fas fa-map-marked-alt mr-2 text-blue-600"></i>
                Levantamentos Fitossanitários
            </span>
            <a href="<?php echo htmlspecialchars($base_path ?? getBasePath()); ?>mapa_fullscreen.php" class="btn-primary text-sm py-1.5 px-3" target="_blank" rel="noopener">
                <i class="fas fa-expand mr-1"></i>Mapa em tela cheia
            </a>
            <span class="flex flex-wrap items-center gap-4 text-sm font-normal text-gray-500">
                <span class="inline-flex items-center"><i class="fas fa-map-marker-alt text-blue-600 mr-1"></i>NORMAL</span>
                <span class="inline-flex items-center"><i class="fas fa-map-marker-alt text-orange-500 mr-1"></i>SUSPEITA</span>
                <span class="inline-flex items-center"><i class="fas fa-map-marker-alt text-red-600 mr-1"></i>FOCO</span>
            </span>
        </h2>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>.custom-marker { background: none !important; border: none !important; }</style>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <div id="mapa-dashboard" style="height: 450px; border-radius: 8px;"></div>
        <script>
        (function() {
            var pontos = <?php echo json_encode($mapa_pontos); ?>;
            var map = L.map('mapa-dashboard').setView([-3.5, -52.0], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            var icones = {
                NORMAL: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background:#2563eb;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                }),
                SUSPEITA: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background:#f97316;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                }),
                FOCO: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background:#dc2626;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })
            };
            var layersByPrograma = {};
            pontos.forEach(function(p) {
                var lat = parseFloat(p.latitude);
                var lon = parseFloat(p.longitude);
                if (isNaN(lat) || isNaN(lon)) return;
                var programaNome = (p.programa_nome || 'Sem programa').trim();
                if (!layersByPrograma[programaNome]) {
                    layersByPrograma[programaNome] = L.layerGroup().addTo(map);
                }
                var status = (p.status || 'NORMAL').toUpperCase();
                var icone = icones[status] || icones.NORMAL;
                var popup = '<div class="text-sm"><strong>Programa:</strong> ' + programaNome + '<br>';
                popup += '<strong>Status:</strong> ' + (p.status || '-') + '<br>';
                if (p.municipio) popup += '<strong>Município:</strong> ' + p.municipio + '<br>';
                if (p.cultura) popup += '<strong>Cultura:</strong> ' + p.cultura + '<br>';
                if (p.tipo_imovel) popup += '<strong>Tipo:</strong> ' + p.tipo_imovel + '<br>';
                if (p.data_formatada) popup += '<strong>Data:</strong> ' + p.data_formatada;
                popup += '</div>';
                L.marker([lat, lon], { icon: icone }).bindPopup(popup).addTo(layersByPrograma[programaNome]);
            });
            var layerControl = L.control.layers({}, {}).addTo(map);
            Object.keys(layersByPrograma).sort().forEach(function(nome) {
                layerControl.addOverlay(layersByPrograma[nome], nome + ' (' + layersByPrograma[nome].getLayers().length + ')');
            });
        })();
        </script>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-seedling mr-2 text-blue-600"></i>
            Programas Recentes
        </h2>
        <div class="table-container">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Código</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programas_recentes as $programa): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?php echo htmlspecialchars($programa['nome']); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($programa['codigo'] ?? '-'); ?></td>
                        <td class="py-3 px-4">
                            <a href="programas/edit.php?id=<?php echo $programa['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-2">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="programas/delete.php?id=<?php echo $programa['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="programas/index.php" class="btn-primary text-sm">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-building mr-2 text-green-600"></i>
            Propriedades Recentes
        </h2>
        <div class="table-container">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">UF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propriedades_recentes as $propriedade): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?php echo htmlspecialchars($propriedade['nome']); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($propriedade['municipio'] ?? '-'); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($propriedade['UF'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="propriedades/index.php" class="btn-primary text-sm">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-user-tie mr-2 text-teal-600"></i>
            Produtores Recentes
        </h2>
        <div class="table-container">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município / UF</th>
                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtores_recentes as $prod): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($prod['nome']); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars(($prod['municipio'] ?? '-') . ' / ' . ($prod['uf'] ?? '-')); ?></td>
                        <td class="py-3 px-4">
                            <a href="produtores/edit.php?id=<?php echo htmlspecialchars($prod['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="produtores/index.php" class="btn-primary text-sm">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>