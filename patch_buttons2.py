import os
import glob
import re

directory = r"e:\ServidorLocal\Projetos\sanvegSPA\pwa-sanveg\src"
files = glob.glob(os.path.join(directory, "*CRUD.jsx"))
files.append(os.path.join(directory, "InteractiveMap.jsx"))

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Match <Plus size={...} /> [Any Text]
    # Replace with <Plus size={...} /> <span className="hidden md:inline">[Any Text]</span>
    content = re.sub(
        r'(<Plus\s+size=\{1[68]\}\s*/>\s*)([^<]+?)(?=\s*</button>)',
        r'\1<span className="hidden md:inline">\2</span>',
        content
    )

    # Match <Save size={...} /> [Any Text]
    # Replace with <Save size={...} /> <span className="hidden md:inline">[Any Text]</span>
    content = re.sub(
        r'(<Save\s+size=\{1[68]\}\s*/>\s*)([^<]+?)(?=\s*</button>)',
        r'\1<span className="hidden md:inline">\2</span>',
        content
    )

    # In InteractiveMap.jsx, match the conditional text
    if "InteractiveMap" in file:
        content = re.sub(
            r"({isFullscreen \? 'Sair da Tela Cheia' : 'Ver Mapa em Tela Cheia'})",
            r'<span className="hidden md:inline">\1</span>',
            content
        )

    # Button: Concluir e Voltar
    if '>Concluir e Voltar</button>' in content or '> Concluir e Voltar\n' in content:
        # We need an ArrowLeft icon if it doesn't have one
        content = re.sub(
            r'>\s*Concluir e Voltar\s*</button>',
            r'><ArrowLeft size={18} className="md:hidden" /> <span className="hidden md:inline">Concluir e Voltar</span></button>',
            content
        )

    # And for "Guardar Laudo da Área" inside InspecaoCRUD
    content = re.sub(
        r'>\s*Guardar Laudo da Área\s*</button>',
        r'><Save size={18} className="md:hidden" /> <span className="hidden md:inline">Guardar Laudo da Área</span></button>',
        content
    )

    # For "Cancelar" buttons that are used without icons
    content = re.sub(
        r'>\s*Cancelar\s*</button>',
        r'><span className="md:hidden text-lg leading-none">&times;</span> <span className="hidden md:inline">Cancelar</span></button>',
        content
    )

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Patched {os.path.basename(file)}")

