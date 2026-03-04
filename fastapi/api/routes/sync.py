from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from typing import List, Dict, Any
from pydantic import BaseModel
from core.database import get_db
from core.security import get_password_hash
from datetime import datetime
from sqlalchemy import text

import models.programas
import models.propriedades
import models.produtores
import models.inspecao
import models.auxiliares
import models.usuarios

router = APIRouter()

class SyncPayload(BaseModel):
    queue: List[Dict[str, Any]]

# Mapping Table Name to Model
MODEL_MAP = {
    "programas": models.programas.Programa,
    "propriedades": models.propriedades.Propriedade,
    "produtores": models.produtores.Produtor,
    "termo_inspecao": models.inspecao.TermoInspecao,
    "area_inspecionada": models.inspecao.AreaInspecionada,
    "hospedeiros": models.auxiliares.Hospedeiro,
    "normas": models.auxiliares.Norma,
    "orgaos": models.auxiliares.Orgao,
    "unidades": models.auxiliares.Unidade,
    "cargos": models.auxiliares.Cargo,
    "formacao": models.auxiliares.Formacao,
    "tipos_orgao": models.auxiliares.TipoOrgao,
    "usuarios": models.usuarios.Usuario,
}

# -------------------------------------------------------------------
# Helpers de geração de número de Termo (com reset anual e matricula)
# -------------------------------------------------------------------

def gerar_numero_tf(db: Session, user_record) -> str:
    """
    Gera o próximo Nº do Termo de Inspeção para o usuário.
    Formato: SEQ/MATRICULA/ANO
    Reseta o sequencial no início de cada novo ano.
    """
    ano_atual = datetime.now().year
    seq_tf_ano = getattr(user_record, 'seq_tf_ano', None)
    # Reset anual: se o ano gravado for diferente do ano atual, zera o sequencial
    if (seq_tf_ano or 0) != ano_atual:
        user_record.seq_tf = 0
        try:
            user_record.seq_tf_ano = ano_atual
        except AttributeError:
            pass  # coluna ainda não existe no banco; ignora
    seq = (user_record.seq_tf or 0) + 1
    user_record.seq_tf = seq
    db.add(user_record)
    matricula = user_record.matricula or "00000"
    return f"{seq}/{matricula}/{ano_atual}"


def gerar_numero_tc(db: Session, user_record) -> str:
    """
    Gera o próximo Nº do Termo de Coleta para o usuário.
    Formato: SEQ/MATRICULA/ANO
    Reseta o sequencial no início de cada novo ano.
    """
    ano_atual = datetime.now().year
    seq_tc_ano = getattr(user_record, 'seq_tc_ano', None)
    # Reset anual: se o ano gravado for diferente do ano atual, zera o sequencial
    if (seq_tc_ano or 0) != ano_atual:
        user_record.seq_tc = 0
        try:
            user_record.seq_tc_ano = ano_atual
        except AttributeError:
            pass  # coluna ainda não existe no banco; ignora
    seq = (user_record.seq_tc or 0) + 1
    user_record.seq_tc = seq
    db.add(user_record)
    matricula = user_record.matricula or "00000"
    return f"{seq}/{matricula}/{ano_atual}"


