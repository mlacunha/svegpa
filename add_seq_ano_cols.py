import pymysql
conn = pymysql.connect(host='localhost', user='root', password='', database='sveg')
cursor = conn.cursor()

cursor.execute("SHOW COLUMNS FROM users LIKE 'seq_tf_ano'")
if not cursor.fetchone():
    cursor.execute("ALTER TABLE users ADD COLUMN seq_tf_ano INT NULL DEFAULT NULL AFTER seq_tc")
    print("seq_tf_ano adicionado")
else:
    print("seq_tf_ano ja existe")

cursor.execute("SHOW COLUMNS FROM users LIKE 'seq_tc_ano'")
if not cursor.fetchone():
    cursor.execute("ALTER TABLE users ADD COLUMN seq_tc_ano INT NULL DEFAULT NULL AFTER seq_tf_ano")
    print("seq_tc_ano adicionado")
else:
    print("seq_tc_ano ja existe")

conn.commit()
conn.close()
print("OK")
