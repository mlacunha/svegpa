<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

$base = getBasePath();
$db = (new Database())->getConnection();

$anos = [];
$programas = [];
$municipios = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT DISTINCT ano FROM vw_relatorio_mapa_dashboard WHERE ano IS NOT NULL AND ano != '' ORDER BY ano DESC");
        $anos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        try {
            $stmt = $db->query("SELECT DISTINCT ano FROM relatorio_mapa WHERE ano IS NOT NULL ORDER BY ano DESC");
            $anos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e2) {}
    }
    $programas = $db->query("SELECT id, nome FROM programas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    try {
        $stmt = $db->query("SELECT DISTINCT TRIM(municipio) as m FROM vw_relatorio_mapa_dashboard WHERE municipio IS NOT NULL AND TRIM(municipio) != '' ORDER BY municipio");
        $municipios = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $municipios = [];
        try {
            $stmt = $db->query("SELECT DISTINCT TRIM(municipio) as m FROM relatorio_mapa WHERE municipio IS NOT NULL AND TRIM(municipio) != '' ORDER BY municipio");
            $municipios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e2) {}
        try {
            $stmt = $db->query("SELECT DISTINCT TRIM(pr.municipio) as m FROM termo_inspecao t LEFT JOIN propriedades pr ON t.id_propriedade = pr.id WHERE pr.municipio IS NOT NULL AND TRIM(pr.municipio) != '' ORDER BY m");
            $m2 = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $municipios = array_unique(array_merge($municipios, $m2));
            sort($municipios);
        } catch (PDOException $e3) {}
    }
}

