import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Briefcase, Building } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function CargosCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const cargos = useLiveQuery(() => db.cargos.toArray()) || [];
    const orgaos = useLiveQuery(() => db.orgaos.toArray()) || [];

    const [currentCargo, setCurrentCargo] = useState(null);

    const filteredCargos = cargos.filter(c =>
        c.nome.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleEdit = (c) => {
        setCurrentCargo({ ...c });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentCargo({
            id: crypto.randomUUID(),
            id_orgao: '',
            nome: '',
            descricao: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir este Cargo?")) {
            await offlineDelete('cargos', id);
        }
    };

    const handleSave = async () => {
        if (!currentCargo.id_orgao) {
            alert('Selecione um Órgão vinculador da carreira!');
            return;
        }
        await offlineSave('cargos', currentCargo);
        setView('list');
    };

    const getOrgaoNome = (id_orgao) => {
        const o = orgaos.find(org => org.id === id_orgao);
        return o ? (o.sigla || o.nome) : 'Não informado';
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Briefcase size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Cargos e Funções</h1>
                            <p className="hidden md:block text-sm text-slate-500">Quadro de servidores e posições vinculadas</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Novo Cargo</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por Nome do cargo..."
                                value={searchTerm}
                                onChange={e => setSearchTerm(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-brand-blue/5 text-slate-600 text-sm border-b border-brand-blue/10">
                                    <th className="py-3 px-4 font-semibold uppercase">Órgão da Carreira</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Nome do Cargo</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Descrição</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredCargos.map(c => (
                                    <tr key={c.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 text-slate-500 flex items-center gap-1.5 font-bold">
                                            <Building className="text-slate-400" size={14} />
                                            {getOrgaoNome(c.id_orgao)}
                                        </td>
                                        <td className="py-3 px-4 text-slate-800 font-semibold">{c.nome}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden md:table-cell">{c.descricao || '-'}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(c)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(c.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredCargos.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhum Cargo encontrado. (Offline-First)</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
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
                        <Briefcase size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentCargo.nome ? currentCargo.nome : 'Novo Cargo'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">Cargos, Funções e Titulações de Operadores</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Órgão Competente <span className="text-red-500">*</span></label>
                            <select
                                value={currentCargo.id_orgao}
                                onChange={e => setCurrentCargo({ ...currentCargo, id_orgao: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Selecione o Órgão...</option>
                                {orgaos.map(o => (
                                    <option key={o.id} value={o.id}>{o.sigla ? `${o.sigla} - ${o.nome}` : o.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Título / Nome do Cargo <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentCargo.nome}
                                onChange={e => setCurrentCargo({ ...currentCargo, nome: e.target.value })}
                                placeholder="Ex: Fiscal Estadual Agropecuário"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Atribuições / Descrição</label>
                            <textarea
                                value={currentCargo.descricao}
                                onChange={e => setCurrentCargo({ ...currentCargo, descricao: e.target.value })}
                                rows={3}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white resize-none"
                            />
                        </div>
                    </div>
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Cargo</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
