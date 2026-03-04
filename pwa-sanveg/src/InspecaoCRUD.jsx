import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, MapPin, CheckCircle, FileText, Crosshair, Navigation, Check, AlertTriangle, XCircle, Info } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';
import clsx from 'clsx';
import { gerarTermoInspecaoPDF, gerarTermoColetaPDF } from './pdfGenerator';

// ─── Modal de Alerta / Informação / Sucesso ───────────────────────────────────
function AppModal({ modal, onClose }) {
    if (!modal) return null;
    const config = {
        success: { icon: <CheckCircle size={32} />, color: 'text-emerald-500', bg: 'bg-emerald-50', border: 'border-emerald-200', btnColor: 'bg-emerald-500 hover:bg-emerald-600' },
        error: { icon: <XCircle size={32} />, color: 'text-red-500', bg: 'bg-red-50', border: 'border-red-200', btnColor: 'bg-red-500 hover:bg-red-600' },
        warning: { icon: <AlertTriangle size={32} />, color: 'text-amber-500', bg: 'bg-amber-50', border: 'border-amber-200', btnColor: 'bg-amber-500 hover:bg-amber-600' },
        info: { icon: <Info size={32} />, color: 'text-brand-blue', bg: 'bg-brand-blue/5', border: 'border-brand-blue/20', btnColor: 'bg-brand-blue hover:bg-brand-blue-light' },
    }[modal.type || 'info'];
    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div className="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 flex flex-col items-center gap-4 text-center animate-in fade-in zoom-in-95 duration-200">
                <div className={clsx('w-16 h-16 rounded-2xl flex items-center justify-center', config.bg, config.border, 'border', config.color)}>
                    {config.icon}
                </div>
                {modal.title && <h3 className="text-lg font-bold text-slate-800">{modal.title}</h3>}
                <p className="text-sm text-slate-600 leading-relaxed">{modal.message}</p>
                <button
                    onClick={onClose}
                    className={clsx('w-full py-2.5 rounded-xl font-bold text-white transition-colors', config.btnColor)}
                >OK</button>
            </div>
        </div>
    );
}

// ─── Modal de Confirmação ─────────────────────────────────────────────────────
function ConfirmModal({ confirm, onCancel, onConfirm }) {
    if (!confirm) return null;
    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div className="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 flex flex-col items-center gap-4 text-center animate-in fade-in zoom-in-95 duration-200">
                <div className="w-16 h-16 rounded-2xl flex items-center justify-center bg-red-50 border border-red-200 text-red-500">
                    <Trash2 size={32} />
                </div>
                <h3 className="text-lg font-bold text-slate-800">{confirm.title || 'Confirmar exclusão'}</h3>
                <p className="text-sm text-slate-600 leading-relaxed">{confirm.message}</p>
                <div className="flex gap-3 w-full">
                    <button
                        onClick={onCancel}
                        className="flex-1 py-2.5 rounded-xl font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                    >Cancelar</button>
                    <button
                        onClick={onConfirm}
                        className="flex-1 py-2.5 rounded-xl font-bold text-white bg-red-500 hover:bg-red-600 transition-colors"
                    >Excluir</button>
                </div>
            </div>
        </div>
    );
}

// Utilitário para converter DMS para Decimal
function convertDMStoDecimal(degrees, minutes, seconds, direction) {
    let decimal = Number(degrees) + (Number(minutes) / 60) + (Number(seconds) / 3600);
    if (direction === 'S' || direction === 'O' || direction === 'W') {
        decimal = decimal * -1;
    }
    return decimal.toFixed(8);
}

// Subcomponente de Coordenadas
function CoordenadaInput({ label, value, onChange, onCapture, type = 'lat' }) {
    const [mode, setMode] = useState('decimal'); // 'decimal' | 'dms'
    const [dms, setDms] = useState({ d: '', m: '', s: '', dir: type === 'lat' ? 'S' : 'O' });

    const handleDmsChange = (field, val) => {
        const newDms = { ...dms, [field]: val };
        setDms(newDms);
        if (newDms.d !== '' && newDms.m !== '' && newDms.s !== '') {
            onChange(convertDMStoDecimal(newDms.d, newDms.m, newDms.s, newDms.dir));
        }
    };

    return (
        <div className="flex flex-col gap-2 p-4 bg-white border border-slate-200 rounded-xl">
            <div className="flex items-center justify-between mb-1">
                <label className="text-sm font-bold text-slate-700">{label}</label>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => setMode(mode === 'decimal' ? 'dms' : 'decimal')}
                        className="text-xs font-semibold text-brand-blue hover:text-brand-blue-light transition-colors"
                    >
                        {mode === 'decimal' ? 'Usar GMS' : 'Usar Decimal'}
                    </button>
                    {onCapture && (
                        <button
                            type="button"
                            onClick={onCapture}
                            className="flex items-center gap-1 text-xs font-bold text-white bg-blue-500 hover:bg-blue-600 px-2 py-1 rounded-md transition-colors shadow-sm"
                        >
                            <Crosshair size={12} /> GPS
                        </button>
                    )}
                </div>
            </div>

            {mode === 'decimal' ? (
                <input
                    type="number"
                    step="0.00000001"
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={type === 'lat' ? "Ex: -1.45502" : "Ex: -48.5024"}
                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                />
            ) : (
                <div className="flex items-center gap-2">
                    <input
                        type="number"
                        placeholder="Graus"
                        value={dms.d}
                        onChange={(e) => handleDmsChange('d', e.target.value)}
                        className="w-full px-2 py-2 rounded-xl border border-slate-200 text-sm focus:border-brand-blue"
                    />°
                    <input
                        type="number"
                        placeholder="Min"
                        value={dms.m}
                        onChange={(e) => handleDmsChange('m', e.target.value)}
                        className="w-full px-2 py-2 rounded-xl border border-slate-200 text-sm focus:border-brand-blue"
                    />'
                    <input
                        type="number"
                        placeholder="Seg"
                        value={dms.s}
                        onChange={(e) => handleDmsChange('s', e.target.value)}
                        className="w-full px-2 py-2 rounded-xl border border-slate-200 text-sm focus:border-brand-blue"
                    />"
                    <select
                        value={dms.dir}
                        onChange={(e) => handleDmsChange('dir', e.target.value)}
                        className="px-2 py-2 rounded-xl border border-slate-200 text-sm focus:border-brand-blue bg-white"
                    >
                        {type === 'lat' ? (
                            <><option value="N">N</option><option value="S">S</option></>
                        ) : (
                            <><option value="L">L (E)</option><option value="O">O (W)</option></>
                        )}
                    </select>
                </div>
            )}
        </div>
    );
}