@router.post("/push")
def sync_push(payload: SyncPayload, db: Session = Depends(get_db)):
    """Receives offline mutations from the Dexie syncQueue and applies them to the DB."""
    results = {"success": 0, "errors": [], "processed_ids": []}

    for item in payload.queue:
        queue_id = item.get("id")
        action = item.get("action")
        table = item.get("route")
        data = item.get("payload")

        if table not in MODEL_MAP:
            results["errors"].append({
                "queue_id": queue_id,
                "error": f"Table '{table}' not supported by sync yet."
            })
            continue

        ModelClass = MODEL_MAP[table]
        mapped_data = {**data}

        # Sanitize empty strings to None to avoid DB coercion errors
        for k, v in list(mapped_data.items()):
            if v == "" or (isinstance(v, str) and v.strip() == ""):
                mapped_data[k] = None

        # Workarounds for DB columns that are strictly NOT NULL in MySQL
        if table == "orgaos":
            if mapped_data.get("logo") is None: mapped_data["logo"] = ""
            if mapped_data.get("UF_sede") is None: mapped_data["UF_sede"] = ""
        if table == "usuarios":
            if mapped_data.get("nome") is None: mapped_data["nome"] = "Sem Nome"
            if mapped_data.get("role") is None: mapped_data["role"] = "comum"

        # --- Always pop transient fields (regardless of create or update) ---
        termo_manual = mapped_data.pop("termo_manual", False)
        val_gerado_tc = mapped_data.pop("termo_coleta_gerado", None)
        is_manual_tc = mapped_data.pop("termo_coleta_manual", False)

        # Field name mapping PWA -> DB
        if table == "orgaos" and "id_tipo_orgao" in mapped_data:
            mapped_data["tipo"] = mapped_data.pop("id_tipo_orgao")
        if table == "unidades" and "id_orgao" in mapped_data:
            mapped_data["orgao"] = mapped_data.pop("id_orgao")
        if table == "cargos" and "id_orgao" in mapped_data:
            mapped_data["orgao"] = mapped_data.pop("id_orgao")

        if table == "usuarios":
            if "senha" in mapped_data:
                pwd = mapped_data.pop("senha")
                if pwd:
                    mapped_data["senha_hash"] = get_password_hash(pwd)

        pk_field = "id"

        try:
            if action in ["put", "post"]:
                record_id = mapped_data.get(pk_field)

                # --- Force cleanup of any existing duplicates (safety net) ---
                dup_count_row = db.execute(
                    text(f"SELECT COUNT(*) FROM `{ModelClass.__tablename__}` WHERE `{pk_field}` = :val"),
                    {"val": record_id}
                ).scalar()

                if dup_count_row and dup_count_row > 1:
                    db.execute(
                        text(f"DELETE FROM `{ModelClass.__tablename__}` WHERE `{pk_field}` = :val LIMIT {dup_count_row - 1}"),
                        {"val": record_id}
                    )
                    db.commit()
                    db.expire_all()

                # --- True UPSERT: check-then-update-or-create ---
                existing = db.query(ModelClass).filter(getattr(ModelClass, pk_field) == record_id).first()

                # =================================================================
                # Geração do Nú do Termo de Inspeção
                # Roda ANTES do check create/update para funcionar em ambos os casos:
                # - Criação normal (record ainda não existe)
                # - Edição de record que existe mas ainda não tem o número gerado
                # =================================================================
                if table == "termo_inspecao" and not termo_manual:
                    # Só gera se a payload NÃO tem número E o registro existente também NÃO tem
                    payload_sem_numero = not mapped_data.get("termo_inspecao")
                    existente_sem_numero = (not existing) or (not getattr(existing, "termo_inspecao", None))
                    if payload_sem_numero and existente_sem_numero:
                        user_id = mapped_data.get("id_usuario")
                        if user_id:
                            user_record = db.query(models.usuarios.Usuario).filter_by(id=user_id).with_for_update().first()
                            if user_record:
                                mapped_data["termo_inspecao"] = gerar_numero_tf(db, user_record)

                # =================================================================
                # Gera Nº do Termo de Coleta vinculado ao pai (TermoInspecao)
                # Roda apenas no momento do processamento da area_inspecionada
                # =================================================================
                if table == "area_inspecionada":
                    coleta = (
                        str(mapped_data.get("coletar_mostra")).lower() == "true"
                        or mapped_data.get("coletar_mostra") == 1
                    )
                    if coleta:
                        id_pai_termo = mapped_data.get("id_termo_inspecao")
                        if id_pai_termo:
                            pai = db.query(models.inspecao.TermoInspecao).filter_by(id=id_pai_termo).first()
                            if pai and not pai.termo_coleta:
                                if is_manual_tc and val_gerado_tc:
                                    pai.termo_coleta = val_gerado_tc
                                    db.add(pai)
                                else:
                                    user_id = pai.id_usuario
                                    if user_id:
                                        user_record = db.query(models.usuarios.Usuario).filter_by(id=user_id).with_for_update().first()
                                        if user_record:
                                            pai.termo_coleta = gerar_numero_tc(db, user_record)
                                            db.add(pai)

                # =================================================================
                if existing:
                    # UPDATE: aplica apenas campos que existem no model E são válidos
                    # Filtra campos inválidos igual ao CREATE para evitar erros de tipo/tamanho
                    CAMPOS_PROTEGIDOS = {"termo_inspecao", "termo_coleta"}
                    valid_model_fields = {k: v for k, v in mapped_data.items() if hasattr(ModelClass, k)}
                    for k, v in valid_model_fields.items():
                        # Não sobrescreve campo protegido com null/vazio se já tem valor
                        if k in CAMPOS_PROTEGIDOS and not v and getattr(existing, k, None):
                            continue
                        setattr(existing, k, v)
                    db.add(existing)
                else:
                    # CREATE: cria o novo registro filtrando apenas campos válidos do Model
                    valid_fields = {k: v for k, v in mapped_data.items() if hasattr(ModelClass, k)}
                    new_record = ModelClass(**valid_fields)
                    db.add(new_record)

            elif action == "delete":
                record_id = mapped_data.get(pk_field)
                # Se for um Termo de Inspeção, deleta as áreas filhas em cascata primeiro
                if table == "termo_inspecao":
                    db.execute(
                        text("DELETE FROM `area_inspecionada` WHERE `id_termo_inspecao` = :val"),
                        {"val": record_id}
                    )
                # Deleta o registro principal (e quaisquer duplicatas remanescentes)
                db.execute(
                    text(f"DELETE FROM `{ModelClass.__tablename__}` WHERE `{pk_field}` = :val"),
                    {"val": record_id}
                )

            db.commit()
            results["success"] += 1
            results["processed_ids"].append(queue_id)

        except Exception as e:
            db.rollback()
            results["errors"].append({"queue_id": queue_id, "error": str(e)})

    return results


