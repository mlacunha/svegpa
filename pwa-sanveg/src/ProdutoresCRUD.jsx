import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Users, Mail, Phone } from 'lucide-react';
import clsx from 'clsx';

// Remove Mock Data
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';
import axios from 'axios';

export default function ProdutoresCRUD() {
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const ITEMS_PER_PAGE = 10;

    const produtores = useLiveQuery(() => db.produtores.toArray()) || [];

    const [currentProdutor, setCurrentProdutor] = useState(null);
    const [activeTab, setActiveTab] = useState('dados');

    // IBGE Address Data
    const [ufs, setUfs] = useState([]);
    const [municipiosCache, setMunicipiosCache] = useState({});
    const [currentMunicipios, setCurrentMunicipios] = useState([]);

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
        if (!currentProdutor?.uf) {
            setCurrentMunicipios([]);
            return;
        }

        const ufSigla = currentProdutor.uf;

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

                if (currentProdutor.municipio && !loadedMunicipios.includes(currentProdutor.municipio)) {
                    setCurrentProdutor(prev => ({ ...prev, municipio: '' }));
                }
            } catch (err) {
                console.error("Erro ao carregar Municípios do IBGE:", err);
            }
        };

        fetchMunicipios();
    }, [currentProdutor?.uf, municipiosCache]);

    const filteredProdutores = produtores.filter(p => {
        const term = searchTerm.toLowerCase();
        return (p.nome || '').toLowerCase().includes(term) ||
            (p.cpf_cnpj || '').includes(term) ||
            (p.municipio || '').toLowerCase().includes(term);
    });


    const totalPages = Math.max(1, Math.ceil(filteredProdutores.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedItems = filteredProdutores.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    const handleEdit = (p) => {
        setCurrentProdutor({ ...p });
        setActiveTab('dados');
        setView('form');
    };

    const handleCreate = () => {
        setCurrentProdutor({
            id: crypto.randomUUID(),
            n_cadastro: '',
            nome: '',
            cpf_cnpj: '',
            RG_IE: '',
            email: '',
            telefone: '',
            CEP: '',
            endereco: '',
            bairro: '',
            municipio: '',
            uf: 'PA'
        });
        setActiveTab('dados');
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir este produtor?")) {
            await offlineDelete('produtores', id);
        }
    };

    const handleSave = async () => {
        await offlineSave('produtores', currentProdutor);
        setView('list');
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex items-center justify-between mb-4 md:mb-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Users size={20} className="md:w-6 md:h-6" />
                        </div>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <h1 className="text-sm md:text-2xl font-bold uppercase md:normal-case text-slate-800 tracking-wider md:tracking-normal">
                                    Produtores
                                </h1>
                                <button
                                    onClick={handleCreate}
                                    className="md:hidden flex items-center justify-center bg-brand-blue text-white w-7 h-7 rounded-full shadow-md hover:bg-brand-blue-light transition-colors"
                                >
                                    <Plus size={16} />
                                </button>
                            </div>
                            <p className="hidden md:block text-sm text-slate-500">Gerencie produtores rurais e empresas do setor</p>
                        </div>
                    </div>
                    {/* Desktop Add Button */}
                    <button
                        onClick={handleCreate}
                        className="hidden md:flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> Novo Produtor
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
                                        placeholder="Buscar por nome, CPF/CNPJ ou município..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Produtor / CPF-CNPJ</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Município / UF</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden lg:table-cell">Contato</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paginatedItems.map(p => (
                                    <tr key={p.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4">
                                            <div className="font-bold text-slate-800">{p.nome}</div>
                                            <div className="text-xs text-slate-500 mt-0.5">{p.cpf_cnpj || 'Sem documento'}</div>
                                        </td>
                                        <td className="py-3 px-4 text-slate-500 hidden md:table-cell">{p.municipio} - {p.uf}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden lg:table-cell">
                                            <div className="flex flex-col text-sm">
                                                <span>{p.telefone}</span>
                                            </div>
                                        </td>
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
                                {filteredProdutores.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="py-8 text-center text-slate-500">Nenhum produtor encontrado.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 mt-auto">
                        <span className="text-sm text-slate-500">
                            Mostrando {startIndex + 1} a {Math.min(startIndex + ITEMS_PER_PAGE, filteredProdutores.length)} de {filteredProdutores.length} registros
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
                        <Users size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentProdutor.nome ? currentProdutor.nome : 'Novo Produtor'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">{currentProdutor.cpf_cnpj ? currentProdutor.cpf_cnpj : 'Preencha os dados de identificação'}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="flex border-b border-brand-blue/10 px-4 pt-4 bg-brand-blue/5 overflow-x-auto gap-4">
                    <TabButton active={activeTab === 'dados'} onClick={() => setActiveTab('dados')} label="Dados Pessoais/Empresariais" />
                    <TabButton active={activeTab === 'contato'} onClick={() => setActiveTab('contato')} label="Contato e Endereço" />
                </div>

                <div className="p-6 flex-1 overflow-y-auto">
                    {activeTab === 'dados' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Nome / Razão Social <span className="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    value={currentProdutor.nome || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, nome: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Nº Cadastro / NIF</label>
                                <input
                                    type="text"
                                    value={currentProdutor.n_cadastro || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, n_cadastro: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">CPF / CNPJ <span className="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    value={currentProdutor.cpf_cnpj || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, cpf_cnpj: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">RG / Inscrição Estadual</label>
                                <input
                                    type="text"
                                    value={currentProdutor.RG_IE || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, RG_IE: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'contato' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600 flex items-center gap-2"><Mail size={16} /> Email Principal</label>
                                <input
                                    type="email"
                                    value={currentProdutor.email}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, email: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600 flex items-center gap-2"><Phone size={16} /> Telefone de Contato</label>
                                <input
                                    type="text"
                                    value={currentProdutor.telefone}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, telefone: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>

                            <div className="col-span-1 md:col-span-2 border-t border-slate-100 my-2"></div>

                            <div className="flex flex-col gap-1.5 lg:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Endereço Residencial/Sede</label>
                                <input
                                    type="text"
                                    value={currentProdutor.endereco || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, endereco: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">Bairro</label>
                                <input
                                    type="text"
                                    value={currentProdutor.bairro || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, bairro: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">CEP</label>
                                <input
                                    type="text"
                                    value={currentProdutor.CEP || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, CEP: e.target.value })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label className="text-sm font-semibold text-slate-600">UF / Estado</label>
                                <select
                                    value={currentProdutor.uf || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, uf: e.target.value, municipio: '' })}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                                >
                                    <option value="">Selecione...</option>
                                    {ufs.map(u => (
                                        <option key={u.id} value={u.sigla}>{u.sigla} - {u.nome}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <label className="text-sm font-semibold text-slate-600">Município IBGE</label>
                                <select
                                    value={currentProdutor.municipio || ''}
                                    onChange={e => setCurrentProdutor({ ...currentProdutor, municipio: e.target.value })}
                                    disabled={!currentProdutor.uf || currentMunicipios.length === 0}
                                    className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white disabled:bg-slate-100"
                                >
                                    <option value="">Selecione o município...</option>
                                    {currentMunicipios.map(m => (
                                        <option key={m} value={m}>{m}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    )}
                </div>

                <div className="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button
                        onClick={() => setView('list')}
                        className="px-4 py-2 font-semibold text-slate-600 hover:bg-slate-200 bg-slate-100 rounded-xl transition-colors"
                    ><span className="md:hidden text-lg leading-none">&times;</span> <span className="hidden md:inline">Cancelar</span></button>
                    <button
                        onClick={handleSave}
                        className="flex items-center gap-2 px-6 py-2 bg-brand-blue hover:bg-brand-blue-light text-white font-bold rounded-xl shadow-md shadow-brand-blue/20 transition-colors"
                    >
                        <Save size={18} /> <span className="hidden md:inline">Salvar Produtor</span>
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
