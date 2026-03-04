from sqlalchemy import Column, String, Text, DateTime, Integer
from sqlalchemy.sql import func
from core.database import Base

class Hospedeiro(Base):
    __tablename__ = "hospedeiros"

    id = Column(String(36), primary_key=True, index=True)
    id_programa = Column(String(36))
    nomes_comuns = Column(String(255))
    nome_cientifico = Column(String(255))
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())

class Norma(Base):
    __tablename__ = "normas"

    id = Column(String(36), primary_key=True, index=True)
    id_programa = Column(String(36))
    nome_norma = Column(String(255))
    ementa = Column(Text)
    url_publicacao = Column(String(512))
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())

class Orgao(Base):
    __tablename__ = "orgaos"

    id = Column(String(36), primary_key=True, index=True)
    sigla = Column(String(20))
    nome = Column(String(100))
    tipo = Column(String(36)) # Equivalent to id_tipo_orgao
    cnpj = Column(String(20), nullable=True)
    logo = Column(String(255))
    UF_sede = Column(String(10))
    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())

class Unidade(Base):
    __tablename__ = "unidades"

    id = Column(String(36), primary_key=True, index=True)
    orgao = Column(String(36)) # Equivalent to id_orgao
    nome = Column(String(255))
    municipio = Column(String(100))
    uf = Column(String(10))

class Cargo(Base):
    __tablename__ = "cargos"

    id = Column(String(36), primary_key=True, index=True)
    orgao = Column(String(36)) # Equivalent to id_orgao
    sigla = Column(String(50))
    nome = Column(String(255))
    descricao = Column(String(500), nullable=True)

class Formacao(Base):
    __tablename__ = "formacao"

    id = Column(Integer, primary_key=True, index=True)
    nome = Column(String(120))

class TipoOrgao(Base):
    __tablename__ = "tipos_orgao"

    id = Column(String(36), primary_key=True, index=True)
    nome = Column(String(100), nullable=False)
    descricao = Column(String(255), nullable=True)
