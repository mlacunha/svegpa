import re

with open('sanveg.sql', 'r', encoding='utf-8') as f:
    sql = f.read()

tables = ['programas', 'propriedades', 'termo_inspecao', 'area_inspecionada']
out = ''
for t in tables:
    pattern = r"CREATE TABLE `" + t + r"` \((.*?)\) ENGINE="
    m = re.search(pattern, sql, re.DOTALL)
    if m:
        out += f'--- {t} ---\n'
        out += m.group(1).strip() + '\n\n'

with open('schema_output.txt', 'w', encoding='utf-8') as f:
    f.write(out)