export default function InspecaoCRUD({ user }) {
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const ITEMS_PER_PAGE = 10;

    // Grid list filters
    const [filtroGridPrograma, setFiltroGridPrograma] = useState('');
    const [filtroGridAno, setFiltroGridAno] = useState('');
    const [filtroGridTrimestre, setFiltroGridTrimestre] = useState('');
    const [filtroGridMunicipio, setFiltroGridMunicipio] = useState('');

    const [filtroMunicipio, setFiltroMunicipio] = useState('');

    // Fetch collections correctly wrapping the Dexie queries
    const inspecoes = useLiveQuery(() => db.termo_inspecao.toArray()) || [];
    const areas = useLiveQuery(() => db.area_inspecionada.toArray()) || [];
    const propriedades = useLiveQuery(() => db.propriedades.toArray()) || [];
    const programas = useLiveQuery(() => db.programas.toArray()) || [];
    const usuarios = useLiveQuery(() => db.usuarios.toArray()) || [];
    const hospedeiros = useLiveQuery(() => db.hospedeiros.toArray()) || [];

    const municipios_disponiveis = [...new Set(propriedades.map(p => p.municipio).filter(Boolean))].sort();

    const [currentInspecao, setCurrentInspecao] = useState(null);
    const [activeTab, setActiveTab] = useState('dados');

    // Estado da sub-ficha (Área Inspecionada)
    const [showAreaModal, setShowAreaModal] = useState(false);
    const [currentArea, setCurrentArea] = useState(null);

    // ─── Modais customizados ──────────────────────────────────────────────────
    const [appModal, setAppModal] = useState(null);   // { type, title, message }
    const [confirmModal, setConfirmModal] = useState(null); // { title, message, onConfirm }
    const showAlert = (message, type = 'info', title = null) => setAppModal({ message, type, title });
    const showConfirm = (title, message, onConfirm) => setConfirmModal({ title, message, onConfirm });

    const filteredInspecoes = inspecoes.filter(i => {
        // Text Search Filtering
        const term = searchTerm.toLowerCase();
        const matchesSearch = (i.termo_inspecao || '').toLowerCase().includes(term) ||
            (i.data_inspecao || '').includes(term);

        if (!matchesSearch) return false;

        // ComboBox Filtering
        if (filtroGridPrograma && i.id_programa !== filtroGridPrograma) return false;

        if (filtroGridMunicipio) {
            const prop = propriedades.find(p => p.id === i.id_propriedade);
            if (!prop || prop.municipio !== filtroGridMunicipio) return false;
        }

        if (filtroGridAno || filtroGridTrimestre) {
            if (!i.data_inspecao) return false;
            const [y, mStr, d] = i.data_inspecao.split('-');
            const m = parseInt(mStr, 10);

            if (filtroGridAno && y !== filtroGridAno) return false;

            if (filtroGridTrimestre) {
                const trimStr = filtroGridTrimestre;
                if (trimStr === '1' && (m > 3)) return false;
                if (trimStr === '2' && (m < 4 || m > 6)) return false;
                if (trimStr === '3' && (m < 7 || m > 9)) return false;
                if (trimStr === '4' && (m < 10)) return false;
            }
        }

        return true;
    }).sort((a, b) => {
        const dateA = a.atualizado_em ? new Date(a.atualizado_em) : (a.data_inspecao ? new Date(a.data_inspecao) : new Date(0));
        const dateB = b.atualizado_em ? new Date(b.atualizado_em) : (b.data_inspecao ? new Date(b.data_inspecao) : new Date(0));
        return dateB - dateA; // Descending (mais recente primeiro)
    });

    const getAnosDisponiveis = () => {
        const anos = inspecoes.map(i => {
            if (!i.data_inspecao) return null;
            return i.data_inspecao.split('-')[0];
        }).filter(Boolean);
        return [...new Set(anos)].sort((a, b) => b - a); // descending
    };


    const totalPages = Math.max(1, Math.ceil(filteredInspecoes.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedItems = filteredInspecoes.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    const handleEdit = (i) => {
        setCurrentInspecao({ ...i });
        const prop = propriedades.find(p => p.id === i.id_propriedade);
        if (prop && prop.municipio) setFiltroMunicipio(prop.municipio);
        else setFiltroMunicipio('');

        setActiveTab('dados');
        setView('form');
    };

    const handleCreate = () => {
        setCurrentInspecao({
            id: crypto.randomUUID(),
            id_propriedade: '',
            id_programa: '',
            data_inspecao: new Date().toISOString().split('T')[0], // Hoje
            termo_inspecao: '',
            termo_manual: false,
            id_usuario: user?.id || '',
            id_auxiliar: ''
        });
        setFiltroMunicipio('');
        setActiveTab('dados');
        setView('form');
    };

    const handleDelete = (id) => {
        showConfirm(
            'Excluir Termo de Inspeção',
            'Deseja realmente excluir este Termo de Inspeção e todas as suas áreas avaliadas? Esta ação não pode ser desfeita.',
            async () => {
                setConfirmModal(null);
                await offlineDelete('termo_inspecao', id);
                const linkedAreas = await db.area_inspecionada.where('id_termo_inspecao').equals(id).toArray();
                for (let ar of linkedAreas) await offlineDelete('area_inspecionada', ar.id);
            }
        );
    };

    const handleSave = async () => {
        if (!currentInspecao.id_propriedade || !currentInspecao.id_programa) {
            showAlert('A identificação da Propriedade e do Programa Fitossanitário são obrigatórios!', 'warning', 'Campos obrigatórios');
            return;
        }
        await offlineSave('termo_inspecao', currentInspecao);
        showAlert('Ficha principal salva com sucesso no dispositivo.', 'success', 'Salvo!');
    };

    // Obter Geolocation do Device
    const getDeviceLocation = () => {
        return new Promise((resolve, reject) => {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    position => resolve(position.coords),
                    error => {
                        showAlert('Erro ao capturar GPS. Verifique se a permissão de localização foi concedida ao navegador.', 'error', 'Erro de GPS');
                        reject(error);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                showAlert('Geolocalização não é suportada neste navegador ou dispositivo.', 'warning', 'GPS indisponível');
                reject();
            }
        });
    };

    const captureAreaGPS = async () => {
        try {
            const coords = await getDeviceLocation();
            setCurrentArea(prev => ({
                ...prev,
                latitude: coords.latitude.toFixed(8),
                longitude: coords.longitude.toFixed(8)
            }));
        } catch (e) { }
    };

    // Sub-fichas lógicas
    const openAreaModal = (area) => {
        if (area) {
            setCurrentArea({ ...area });
        } else {
            setCurrentArea({
                id: crypto.randomUUID(),
                id_termo_inspecao: currentInspecao.id,
                tipo_area: 'Lavouras Múltiplas',
                nome_local: '',
                latitude: '',
                longitude: '',
                especie: '',
                variedade: '',
                numero_plantas: '',
                numero_inspecionadas: '',
                numero_suspeitas: '',
                coletar_mostra: false,
                identificacao_amostra: '',
                raiz: false, caule: false, peciolo: false, folha: false, flor: false, fruto: false, semente: false,
                resultado: 'Sem suspeitas no momento',
                obs: ''
            });
        }
        setShowAreaModal(true);
    };

    const saveArea = async () => {
        await offlineSave('area_inspecionada', currentArea);
        setShowAreaModal(false);
    };

    const deleteArea = (id) => {
        showConfirm(
            'Remover Sub-Área',
            'Deseja realmente remover esta área inspecionada do termo? Os dados lançados serão perdidos.',
            async () => {
                setConfirmModal(null);
                await offlineDelete('area_inspecionada', id);
            }
        );
    };

    const propName = (id) => propriedades.find(p => p.id === id)?.nome || 'Desconhecida';
    const progName = (id) => programas.find(p => p.id === id)?.nome || 'Programa Inválido';

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex items-center justify-between mb-4 md:mb-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <CheckCircle size={20} className="md:w-6 md:h-6" />
                        </div>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <h1 className="text-sm md:text-2xl font-bold uppercase md:normal-case text-slate-800 tracking-wider md:tracking-normal">
                                    Inspeção Fitossanitária
                                </h1>
                                <button
                                    onClick={handleCreate}
                                    className="md:hidden flex items-center justify-center bg-brand-blue text-white w-7 h-7 rounded-full shadow-md hover:bg-brand-blue-light transition-colors"
                                >
                                    <Plus size={16} />
                                </button>
                            </div>
                            <p className="hidden md:block text-sm text-slate-500">Avaliações e Termos (Uso Offline-First Ativado)</p>
                        </div>
                    </div>
                    {/* Desktop Add Button */}
                    <button
                        onClick={handleCreate}
                        className="hidden md:flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> Nova Inspeção
                    </button>
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="border-b border-brand-blue/10 bg-brand-blue/5">
                        <details className="group [&_summary::-webkit-details-marker]:hidden">
                            <summary className="p-4 text-sm font-bold text-brand-blue cursor-pointer list-none flex items-center justify-between border-b border-brand-blue/5 group-open:border-transparent transition-all">
                                <span className="flex items-center gap-2">
                                    <Search size={16} />
                                    <span className="group-open:hidden">Exibir Pesquisa e Filtros</span>
                                    <span className="hidden group-open:inline">Ocultar Pesquisa e Filtros</span>
                                </span>
                            </summary>

                            <div className="p-4 hidden group-open:flex flex-col gap-4">
                                <div className="relative w-full max-w-md">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                                    <input
                                        type="text"
                                        placeholder="Buscar por Termo ou Data..."
                                        value={searchTerm}
                                        onChange={e => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                                        className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                    />
                                </div>
                                <div className="flex flex-col md:flex-row flex-wrap items-start md:items-center gap-3">
                                    <select
                                        value={filtroGridPrograma}
                                        onChange={e => { setFiltroGridPrograma(e.target.value); setCurrentPage(1); }}
                                        className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm text-slate-600 bg-white"
                                    >
                                        <option value="">Todos os Programas...</option>
                                        {programas.map(p => <option key={p.id} value={p.id}>{p.nome}</option>)}
                                    </select>

                                    <select
                                        value={filtroGridMunicipio}
                                        onChange={e => { setFiltroGridMunicipio(e.target.value); setCurrentPage(1); }}
                                        className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm text-slate-600 bg-white"
                                    >
                                        <option value="">Todos os Municípios...</option>
                                        {municipios_disponiveis.map(m => <option key={m} value={m}>{m}</option>)}
                                    </select>

                                    <select
                                        value={filtroGridAno}
                                        onChange={e => { setFiltroGridAno(e.target.value); setCurrentPage(1); }}
                                        className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm text-slate-600 bg-white"
                                    >
                                        <option value="">Qualquer Ano...</option>
                                        {getAnosDisponiveis().map(ano => <option key={ano} value={ano}>{ano}</option>)}
                                    </select>

                                    <select
                                        value={filtroGridTrimestre}
                                        onChange={e => { setFiltroGridTrimestre(e.target.value); setCurrentPage(1); }}
                                        className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue text-sm text-slate-600 bg-white"
                                        disabled={!filtroGridAno && filtroGridTrimestre === ''}
                                    >
                                        <option value="">Qualquer Trimestre...</option>
                                        <option value="1">1º Trimestre (Jan-Mar)</option>
                                        <option value="2">2º Trimestre (Abr-Jun)</option>
                                        <option value="3">3º Trimestre (Jul-Set)</option>
                                        <option value="4">4º Trimestre (Out-Dez)</option>
                                    </select>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-brand-blue/5 text-slate-600 text-sm border-b border-brand-blue/10">
                                    <th className="py-3 px-4 font-semibold uppercase">Nº Termo / Data</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Propriedade</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden lg:table-cell">Programa</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paginatedItems.map(i => (
                                    <tr key={i.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4">
                                            <div className="font-bold text-slate-800">{i.termo_inspecao || 'S/N'}</div>
                                            <div className="text-xs text-slate-500 mt-0.5">{i.data_inspecao || 'Sem data'}</div>
                                        </td>
                                        <td className="py-3 px-4 text-slate-600 hidden md:table-cell font-medium">{propName(i.id_propriedade)}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden lg:table-cell">{progName(i.id_programa)}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">
                                                {areas.some(a => a.id_termo_inspecao === i.id && (String(a.coletar_mostra).toLowerCase() === 'true' || a.coletar_mostra === 1 || a.coletar_mostra === true)) && (
                                                    <button onClick={() => gerarTermoColetaPDF(i.id)} className="p-2 text-slate-400 hover:text-amber-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200" title="Gerar PDF do Termo de Coleta (TCA)">
                                                        <FileText size={18} />
                                                    </button>
                                                )}
                                                <button onClick={() => gerarTermoInspecaoPDF(i.id)} className="p-2 text-slate-400 hover:text-emerald-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200" title="Gerar PDF do Termo de Inspeção">
                                                    <FileText size={18} />
                                                </button>
                                                <button onClick={() => handleEdit(i)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200" title="Editar Termo">
                                                    <Edit2 size={18} />
                                                </button>
                                                <button onClick={() => handleDelete(i.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200" title="Excluir">
                                                    <Trash2 size={18} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredInspecoes.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="py-8 text-center text-slate-500 flex flex-col items-center justify-center">
                                            <CheckCircle size={48} className="text-slate-300 mb-3" />
                                            Nenhuma inspeção listada no dispositivo local.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 mt-auto">
                        <span className="text-sm text-slate-500">
                            Mostrando {startIndex + 1} a {Math.min(startIndex + ITEMS_PER_PAGE, filteredInspecoes.length)} de {filteredInspecoes.length} registros
                        </span>
                        <div className="flex items-center gap-2">
                            <button disabled={currentPage === 1} onClick={() => setCurrentPage(prev => prev - 1)} className="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-sm">Anterior</button>
                            <span className="text-sm font-semibold text-slate-600">{currentPage} / {totalPages}</span>
                            <button disabled={currentPage === totalPages || totalPages === 0} onClick={() => setCurrentPage(prev => prev + 1)} className="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-sm">Próxima</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }


    return (
        <>
            <div className="flex flex-col h-full bg-slate-50">
                <div className="flex items-center gap-4 mb-6">
                    <button onClick={() => setView('list')} className="p-2 rounded-xl bg-white text-slate-600 hover:text-brand-blue shadow-sm border border-slate-100 transition-colors">
                        <ArrowLeft size={20} />
                    </button>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <CheckCircle size={20} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentInspecao.termo_inspecao ? `Inspeção #${currentInspecao.termo_inspecao}` : 'Nova Ficha de Inspeção'}</h1>
                            <p className="hidden md:block text-sm text-slate-500">Cadastro de Avaliação a Campo</p>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1 relative">

                    {/* Abas */}
                    <div className="flex border-b border-brand-blue/10 px-4 pt-4 bg-brand-blue/5 overflow-x-auto gap-4">
                        <button
                            onClick={() => setActiveTab('dados')}
                            className={clsx("pb-3 px-2 text-sm font-bold border-b-2 transition-all flex items-center gap-2", activeTab === 'dados' ? "border-brand-blue text-brand-blue" : "border-transparent text-slate-500 hover:text-slate-700")}
                        >
                            Geral e Identificação
                        </button>
                        <button
                            onClick={() => {
                                if (!currentInspecao.id_propriedade || !currentInspecao.id_programa) {
                                    showAlert("Preencha a Propriedade e o Programa Fitossanitário na aba 'Geral' antes de lançar áreas.", 'warning', 'Dados obrigatórios incompletos');
                                } else {
                                    setActiveTab('areas');
                                }
                            }}
                            className={clsx("pb-3 px-2 text-sm font-bold border-b-2 transition-all flex items-center gap-2", activeTab === 'areas' ? "border-brand-blue text-brand-blue" : "border-transparent text-slate-500 hover:text-slate-700")}
                        >
                            Áreas Avaliadas e Amostragem
                            <span className="bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded-full text-xs ml-1">{areas.filter(a => a.id_termo_inspecao === currentInspecao.id).length}</span>
                        </button>
                    </div>

                    <div className="p-6 flex-1 overflow-y-auto">
                        {activeTab === 'dados' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                                <div className="flex flex-col gap-1.5 md:col-span-2">
                                    <label className="text-sm font-semibold text-slate-600">Nº do Termo de Inspeção (TI)</label>
                                    <div className="flex flex-col gap-2">
                                        <label className="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={currentInspecao.termo_manual || false}
                                                onChange={e => setCurrentInspecao({ ...currentInspecao, termo_manual: e.target.checked, termo_inspecao: e.target.checked ? currentInspecao.termo_inspecao : '' })}
                                                className="rounded text-brand-blue"
                                            />
                                            Preenchido manualmente (via impressa em campo)
                                        </label>
                                        <input
                                            type="text"
                                            value={currentInspecao.termo_manual ? currentInspecao.termo_inspecao : (currentInspecao.termo_inspecao || '')}
                                            disabled={!currentInspecao.termo_manual && !currentInspecao.termo_inspecao}
                                            onChange={e => setCurrentInspecao({ ...currentInspecao, termo_inspecao: e.target.value })}
                                            placeholder={!currentInspecao.termo_manual && !currentInspecao.termo_inspecao ? "(Será gerado pelo sistema ao Salvar: seq_ti/carteira/ano)" : "Ex: 123/2234/2026"}
                                            className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-50 disabled:text-slate-500 font-semibold"
                                        />
                                        {!currentInspecao.termo_manual && !currentInspecao.termo_inspecao && (
                                            <span className="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block w-fit font-medium">Nota: O número automático subirá no PWA na sua próxima Sincronização.</span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-sm font-semibold text-slate-600">Data da Inspeção <span className="text-red-500">*</span></label>
                                    <input
                                        type="date"
                                        value={currentInspecao.data_inspecao}
                                        onChange={e => setCurrentInspecao({ ...currentInspecao, data_inspecao: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                    />
                                </div>

                                <div className="flex flex-col gap-1.5 md:col-span-1">
                                    <label className="text-sm font-semibold text-slate-600">Responsável Técnico / Fiscal <span className="text-red-500">*</span></label>
                                    <select
                                        value={currentInspecao.id_usuario || ''}
                                        onChange={e => setCurrentInspecao({ ...currentInspecao, id_usuario: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-brand-blue/5 border-brand-blue/20 font-medium text-slate-800"
                                    >
                                        <option value="">Selecione o Fiscal...</option>
                                        {usuarios.filter(u => u.ativo && (u.role === 'admin' || u.role === 'comum' || u.role === 'superusuario')).map(u => (
                                            <option key={u.id} value={u.id}>{u.nome} ({u.role})</option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex flex-col gap-1.5 md:col-span-1">
                                    <label className="text-sm font-semibold text-slate-600">Técnico Auxiliar (Participante Opcional)</label>
                                    <select
                                        value={currentInspecao.id_auxiliar || ''}
                                        onChange={e => setCurrentInspecao({ ...currentInspecao, id_auxiliar: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                    >
                                        <option value="">Inspeção Individual (Sem assistente/auxiliar na equipe)</option>
                                        {usuarios.filter(u => u.ativo && u.id !== currentInspecao.id_usuario).map(u => (
                                            <option key={u.id} value={u.id}>{u.nome} ({u.role})</option>
                                        ))}
                                    </select>
                                </div>

                                <div className="col-span-1 md:col-span-2 border-t border-slate-100 my-2"></div>

                                <div className="flex flex-col gap-1.5 md:col-span-1">
                                    <label className="text-sm font-semibold text-slate-600">Filtro Rápido: Município</label>
                                    <select
                                        value={filtroMunicipio}
                                        onChange={e => {
                                            setFiltroMunicipio(e.target.value);
                                            setCurrentInspecao({ ...currentInspecao, id_propriedade: '' });
                                        }}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                    >
                                        <option value="">Listar propriedades de todo o estado...</option>
                                        {municipios_disponiveis.map(m => (
                                            <option key={m} value={m}>{m}</option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex flex-col gap-1.5 md:col-span-1">
                                    <label className="text-sm font-semibold text-slate-600">Local Alvo: Propriedade Rural <span className="text-red-500">*</span></label>
                                    <select
                                        value={currentInspecao.id_propriedade}
                                        onChange={e => setCurrentInspecao({ ...currentInspecao, id_propriedade: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-brand-blue/5 border-brand-blue/20 font-medium text-slate-800"
                                    >
                                        <option value="">Selecione a Propriedade...</option>
                                        {propriedades.filter(p => !filtroMunicipio || p.municipio === filtroMunicipio).map(p => (
                                            <option key={p.id} value={p.id}>{p.nome} {p.municipio ? `- ${p.municipio}` : ''}</option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex flex-col gap-1.5 md:col-span-2">
                                    <label className="text-sm font-semibold text-slate-600">Programa Fitossanitário Avaliado <span className="text-red-500">*</span></label>
                                    <select
                                        value={currentInspecao.id_programa}
                                        onChange={e => setCurrentInspecao({ ...currentInspecao, id_programa: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all bg-emerald-50/50 border-emerald-200 font-medium text-slate-800"
                                    >
                                        <option value="">Indique qual alvo fitossanitário da inspeção...</option>
                                        {programas.map(p => (
                                            <option key={p.id} value={p.id}>{p.nome}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        )}

                        {activeTab === 'areas' && (
                            <div className="flex flex-col h-full max-w-6xl mx-auto">
                                <div className="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 className="font-bold text-slate-700 text-lg">Sub-Áreas de Inspeção</h3>
                                        <p className="hidden md:block text-sm text-slate-500">Seções e lotes verificados dentro da propriedade</p>
                                    </div>
                                    <button
                                        onClick={() => openAreaModal(null)}
                                        className="flex items-center gap-1.5 bg-brand-blue text-white hover:bg-brand-blue-light px-4 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-brand-blue/20 transition-colors"
                                    >
                                        <Plus size={16} /> <span className="hidden md:inline">Lançar Nova Área/Lote</span>
                                    </button>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {areas.filter(a => a.id_termo_inspecao === currentInspecao.id).map(a => (
                                        <div key={a.id} className="p-4 bg-white border border-slate-200 shadow-sm rounded-xl relative group hover:border-brand-blue/30 transition-colors">
                                            <h4 className="font-bold text-slate-800 text-lg mb-1">{a.nome_local || 'Lote sem nome'}</h4>
                                            <p className="text-sm text-slate-500 flex items-center gap-2 mb-2"><Navigation size={14} /> {a.latitude}, {a.longitude}</p>
                                            <div className="flex gap-2 mb-4 flex-wrap">
                                                <span className="px-2 py-1 text-xs font-bold rounded-md bg-slate-100 text-slate-600">{a.especie || 'S/ Espécie'}</span>
                                                {a.coletar_mostra && <span className="px-2 py-1 text-xs font-bold rounded-md bg-amber-100 text-amber-700">Amostra Coletada</span>}
                                            </div>
                                            <div className="absolute top-4 right-4 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white p-1 rounded-lg border border-slate-100 shadow-sm">
                                                <button onClick={() => openAreaModal(a)} className="p-1.5 text-slate-400 hover:text-brand-blue rounded"><Edit2 size={16} /></button>
                                                <button onClick={() => deleteArea(a.id)} className="p-1.5 text-slate-400 hover:text-red-500 rounded"><Trash2 size={16} /></button>
                                            </div>
                                            <div className="flex justify-between items-center bg-slate-50 rounded-lg p-2 mt-2">
                                                <span className="text-xs font-semibold text-slate-500">Resultado:</span>
                                                <span className={clsx("text-xs font-bold px-2 py-0.5 rounded-full",
                                                    a.resultado === 'Foco Detectado' ? "bg-red-100 text-red-700" :
                                                        a.resultado?.includes('Suspeita') ? "bg-amber-100 text-amber-700" : "bg-green-100 text-green-700"
                                                )}>{a.resultado}</span>
                                            </div>
                                        </div>
                                    ))}
                                    {areas.filter(a => a.id_termo_inspecao === currentInspecao.id).length === 0 && (
                                        <div className="col-span-1 md:col-span-2 py-12 text-center text-slate-400 bg-slate-50 border border-dashed border-slate-300 rounded-2xl">
                                            Clique no botão acima para inspecionar um novo lote, estufa ou área geográfica.
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="p-4 border-t border-slate-100 bg-slate-50 flex justify-start gap-3 z-10">
                        <button
                            onClick={() => setView('list')}
                            className="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-xl transition-colors"
                        >
                            Concluir e Voltar
                        </button>
                        <button
                            onClick={handleSave}
                            className="flex items-center gap-2 px-6 py-2 bg-brand-blue hover:bg-brand-blue-light text-white font-bold rounded-xl shadow-md shadow-brand-blue/20 transition-colors"
                        >
                            <Save size={18} /> <span className="hidden md:inline">Salvar Ficha Principal</span>
                        </button>
                    </div>
                </div>

                {/* MODAL para Lançamento Detalhado da Área Inspecionada */}
                {showAreaModal && currentArea && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
                        <div className="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-full overflow-hidden flex flex-col my-4">
                            <div className="p-5 border-b border-slate-100 flex justify-between items-center bg-brand-blue/5">
                                <h2 className="text-xl font-bold text-brand-blue flex items-center gap-2"><MapPin size={22} /> Áreas Inspecionadas na Propriedade</h2>
                            </div>

                            <div className="p-6 overflow-y-auto flex-1 bg-slate-50">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white p-6 rounded-2xl border border-slate-100 mb-6">
                                    <div className="flex flex-col gap-1.5 md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-600">Localização Coordenada Geográfica (Captura Offline ou Manual)</label>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <CoordenadaInput label="Latitude (Y)" type="lat" value={currentArea.latitude} onChange={v => setCurrentArea({ ...currentArea, latitude: v })} onCapture={captureAreaGPS} />
                                            <CoordenadaInput label="Longitude (X)" type="lng" value={currentArea.longitude} onChange={v => setCurrentArea({ ...currentArea, longitude: v })} />
                                        </div>
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Nome Trivial ou Ref. do Local</label>
                                        <input type="text" value={currentArea.nome_local || ''} onChange={e => setCurrentArea({ ...currentArea, nome_local: e.target.value })} placeholder="Ex: Lote 14, Talhão do Eucalipto..." className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Espécie Avaliada e Variedade</label>
                                        <div className="flex gap-2">
                                            <select value={currentArea.especie || ''} onChange={e => setCurrentArea({ ...currentArea, especie: e.target.value })} className="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all bg-white text-slate-700">
                                                <option value="">Selecione a Espécie...</option>
                                                {hospedeiros.filter(h => h.id_programa === currentInspecao.id_programa).map(h => (
                                                    <option key={h.id} value={h.nome_cientifico}>{h.nome_cientifico}</option>
                                                ))}
                                            </select>
                                            <input type="text" value={currentArea.variedade || ''} onChange={e => setCurrentArea({ ...currentArea, variedade: e.target.value })} placeholder="Var." className="w-1/3 px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Material de Multiplicação</label>
                                        <input type="text" value={currentArea.material_multiplicacao || ''} onChange={e => setCurrentArea({ ...currentArea, material_multiplicacao: e.target.value })} placeholder="Ex: Sementes, Mudas..." className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Origem do Material</label>
                                        <input type="text" value={currentArea.origem || ''} onChange={e => setCurrentArea({ ...currentArea, origem: e.target.value })} placeholder="Local de Origem" className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Idade do Plantio</label>
                                        <input type="number" step="0.1" value={currentArea.idade_plantio || ''} onChange={e => setCurrentArea({ ...currentArea, idade_plantio: e.target.value })} placeholder="Em meses/anos" className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Área Plantada (hectares)</label>
                                        <input type="number" step="0.01" value={currentArea.area_plantada || ''} onChange={e => setCurrentArea({ ...currentArea, area_plantada: e.target.value })} placeholder="Ex: 15.5" className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                    </div>

                                    <div className="col-span-1 md:col-span-2 border-t border-slate-100 my-2"></div>

                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-slate-600">Plantas Lote / Inspecionadas <span className="text-xs text-slate-400 font-normal ml-2">(Quantidade)</span></label>
                                        <div className="flex gap-2">
                                            <input type="number" value={currentArea.numero_plantas || ''} onChange={e => setCurrentArea({ ...currentArea, numero_plantas: e.target.value })} placeholder="Total no Lote" className="w-1/2 px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                            <input type="number" value={currentArea.numero_inspecionadas || ''} onChange={e => setCurrentArea({ ...currentArea, numero_inspecionadas: e.target.value })} placeholder="Avaliadas" className="w-1/2 px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue transition-all" />
                                        </div>
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <label className="text-sm font-semibold text-red-500">Número de Suspeitas</label>
                                        <input type="number" value={currentArea.numero_suspeitas || ''} onChange={e => setCurrentArea({ ...currentArea, numero_suspeitas: e.target.value })} placeholder="Quantas c/ sintomas?" className="px-3 py-2.5 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:border-red-500 transition-all font-bold text-red-600" />
                                    </div>
                                </div>

                                <div className="bg-white p-6 rounded-2xl border border-slate-100 flex flex-col gap-4">
                                    <label className="flex items-center gap-3 cursor-pointer group pb-4 border-b border-slate-100">
                                        <div className={clsx("w-6 h-6 rounded-md flex items-center justify-center transition-all", currentArea.coletar_mostra ? 'bg-brand-blue text-white' : 'bg-slate-200 border border-slate-300')}>
                                            {currentArea.coletar_mostra && <Check size={16} strokeWidth={3} />}
                                        </div>
                                        <input
                                            type="checkbox"
                                            checked={currentArea.coletar_mostra || false}
                                            onChange={e => setCurrentArea({ ...currentArea, coletar_mostra: e.target.checked })}
                                            className="hidden"
                                        />
                                        <span className={clsx("text-lg font-bold transition-colors", currentArea.coletar_mostra ? "text-brand-blue" : "text-slate-600")}>Ativar Termo de Coleta / Amostragem</span>
                                    </label>

                                    {currentArea.coletar_mostra && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 animate-in fade-in slide-in-from-top-4">
                                            <div className="flex flex-col gap-1.5 md:col-span-2 p-4 bg-brand-blue/5 rounded-2xl border border-brand-blue/20">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <input
                                                        type="checkbox"
                                                        id="tc_manual_check"
                                                        checked={currentArea.termo_coleta_manual || false}
                                                        onChange={e => setCurrentArea({ ...currentArea, termo_coleta_manual: e.target.checked, termo_coleta_gerado: e.target.checked ? currentArea.termo_coleta_gerado : '' })}
                                                        className="rounded text-brand-blue"
                                                    />
                                                    <label htmlFor="tc_manual_check" className="text-sm font-bold text-slate-700 cursor-pointer">
                                                        Nº do Termo de Coleta preenchido manualmente
                                                    </label>
                                                </div>
                                                <input
                                                    type="text"
                                                    value={currentArea.termo_coleta_manual ? currentArea.termo_coleta_gerado : (currentArea.termo_coleta_gerado || '')}
                                                    disabled={!currentArea.termo_coleta_manual}
                                                    onChange={e => setCurrentArea({ ...currentArea, termo_coleta_gerado: e.target.value })}
                                                    placeholder={currentArea.termo_coleta_manual ? "Ex: TC-1234/26" : "Será gerado sequencialmente pela Nuvem ao sincronizar (seq_tc/ano)"}
                                                    className="px-3 py-2.5 rounded-xl border border-brand-blue/30 focus:outline-none focus:border-brand-blue transition-all disabled:bg-slate-100 disabled:text-slate-500 font-semibold"
                                                />
                                            </div>
                                            <div className="flex flex-col gap-1.5 mt-2">
                                                <label className="text-sm font-semibold text-slate-600">ID Lacre / Amostra</label>
                                                <input type="text" value={currentArea.identificacao_amostra || ''} onChange={e => setCurrentArea({ ...currentArea, identificacao_amostra: e.target.value })} placeholder="Ex: AM-00123" className="px-3 py-2.5 rounded-xl border border-brand-blue/30 focus:outline-none focus:border-brand-blue transition-all" />
                                            </div>
                                            <div className="flex flex-col gap-1.5">
                                                <label className="text-sm font-semibold text-slate-600">Partes Coletadas</label>
                                                <div className="grid grid-cols-3 gap-2 mt-2">
                                                    {['raiz', 'caule', 'peciolo', 'folha', 'fruto', 'flor', 'semente'].map(part => (
                                                        <label key={part} className="flex items-center gap-2 text-sm capitalize">
                                                            <input type="checkbox" checked={currentArea[part] || false} onChange={e => setCurrentArea({ ...currentArea, [part]: e.target.checked })} className="rounded text-brand-blue" />
                                                            {part}
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex flex-col gap-1.5 mt-2">
                                        <label className="text-sm font-semibold text-slate-600">Parecer Oficial / Resultado de Campo</label>
                                        <select
                                            value={currentArea.resultado}
                                            onChange={e => setCurrentArea({ ...currentArea, resultado: e.target.value })}
                                            className={clsx("px-3 py-2.5 rounded-xl border focus:outline-none transition-all font-bold",
                                                currentArea.resultado?.includes('Suspeita') ? "bg-amber-50 border-amber-300 text-amber-700" :
                                                    currentArea.resultado === 'Foco Detectado' ? "bg-red-50 border-red-300 text-red-700" :
                                                        "bg-green-50 border-green-300 text-green-700"
                                            )}
                                        >
                                            <option value="Sem suspeitas no momento">S/ Sintomas / Sem suspeitas no momento</option>
                                            <option value="Suspeita Encontrada">Suspeita Encontrada (Aguardando Lab)</option>
                                            <option value="Foco Detectado">Foco Confirmado / Visual Detrimento</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div className="p-5 border-t border-slate-100 flex justify-end gap-3 bg-white">
                                <button onClick={() => setShowAreaModal(false)} className="px-5 py-2.5 font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"><span className="md:hidden text-lg leading-none">&times;</span> <span className="hidden md:inline">Cancelar</span></button>
                                <button onClick={saveArea} className="px-6 py-2.5 bg-brand-blue hover:bg-brand-blue-light text-white font-bold rounded-xl shadow-md transition-colors"><Save size={18} className="md:hidden" /> <span className="hidden md:inline">Guardar Laudo da Área</span></button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
            <AppModal modal={appModal} onClose={() => setAppModal(null)} />
            <ConfirmModal
                confirm={confirmModal}
                onCancel={() => setConfirmModal(null)}
                onConfirm={confirmModal?.onConfirm}
            />
        </>
    );
}
