import re
import sys

with open('sanveg.sql', 'r', encoding='utf-8') as f:
    sql = f.read()

tables = ['programas', 'propriedades', 'termo_inspecao', 'area_inspecionada']
for t in tables:
    pattern = r"CREATE TABLE `" + t + r"` \((.*?)\)\s*ENGINE"
    m = re.search(pattern, sql, re.DOTALL)
    if m:
        print(f"--- {t} ---")
        print(m.group(1).strip())
