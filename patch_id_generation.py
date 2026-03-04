import glob
import re

files = glob.glob('pwa-sanveg/src/*.jsx')
for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    if 'Date.now().toString()' in content:
        content = content.replace("Date.now().toString()", "crypto.randomUUID()")
        with open(f, 'w', encoding='utf-8') as file:
            file.write(content)
        print(f"Patched {f}")
