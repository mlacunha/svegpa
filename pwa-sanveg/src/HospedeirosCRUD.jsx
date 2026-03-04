import React, { useState, useEffect } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, Leaf, Folder } from 'lucide-react';
import clsx from 'clsx';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function HospedeirosCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const hospedeiros = useLiveQuery(() => db.hospedeiros.toArray()) || [];
    const programas = useLiveQuery(() => db.programas.toArray()) || [];

    const [currentHospedeiro, setCurrentHospedeiro] = useState(null);

    // Initial mock data populate
    useEffect(() => {
        const initData = async () => {
            const count = await db.programas.count();
            if (count === 0) {
                await db.programas.bulkAdd([
                    { id: '1', codigo: 'P01', nome: 'Programa Nacional de Prevenção' },
                    { id: '2', codigo: 'P02', nome: 'Controle Preventivo' }
                ]);
            }
        };
        initData();
    }, []);

    const filteredHospedeiros = hospedeiros.filter(h =>
        (h.nomes_comuns && h.nomes_comuns.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (h.nome_cientifico && h.nome_cientifico.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const handleEdit = (h) => {
        setCurrentHospedeiro({ ...h });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentHospedeiro({
            id: crypto.randomUUID(),
            id_programa: '',
            nomes_comuns: '',
            nome_cientifico: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir este hospedeiro?")) {
            await offlineDelete('hospedeiros', id);
        }
    };

    const handleSave = async () => {
        if (!currentHospedeiro.id_programa) {
            alert('Selecione um programa!');
            return;
        }
        await offlineSave('hospedeiros', currentHospedeiro);
        setView('list');
    };

    const getProgramaNome = (id_programa) => {
        const p = programas.find(prog => prog.id === id_programa);
        return p ? p.nome : 'Desconhecido';
    };

    if (view === 'list') {
        return (
            <div className="flex flex-col h-full bg-slate-50 relative">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                            <Leaf size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Cadastro de Hospedeiros</h1>
                            <p className="hidden md:block text-sm text-slate-500">Espécies de plantas vinculadas a programas</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Novo Hospedeiro</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por nomes comuns ou científicos..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Programa Vinculado</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Nomes Comuns</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Nome Científico</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredHospedeiros.map(h => (
                                    <tr key={h.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 text-slate-800 font-semibold flex items-center gap-2">
                                            <Folder className="text-slate-400" size={16} />
                                            {getProgramaNome(h.id_programa)}
                                        </td>
                                        <td className="py-3 px-4 font-medium text-slate-700">{h.nomes_comuns || '-'}</td>
                                        <td className="py-3 px-4 text-slate-500 italic hidden md:table-cell">{h.nome_cientifico || '-'}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(h)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(h.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredHospedeiros.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhum hospedeiro encontrado. (Modo Offline-First)</td>
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
                        <Leaf size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentHospedeiro.nomes_comuns ? currentHospedeiro.nomes_comuns : 'Novo Hospedeiro'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">Vincule a planta hospedeira a um programa específico</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Programa Fitossanitário <span className="text-red-500">*</span></label>
                            <select
                                value={currentHospedeiro.id_programa}
                                onChange={e => setCurrentHospedeiro({ ...currentHospedeiro, id_programa: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Selecione um programa...</option>
                                {programas.map(p => (
                                    <option key={p.id} value={p.id}>{p.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nomes Comuns <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentHospedeiro.nomes_comuns}
                                onChange={e => setCurrentHospedeiro({ ...currentHospedeiro, nomes_comuns: e.target.value })}
                                placeholder="Ex: Mandioca, Macaxeira"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Nome Científico</label>
                            <input
                                type="text"
                                value={currentHospedeiro.nome_cientifico}
                                onChange={e => setCurrentHospedeiro({ ...currentHospedeiro, nome_cientifico: e.target.value })}
                                placeholder="Ex: Manihot esculenta"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white italic"
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Hospedeiro</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
