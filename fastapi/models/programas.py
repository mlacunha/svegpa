from sqlalchemy import Column, String, Text, DECIMAL, Boolean, Integer, Date, ForeignKey, DateTime
from sqlalchemy.sql import func
from core.database import Base

class Programa(Base):
    __tablename__ = "programas"

    id = Column(String(36), primary_key=True, index=True)
    codigo = Column(String(20), nullable=True)
    nome = Column(String(255), nullable=False)
    nomes_comuns = Column(String(255), nullable=True)
    nome_cientifico = Column(String(255), nullable=True)
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())
