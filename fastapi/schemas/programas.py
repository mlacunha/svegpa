from pydantic import BaseModel
from datetime import datetime
from typing import Optional

class ProgramaBase(BaseModel):
    codigo: Optional[str] = None
    nome: str
    nomes_comuns: Optional[str] = None
    nome_cientifico: Optional[str] = None

class ProgramaCreate(ProgramaBase):
    pass

class ProgramaResponse(ProgramaBase):
    id: str
    criado_em: datetime
    atualizado_em: datetime


    class Config:
        from_attributes = True
