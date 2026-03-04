import React, { useState } from 'react';
import { Search, MapPin, CheckCircle, FileText, ArrowLeft, Beaker } from 'lucide-react';
import { useLiveQuery } from 'dexie-react-hooks';
import { db } from './db';
import { gerarTermoColetaPDF } from './pdfGenerator';

export default function AmostragemCRUD() {
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const ITEMS_PER_PAGE = 10;

    // Grid list filters
    const [filtroGridPrograma, setFiltroGridPrograma] = useState('');
    const [filtroGridAno, setFiltroGridAno] = useState('');
    const [filtroGridTrimestre, setFiltroGridTrimestre] = useState('');
    const [filtroGridMunicipio, setFiltroGridMunicipio] = useState('');

    // Fetch collections from Dexie
    const inspecoes = useLiveQuery(() => db.termo_inspecao.toArray()) || [];
    const areas = useLiveQuery(() => db.area_inspecionada.toArray()) || [];
    const propriedades = useLiveQuery(() => db.propriedades.toArray()) || [];
    const programas = useLiveQuery(() => db.programas.toArray()) || [];

    const municipios_disponiveis = [...new Set(propriedades.map(p => p.municipio).filter(Boolean))].sort();

    // Filtra term_inspeção apenas para aqueles que possuem amostras (coletar_mostra = true)
    const inspecoesComAmostra = inspecoes.filter(i => {
        return areas.some(a => a.id_termo_inspecao === i.id && (String(a.coletar_mostra).toLowerCase() === 'true' || a.coletar_mostra === 1 || a.coletar_mostra === true));
    });

    const filteredAmostragens = inspecoesComAmostra.filter(i => {
        // Text Search Filtering on Termo de Coleta ou Data
        const term = searchTerm.toLowerCase();
        const matchesSearch = (i.termo_coleta || '').toLowerCase().includes(term) ||
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
    });

    const getAnosDisponiveis = () => {
        const anos = inspecoesComAmostra.map(i => {
            if (!i.data_inspecao) return null;
            return i.data_inspecao.split('-')[0];
        }).filter(Boolean);
        return [...new Set(anos)].sort((a, b) => b - a); // descending
    };

    const totalPages = Math.max(1, Math.ceil(filteredAmostragens.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedItems = filteredAmostragens.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    const propName = (id) => propriedades.find(p => p.id === id)?.nome || 'Desconhecida';
    const progName = (id) => programas.find(p => p.id === id)?.nome || 'Programa Inválido';

    return (
        <div className="flex flex-col h-full bg-slate-50 relative">
            <div className="flex items-center justify-between mb-4 md:mb-6">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 shadow-sm">
                        <Beaker size={20} className="md:w-6 md:h-6" />
                    </div>
                    <div className="flex flex-col">
                        <div className="flex items-center gap-2">
                            <h1 className="text-sm md:text-2xl font-bold uppercase md:normal-case text-slate-800 tracking-wider md:tracking-normal">
                                Amostragem
                            </h1>
                        </div>
                        <p className="hidden md:block text-sm text-slate-500">Consulta de Termos de Coleta de Amostras Gerados</p>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">
                <div className="border-b border-amber-500/10 bg-amber-500/5">
                    <details className="group [&_summary::-webkit-details-marker]:hidden">
                        <summary className="p-4 text-sm font-bold text-amber-600 cursor-pointer list-none flex items-center justify-between border-b border-amber-500/5 group-open:border-transparent transition-all">
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
                                    placeholder="Buscar por TCA ou Data..."
                                    value={searchTerm}
                                    onChange={e => { setSearchTerm(e.target.value); setCurrentPage(1); }}
                                    className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all"
                                />
                            </div>
                            <div className="flex flex-col md:flex-row flex-wrap items-start md:items-center gap-3">
                                <select
                                    value={filtroGridPrograma}
                                    onChange={e => { setFiltroGridPrograma(e.target.value); setCurrentPage(1); }}
                                    className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-slate-600 bg-white"
                                >
                                    <option value="">Todos os Programas...</option>
                                    {programas.map(p => <option key={p.id} value={p.id}>{p.nome}</option>)}
                                </select>

                                <select
                                    value={filtroGridMunicipio}
                                    onChange={e => { setFiltroGridMunicipio(e.target.value); setCurrentPage(1); }}
                                    className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-slate-600 bg-white"
                                >
                                    <option value="">Todos os Municípios...</option>
                                    {municipios_disponiveis.map(m => <option key={m} value={m}>{m}</option>)}
                                </select>

                                <select
                                    value={filtroGridAno}
                                    onChange={e => { setFiltroGridAno(e.target.value); setCurrentPage(1); }}
                                    className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-slate-600 bg-white"
                                >
                                    <option value="">Qualquer Ano...</option>
                                    {getAnosDisponiveis().map(ano => <option key={ano} value={ano}>{ano}</option>)}
                                </select>

                                <select
                                    value={filtroGridTrimestre}
                                    onChange={e => { setFiltroGridTrimestre(e.target.value); setCurrentPage(1); }}
                                    className="w-full md:w-auto px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-slate-600 bg-white"
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
                            <tr className="bg-amber-500/5 text-slate-600 text-sm border-b border-amber-500/10">
                                <th className="py-3 px-4 font-semibold uppercase">Nº TCA / Data</th>
                                <th className="py-3 px-4 font-semibold uppercase hidden md:table-cell">Propriedade</th>
                                <th className="py-3 px-4 font-semibold uppercase hidden lg:table-cell">Programa</th>
                                <th className="py-3 px-4 font-semibold uppercase text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            {paginatedItems.map(i => (
                                <tr key={i.id} className="border-b border-amber-500/5 hover:bg-amber-500/5 transition-colors">
                                    <td className="py-3 px-4">
                                        <div className="font-bold text-slate-800">{i.termo_coleta || 'PENDENTE_SYNC'}</div>
                                        <div className="text-xs text-slate-500 mt-0.5">{i.data_inspecao || 'Sem data'}</div>
                                    </td>
                                    <td className="py-3 px-4 text-slate-600 hidden md:table-cell font-medium">{propName(i.id_propriedade)}</td>
                                    <td className="py-3 px-4 text-slate-500 hidden lg:table-cell">{progName(i.id_programa)}</td>
                                    <td className="py-3 px-4">
                                        <div className="flex items-center justify-end gap-2">
                                            <button onClick={() => gerarTermoColetaPDF(i.id)} className="p-2 text-slate-400 hover:text-amber-500 transition-colors rounded-lg hover:bg-white border border-transparent hover:border-slate-200" title="Visualizar TCA (PDF)">
                                                <FileText size={18} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {filteredAmostragens.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="py-8 text-center text-slate-500 flex flex-col items-center justify-center">
                                        <Beaker size={48} className="text-slate-300 mb-3" />
                                        Nenhum termo de coleta listado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 mt-auto">
                    <span className="text-sm text-slate-500">
                        Mostrando {startIndex + 1} a {Math.min(startIndex + ITEMS_PER_PAGE, filteredAmostragens.length)} de {filteredAmostragens.length} registros
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
