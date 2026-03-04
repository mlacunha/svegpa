import React, { useState, useEffect, useCallback } from 'react';
import {
    Search, FileText, Download, ChevronLeft, ChevronRight,
    RefreshCw, AlertTriangle, CheckCircle, AlertCircle, Filter, X
} from 'lucide-react';
import axios from 'axios';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import clsx from 'clsx';

const API_URL = import.meta.env.VITE_API_URL || 'https://svegpa-api.vps5908.panel.icontainer.run/api';
const PER_PAGE = 50;

// ─── Helpers ──────────────────────────────────────────────────────────────────
const fmtCoord = (v) => (v != null ? Number(v).toFixed(6) : '—');
const fmtTrim = (v) => v ? `${v}º Trim.` : '—';

const STATUS_CFG = {
    NORMAL: { label: 'Normal', cls: 'bg-blue-100 text-blue-700', dot: 'bg-blue-500' },
    SUSPEITA: { label: 'Suspeita', cls: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
    FOCO: { label: 'Foco', cls: 'bg-red-100 text-red-700', dot: 'bg-red-600' },
    POSITIVO: { label: 'Foco', cls: 'bg-red-100 text-red-700', dot: 'bg-red-600' },
};

function StatusBadge({ status }) {
    const cfg = STATUS_CFG[status?.toUpperCase()] || STATUS_CFG.NORMAL;
    return (
        <span className={clsx('inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold', cfg.cls)}>
            <span className={clsx('w-2 h-2 rounded-full', cfg.dot)} />
            {cfg.label}
        </span>
    );
}

// ─── Exportação CSV (padrão Excel Brasil: separador ; e decimal ,) ────────────
function exportCSV(items, user) {
    const header = [
        'Programa', 'Ano', 'Trimestre', 'Município',
        'Propriedade', 'Tipo Imóvel', 'Cultura/Espécie',
        'Latitude', 'Longitude', 'Status'
    ];

    const escapeCell = (v) => {
        const s = String(v ?? '').replace(/"/g, '""');
        return `"${s}"`;
    };

    const fmtNum = (n) => (n != null ? String(Number(n).toFixed(6)).replace('.', ',') : '');

    const rows = items.map(r => [
        r.nome_programa, r.ano, fmtTrim(r.trimestre).replace('º ', 'T'),
        r.municipio, r.nome_propriedade, r.tipo_imovel, r.cultura,
        fmtNum(r.latitude), fmtNum(r.longitude), r.status
    ].map(escapeCell).join(';'));

    // BOM para Excel reconhecer UTF-8
    const bom = '\uFEFF';
    const csv = bom + [header.map(escapeCell).join(';'), ...rows].join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `levantamentos_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ─── Exportação PDF ────────────────────────────────────────────────────────────
function exportPDF(items, user, filters) {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    const orgao = user?.orgao_nome || user?.orgao || 'Órgão não informado';
    const unidade = user?.unidade_nome || user?.unidade || 'Unidade não informada';
    const emitido = new Date().toLocaleString('pt-BR');

    // ── Cabeçalho ──
    doc.setFontSize(13);
    doc.setFont('helvetica', 'bold');
    doc.text('Histórico de Levantamentos Fitossanitários', 148, 14, { align: 'center' });

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text(`Órgão: ${orgao}   |   Unidade: ${unidade}`, 148, 20, { align: 'center' });

    // Filtros ativos
    const filtrosAtivos = Object.entries(filters)
        .filter(([, v]) => v)
        .map(([k, v]) => `${k}: ${v}`)
        .join('   ');
    if (filtrosAtivos) {
        doc.setFontSize(8);
        doc.setTextColor(100);
        doc.text(`Filtros aplicados: ${filtrosAtivos}`, 148, 25, { align: 'center' });
        doc.setTextColor(0);
    }

    doc.setFontSize(8);
    doc.text(`Emitido em: ${emitido}   |   Total de registros: ${items.length}`, 148, 29, { align: 'center' });

    // ── Tabela ──
    autoTable(doc, {
        startY: 33,
        head: [[
            'Programa', 'Ano', 'Trim.', 'Município',
            'Propriedade', 'Tipo Imóvel', 'Cultura/Espécie',
            'Latitude', 'Longitude', 'Status'
        ]],
        body: items.map(r => [
            r.nome_programa || '—',
            r.ano || '—',
            r.trimestre ? `${r.trimestre}T` : '—',
            r.municipio || '—',
            r.nome_propriedade || '—',
            r.tipo_imovel || '—',
            r.cultura || '—',
            fmtCoord(r.latitude),
            fmtCoord(r.longitude),
            (STATUS_CFG[r.status?.toUpperCase()]?.label || r.status || 'Normal'),
        ]),
        styles: { fontSize: 7, cellPadding: 1.5 },
        headStyles: { fillColor: [30, 80, 160], textColor: 255, fontStyle: 'bold', fontSize: 7 },
        alternateRowStyles: { fillColor: [245, 247, 250] },
        columnStyles: {
            0: { cellWidth: 38 },  // Programa
            1: { cellWidth: 10 },  // Ano
            2: { cellWidth: 10 },  // Trim
            3: { cellWidth: 30 },  // Município
            4: { cellWidth: 35 },  // Propriedade
            5: { cellWidth: 25 },  // Tipo Imóvel
            6: { cellWidth: 28 },  // Cultura
            7: { cellWidth: 20 },  // Lat
            8: { cellWidth: 20 },  // Lon
            9: { cellWidth: 14 },  // Status
        },
        // ── Numeração de páginas ──
        didDrawPage: (data) => {
            const pageCount = doc.internal.getNumberOfPages();
            const pageNumber = doc.internal.getCurrentPageInfo().pageNumber;
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text(
                `Página ${pageNumber} de ${pageCount}`,
                data.settings.margin.left,
                doc.internal.pageSize.height - 6
            );
            doc.text(
                `SANVEG PA — ${orgao}`,
                148,
                doc.internal.pageSize.height - 6,
                { align: 'center' }
            );
            doc.setTextColor(0);
        },
    });

    doc.save(`levantamentos_${new Date().toISOString().slice(0, 10)}.pdf`);
}

// ─── Componente Principal ──────────────────────────────────────────────────────
export default function HistoricoLevantamentos({ user }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);

    // Filtros
    const [filterAno, setFilterAno] = useState('');
    const [filterTrimestre, setFilterTrimestre] = useState('');
    const [filterPrograma, setFilterPrograma] = useState('');
    const [filterMunicipio, setFilterMunicipio] = useState('');
    const [filterStatus, setFilterStatus] = useState('');

    const hasFilter = filterAno || filterTrimestre || filterPrograma || filterMunicipio || filterStatus;

    const loadData = useCallback(async (pg = 1) => {
        setLoading(true);
        setError(null);
        try {
            const params = { page: pg, per_page: PER_PAGE };
            if (filterAno) params.ano = filterAno;
            if (filterTrimestre) params.trimestre = filterTrimestre;
            if (filterPrograma) params.programa = filterPrograma;
            if (filterMunicipio) params.municipio = filterMunicipio;
            if (filterStatus) params.status = filterStatus;

            const res = await axios.get(`${API_URL}/relatorios/levantamentos`, { params });
            setData(res.data);
            setPage(pg);
        } catch (e) {
            setError('Não foi possível carregar os dados. Verifique a conexão com o servidor.');
        } finally {
            setLoading(false);
        }
    }, [filterAno, filterTrimestre, filterPrograma, filterMunicipio, filterStatus]);

    useEffect(() => { loadData(1); }, [loadData]);

    const clearFilters = () => {
        setFilterAno(''); setFilterTrimestre(''); setFilterPrograma('');
        setFilterMunicipio(''); setFilterStatus('');
    };

    const activeFiltersLabel = { Ano: filterAno, Trimestre: filterTrimestre, Programa: filterPrograma, Município: filterMunicipio, Status: filterStatus };

    // Todos os registros filtrados para exportação completa (sem paginação)
    const exportAll = async (format) => {
        try {
            const params = { page: 1, per_page: 9999 };
            if (filterAno) params.ano = filterAno;
            if (filterTrimestre) params.trimestre = filterTrimestre;
            if (filterPrograma) params.programa = filterPrograma;
            if (filterMunicipio) params.municipio = filterMunicipio;
            if (filterStatus) params.status = filterStatus;

            const res = await axios.get(`${API_URL}/relatorios/levantamentos`, { params });
            if (format === 'csv') exportCSV(res.data.items, user);
            else exportPDF(res.data.items, user, activeFiltersLabel);
        } catch (e) {
            alert('Erro ao exportar. Verifique a conexão.');
        }
    };

    const opts = data?.filter_options || {};

    return (
        <div className="flex flex-col h-full bg-slate-50">
            {/* Header */}
            <div className="flex items-center justify-between mb-4 md:mb-6">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-brand-blue/10 flex items-center justify-center text-brand-blue shadow-sm">
                        <FileText size={20} className="md:w-6 md:h-6" />
                    </div>
                    <div>
                        <h1 className="text-sm md:text-2xl font-bold uppercase md:normal-case text-slate-800 tracking-wider md:tracking-normal">
                            Histórico de Levantamentos
                        </h1>
                        <p className="hidden md:block text-sm text-slate-500">
                            Consolidado de levantamentos fitossanitários ({data?.total ?? '…'} registros)
                        </p>
                    </div>
                </div>

                {/* Botões Exportar */}
                <div className="flex items-center gap-2">
                    <button
                        onClick={() => exportAll('csv')}
                        disabled={loading || !data?.total}
                        className="flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition-colors shadow-sm"
                        title="Exportar CSV (Excel BR)"
                    >
                        <Download size={15} />
                        <span className="hidden sm:inline">CSV</span>
                    </button>
                    <button
                        onClick={() => exportAll('pdf')}
                        disabled={loading || !data?.total}
                        className="flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl transition-colors shadow-sm"
                        title="Exportar PDF"
                    >
                        <FileText size={15} />
                        <span className="hidden sm:inline">PDF</span>
                    </button>
                    <button
                        onClick={() => loadData(1)}
                        disabled={loading}
                        className="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-brand-blue hover:border-brand-blue transition-colors shadow-sm"
                        title="Recarregar"
                    >
                        <RefreshCw size={16} className={loading ? 'animate-spin' : ''} />
                    </button>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col flex-1">

                {/* Painel de filtros */}
                <div className="border-b border-brand-blue/10 bg-brand-blue/5">
                    <details className="group [&_summary::-webkit-details-marker]:hidden" open>
                        <summary className="p-4 text-sm font-bold text-brand-blue cursor-pointer list-none flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <Filter size={16} />
                                <span className="group-open:hidden">Exibir Filtros {hasFilter && <span className="ml-1 bg-brand-blue text-white text-xs px-1.5 py-0.5 rounded-full">ON</span>}</span>
                                <span className="hidden group-open:inline">Ocultar Filtros</span>
                            </span>
                        </summary>

                        <div className="px-4 pb-4 hidden group-open:block">
                            <div className="flex flex-wrap gap-3 items-end">
                                <FilterSelect label="Ano" value={filterAno} onChange={e => setFilterAno(e.target.value)} options={opts.anos}
                                />
                                <FilterSelect label="Trimestre" value={filterTrimestre} onChange={e => setFilterTrimestre(e.target.value)} options={opts.trimestres}
                                    renderOpt={v => `${v}º Trimestre`}
                                />
                                <FilterSelect label="Programa" value={filterPrograma} onChange={e => setFilterPrograma(e.target.value)} options={opts.programas} wide />
                                <FilterSelect label="Município" value={filterMunicipio} onChange={e => setFilterMunicipio(e.target.value)} options={opts.municipios} wide />
                                <FilterSelect label="Status" value={filterStatus} onChange={e => setFilterStatus(e.target.value)} options={opts.statuses}
                                    renderOpt={v => STATUS_CFG[v]?.label || v}
                                />
                                {hasFilter && (
                                    <button
                                        onClick={clearFilters}
                                        className="flex items-center gap-1 text-sm text-slate-500 hover:text-red-500 underline transition-colors"
                                    >
                                        <X size={14} /> Limpar Filtros
                                    </button>
                                )}
                            </div>

                            {/* Tags de filtros ativos */}
                            {hasFilter && (
                                <div className="flex flex-wrap gap-2 mt-3">
                                    {Object.entries(activeFiltersLabel).filter(([, v]) => v).map(([k, v]) => (
                                        <span key={k} className="flex items-center gap-1 bg-brand-blue/10 text-brand-blue text-xs font-semibold px-2 py-1 rounded-full">
                                            {k}: {k === 'Trimestre' ? `${v}º Trim.` : v}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </details>
                </div>

                {/* Mensagens de estado */}
                {error && (
                    <div className="m-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
                        <AlertCircle size={20} />
                        <span className="text-sm font-medium">{error}</span>
                    </div>
                )}

                {loading && (
                    <div className="flex-1 flex items-center justify-center py-16 text-slate-400">
                        <RefreshCw size={32} className="animate-spin mr-3" />
                        <span className="font-medium">Carregando levantamentos...</span>
                    </div>
                )}

                {/* Grid */}
                {!loading && data && (
                    <div className="overflow-x-auto flex-1">
                        <table className="w-full text-left text-sm min-w-[900px]">
                            <thead>
                                <tr className="bg-brand-blue/5 text-slate-600 border-b border-brand-blue/10">
                                    <th className="py-3 px-3 font-semibold uppercase text-xs">Programa</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs">Ano</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs">Trim.</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs">Município</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs hidden lg:table-cell">Propriedade</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs hidden xl:table-cell">Tipo Imóvel</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs hidden md:table-cell">Cultura/Espécie</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs hidden xl:table-cell">Latitude</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs hidden xl:table-cell">Longitude</th>
                                    <th className="py-3 px-3 font-semibold uppercase text-xs">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.items.length === 0 && (
                                    <tr>
                                        <td colSpan={10} className="py-16 text-center text-slate-400">
                                            <CheckCircle size={40} className="mx-auto mb-3 text-slate-300" />
                                            Nenhum levantamento encontrado com os filtros aplicados.
                                        </td>
                                    </tr>
                                )}
                                {data.items.map((row, i) => (
                                    <tr
                                        key={i}
                                        className="border-b border-slate-50 hover:bg-brand-blue/3 transition-colors group"
                                    >
                                        <td className="py-2.5 px-3 font-semibold text-slate-800 max-w-[180px] truncate" title={row.nome_programa}>
                                            {row.nome_programa || '—'}
                                        </td>
                                        <td className="py-2.5 px-3 text-slate-600 font-mono text-xs">{row.ano || '—'}</td>
                                        <td className="py-2.5 px-3 text-slate-600 text-xs">{fmtTrim(row.trimestre)}</td>
                                        <td className="py-2.5 px-3 text-slate-700 font-medium">{row.municipio || '—'}</td>
                                        <td className="py-2.5 px-3 text-slate-600 hidden lg:table-cell max-w-[160px] truncate" title={row.nome_propriedade}>
                                            {row.nome_propriedade || '—'}
                                        </td>
                                        <td className="py-2.5 px-3 text-slate-500 text-xs hidden xl:table-cell">{row.tipo_imovel || '—'}</td>
                                        <td className="py-2.5 px-3 text-slate-600 italic text-xs hidden md:table-cell">{row.cultura || '—'}</td>
                                        <td className="py-2.5 px-3 text-slate-400 font-mono text-xs hidden xl:table-cell">{fmtCoord(row.latitude)}</td>
                                        <td className="py-2.5 px-3 text-slate-400 font-mono text-xs hidden xl:table-cell">{fmtCoord(row.longitude)}</td>
                                        <td className="py-2.5 px-3">
                                            <StatusBadge status={row.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Paginação */}
                {!loading && data && data.pages > 1 && (
                    <div className="p-4 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 mt-auto">
                        <span className="text-sm text-slate-500">
                            Exibindo {((page - 1) * PER_PAGE) + 1}–{Math.min(page * PER_PAGE, data.total)} de <strong>{data.total}</strong> registros
                            {hasFilter && <span className="ml-1 text-brand-blue font-medium">(filtrado)</span>}
                        </span>
                        <div className="flex items-center gap-2">
                            <button
                                disabled={page <= 1}
                                onClick={() => loadData(page - 1)}
                                className="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-sm font-medium transition-colors"
                            >
                                <ChevronLeft size={16} /> Anterior
                            </button>
                            <span className="text-sm font-semibold text-slate-700 px-2">
                                {page} / {data.pages}
                            </span>
                            <button
                                disabled={page >= data.pages}
                                onClick={() => loadData(page + 1)}
                                className="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-sm font-medium transition-colors"
                            >
                                Próxima <ChevronRight size={16} />
                            </button>
                        </div>
                    </div>
                )}

                {/* Totalizador quando resultado cabe em 1 página */}
                {!loading && data && data.pages <= 1 && data.total > 0 && (
                    <div className="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between mt-auto">
                        <span className="text-sm text-slate-500">
                            {data.total} registro{data.total !== 1 ? 's' : ''} encontrado{data.total !== 1 ? 's' : ''}
                            {hasFilter && <span className="ml-1 text-brand-blue font-medium">(filtrado)</span>}
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}

// ─── Subcomponente FilterSelect ────────────────────────────────────────────────
function FilterSelect({ label, value, onChange, options = [], renderOpt, wide }) {
    if (!options || options.length === 0) return null;
    return (
        <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-slate-500 uppercase tracking-wide">{label}</label>
            <select
                value={value}
                onChange={onChange}
                className={clsx(
                    'px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm focus:outline-none focus:border-brand-blue focus:ring-1 focus:ring-brand-blue transition-all',
                    wide ? 'min-w-[180px]' : 'min-w-[110px]'
                )}
            >
                <option value="">Todos</option>
                {options.map(opt => (
                    <option key={opt} value={opt}>{renderOpt ? renderOpt(opt) : opt}</option>
                ))}
            </select>
        </div>
    );
}
