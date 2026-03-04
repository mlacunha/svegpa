import pymysql

conn = pymysql.connect(host='localhost', user='root', password='', database='sveg', cursorclass=pymysql.cursors.DictCursor)
with conn.cursor() as cursor:
    tables = ['users', 'termo_inspecao', 'area_inspecionada']
    for t in tables:
        cursor.execute(f"DESCRIBE `{t}`")
        print(f"--- table: {t} ---")
        for row in cursor.fetchall():
            print(f"{row['Field']} : {row['Type']}")
