from core.database import SessionLocal
from sqlalchemy import text
db = SessionLocal()
db.execute(text("UPDATE users SET role = 'superusuario' WHERE id = 'admin' OR id = 'super-admin-001'"))
db.execute(text("UPDATE users SET role = 'admin' WHERE id = '678apps'"))
db.execute(text("UPDATE users SET role = 'comum' WHERE role NOT IN ('superusuario', 'admin')"))
db.commit()
print("Update complete")
