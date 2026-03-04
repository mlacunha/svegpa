<?php
/**
 * Modal para cadastro de propriedade em popup.
 * Usar em páginas que tenham o select #id_propriedade.
 * Requer $base_path definido (ex: do header).
 */
$prop_modal_bp = $base_path ?? '';
?>
<!-- Modal Nova Propriedade -->
<div id="propriedadeCreateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">Nova Propriedade</h3>
            <button type="button" id="propriedadeCreateModalClose" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <iframe id="propriedadeCreateIframe" src="about:blank" class="w-full flex-1 min-h-[400px] border-0" title="Cadastro de propriedade"></iframe>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('propriedadeCreateModal');
    var iframe = document.getElementById('propriedadeCreateIframe');
    var closeBtn = document.getElementById('propriedadeCreateModalClose');
    var openBtn = document.getElementById('btnNovaPropriedade');
    
    if (!modal || !iframe) return;
    
    function openModal() {
        iframe.src = '<?php echo htmlspecialchars($prop_modal_bp); ?>propriedades/create_popup.php';
        modal.classList.remove('hidden');
    }
    
    function closeModal() {
        modal.classList.add('hidden');
        iframe.src = 'about:blank';
    }
    
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'propriedade_created' && e.data.id && e.data.nome) {
            var nCad = (e.data.n_cadastro && String(e.data.n_cadastro).trim()) ? e.data.n_cadastro : '';
            if (typeof window.onPropriedadeCreated === 'function') {
                window.onPropriedadeCreated(e.data.id, e.data.nome, nCad);
            } else {
                var sel = document.getElementById('id_propriedade');
                if (sel && sel.tagName === 'SELECT') {
                    var opt = document.createElement('option');
                    opt.value = e.data.id;
                    opt.textContent = e.data.nome;
                    opt.selected = true;
                    sel.appendChild(opt);
                } else if (sel) {
                    sel.value = e.data.id;
                    var disp = document.getElementById('propriedade_display');
                    if (disp) {
                        var pid = nCad || e.data.id;
                        disp.value = pid + '-' + (e.data.nome || '');
                    }
                }
            }
            closeModal();
        }
    });
    
    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(ev) { if (ev.target === modal) closeModal(); });
})();
</script>
