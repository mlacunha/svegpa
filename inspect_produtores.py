from sqlalchemy import create_engine, inspect

engine = create_engine('mysql+pymysql://root:@localhost/sveg')
inspector = inspect(engine)
columns = inspector.get_columns('produtores')
for col in columns:
    print(f"{col['name']}: {col['type']}")
