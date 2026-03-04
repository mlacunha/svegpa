<?php
/**
 * Widget de upload de logo para órgão.
 * Requer: $logo_path (caminho atual ou vazio), $base_path (do header)
 * Drag-and-drop, preview, excluir, alterar.
 */
$logo_path = $logo_path ?? '';
$bp = $base_path ?? '/';
$logo_url = $logo_path ? ($bp . $logo_path) : '';
?>
<div class="form-group" id="logo-upload-wrap">
    <label>Logo do órgão</label>
    <p class="text-sm text-gray-500 mb-2">Imagem para cabeçalho de documentos (máx. 400×120px). Arraste a imagem aqui ou clique para selecionar.</p>
    <input type="hidden" name="logo" id="logo" value="<?php echo htmlspecialchars($logo_path); ?>">
    <div id="logo-drop-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary hover:bg-gray-50 transition-colors min-h-[100px] flex flex-col items-center justify-center">
        <div id="logo-placeholder" class="<?php echo $logo_path ? 'hidden' : ''; ?>">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">Arraste a imagem aqui ou clique para selecionar</p>
            <input type="file" id="logo-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
        </div>
        <div id="logo-preview-wrap" class="<?php echo $logo_path ? '' : 'hidden'; ?>">
            <img id="logo-preview" src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo" class="max-h-[80px] max-w-full mx-auto object-contain">
            <div class="flex gap-2 mt-2 justify-center">
                <button type="button" id="logo-btn-alterar" class="btn-secondary text-sm">Alterar</button>
                <button type="button" id="logo-btn-excluir" class="btn-secondary text-sm text-red-600 hover:bg-red-50">Excluir</button>
            </div>
        </div>
    </div>
    <div id="logo-upload-error" class="text-red-600 text-sm mt-1 hidden"></div>
</div>
<script>
(function() {
    var dropZone = document.getElementById('logo-drop-zone');
    var fileInput = document.getElementById('logo-file-input');
    var logoInput = document.getElementById('logo');
    var placeholder = document.getElementById('logo-placeholder');
    var previewWrap = document.getElementById('logo-preview-wrap');
    var preview = document.getElementById('logo-preview');
    var btnAlterar = document.getElementById('logo-btn-alterar');
    var btnExcluir = document.getElementById('logo-btn-excluir');
    var errEl = document.getElementById('logo-upload-error');
    var basePath = <?php echo json_encode($bp); ?>;
    // URL do upload: mesmo diretório da página (orgaos/), mais robusto que base_path
    var uploadUrl = document.location.pathname.replace(/[^/]*$/, '') + 'logo_upload.php';

    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.classList.toggle('hidden', !msg);
    }
    function updatePreview(path) {
        logoInput.value = path || '';
        if (path) {
            placeholder.classList.add('hidden');
            previewWrap.classList.remove('hidden');
            preview.src = basePath + path;
        } else {
            placeholder.classList.remove('hidden');
            previewWrap.classList.add('hidden');
            preview.src = '';
        }
        showError('');
    }
    function uploadFile(file) {
        if (!file || !file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
            showError('Formato inválido. Use JPEG, PNG, GIF ou WebP.');
            return;
        }
        showError('');
        var fd = new FormData();
        fd.append('logo_file', file);
        fd.append('old_logo', logoInput.value || '');
        fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.text(); })
            .then(function(t) {
                var res;
                try { res = JSON.parse(t); } catch (e) { throw new Error('Resposta inválida: ' + (t.substring(0, 80) || 'vazia')); }
                if (res.ok) updatePreview(res.path);
                else showError(res.error || 'Erro ao enviar.');
            })
            .catch(function(e) { showError(e && e.message ? e.message : 'Erro de conexão.'); });
    }
    function removeLogo() {
        if (!logoInput.value) { updatePreview(''); return; }
        var fd = new FormData();
        fd.append('remove', '1');
        fd.append('old_logo', logoInput.value);
        fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.text(); })
            .then(function(t) {
                var res;
                try { res = JSON.parse(t); } catch (e) { throw new Error('Resposta inválida: ' + (t.substring(0, 80) || 'vazia')); }
                if (res.ok) updatePreview('');
                else showError(res.error || 'Erro ao excluir.');
            })
            .catch(function(e) { showError(e && e.message ? e.message : 'Erro de conexão.'); });
    }
    dropZone.addEventListener('click', function(e) {
        if (!e.target.closest('#logo-btn-alterar') && !e.target.closest('#logo-btn-excluir')) fileInput.click();
    });
    btnAlterar.addEventListener('click', function(e) { e.stopPropagation(); fileInput.click(); });
    btnExcluir.addEventListener('click', function(e) { e.stopPropagation(); removeLogo(); });
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) uploadFile(this.files[0]);
        this.value = '';
    });
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('border-primary', 'bg-blue-50');
    });
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('border-primary', 'bg-blue-50');
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('border-primary', 'bg-blue-50');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) uploadFile(e.dataTransfer.files[0]);
    });
})();
</script>
