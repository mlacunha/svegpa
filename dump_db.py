import os
import pymysql
import sys

def dump_database(host='localhost', user='root', password='', db='sveg', output_file='sveg_dump.sql'):
    try:
        conn = pymysql.connect(host=host, user=user, password=password, database=db, cursorclass=pymysql.cursors.DictCursor)
        with conn.cursor() as cursor:
            # Drop foreign keys for seamless import
            with open(output_file, 'w', encoding='utf-8') as f:
                f.write("SET FOREIGN_KEY_CHECKS=0;\n\n")

                # Get all tables
                cursor.execute("SHOW TABLES")
                tables = [list(t.values())[0] for t in cursor.fetchall()]

                for table in tables:
                    f.write(f"-- Table structure for table `{table}`\n")
                    f.write(f"DROP TABLE IF EXISTS `{table}`;\n")

                    cursor.execute(f"SHOW CREATE TABLE `{table}`")
                    create_table_stmt = cursor.fetchone()['Create Table']
                    f.write(f"{create_table_stmt};\n\n")

                    # Dump Datal
                    f.write(f"-- Dumping data for table `{table}`\n")
                    cursor.execute(f"SELECT * FROM `{table}`")
                    rows = cursor.fetchall()
                    if rows:
                        columns = rows[0].keys()
                        col_str = ", ".join([f"`{col}`" for col in columns])
                        
                        chunk_size = 500
                        for i in range(0, len(rows), chunk_size):
                            chunk = rows[i:i + chunk_size]
                            values_list = []
                            for row in chunk:
                                values = []
                                for col in columns:
                                    val = row[col]
                                    if val is None:
                                        values.append("NULL")
                                    elif isinstance(val, str):
                                        # Escape single quotes and backslashes
                                        val = val.replace('\\', '\\\\').replace("'", "''")
                                        values.append(f"'{val}'")
                                    else:
                                        values.append(f"'{val}'")
                                
                                val_str = ", ".join(values)
                                values_list.append(f"({val_str})")
                            
                            all_vals = ",\n".join(values_list)
                            f.write(f"INSERT INTO `{table}` ({col_str}) VALUES \n{all_vals};\n")
                    f.write("\n")

                f.write("SET FOREIGN_KEY_CHECKS=1;\n")
        print(f"Dumped successfully to {output_file}")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    dump_database(output_file='sveg_dump_para_vps.sql')
