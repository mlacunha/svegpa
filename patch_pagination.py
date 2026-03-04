import os

base_path = r'e:\ServidorLocal\Projetos\sanvegSPA\pwa-sanveg\src'

def patch_file(filename, entity_name, filter_var, search_placeholder):
    path = os.path.join(base_path, filename)
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    if 'setCurrentPage(' in content:
        print(f"Already patched {filename}")
        return

    # 1. Add state
    content = content.replace(
        "const [searchTerm, setSearchTerm] = useState('');",
        "const [searchTerm, setSearchTerm] = useState('');\n    const [currentPage, setCurrentPage] = useState(1);\n    const ITEMS_PER_PAGE = 10;"
    )

    # 2. Add calculating logic
    # Find the filter logic end, which is variable specific
    calc_logic = f"""
    const totalPages = Math.max(1, Math.ceil({filter_var}.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedItems = {filter_var}.slice(startIndex, startIndex + ITEMS_PER_PAGE);
"""
    # Replace the filter definition to include pagination computation
    content = content.replace(
        f"const handleEdit = (",
        f"{calc_logic}\n    const handleEdit = ("
    )

    # 3. Update search input onChange
    content = content.replace(
        "onChange={e => setSearchTerm(e.target.value)}",
        "onChange={e => { setSearchTerm(e.target.value); setCurrentPage(1); }}"
    )

    # 4. Map paginated items
    content = content.replace(
        f"{filter_var}.map(",
        f"paginatedItems.map("
    )

    # 5. Add Pagination Component UI
    pagination_ui = f"""
                    <div className="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50 mt-auto">
                        <span className="text-sm text-slate-500">
                            Mostrando {{startIndex + 1}} a {{Math.min(startIndex + ITEMS_PER_PAGE, {filter_var}.length)}} de {{{filter_var}.length}} registros
                        </span>
                        <div className="flex items-center gap-2">
                            <button disabled={{currentPage === 1}} onClick={{() => setCurrentPage(prev => prev - 1)}} className="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-sm">Anterior</button>
                            <span className="text-sm font-semibold text-slate-600">{{currentPage}} / {{totalPages}}</span>
                            <button disabled={{currentPage === totalPages || totalPages === 0}} onClick={{() => setCurrentPage(prev => prev + 1)}} className="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-sm">Próxima</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }}
"""
    # Replace the end of view === 'list' return
    content = content.replace(
        """                </div>\n            </div>\n        );\n    }""",
        pagination_ui
    )


    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Patched {filename}")

patch_file('ProdutoresCRUD.jsx', 'Produtor', 'filteredProdutores', 'Buscar por nome, CPF/CNPJ ou município...')
patch_file('PropriedadesCRUD.jsx', 'Propriedade', 'filteredPropriedades', 'Buscar por código, nome ou município...')
patch_file('InspecaoCRUD.jsx', 'Inspecao', 'filteredInspecoes', 'Buscar por Termo ou Data...')

