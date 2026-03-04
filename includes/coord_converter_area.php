<!-- Modal Converter Coordenadas DMS para área inspecionada -->
<div id="areaCoordConverterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4" onclick="if(event.target===this)document.getElementById('areaCoordConverterModal').classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">GMS → Grau Decimal</h3>
            <button type="button" id="areaCoordConverterClose" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Latitude (Grau, Minuto, Segundo)</label>
                <div class="flex gap-2 items-center flex-wrap">
                    <input type="number" id="area_lat_grau" class="form-control w-20" placeholder="Grau" min="0" max="90" step="1">
                    <span>°</span>
                    <input type="number" id="area_lat_minuto" class="form-control w-20" placeholder="Min" min="0" max="59" step="1">
                    <span>'</span>
                    <input type="number" id="area_lat_segundo" class="form-control w-24" placeholder="Seg" min="0" max="59" step="any">
                    <span>"</span>
                    <select id="area_lat_dir" class="form-control w-16">
                        <option value="N">N</option>
                        <option value="S">S</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Longitude (Grau, Minuto, Segundo)</label>
                <div class="flex gap-2 items-center flex-wrap">
                    <input type="number" id="area_lon_grau" class="form-control w-20" placeholder="Grau" min="0" max="180" step="1">
                    <span>°</span>
                    <input type="number" id="area_lon_minuto" class="form-control w-20" placeholder="Min" min="0" max="59" step="1">
                    <span>'</span>
                    <input type="number" id="area_lon_segundo" class="form-control w-24" placeholder="Seg" min="0" max="59" step="any">
                    <span>"</span>
                    <select id="area_lon_dir" class="form-control w-16">
                        <option value="E">E</option>
                        <option value="W">W</option>
                    </select>
                </div>
            </div>
            <button type="button" id="areaCoordConverterCalc" class="btn-primary w-full">
                <i class="fas fa-calculator mr-2"></i>Calcular e aplicar
            </button>
        </div>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('areaCoordConverterModal');
    var closeBtn = document.getElementById('areaCoordConverterClose');
    var calcBtn = document.getElementById('areaCoordConverterCalc');
    var btn = document.getElementById('areaCoordConverterBtn');
    if (!modal || !calcBtn) return;
    if (btn) btn.addEventListener('click', function() { modal.classList.remove('hidden'); });
    if (closeBtn) closeBtn.addEventListener('click', function() { modal.classList.add('hidden'); });
    calcBtn.addEventListener('click', function() {
        var latG = parseFloat(document.getElementById('area_lat_grau').value) || 0;
        var latM = parseFloat(document.getElementById('area_lat_minuto').value) || 0;
        var latS = parseFloat(document.getElementById('area_lat_segundo').value) || 0;
        var latDir = document.getElementById('area_lat_dir').value;
        var lonG = parseFloat(document.getElementById('area_lon_grau').value) || 0;
        var lonM = parseFloat(document.getElementById('area_lon_minuto').value) || 0;
        var lonS = parseFloat(document.getElementById('area_lon_segundo').value) || 0;
        var lonDir = document.getElementById('area_lon_dir').value;
        var latDec = latG + (latM/60) + (latS/3600);
        if (latDir === 'S') latDec = -latDec;
        var lonDec = lonG + (lonM/60) + (lonS/3600);
        if (lonDir === 'W') lonDec = -lonDec;
        var latInput = document.getElementById('area_latitude');
        var lonInput = document.getElementById('area_longitude');
        if (latInput) { latInput.value = latDec.toFixed(8); latInput.dispatchEvent(new Event('input')); }
        if (lonInput) { lonInput.value = lonDec.toFixed(8); lonInput.dispatchEvent(new Event('input')); }
        modal.classList.add('hidden');
    });
})();
</script>
