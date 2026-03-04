import React, { useState } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, FileText } from 'lucide-react';
import clsx from 'clsx';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function ProgramasCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list'); // 'list' | 'form'
    const programas = useLiveQuery(() => db.programas.toArray()) || [];
    const [searchTerm, setSearchTerm] = useState('');

    // Form State
    const [currentPrograma, setCurrentPrograma] = useState(null);

    const filteredProgramas = programas.filter(p =>
        p.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.codigo.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleEdit = (p) => {
        setCurrentPrograma({ ...p });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentPrograma({
            id: crypto.randomUUID(),
            codigo: '',
            nome: '',
            nomes_comuns: '',
            nome_cientifico: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir este programa? Hospedeiros e normas vinculados devem ser tratados.")) {
            await offlineDelete('programas', id);
        }
    };

    const handleSave = async () => {
        await offlineSave('programas', currentPrograma);
        setView('list');
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <FileText size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Cadastro de Programas</h1>
                            <p className="hidden md:block text-sm text-slate-500">Gerencie programas, hospedeiros e normas</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Novo Programa</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por código ou nome..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Código</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Nome</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Nomes Comuns</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredProgramas.map(p => (
                                    <tr key={p.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 font-medium text-slate-700">{p.codigo || '-'}</td>
                                        <td className="py-3 px-4 text-slate-800 font-semibold">{p.nome}</td>
                                        <td className="py-3 px-4 text-slate-500 hidden md:table-cell">{p.nomes_comuns || '-'}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(p)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(p.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredProgramas.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhum programa encontrado. (Modo Offline-First)</td>
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
                <div>
                    <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentPrograma.nome ? currentPrograma.nome : 'Novo Programa'}</h1>
                    <p className="hidden md:block text-sm text-slate-500">{currentPrograma.codigo ? `Código: ${currentPrograma.codigo}` : 'Preencha os dados abaixo'}</p>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Código</label>
                            <input
                                type="text"
                                value={currentPrograma.codigo}
                                onChange={e => setCurrentPrograma({ ...currentPrograma, codigo: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-semibold text-slate-600">Nome <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentPrograma.nome}
                                onChange={e => setCurrentPrograma({ ...currentPrograma, nome: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nomes Comuns</label>
                            <input
                                type="text"
                                value={currentPrograma.nomes_comuns || ''}
                                onChange={e => setCurrentPrograma({ ...currentPrograma, nomes_comuns: e.target.value })}
                                placeholder="Ex: Mandioca, Macaxeira..."
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nome Científico</label>
                            <input
                                type="text"
                                value={currentPrograma.nome_cientifico || ''}
                                onChange={e => setCurrentPrograma({ ...currentPrograma, nome_cientifico: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all italic bg-white"
                            />
                        </div>
                    </div>
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Programa</span>
                    </button>
                </div>
            </div>
        </div>
    );
}

