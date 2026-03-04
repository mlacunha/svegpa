from sqlalchemy import Column, String, Boolean, Integer, DateTime
from sqlalchemy.sql import func
from core.database import Base
import uuid

class Usuario(Base):
    __tablename__ = "users"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()), index=True)
    nome = Column(String(255), nullable=False)
    telefone = Column(String(64), nullable=True)
    role = Column(String(50), nullable=False, default="comum") # superusuario, admin, comum
    id_formacao = Column(Integer, nullable=True)
    id_cargo = Column(String(36), nullable=True)
    id_orgao = Column(String(36), nullable=True)
    id_unidade = Column(String(36), nullable=True)
    matricula = Column(String(20), nullable=True)
    carteirafiscal = Column(String(20), nullable=True)
    ativo = Column(Boolean, default=True)
    seq_tf = Column(Integer, default=0)
    seq_tc = Column(Integer, default=0)
    seq_tf_ano = Column(Integer, nullable=True)  # Ano de controle do reset anual do seq_tf
    seq_tc_ano = Column(Integer, nullable=True)  # Ano de controle do reset anual do seq_tc
    
    email = Column(String(255), unique=True, index=True, nullable=True)
    cpf = Column(String(20), unique=True, index=True, nullable=True)
    senha_hash = Column(String(255), nullable=True)

    criado_em = Column(DateTime, server_default=func.now())
    atualizado_em = Column(DateTime, server_default=func.now(), onupdate=func.now())
