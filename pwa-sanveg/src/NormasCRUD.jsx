import React, { useState, useEffect } from 'react';
import { Plus, Edit2, Trash2, Search, ArrowLeft, Save, BookOpen, Folder, ExternalLink } from 'lucide-react';
import clsx from 'clsx';
import { useLiveQuery } from 'dexie-react-hooks';
import { db, offlineSave, offlineDelete } from './db';

export default function NormasCRUD({ user }) {
    const isComum = user?.role === "comum";
    const [view, setView] = useState('list');
    const [searchTerm, setSearchTerm] = useState('');

    const normas = useLiveQuery(() => db.normas.toArray()) || [];
    const programas = useLiveQuery(() => db.programas.toArray()) || [];

    const [currentNorma, setCurrentNorma] = useState(null);

    const filteredNormas = normas.filter(n =>
        (n.nome_norma && n.nome_norma.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const handleEdit = (n) => {
        setCurrentNorma({ ...n });
        setView('form');
    };

    const handleCreate = () => {
        setCurrentNorma({
            id: crypto.randomUUID(),
            id_programa: '',
            nome_norma: '',
            ementa: '',
            url_publicacao: ''
        });
        setView('form');
    };

    const handleDelete = async (id) => {
        if (window.confirm("Deseja realmente excluir esta norma?")) {
            await offlineDelete('normas', id);
        }
    };

    const handleSave = async () => {
        if (!currentNorma.id_programa) {
            alert('Selecione um programa!');
            return;
        }
        await offlineSave('normas', currentNorma);
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
                            <BookOpen size={24} />
                        </div>
                        <div>
                            <h1 className="text-xl md:text-2xl font-bold text-slate-800">Cadastro de Normas</h1>
                            <p className="hidden md:block text-sm text-slate-500">Documentações legais e normativas fitossanitárias</p>
                        </div>
                    </div>
                    {!isComum && (
                        <button
                        onClick={handleCreate}
                        className="flex items-center gap-2 bg-brand-blue hover:bg-brand-blue-light transition-colors text-white px-4 py-2.5 rounded-xl font-semibold shadow-md shadow-brand-blue/20"
                    >
                        <Plus size={18} /> <span className="hidden md:inline">Nova Norma</span>
                    </button>
                    )}
                </div>

                <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                    <div className="p-4 border-b border-brand-blue/10 bg-brand-blue/5 flex items-center">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                            <input
                                type="text"
                                placeholder="Buscar por título da norma..."
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
                                    <th className="py-3 px-4 font-semibold uppercase">Programa</th>
                                    <th className="py-3 px-4 font-semibold uppercase">Norma</th>
                                    <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Ementa</th>
                                    <th className="py-3 px-4 font-semibold uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredNormas.map(n => (
                                    <tr key={n.id} className="border-b border-brand-blue/5 hover:bg-brand-blue/5 transition-colors">
                                        <td className="py-3 px-4 text-slate-600 font-medium">
                                            {getProgramaNome(n.id_programa)}
                                        </td>
                                        <td className="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
                                            {n.url_publicacao ? (
                                                <a href={n.url_publicacao} target="_blank" rel="noopener noreferrer" className="text-brand-blue hover:text-brand-blue-light transition-colors flex items-center gap-1.5" title="Abrir publicação">
                                                    {n.nome_norma} <ExternalLink size={14} />
                                                </a>
                                            ) : (
                                                n.nome_norma
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-slate-500 hidden md:table-cell text-sm max-w-sm truncate" title={n.ementa}>{n.ementa || '-'}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center justify-end gap-2">

                                                {!isComum && (<button onClick={() => handleEdit(n)} className="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Edit2 size={18} />
                                                </button>)}

                                                {!isComum && (<button onClick={() => handleDelete(n.id)} className="p-2 text-slate-400 hover:text-red-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                                    <Trash2 size={18} />
                                                </button>)}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {filteredNormas.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-500">Nenhuma norma registrada. (Modo Offline-First)</td>
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
                        <BookOpen size={20} />
                    </div>
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold text-slate-800">{currentNorma.nome_norma ? currentNorma.nome_norma : 'Nova Norma'}</h1>
                        <p className="hidden md:block text-sm text-slate-500">Relacione regras e normativas a um programa</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 text-slate-800 overflow-hidden flex flex-col flex-1">
                <div className="p-6 flex-1 overflow-y-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl border border-brand-blue/10 bg-brand-blue/5 p-6 rounded-2xl">
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Programa Vinculado <span className="text-red-500">*</span></label>
                            <select
                                value={currentNorma.id_programa}
                                onChange={e => setCurrentNorma({ ...currentNorma, id_programa: e.target.value })}
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            >
                                <option value="">Selecione o programa respectivo...</option>
                                {programas.map(p => (
                                    <option key={p.id} value={p.id}>{p.nome}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Título ou Identificação da Norma <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                value={currentNorma.nome_norma}
                                onChange={e => setCurrentNorma({ ...currentNorma, nome_norma: e.target.value })}
                                placeholder="Ex: Instrução Normativa nº 10/2023"
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Link / URL de Publicação</label>
                            <input
                                type="url"
                                value={currentNorma.url_publicacao}
                                onChange={e => setCurrentNorma({ ...currentNorma, url_publicacao: e.target.value })}
                                placeholder="Ex: https://..."
                                className="px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all bg-white text-brand-blue"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <label className="text-sm font-semibold text-slate-600">Ementa / Descrição</label>
                            <textarea
                                value={currentNorma.ementa}
                                onChange={e => setCurrentNorma({ ...currentNorma, ementa: e.target.value })}
                                rows={4}
                                placeholder="Declara os parâmetros exigidos para o trânsito..."
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
                        <Save size={18} /> <span className="hidden md:inline">Salvar Norma</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
