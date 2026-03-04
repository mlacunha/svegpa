import os
import glob
import re

directory = r"e:\ServidorLocal\Projetos\sanvegSPA\pwa-sanveg\src"
files = glob.glob(os.path.join(directory, "*CRUD.jsx"))

# InteractiveMap.jsx is another one to patch
files.append(os.path.join(directory, "InteractiveMap.jsx"))

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Match all <Plus size={..} /> Nova [Something]
    # Example: <Plus size={18} /> Nova Inspeção
    content = re.sub(
        r'(<Plus\s+size=\{1[68]\}\s*/>\s*)(Novo \w+|Nova \w+|Lançar Nova Área/Lote)',
        r'\1<span className="hidden md:inline">\2</span>',
        content
    )

    # Match all <Save size={..} /> Salvar [Something]
    # Example: <Save size={18} /> Salvar Ficha Principal
    content = re.sub(
        r'(<Save\s+size=\{1[68]\}\s*/>\s*)(Salvar \w+|Salvar \w+ \w+|Guardar Laudo da Área|Salvar)',
        r'\1<span className="hidden md:inline">\2</span>',
        content
    )

    # Match InteractiveMap.jsx 'Ver Mapa em Tela Cheia' and 'Sair da Tela Cheia'
    content = re.sub(
        r"(isFullscreen \? 'Sair da Tela Cheia' : 'Ver Mapa em Tela Cheia')",
        r'<span className="hidden md:inline">{\1}</span>',
        content
    )

    # InspecaoCRUD "Concluir e Voltar" (line 648 approximately) -> make sure it has an icon if we hide text.
    # Actually, we can just replace text with <ArrowLeft size={18} className="md:hidden" /> <span className="hidden md:inline">Concluir e Voltar</span>
    content = re.sub(
        r'>\s*Concluir e Voltar\s*</button>',
        r'><ArrowLeft size={18} className="md:hidden" /> <span className="hidden md:inline">Concluir e Voltar</span></button>',
        content
    )
    
    # "Cancelar" is mostly used in modais:
    content = re.sub(
        r'>\s*Cancelar\s*</button>',
        r'>Cancelar</button>',  # Actually Cancelar is fine to show text or we can just keep Cancelar text OR replace with <X size={18} className="md:hidden"/>
        content
    )

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Patched {os.path.basename(file)}")
