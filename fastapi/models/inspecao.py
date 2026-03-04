from sqlalchemy import Column, String, Text, DECIMAL, Boolean, Integer, Date, ForeignKey, DateTime
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from core.database import Base

class TermoInspecao(Base):
    __tablename__ = "termo_inspecao"

    id = Column(String(36), primary_key=True, index=True)
    data_inspecao = Column(Date)
    data_amostragem = Column(Date)
    termo_inspecao = Column(String(30))
    termo_coleta = Column(String(30))
    id_usuario = Column(String(255))
    id_auxiliar = Column(String(255))
    id_propriedade = Column(String(36))
    id_programa = Column(String(36))
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())
    
    areas = relationship("AreaInspecionada", back_populates="termo", cascade="all, delete-orphan")

class AreaInspecionada(Base):
    __tablename__ = "area_inspecionada"

    id = Column(String(36), primary_key=True, index=True)
    id_termo_inspecao = Column(String(36), ForeignKey("termo_inspecao.id"))
    tipo_area = Column(String(40))
    nome_local = Column(String(255))
    latitude = Column(DECIMAL(10, 8))
    longitude = Column(DECIMAL(11, 8))
    especie = Column(String(120))
    variedade = Column(String(255))
    material_multiplicacao = Column(String(120))
    origem = Column(String(120))
    idade_plantio = Column(DECIMAL(10, 2))
    area_plantada = Column(DECIMAL(10, 2))
    numero_plantas = Column(Integer)
    numero_inspecionadas = Column(Integer)
    numero_suspeitas = Column(Integer)
    coletar_mostra = Column(Boolean)
    identificacao_amostra = Column(String(120))
    raiz = Column(Boolean)
    caule = Column(Boolean)
    peciolo = Column(Boolean)
    folha = Column(Boolean)
    flor = Column(Boolean)
    fruto = Column(Boolean)
    semente = Column(Boolean)
    resultado = Column(String(30))
    associado = Column(String(120))
    data_criacao = Column(DateTime, server_default=func.now())
    data_atualizacao = Column(DateTime, server_default=func.now(), onupdate=func.now())
    obs = Column(Text)
    
    termo = relationship("TermoInspecao", back_populates="areas")
