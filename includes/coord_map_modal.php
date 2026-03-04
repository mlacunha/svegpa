<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<!-- Modal Mapa -->
<div id="coordMapaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Localização no Mapa</h3>
            <button type="button" id="coordMapaClose" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div id="coordMapaContainer" class="w-full h-96 min-h-[384px]"></div>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('coordMapaBtn');
    var modal = document.getElementById('coordMapaModal');
    var closeBtn = document.getElementById('coordMapaClose');
    var container = document.getElementById('coordMapaContainer');
    var mapInstance = null;
    var marker = null;

    if (!btn || !modal) return;

    function openMapa() {
        var latInput = document.getElementById('latitude');
        var lonInput = document.getElementById('longitude');
        var lat = parseFloat((latInput && latInput.value) ? latInput.value.replace(',', '.') : 0);
        var lon = parseFloat((lonInput && lonInput.value) ? lonInput.value.replace(',', '.') : 0);
        if (isNaN(lat) || isNaN(lon) || (lat === 0 && lon === 0)) {
            lat = -1.456; lon = -48.503;
        }
        modal.classList.remove('hidden');
        setTimeout(function() {
            if (mapInstance) {
                mapInstance.setView([lat, lon], 13);
                if (marker) marker.setLatLng([lat, lon]);
                mapInstance.invalidateSize();
            } else if (container && typeof L !== 'undefined') {
                mapInstance = L.map('coordMapaContainer').setView([lat, lon], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapInstance);
                marker = L.marker([lat, lon]).addTo(mapInstance);
            }
        }, 50);
    }

    btn.addEventListener('click', openMapa);

    closeBtn.addEventListener('click', function() { modal.classList.add('hidden'); });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.add('hidden'); });
})();
</script>
