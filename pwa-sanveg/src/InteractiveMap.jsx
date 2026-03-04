import React, { useEffect, useState, useMemo } from 'react';
import { MapContainer, TileLayer, CircleMarker, Popup, Tooltip, useMap, GeoJSON } from 'react-leaflet';
import { Copy, Maximize, Minimize, Search } from 'lucide-react';
import L from 'leaflet';
import clsx from 'clsx';

function ChangeView({ bounds, isFullscreen }) {
    const map = useMap();
    useEffect(() => {
        if (bounds) {
            // Força o recálculo do tamanho do container antes de ajustar o enquadramento,
            // evitando que o fitBounds use dimensões incorretas (especialmente no mobile e no toggle)
            setTimeout(() => {
                map.invalidateSize();
                map.fitBounds(bounds, { padding: [30, 30] });
            }, 300);
        }
    }, [bounds, map, isFullscreen]);
    return null;
}

function MapResizer({ isFullscreen }) {
    const map = useMap();
    useEffect(() => {
        // Dois disparos: um rápido para não deixar tela cinza, outro mais tarde para garantir precisão
        const t1 = setTimeout(() => map.invalidateSize(), 100);
        const t2 = setTimeout(() => map.invalidateSize(), 400);
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, [isFullscreen, map]);
    return null;
}

export default function InteractiveMap({ data, isFullscreen, onToggleFullscreen }) {
    const [geoData, setGeoData] = useState(null);
    const [isMobile, setIsMobile] = useState(window.innerWidth < 768);

    // Monitorar redimensionamento para ajustar tamanho dos pins e detecção mobile
    useEffect(() => {
        const handleResize = () => setIsMobile(window.innerWidth < 768);
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    // Filtros
    const [filterAno, setFilterAno] = useState('');
    const [filterTrimestre, setFilterTrimestre] = useState('');
    const [filterPrograma, setFilterPrograma] = useState('');
    const [filterMunicipio, setFilterMunicipio] = useState('');
    const [filterStatus, setFilterStatus] = useState('');

    useEffect(() => {
        // Carregar GeoJSON do Pará (15)
        fetch('/geojs-15-mun.json')
            .then(res => res.json())
            .then(json => setGeoData(json))
            .catch(err => console.error("Erro ao carregar GeoJSON:", err));
    }, []);

    useEffect(() => {
        // Log para diagnóstico em desenvolvimento
        if (data && data.length >= 0) {
            console.log('[InteractiveMap] Pontos recebidos:', data.length, data.slice(0, 3));
        }
    }, [data]);

    // Extrair Opções Distintas
    const filterOptions = useMemo(() => {
        const anos = new Set();
        const trimestres = new Set();
        const programas = new Set();
        const municipios = new Set();
        const statuses = new Set();

        data.forEach(p => {
            if (p.ano) anos.add(p.ano);
            if (p.trimestre) trimestres.add(p.trimestre);
            if (p.nome_programa) programas.add(p.nome_programa);
            if (p.municipio) municipios.add(p.municipio);
            if (p.status) statuses.add(p.status);
        });

        return {
            anos: [...anos].sort(),
            trimestres: [...trimestres].sort(),
            programas: [...programas].sort(),
            municipios: [...municipios].sort(),
            statuses: [...statuses].sort(),
        }
    }, [data]);

    // Aplicar Filtros + Validação defensiva de coordenadas
    const filteredData = useMemo(() => {
        return data.filter(p => {
            // Valida coordenadas
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);
            if (isNaN(lat) || isNaN(lng)) return false;
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return false;
            // Filtra valores nulos comuns (0,0 geralmente indica dado inválido)
            if (lat === 0 && lng === 0) return false;

            if (filterAno && p.ano !== filterAno) return false;
            if (filterTrimestre && p.trimestre !== filterTrimestre) return false;
            if (filterPrograma && p.nome_programa !== filterPrograma) return false;
            if (filterMunicipio && p.municipio !== filterMunicipio) return false;
            if (filterStatus && p.status !== filterStatus) return false;
            return true;
        });
    }, [data, filterAno, filterTrimestre, filterPrograma, filterMunicipio, filterStatus]);

    // Bounds de visualização do mapa (FitBounds do GeoJSON)
    const mapBounds = useMemo(() => {
        if (!geoData) return null;

        // Se selecionou município, fit apenas nele
        if (filterMunicipio) {
            const feature = geoData.features.find(f =>
                f.properties.name.toLowerCase() === filterMunicipio.toLowerCase() ||
                f.properties.description === filterMunicipio // caso do seu geojson
            );
            if (feature) {
                const geojsonLayer = L.geoJSON(feature);
                return geojsonLayer.getBounds();
            }
        }

        // Se não, fit estado todo
        const geojsonLayer = L.geoJSON(geoData);
        return geojsonLayer.getBounds();
    }, [geoData, filterMunicipio]);

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        alert('Dados copiados para a área de transferência!');
    };

    return (
        <div className={clsx(
            "flex flex-col w-full bg-white rounded-2xl card-shadow border border-slate-200/60 overflow-hidden min-h-0",
            "h-full"
        )}>

            {/* Float Close Button for Mobile Fullscreen */}
            {isFullscreen && (
                <button
                    onClick={onToggleFullscreen}
                    className="md:hidden fixed bottom-24 right-6 z-[2000] bg-white text-slate-800 p-4 rounded-full shadow-2xl border-2 border-slate-100 flex items-center justify-center transition-transform active:scale-95"
                    style={{ boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.3)' }}
                >
                    <Minimize size={24} className="text-brand-blue" />
                </button>
            )}

            {/* Map Header & Toolbar — oculto no mobile (redundante com o card do App.jsx) */}
            <div className="p-4 border-b border-slate-100 bg-white z-10 hidden md:flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative">
                <h2 className="text-lg font-bold flex items-center gap-2 text-slate-800">
                    Mapa de Levantamentos Fitossanitários ({filteredData.length})
                </h2>

                <div className="flex gap-4 items-center w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                    <div className="flex gap-3 text-sm font-medium mr-4">
                        <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-full bg-blue-500 shadow-sm" /> Normal</span>
                        <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-full bg-orange-500 shadow-sm" /> Suspeita</span>
                        <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-full bg-red-600 shadow-sm" /> Foco</span>
                    </div>

                    <button
                        onClick={onToggleFullscreen}
                        className="flex items-center gap-2 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 font-bold text-sm transition"
                    >
                        {isFullscreen ? <Minimize size={16} /> : <Maximize size={16} />}
                        <span className="hidden md:inline">{isFullscreen ? 'Sair da Tela Cheia' : 'Ver Mapa em Tela Cheia'}</span>
                    </button>
                </div>
            </div>

            {/* Filters (Only visible in Fullscreen as requested, or maybe always if we want) */}
            {isFullscreen && (
                <details className="group bg-slate-50 border-b border-slate-200 z-10 relative [&_summary::-webkit-details-marker]:hidden">
                    <summary className="p-3 text-sm font-bold text-slate-700 cursor-pointer list-none flex items-center justify-between border-b border-slate-200/50 group-open:border-transparent">
                        <span className="flex items-center gap-2">
                            <Search size={16} className="text-brand-blue" />
                            <span className="group-open:hidden">Exibir Pesquisa e Filtros de Mapa</span>
                            <span className="hidden group-open:inline">Ocultar Pesquisa e Filtros de Mapa</span>
                        </span>
                    </summary>
                    <div className="p-3 hidden group-open:flex gap-3 flex-wrap items-center text-sm border-t border-slate-200 md:border-t-0">
                        <span className="font-bold text-slate-600 hidden md:inline">Pesquisar por:</span>

                        <FilterSelect value={filterAno} onChange={e => setFilterAno(e.target.value)} options={filterOptions.anos} label="Ano" />
                        <FilterSelect value={filterTrimestre} onChange={e => setFilterTrimestre(e.target.value)} options={filterOptions.trimestres} label="Trimestre" />
                        <FilterSelect value={filterPrograma} onChange={e => setFilterPrograma(e.target.value)} options={filterOptions.programas} label="Programa" />
                        <FilterSelect value={filterMunicipio} onChange={e => setFilterMunicipio(e.target.value)} options={filterOptions.municipios} label="Município" />
                        <FilterSelect value={filterStatus} onChange={e => setFilterStatus(e.target.value)} options={filterOptions.statuses} label="Status" />

                        <button
                            onClick={() => {
                                setFilterAno(''); setFilterTrimestre(''); setFilterPrograma(''); setFilterMunicipio(''); setFilterStatus('');
                            }}
                            className="text-slate-500 underline ml-2 hover:text-brand-blue"
                        >
                            Limpar Filtros
                        </button>
                    </div>
                </details>
            )}

            {/* Real Leaflet Map Container */}
            <div className="flex-1 w-full relative z-0 bg-slate-200">
                <MapContainer center={[-5.0, -50.0]} zoom={6} scrollWheelZoom={true} style={{ height: "100%", width: "100%" }}>
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png"
                    />

                    <MapResizer isFullscreen={isFullscreen} />
                    {mapBounds && <ChangeView bounds={mapBounds} isFullscreen={isFullscreen} />}

                    {/* GeoJSON layer for visual boundary if desired (opacity 0 to just be there or a light stroke) */}
                    {geoData && !filterMunicipio && (
                        <GeoJSON data={geoData} interactive={false} style={{ color: '#46ec13', weight: 1, fillOpacity: 0.05 }} />
                    )}

                    {/* Mensagem quando sem pontos */}
                    {filteredData.length === 0 && (
                        <div className="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                            <div className="bg-white/90 rounded-xl px-5 py-3 shadow-md border border-slate-200 text-slate-500 text-sm font-medium">
                                {data.length === 0
                                    ? '⏳ Carregando dados do servidor...'
                                    : '🔍 Nenhum ponto com coordenadas válidas para os filtros selecionados.'}
                            </div>
                        </div>
                    )}

                    {filteredData.map((pt, idx) => {
                        // Azul (NORMAL), Laranja (SUSPEITA) e vermelho (POSITIVO/FOCO)
                        const lat = parseFloat(pt.latitude);
                        const lng = parseFloat(pt.longitude);
                        const color = pt.status === 'FOCO' || pt.status === 'POSITIVO' ? '#dc2626' : (pt.status === 'SUSPEITA' ? '#f97316' : '#3b82f6');
                        const copyContent = `Programa: ${pt.nome_programa || 'N/A'}\nMunicípio: ${pt.municipio || 'N/A'}\nPropriedade: ${pt.nome_propriedade || 'N/A'}\nCultura: ${pt.cultura || 'N/A'}\nCoordenadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}\nStatus: ${pt.status || 'NORMAL'}`;

                        return (
                            <CircleMarker
                                key={`${idx}-${lat}-${lng}`}
                                center={[lat, lng]}
                                pathOptions={{ color: 'white', fillColor: color, fillOpacity: 0.85, weight: 1 }}
                                radius={isMobile ? 4 : 7}
                            >
                                <Popup className="custom-popup" minWidth={220}>
                                    <div className="p-1">
                                        <div className="flex justify-between items-start mb-2 border-b border-slate-100 pb-2">
                                            <h3 className="font-bold text-slate-800 leading-tight pr-4 text-sm">{pt.nome_programa || 'Programa N/A'}</h3>
                                            <button
                                                onClick={(e) => { e.stopPropagation(); copyToClipboard(copyContent); }}
                                                className="text-slate-400 hover:text-brand-blue bg-slate-50 hover:bg-slate-100 p-1.5 rounded-md transition-colors"
                                                title="Copiar dados para área de transferência"
                                            >
                                                <Copy size={14} />
                                            </button>
                                        </div>
                                        {pt.nome_propriedade && <p className="text-xs text-slate-600 mb-1"><strong>Propriedade:</strong> {pt.nome_propriedade}</p>}
                                        <p className="text-xs text-slate-600 mb-1"><strong>Município:</strong> {pt.municipio}</p>
                                        <p className="text-xs text-slate-600 mb-1"><strong>Cultura:</strong> {pt.cultura || 'N/A'}</p>
                                        <p className="text-xs text-slate-600 mb-1"><strong>Coordenadas:</strong> {pt.latitude}, {pt.longitude}</p>
                                        <p className="text-xs mt-2 pt-2 border-t border-slate-100">
                                            Status: <span className="font-bold uppercase" style={{ color: color }}>{pt.status || 'NORMAL'}</span>
                                        </p>
                                    </div>
                                </Popup>
                            </CircleMarker>
                        )
                    })}
                </MapContainer>
            </div>
        </div>
    );
}

function FilterSelect({ label, value, options, onChange }) {
    if (!options || options.length === 0) return null;
    return (
        <select
            value={value}
            onChange={onChange}
            className="border border-slate-200 rounded-md bg-white text-slate-700 px-2 py-1 outline-none focus:border-brand-blue"
        >
            <option value="">{label} (Todos)</option>
            {options.map(opt => (
                <option key={opt} value={opt}>{opt}</option>
            ))}
        </select>
    );
}