$trimestres = [1 => '1º Trimestre', 2 => '2º Trimestre', 3 => '3º Trimestre', 4 => '4º Trimestre'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa - Levantamentos Fitossanitários | SVEG</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>css/style.css">
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
        #mapa-fs { position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0; }
        .filtros-panel {
            position: absolute; top: 12px; left: 12px; z-index: 1000;
            background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            padding: 12px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; max-width: 95%;
        }
        .filtros-panel select { min-width: 120px; padding: 6px 8px; }
        .btn-consultar { padding: 6px 14px; background: #1e40af; color: white; border-radius: 6px; border: none; cursor: pointer; }
        .btn-consultar:hover { background: #1d4ed8; }
        .btn-voltar {
            position: absolute; top: 12px; right: 12px; z-index: 1001;
            padding: 8px 16px; background: white; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            text-decoration: none; color: #1e40af; font-weight: 500;
        }
        .btn-voltar:hover { background: #f1f5f9; }
        .custom-marker { background: none !important; border: none !important; }
        .legenda { position: absolute; bottom: 24px; left: 12px; z-index: 1000; background: white; padding: 8px 12px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); font-size: 12px; }
    </style>
</head>
<body>
    <div class="filtros-panel">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Ano</label>
            <select id="filtro-ano">
                <option value="">Todos</option>
                <?php foreach ($anos as $a): ?><option value="<?php echo htmlspecialchars($a); ?>"><?php echo htmlspecialchars($a); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Trimestre</label>
            <select id="filtro-trimestre">
                <option value="">Todos</option>
                <?php foreach ($trimestres as $v => $l): ?><option value="<?php echo $v; ?>"><?php echo htmlspecialchars($l); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Programa</label>
            <select id="filtro-programa">
                <option value="">Todos</option>
                <?php foreach ($programas as $pr): ?><option value="<?php echo htmlspecialchars($pr['id']); ?>"><?php echo htmlspecialchars($pr['nome']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Município</label>
            <select id="filtro-municipio">
                <option value="">Todos (Pará)</option>
                <?php foreach ($municipios as $m): ?><option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="button" id="btn-consultar" class="btn-consultar"><i class="fas fa-search mr-1"></i>Consultar</button>
    </div>
    <a href="<?php echo htmlspecialchars($base); ?>dashboard.php" class="btn-voltar"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
    <div class="legenda">
        <span class="inline-flex items-center mr-3"><i class="fas fa-circle text-blue-600 mr-1" style="font-size:10px;"></i>NORMAL</span>
        <span class="inline-flex items-center mr-3"><i class="fas fa-circle text-orange-500 mr-1" style="font-size:10px;"></i>SUSPEITA</span>
        <span class="inline-flex items-center"><i class="fas fa-circle text-red-600 mr-1" style="font-size:10px;"></i>FOCO</span>
    </div>
    <div id="mapa-fs"></div>

    <script>
    (function() {
        var base = <?php echo json_encode($base); ?>;
        var geoJsonUrl = base.replace(/\/$/, '') + '/geojs-15-mun.json';
        var paraCenter = [-3.5, -52.0];
        var paraZoom = 6;
        var municipioZoom = 11;

        var map = L.map('mapa-fs').setView(paraCenter, paraZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var layerMunicipios = null;
        var markersLayer = L.layerGroup().addTo(map);
        var icones = {
            NORMAL: L.divIcon({ className: 'custom-marker', html: '<div style="background:#2563eb;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] }),
            SUSPEITA: L.divIcon({ className: 'custom-marker', html: '<div style="background:#f97316;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] }),
            FOCO: L.divIcon({ className: 'custom-marker', html: '<div style="background:#dc2626;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
        };

        function centralizarMapa(municipioNome) {
            if (!municipioNome) {
                map.setView(paraCenter, paraZoom);
                return;
            }
            fetch(geoJsonUrl)
                .then(function(r) { return r.json(); })
                .then(function(geojson) {
                    var feat = geojson.features.find(function(f) {
                        var n = (f.properties && f.properties.name) || '';
                        return n.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') === municipioNome.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    });
                    if (feat && feat.geometry) {
                        var layer = L.geoJSON(feat);
                        var b = layer.getBounds();
                        if (b.isValid()) {
                            map.fitBounds(b, { padding: [40, 40], maxZoom: municipioZoom });
                        } else {
                            var coords = feat.geometry.coordinates;
                            if (feat.geometry.type === 'Polygon' && coords[0] && coords[0][0]) {
                                map.setView([coords[0][0][1], coords[0][0][0]], municipioZoom);
                            }
                        }
                    } else {
                        map.setView(paraCenter, paraZoom);
                    }
                })
                .catch(function() { map.setView(paraCenter, paraZoom); });
        }

        function carregarMunicipiosGeoJson() {
            if (layerMunicipios) map.removeLayer(layerMunicipios);
            fetch(geoJsonUrl)
                .then(function(r) { return r.json(); })
                .then(function(geojson) {
                    layerMunicipios = L.geoJSON(geojson, {
                        style: { color: '#64748b', weight: 1, fillColor: '#94a3b8', fillOpacity: 0.08 }
                    }).addTo(map);
                })
                .catch(function() {});
        }

        function consultar() {
            var params = new URLSearchParams();
            var ano = document.getElementById('filtro-ano').value;
            var trim = document.getElementById('filtro-trimestre').value;
            var prog = document.getElementById('filtro-programa').value;
            var mun = document.getElementById('filtro-municipio').value;
            if (ano) params.set('ano', ano);
            if (trim) params.set('trimestre', trim);
            if (prog) params.set('programa', prog);
            if (mun) params.set('municipio', mun);

            markersLayer.clearLayers();
            fetch(base + 'api_mapa_pontos.php?' + params.toString())
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var pontos = data.pontos || [];
                    pontos.forEach(function(p) {
                        var lat = parseFloat(p.latitude), lon = parseFloat(p.longitude);
                        if (isNaN(lat) || isNaN(lon)) return;
                        var status = (p.status || 'NORMAL').toUpperCase();
                        var icone = icones[status] || icones.NORMAL;
                        var popup = '<div class="text-sm"><strong>Programa:</strong> ' + (p.programa_nome || '-') + '<br><strong>Status:</strong> ' + (p.status || '-') + '<br>';
                        if (p.municipio) popup += '<strong>Município:</strong> ' + p.municipio + '<br>';
                        if (p.cultura) popup += '<strong>Cultura:</strong> ' + p.cultura + '<br>';
                        if (p.data_formatada) popup += '<strong>Data:</strong> ' + p.data_formatada;
                        popup += '</div>';
                        L.marker([lat, lon], { icon: icone }).bindPopup(popup).addTo(markersLayer);
                    });
                    centralizarMapa(mun || null);
                })
                .catch(function() { centralizarMapa(mun || null); });
        }

        document.getElementById('btn-consultar').addEventListener('click', consultar);
        carregarMunicipiosGeoJson();
        consultar();
    })();
    </script>
</body>
</html>
