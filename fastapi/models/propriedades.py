from sqlalchemy import Column, String, Text, DECIMAL, Boolean, Integer, Date, ForeignKey, DateTime
from sqlalchemy.sql import func
from core.database import Base

class Propriedade(Base):
    __tablename__ = "propriedades"

    id = Column(String(36), primary_key=True, index=True)
    n_cadastro = Column(String(50))
    cpf_cnpj = Column(String(20))
    RG_IE = Column(String(20))
    nome = Column(String(100), nullable=False)
    CEP = Column(String(10))
    endereco = Column(String(200))
    bairro = Column(String(100))
    municipio = Column(String(255))
    UF = Column(String(2))
    area_total = Column(DECIMAL(10, 2))
    destino_producao = Column(String(255))
    id_proprietario = Column(String(36))
    classificacao = Column(String(50))
    producao_familiar = Column(String(3))
    latitude = Column(DECIMAL(10, 8))
    longitude = Column(DECIMAL(11, 8))
    observacoes = Column(Text)
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())
