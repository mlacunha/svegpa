from sqlalchemy import Column, String, Boolean, DateTime
from sqlalchemy.sql import func
from core.database import Base

class Produtor(Base):
    __tablename__ = "produtores"

    id = Column(String(36), primary_key=True, index=True)
    n_cadastro = Column(String(50))
    cpf_cnpj = Column(String(20))
    RG_IE = Column(String(20))
    nome = Column(String(255))
    CEP = Column(String(10))
    endereco = Column(String(200))
    bairro = Column(String(100))
    municipio = Column(String(255))
    uf = Column(String(2))
    telefone = Column(String(20))
    email = Column(String(100))
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())
