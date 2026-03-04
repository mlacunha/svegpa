import os
import re

files = [
    'ProgramasCRUD.jsx',
    'HospedeirosCRUD.jsx',
    'NormasCRUD.jsx',
    'TiposOrgaoCRUD.jsx',
    'OrgaosCRUD.jsx',
    'UnidadesCRUD.jsx',
    'CargosCRUD.jsx'
]

dir_path = r'e:\ServidorLocal\Projetos\sanvegSPA\pwa-sanveg\src'

for f in files:
    path = os.path.join(dir_path, f)
    with open(path, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Update signature
    content = re.sub(
        r'export default function (\w+CRUD)\(\) \{',
        r'export default function \1({ user }) {\n    const isComum = user?.role === "comum";',
        content
    )
    
    # Hide handleCreate button
    content = re.sub(
        r'([ \t]+)(<button[^>]*onClick=\{handleCreate\}[\s\S]*?</button>)',
        r'\1{!isComum && (\n\1    \2\n\1)}',
        content
    )
    
    # Hide handleEdit button
    content = re.sub(
        r'([ \t]+)(<button[^>]*onClick=\{\(\) => handleEdit\([^)]+\)\}[\s\S]*?</button>)',
        r'\n\1{!isComum && (\2)}',
        content
    )
    
    # Hide handleDelete button
    content = re.sub(
        r'([ \t]+)(<button[^>]*onClick=\{\(\) => handleDelete\([^)]+\)\}[\s\S]*?</button>)',
        r'\n\1{!isComum && (\2)}',
        content
    )
    
    with open(path, 'w', encoding='utf-8') as file:
        file.write(content)

print("Patch concluded.")
