import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Building, Bookmark } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function OrgaosCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const orgaos = useLiveQuery(() => db.orgaos.toArray()) || [];
    const tiposOrgao = useLiveQuery(() => db.tipos_orgao.toArray()) || [];

    const [currentOrgao, setCurrentOrgao] = useState(null);

    const filteredOrgaos = orgaos.filter(o =>
        o.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
        o.sigla.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleEdit = (o) => {
        setCurrentOrgao({ ...o });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentOrgao({
            id: crypto.randomUUID(),
            id_tipo_orgao: '',
            nome: '',
            sigla: '',
            cnpj: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir este Órgão?")) {
            await offlineDelete('orgaos', id);
        }
    };

    const handleSave = async () => {
        if (!currentOrgao.id_tipo_orgao) {
            alert('Selecione um Tipo de Órgão!');
            return;
        }
        await offlineSave('orgaos', currentOrgao);
        setView('list');
    };

    const getTipoOrgaoNome = (id_tipo) => {
        const t = tiposOrgao.find(tipo => tipo.id === id_tipo);
        return t ? t.nome : 'Não informado';
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Building size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Órgãos Vinculados</h1>
                            <p className="hidden md:block text-sm text-slate-500">Gestão das entidades atreladas</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Novo Órgão</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por Nome ou Sigla..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Sigla</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Nome do Órgão</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Tipo</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredOrgaos.map(o => (
                                    <tr key={o.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 font-bold text-slate-700">{o.sigla || '-'}</td>
                                        <td className="py-3 px-4 text-slate-800 font-semibold">{o.nome}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden md:table-cell flex flex-row items-center gap-1.5">
                                            <Bookmark className="text-slate-400" size={14} /> {getTipoOrgaoNome(o.id_tipo_orgao)}
                                        </td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(o)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(o.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredOrgaos.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhum Órgão encontrado. (Offline-First)</td>
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
                        <Building size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentOrgao.nome ? currentOrgao.nome : 'Novo Órgão'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">{currentOrgao.sigla ? currentOrgao.sigla : 'Preencha os dados institucionais'}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Tipo de Órgão Vinculado <span className="text-red-500">*</span></label>
                            <select
                                value={currentOrgao.id_tipo_orgao}
                                onChange={e => setCurrentOrgao({ ...currentOrgao, id_tipo_orgao: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Selecione o Tipo de Órgão...</option>
                                {tiposOrgao.map(t => (
                                    <option key={t.id} value={t.id}>{t.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Razão Social / Nome Institucional <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentOrgao.nome}
                                onChange={e => setCurrentOrgao({ ...currentOrgao, nome: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Sigla <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentOrgao.sigla}
                                onChange={e => setCurrentOrgao({ ...currentOrgao, sigla: e.target.value })}
                                placeholder="Ex: ADEPARA"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">CNPJ</label>
                            <input
                                type="text"
                                value={currentOrgao.cnpj}
                                onChange={e => setCurrentOrgao({ ...currentOrgao, cnpj: e.target.value })}
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Órgão</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
