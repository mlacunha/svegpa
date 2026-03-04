import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, MapPin, Building } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function UnidadesCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const unidades = useLiveQuery(() => db.unidades.toArray()) || [];
    const orgaos = useLiveQuery(() => db.orgaos.toArray()) || [];

    const [currentUnidade, setCurrentUnidade] = useState(null);

    const filteredUnidades = unidades.filter(u =>
        u.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
        u.municipio.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleEdit = (u) => {
        setCurrentUnidade({ ...u });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentUnidade({
            id: crypto.randomUUID(),
            id_orgao: '',
            nome: '',
            municipio: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir esta Unidade?")) {
            await offlineDelete('unidades', id);
        }
    };

    const handleSave = async () => {
        if (!currentUnidade.id_orgao) {
            alert('Selecione um Órgão vinculador!');
            return;
        }
        await offlineSave('unidades', currentUnidade);
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
                            <MapPin size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Unidades Locais</h1>
                            <p className="hidden md:block text-sm text-slate-500">Unidades de atendimento e regionais</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Nova Unidade</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por Nome ou Município..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Órgão</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Nome da Unidade</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Município Sede</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredUnidades.map(u => (
                                    <tr key={u.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 text-slate-500 flex items-center gap-1.5 font-bold">
                                            <Building className="text-slate-400" size={14} />
                                            {getOrgaoNome(u.id_orgao)}
                                        </td>
                                        <td className="py-3 px-4 text-slate-800 font-semibold">{u.nome}</td>
                                        <td className="py-3 px-4 text-slate-600 hidden md:table-cell">{u.municipio || '-'}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(u)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(u.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredUnidades.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhuma Unidade encontrada. (Offline-First)</td>
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
                        <MapPin size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentUnidade.nome ? currentUnidade.nome : 'Nova Unidade'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">Postos de atendimento e polos regionais</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Órgão Vinculado <span className="text-red-500">*</span></label>
                            <select
                                value={currentUnidade.id_orgao}
                                onChange={e => setCurrentUnidade({ ...currentUnidade, id_orgao: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Selecione o Órgão...</option>
                                {orgaos.map(o => (
                                    <option key={o.id} value={o.id}>{o.sigla ? `${o.sigla} - ${o.nome}` : o.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nome da Unidade Local <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentUnidade.nome}
                                onChange={e => setCurrentUnidade({ ...currentUnidade, nome: e.target.value })}
                                placeholder="Ex: ULSAV de Marituba"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Município Sede</label>
                            <input
                                type="text"
                                value={currentUnidade.municipio}
                                onChange={e => setCurrentUnidade({ ...currentUnidade, municipio: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Unidade</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
