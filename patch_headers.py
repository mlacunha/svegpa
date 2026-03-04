import os
import glob
import re

directory = r"e:\ServidorLocal\Projetos\sanvegSPA\pwa-sanveg\src"
files = glob.glob(os.path.join(directory, "*CRUD.jsx"))

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # We want to change the title classes and hide the subtitle paragraphs
    # Example: <h1 className="text-2xl font-bold text-slate-800">
    # Result: <h1 className="text-xl md:text-2xl font-bold text-slate-800">
    content = re.sub(r'<h1 className="text-2xl font-bold text-slate-800">', r'<h1 className="text-xl md:text-2xl font-bold text-slate-800">', content)
    
    # We want to hide the `<p>` subtitles on mobile:
    # Most of them are right below the h1: <p className="text-sm text-slate-500">
    content = re.sub(r'<p className="text-sm text-slate-500">([\s\S]*?)</p>', r'<p className="hidden md:block text-sm text-slate-500">\1</p>', content)

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Patched {os.path.basename(file)}")

