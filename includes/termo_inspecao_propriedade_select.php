<?php
/**
 * Select de Propriedade para Termo de Inspeção.
 * Município obrigatório como primeiro campo. Propriedade exibe n_cadastro - nome, pré-filtrada por município.
 * Requer: $propriedades (id, n_cadastro, nome, municipio), $selected_prop (id)
 */
$prop_select_props = $propriedades ?? [];
$prop_select_sel = $selected_prop ?? '';
$prop_json = json_encode(array_map(function($p){ return ['id'=>$p['id'],'n_cadastro'=>$p['n_cadastro']??'','nome'=>$p['nome'],'municipio'=>$p['municipio']??'']; }, $prop_select_props));
$municipios_distinct = [];
foreach ($prop_select_props as $p) {
    $m = trim($p['municipio'] ?? '');
    if ($m !== '' && !in_array($m, $municipios_distinct)) $municipios_distinct[] = $m;
}
sort($municipios_distinct);
$sel_municipio = $selected_municipio ?? '';
$sel_nome = '';
foreach ($prop_select_props as $p) {
    if ($p['id'] === $prop_select_sel) {
        $nc = trim($p['n_cadastro'] ?? '');
        $sel_nome = $nc ? ($nc . ' - ' . ($p['nome'] ?? '')) : ($p['nome'] ?? '');
        break;
    }
}
?>
<div class="form-group min-w-0">
    <label for="termo_municipio">Município *</label>
    <select id="termo_municipio" name="municipio" class="form-control" required title="Selecione o município primeiro para filtrar as propriedades">
        <option value="">-- Selecione o município --</option>
        <?php foreach ($municipios_distinct as $m): ?>
        <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $sel_municipio === $m ? 'selected' : ''; ?>><?php echo htmlspecialchars($m); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="propriedade-combobox-wrapper min-w-0">
    <input type="hidden" id="id_propriedade" name="id_propriedade" value="<?php echo htmlspecialchars($prop_select_sel); ?>">
    <div class="form-group">
        <label for="propriedade_display">Propriedade *</label>
        <div class="flex gap-2">
            <div class="propriedade-combobox flex-1 min-w-0 relative">
                <input type="text" id="propriedade_display" class="form-control flex-1" readonly placeholder="<?php echo $sel_municipio ? '-- Selecione a propriedade --' : 'Selecione o município primeiro'; ?>" value="<?php echo htmlspecialchars($sel_nome); ?>" autocomplete="off">
                <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="propriedade_dropdown" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-64 overflow-hidden flex flex-col">
                    <div class="p-2 border-b bg-gray-50">
                        <input type="text" id="propriedade_search" class="form-control w-full text-sm" placeholder="Digite para buscar..." autocomplete="off">
                    </div>
                    <div id="propriedade_list" class="overflow-y-auto flex-1 py-1"></div>
                </div>
            </div>
            <button type="button" id="btnNovaPropriedade" class="btn-primary shrink-0" title="Nova propriedade">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
</div>
<script>
(function(){
    var props = <?php echo $prop_json; ?>;
    var hiddenInput = document.getElementById('id_propriedade');
    var displayInput = document.getElementById('propriedade_display');
    var dropdown = document.getElementById('propriedade_dropdown');
    var searchInput = document.getElementById('propriedade_search');
    var listEl = document.getElementById('propriedade_list');
    var municipioSelect = document.getElementById('termo_municipio');
    var wrapper = document.querySelector('.propriedade-combobox');

    function getDisp(p) {
        var nc = (p.n_cadastro && String(p.n_cadastro).trim()) ? p.n_cadastro : '';
        return nc ? (nc + ' - ' + (p.nome || '')) : (p.nome || '');
    }

    function filterProps() {
        var munic = (municipioSelect && municipioSelect.value) || '';
        var q = (searchInput && searchInput.value || '').toLowerCase().trim();
        if (!munic) return [];
        return props.filter(function(p) {
            if ((p.municipio || '') !== munic) return false;
            var disp = getDisp(p);
            if (q && disp.toLowerCase().indexOf(q) < 0 && (p.nome || '').toLowerCase().indexOf(q) < 0) return false;
            return true;
        });
    }

    function renderList() {
        var arr = filterProps();
        listEl.innerHTML = '';
        var munic = (municipioSelect && municipioSelect.value) || '';
        if (!munic) {
            var empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-gray-500 text-sm';
            empty.textContent = 'Selecione o município primeiro';
            listEl.appendChild(empty);
            return;
        }
        arr.forEach(function(p) {
            var div = document.createElement('div');
            div.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
            div.textContent = getDisp(p);
            div.dataset.id = p.id;
            div.dataset.disp = getDisp(p);
            div.addEventListener('click', function() {
                hiddenInput.value = p.id;
                displayInput.value = getDisp(p);
                dropdown.classList.add('hidden');
                searchInput.value = '';
                displayInput.placeholder = '';
            });
            listEl.appendChild(div);
        });
        if (arr.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-gray-500 text-sm';
            empty.textContent = 'Nenhuma propriedade neste município';
            listEl.appendChild(empty);
        }
    }

    function updatePlaceholder() {
        var munic = (municipioSelect && municipioSelect.value) || '';
        if (!munic) {
            displayInput.placeholder = 'Selecione o município primeiro';
            hiddenInput.value = '';
            displayInput.value = '';
        } else {
            var selId = hiddenInput.value;
            var selProp = props.filter(function(p) { return p.id === selId; })[0];
            if (selProp && (selProp.municipio || '') !== munic) {
                hiddenInput.value = '';
                displayInput.value = '';
            }
            displayInput.placeholder = '-- Selecione a propriedade --';
        }
    }

    if (displayInput) {
        displayInput.addEventListener('focus', function() {
            dropdown.classList.remove('hidden');
            renderList();
            setTimeout(function() { searchInput && searchInput.focus(); }, 50);
        });
        displayInput.addEventListener('click', function() {
            dropdown.classList.remove('hidden');
            renderList();
            setTimeout(function() { searchInput && searchInput.focus(); }, 50);
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function() { renderList(); });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { dropdown.classList.add('hidden'); displayInput.focus(); }
        });
    }
    if (municipioSelect) {
        municipioSelect.addEventListener('change', function() {
            updatePlaceholder();
            renderList();
        });
    }

    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.classList.contains('hidden') && wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    if (typeof window.onPropriedadeCreated === 'function') {
        var orig = window.onPropriedadeCreated;
        window.onPropriedadeCreated = function(id, nome, n_cadastro) {
            var munic = (municipioSelect && municipioSelect.value) || '';
            props.push({id: id, n_cadastro: n_cadastro || '', nome: nome, municipio: munic});
            hiddenInput.value = id;
            displayInput.value = getDisp({n_cadastro: n_cadastro, nome: nome});
            orig && orig(id, nome, n_cadastro);
        };
    } else {
        window.onPropriedadeCreated = function(id, nome, n_cadastro) {
            var munic = (municipioSelect && municipioSelect.value) || '';
            props.push({id: id, n_cadastro: n_cadastro || '', nome: nome, municipio: munic});
            hiddenInput.value = id;
            displayInput.value = getDisp({n_cadastro: n_cadastro, nome: nome});
        };
    }
})();
</script>
