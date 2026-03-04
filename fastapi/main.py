from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from core.database import engine, Base

import models.programas
import models.propriedades
import models.produtores
import models.inspecao
import models.auxiliares
import models.usuarios

# Criar tabelas se não existirem (embora já existam no banco de dados)
Base.metadata.create_all(bind=engine)

app = FastAPI(
    title="Sanveg SPA API",
    description="API para o PWA offline-first Sanveg",
    version="1.0.0"
)

# Configuração de CORS para a PWA
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/api/health")
def health_check():
    return {"status": "ok", "message": "FastAPI rodando com sucesso"}

from api.routes import programas
from api.routes import dashboard
from api.routes import sync
from api.routes import auth
from api.routes import relatorios

app.include_router(programas.router)
app.include_router(dashboard.router)
app.include_router(sync.router, prefix="/api/sync", tags=["Sync Offline"])
app.include_router(auth.router, prefix="/api/auth", tags=["Auth JWT"])
app.include_router(relatorios.router)