@router.get("/pull")
def sync_pull(db: Session = Depends(get_db)):
    """Returns the master dataset for offline caching."""
    def clean(records, model_type=""):
        cleaned = []
        for r in records:
            item = {**r.__dict__}
            item.pop("_sa_instance_state", None)

            # Field Mapping DB -> PWA
            if model_type == "orgaos":
                item["id_tipo_orgao"] = item.pop("tipo", None)
            elif model_type in ("unidades", "cargos"):
                item["id_orgao"] = item.pop("orgao", None)

            cleaned.append(item)
        return cleaned

    programas = clean(db.query(models.programas.Programa).all())
    propriedades = clean(db.query(models.propriedades.Propriedade).all())
    produtores = clean(db.query(models.produtores.Produtor).all())
    termos = clean(db.query(models.inspecao.TermoInspecao).all())
    areas = clean(db.query(models.inspecao.AreaInspecionada).all())

    hospedeiros = clean(db.query(models.auxiliares.Hospedeiro).all())
    normas = clean(db.query(models.auxiliares.Norma).all())
    orgaos = clean(db.query(models.auxiliares.Orgao).all(), "orgaos")
    unidades = clean(db.query(models.auxiliares.Unidade).all(), "unidades")
    cargos = clean(db.query(models.auxiliares.Cargo).all(), "cargos")
    formacoes = clean(db.query(models.auxiliares.Formacao).all(), "formacao")
    usuarios = clean(db.query(models.usuarios.Usuario).all(), "usuarios")

    tipos_orgao_db = clean(db.query(models.auxiliares.TipoOrgao).all())

    return {
        "programas": programas,
        "propriedades": propriedades,
        "produtores": produtores,
        "termo_inspecao": termos,
        "area_inspecionada": areas,
        "hospedeiros": hospedeiros,
        "normas": normas,
        "tipos_orgao": tipos_orgao_db,
        "orgaos": orgaos,
        "unidades": unidades,
        "cargos": cargos,
        "formacoes": formacoes,
        "usuarios": usuarios
    }
