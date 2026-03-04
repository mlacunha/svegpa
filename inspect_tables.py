import sys
from sqlalchemy import create_engine, inspect

engine = create_engine('mysql+pymysql://root:@localhost/sveg')
inspector = inspect(engine)

tables = ['hospedeiros', 'normas', 'tipos_orgao', 'orgaos', 'unidades', 'cargos', 'sec_users']
existing_tables = inspector.get_table_names()

for t in tables:
    if t in existing_tables:
        print(f"--- {t} ---")
        for col in inspector.get_columns(t):
            print(f"  {col['name']}: {col['type']}")
    else:
        print(f"--- {t} NOT FOUND ---")
