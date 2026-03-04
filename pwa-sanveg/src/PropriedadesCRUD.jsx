import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Home, Crosshair } from 'lucide-react';
import clsx from 'clsx';

// Remove Mock Data
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';
import axios from 'axios';

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

export default function PropriedadesCRUD() {
    const [view, setView] = useState('list'); // 'list' | 'form'
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const ITEMS_PER_PAGE = 10;

    const propriedades = useLiveQuery(() => db.propriedades.toArray()) || [];
    const produtores = useLiveQuery(() => db.produtores.toArray()) || [];

    // Form State
    const [currentPropriedade, setCurrentPropriedade] = useState(null);
    const [activeTab, setActiveTab] = useState('dados'); // 'dados' | 'producao' | 'localizacao'

    // IBGE Address Data
    const [ufs, setUfs] = useState([]);
    const [municipiosCache, setMunicipiosCache] = useState({}); // To avoid repeated fetches
    const [currentMunicipios, setCurrentMunicipios] = useState([]);

    // Quick Produtor Registration Flow
    const [showNovoProdutor, setShowNovoProdutor] = useState(false);
    const [novoProdutor, setNovoProdutor] = useState({ id: '', nome: '', cpf_cnpj: '' });

    // Load UFs on mount
    React.useEffect(() => {
        const fetchUfs = async () => {
            try {
                const res = await axios.get('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome');
                setUfs(res.data);
            } catch (err) {
                console.error("Erro ao carregar UFs do IBGE:", err);
            }
        };
        fetchUfs();
    }, []);

    // Load Municipios when UF changes
    React.useEffect(() => {
        if (!currentPropriedade?.UF) {
            setCurrentMunicipios([]);
            return;
        }

        const ufSigla = currentPropriedade.UF;

        // Use cache if available
        if (municipiosCache[ufSigla]) {
            setCurrentMunicipios(municipiosCache[ufSigla]);
            return;
        }

        const fetchMunicipios = async () => {
            try {
                const res = await axios.get(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${ufSigla}/municipios?orderBy=nome`);
                const loadedMunicipios = res.data.map(m => m.nome);
                setMunicipiosCache(prev => ({ ...prev, [ufSigla]: loadedMunicipios }));
                setCurrentMunicipios(loadedMunicipios);

                // If the selected UF changed but the municipio isn't in the new list, clear the selected municipio
                if (currentPropriedade.municipio && !loadedMunicipios.includes(currentPropriedade.municipio)) {
                    setCurrentPropriedade(prev => ({ ...prev, municipio: '' }));
                }
            } catch (err) {
                console.error("Erro ao carregar Municípios do IBGE:", err);
            }
        };

        fetchMunicipios();
    }, [currentPropriedade?.UF, municipiosCache]);

    const filteredPropriedades = propriedades.filter(p => {
        const term = searchTerm.toLowerCase();
        return (p.nome || '').toLowerCase().includes(term) ||
            (p.n_cadastro || '').toLowerCase().includes(term) ||
            (p.municipio || '').toLowerCase().includes(term);
    });


    const totalPages = Math.max(1, Math.ceil(filteredPropriedades.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedItems = filteredPropriedades.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    const handleEdit = (p) => {
        setCurrentPropriedade({ ...p });
        setActiveTab('dados');
        setView('form');
    };

    const handleCreate = () => {
        setCurrentPropriedade({
            id: crypto.randomUUID(),
            n_cadastro: '',
            nome: '',
            municipio: '',
            UF: 'PA',
            area_total: '',
            destino_producao: '',
            classificacao: '',
            producao_familiar: 'Não',
            endereco: '',
            bairro: '',
            CEP: '',
            latitude: '',
            longitude: '',
            observacoes: '',
            id_proprietario: ''
        });
        setActiveTab('dados');
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir esta propriedade?")) {
            await offlineDelete('propriedades', id);
        }
    };

    const handleSave = async () => {
        await offlineSave('propriedades', currentPropriedade);
        setView('list');
    };

    // Obter Geolocation do Device
    const getDeviceLocation = () => {
        return new Promise((resolve, reject) => {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    position => resolve(position.coords),
                    error => {
                        alert("Erro ao capturar GPS. Verifique se a permissão foi concedida.");
                        reject(error);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                alert("Geolocalização não suportada no seu navegador.");
                reject();
            }
        });
    };

    const capturePropriedadeGPS = async () => {
        try {
            const coords = await getDeviceLocation();
            setCurrentPropriedade(prev => ({
                ...prev,
                latitude: coords.latitude.toFixed(8),
                longitude: coords.longitude.toFixed(8)
            }));
        } catch (e) { }
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex items-center justify-between mb-4 md:mb-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Home size={20} className="md:w-6 md:h-6" />
                        </div>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <h1 className="text-sm md:text-2xl font-bold uppercase md:normal-case text-slate-800 tracking-wider md:tracking-normal">
                                    Propriedades
                                </h1>
                                <button
                                    onClick={handleCreate}
                                    className="md:hidden flex items-center justify-center bg-brand-blue text-white w-7 h-7 rounded-full shadow-md hover:bg-brand-blue-light transition-colors"
                                >
                                    <Plus size={16} />
                                </button>
                            </div>
                            <p className="hidden md:block text-sm text-slate-500">Gerenciamento de áreas de produção e imóveis rurais</p>
                        </div>
                    </div>
                    {/* Desktop Add Button */}
                    <button
                        onClick={handleCreate}
                        className="hidden md:flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> Nova Propriedade
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

                            <div className="p-4 hidden group-open:flex flex-col md:flex-row items-center gap-4">
                                <div className="relative w-full max-w-md">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                                    <input
                                        type="text"
                                        placeholder="Buscar por código, nome ou município..."
                                        value={searchTerm}
                                        onChange={e => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                                        className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                    />
                                </div>
                            </div>
                        </details>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-brand-blue/5 text-slate-600 text-sm border-b border-brand-blue/10">
                                    <th className="py-3 px-4 font-semibold uppercase">Nº Cadastro / Propriedade</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Município / UF</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden lg:table-cell">Área Total (ha)</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paginatedItems.map(p => (
                                    <tr key={p.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4">
                                            <div className="font-bold text-slate-800">{p.n_cadastro || 'S/N'}</div>
                                            <div className="text-xs text-slate-500 mt-0.5">{p.nome}</div>
                                        </td>
                                        <td className="py-3 px-4 text-slate-600 hidden md:table-cell">{p.municipio} - {p.UF}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden lg:table-cell">{p.area_total}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => handleEdit(p)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>
                                                <button onClick={() => handleDelete(p.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredPropriedades.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="py-8 text-center text-slate-500">Nenhuma propriedade encontrada.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 mt-auto">
                        <span className="text-sm text-slate-500">
                            Mostrando {startIndex + 1} a {Math.min(startIndex + ITEMS_PER_PAGE, filteredPropriedades.length)} de {filteredPropriedades.length} registros
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
        <div className="flex flex-col h-full bg-slate-50">
            <div className="flex items-center gap-4 mb-6">
                <button onClick={() => setView('list')} className="p-2 rounded-xl bg-white text-slate-600 hover:text-brand-blue shadow-sm border border-slate-100 transition-colors">
                    <ArrowLeft size={20} />
                </button>
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                        <Home size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentPropriedade.nome ? currentPropriedade.nome : 'Nova Propriedade'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">{currentPropriedade.n_cadastro ? `Cadastro: ${currentPropriedade.n_cadastro}` : 'Preencha os dados abaixo'}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                {/* Tabs Header */}
                <div className="flex border-b border-brand-blue/10 px-4 pt-4 bg-brand-blue/5 overflow-x-auto gap-4">
                    <TabButton active={activeTab === 'dados'} onClick={() => setActiveTab('dados')} label="Dados Gerais" />
                    <TabButton active={activeTab === 'localizacao'} onClick={() => setActiveTab('localizacao')} label="Localização e Contato" />
                    <TabButton active={activeTab === 'producao'} onClick={() => setActiveTab('producao')} label="Área e Produção" />
                </div>

                {/* Tab Content */}
                <div className="p-6 flex-1 overflow-y-auto">
                    {activeTab === 'dados' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Nome da Propriedade <span className="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    value={currentPropriedade.nome}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, nome: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Nº Cadastro</label>
                                <input
                                    type="text"
                                    placeholder="Ex: PR-2023-001"
                                    value={currentPropriedade.n_cadastro}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, n_cadastro: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Classificação</label>
                                <select
                                    value={currentPropriedade.classificacao}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, classificacao: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                >
                                    <option value="">Selecione...</option>
                                    <option value="Agricultura Familiar">Agricultura Familiar</option>
                                    <option value="Médio Produtor">Médio Produtor</option>
                                    <option value="Grande Empreendimento">Grande Empreendimento</option>
                                </select>
                            </div>
                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Observações Gerais</label>
                                <textarea
                                    value={currentPropriedade.observacoes}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, observacoes: e.target.value })}
                                    rows={3}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all resize-none"
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'localizacao' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div className="flex flex-col gap-1.5 lg:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Sub-Região / Endereço / Localidade</label>
                                <input
                                    type="text"
                                    value={currentPropriedade.endereco}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, endereco: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">CEP</label>
                                <input
                                    type="text"
                                    value={currentPropriedade.CEP}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, CEP: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5 lg:col-span-1">
                                <label className="text-sm font-semibold text-slate-600">Bairro / Ramal</label>
                                <input
                                    type="text"
                                    value={currentPropriedade.bairro}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, bairro: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">UF / Estado da Federação</label>
                                <select
                                    value={currentPropriedade.UF || ''}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, UF: e.target.value, municipio: '' })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                >
                                    <option value="">Selecione...</option>
                                    {ufs.map(uf => (
                                        <option key={uf.id} value={uf.sigla}>{uf.sigla} - {uf.nome}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Município IBGE</label>
                                <select
                                    value={currentPropriedade.municipio || ''}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, municipio: e.target.value })}
                                    disabled={!currentPropriedade.UF || currentMunicipios.length === 0}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100"
                                >
                                    <option value="">Selecione o município...</option>
                                    {currentMunicipios.map(m => (
                                        <option key={m} value={m}>{m}</option>
                                    ))}
                                </select>
                            </div>

                            {/* MODULE: PROPRIETÁRIO INLINE INJECTION */}
                            <div className="flex flex-col gap-2 lg:col-span-3 bg-slate-100 p-4 rounded-xl border border-slate-200 mt-2 shadow-inner">
                                <label className="text-sm font-bold text-slate-700">Responsável Vinculado (Proprietário ou Arrendatário ativo na terra)</label>
                                <p className="text-xs text-slate-500 mb-1">A lista abaixo apresenta apenas os produtores rurais que atuam em <strong>{currentPropriedade.municipio ? currentPropriedade.municipio : 'Nenhum município selecionado'}</strong>.</p>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <select
                                        value={currentPropriedade.id_proprietario || ''}
                                        onChange={e => setCurrentPropriedade({ ...currentPropriedade, id_proprietario: e.target.value })}
                                        className="px-3 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:border-brand-blue flex-1 bg-white font-medium text-slate-700"
                                    >
                                        <option value="">-- Selecione ou pesquise o Produtor da lista --</option>
                                        {produtores
                                            .filter(p => !currentPropriedade.municipio || p.municipio === currentPropriedade.municipio)
                                            .sort((a, b) => a.nome.localeCompare(b.nome))
                                            .map(p => (
                                                <option key={p.id} value={p.id}>
                                                    {p.nome} {p.cpf_cnpj ? ` | Ref: ${p.cpf_cnpj}` : ''}
                                                </option>
                                            ))}
                                    </select>
                                    <button
                                        type="button"
                                        onClick={() => setShowNovoProdutor(!showNovoProdutor)}
                                        className={clsx(
                                            "px-4 py-2.5 font-bold rounded-xl transition-all whitespace-nowrap border",
                                            showNovoProdutor ? "bg-red-50 text-red-600 border-red-200 hover:bg-red-100" : "bg-white text-brand-blue border-brand-blue/30 hover:bg-brand-blue/5"
                                        )}
                                    >
                                        {showNovoProdutor ? 'Cancelar Inserção' : '+ Cadastrar Novo'}
                                    </button>
                                </div>

                                {showNovoProdutor && (
                                    <div className="mt-3 p-4 bg-white border border-brand-blue/20 rounded-xl shadow-sm">
                                        <h4 className="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3">Sub-Ficha: Adicionar Produtor no Cadastro</h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                            <div className="flex flex-col gap-1">
                                                <label className="text-xs font-semibold text-slate-500">Nome Oficial</label>
                                                <input type="text" placeholder="Nome Completo ou Razão Social..." value={novoProdutor.nome} onChange={e => setNovoProdutor({ ...novoProdutor, nome: e.target.value })} className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-brand-blue focus:outline-none" />
                                            </div>
                                            <div className="flex flex-col gap-1">
                                                <label className="text-xs font-semibold text-slate-500">Documento Atrelado</label>
                                                <input type="text" placeholder="CPF ou CNPJ (Opcional)" value={novoProdutor.cpf_cnpj} onChange={e => setNovoProdutor({ ...novoProdutor, cpf_cnpj: e.target.value })} className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-brand-blue focus:outline-none" />
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={async () => {
                                                if (!novoProdutor.nome) return alert('O Nome do Produtor é um vínculo obrigatório!');
                                                const nid = 'prod-' + crypto.randomUUID();
                                                const payload = {
                                                    id: nid,
                                                    nome: novoProdutor.nome,
                                                    cpf_cnpj: novoProdutor.cpf_cnpj,
                                                    municipio: currentPropriedade.municipio || '',
                                                    uf: currentPropriedade.UF || 'PA'
                                                };
                                                await offlineSave('produtores', payload);
                                                setCurrentPropriedade({ ...currentPropriedade, id_proprietario: nid });
                                                setShowNovoProdutor(false);
                                                setNovoProdutor({ id: '', nome: '', cpf_cnpj: '' });
                                            }}
                                            className="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold shadow-md shadow-emerald-500/20"
                                        >
                                            Salvar Produtor e Vincular à Área
                                        </button>
                                    </div>
                                )}
                            </div>

                            <div className="col-span-1 lg:col-span-3 border-t border-slate-100 my-4"></div>
                            <div className="flex flex-col gap-1.5 md:col-span-1 lg:col-span-1">
                                <CoordenadaInput
                                    label="Latitude da Propriedade"
                                    type="lat"
                                    value={currentPropriedade.latitude}
                                    onChange={v => setCurrentPropriedade({ ...currentPropriedade, latitude: v })}
                                    onCapture={capturePropriedadeGPS}
                                />
                            </div>
                            <div className="flex flex-col gap-1.5 md:col-span-1 lg:col-span-1">
                                <CoordenadaInput
                                    label="Longitude da Propriedade"
                                    type="lon"
                                    value={currentPropriedade.longitude}
                                    onChange={v => setCurrentPropriedade({ ...currentPropriedade, longitude: v })}
                                    onCapture={capturePropriedadeGPS}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'producao' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-brand-blue/5 p-6 rounded-2xl border border-brand-blue/10">
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Área Total Explorada (hectares)</label>
                                <input
                                    type="number"
                                    value={currentPropriedade.area_total}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, area_total: parseFloat(e.target.value) })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Destino da Produção</label>
                                <select
                                    value={currentPropriedade.destino_producao}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, destino_producao: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                >
                                    <option value="">Selecione...</option>
                                    <option value="Mercado Interno (Local)">Mercado Interno (Local)</option>
                                    <option value="Mercado Interno (Nacional)">Mercado Interno (Nacional)</option>
                                    <option value="Exportação">Exportação</option>
                                    <option value="Subsistência">Subsistência</option>
                                </select>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Produção Familiar?</label>
                                <select
                                    value={currentPropriedade.producao_familiar}
                                    onChange={e => setCurrentPropriedade({ ...currentPropriedade, producao_familiar: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                >
                                    <option value="Não">Não</option>
                                    <option value="Sim">Sim</option>
                                </select>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer Actions */}
                <div className="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button
                        onClick={() => setView('list')}
                        className="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-xl transition-colors"
                    ><span className="md:hidden text-lg leading-none">&times;</span> <span className="hidden md:inline">Cancelar</span></button>
                    <button
                        onClick={handleSave}
                        className="flex items-center gap-2 px-6 py-2 bg-brand-blue hover:bg-brand-blue-light text-white font-bold rounded-xl shadow-md shadow-brand-blue/20 transition-colors"
                    >
                        <Save size={18} /> <span className="hidden md:inline">Salvar Propriedade</span>
                    </button>
                </div>
            </div>
        </div>
    );
}

function TabButton({ active, label, onClick }) {
    return (
        <button
            onClick={onClick}
            className={clsx(
                "pb-3 px-2 text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap",
                active
                    ? "border-brand-blue text-brand-blue"
                    : "border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300"
            )}
        >
            {label}
        </button>
    );
}
