from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import text
from typing import Optional
from core.database import get_db

router = APIRouter(prefix="/api/relatorios", tags=["Relatórios"])


@router.get("/levantamentos")
def get_levantamentos(
    db: Session = Depends(get_db),
    ano: Optional[str] = Query(None),
    trimestre: Optional[str] = Query(None),
    programa: Optional[str] = Query(None),
    municipio: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    page: int = Query(1, ge=1),
    per_page: int = Query(50, ge=1, le=10000),
):
    """
    Retorna os pontos de levantamento fitossanitário (mesma origem do mapa),
    com filtros opcionais e paginação.
    """
    base_sql = """
        SELECT
            latitude, longitude, status, municipio,
            cultura, tipo_imovel, id_programa, nome_programa,
            nome_propriedade, ano, trimestre
        FROM (
            SELECT
                r.latitude, r.longitude,
                (CASE WHEN UPPER(TRIM(r.status)) = 'POSITIVO' THEN 'FOCO'
                      ELSE UPPER(TRIM(COALESCE(r.status, 'NORMAL'))) END)
                    COLLATE utf8mb4_general_ci AS status,
                r.municipio        COLLATE utf8mb4_general_ci AS municipio,
                r.cultura          COLLATE utf8mb4_general_ci AS cultura,
                r.tipo_imovel      COLLATE utf8mb4_general_ci AS tipo_imovel,
                CAST(r.id_programa AS CHAR) COLLATE utf8mb4_general_ci AS id_programa,
                p.nome             COLLATE utf8mb4_general_ci AS nome_programa,
                NULL AS nome_propriedade,
                CAST(r.ano AS CHAR)       COLLATE utf8mb4_general_ci AS ano,
                CAST(r.trimestre AS CHAR) COLLATE utf8mb4_general_ci AS trimestre
            FROM relatorio_mapa r
            LEFT JOIN programas p
                ON r.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci
            WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL

            UNION ALL

            SELECT
                COALESCE(a.latitude,  pr.latitude)  AS latitude,
                COALESCE(a.longitude, pr.longitude) AS longitude,
                (CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA'
                      ELSE 'NORMAL' END) COLLATE utf8mb4_general_ci AS status,
                pr.municipio                   COLLATE utf8mb4_general_ci AS municipio,
                a.especie                      COLLATE utf8mb4_general_ci AS cultura,
                COALESCE(a.tipo_area, pr.classificacao)
                                               COLLATE utf8mb4_general_ci AS tipo_imovel,
                CAST(t.id_programa AS CHAR)    COLLATE utf8mb4_general_ci AS id_programa,
                p.nome                         COLLATE utf8mb4_general_ci AS nome_programa,
                pr.nome                        COLLATE utf8mb4_general_ci AS nome_propriedade,
                CAST(YEAR(t.data_inspecao)    AS CHAR) COLLATE utf8mb4_general_ci AS ano,
                CAST(QUARTER(t.data_inspecao) AS CHAR) COLLATE utf8mb4_general_ci AS trimestre
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a
                ON a.id_termo_inspecao COLLATE utf8mb4_general_ci = t.id COLLATE utf8mb4_general_ci
            LEFT JOIN propriedades pr
                ON t.id_propriedade COLLATE utf8mb4_general_ci = pr.id COLLATE utf8mb4_general_ci
            LEFT JOIN programas p
                ON t.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci
            WHERE (a.latitude IS NOT NULL OR pr.latitude IS NOT NULL)
              AND (a.longitude IS NOT NULL OR pr.longitude IS NOT NULL)
        ) AS combined
        WHERE 1=1
    """

    params = {}

    if ano:
        base_sql += " AND ano = :ano"
        params["ano"] = ano
    if trimestre:
        base_sql += " AND trimestre = :trimestre"
        params["trimestre"] = trimestre
    if programa:
        base_sql += " AND nome_programa = :programa"
        params["programa"] = programa
    if municipio:
        base_sql += " AND municipio = :municipio"
        params["municipio"] = municipio
    if status:
        base_sql += " AND status = :status"
        params["status"] = status.upper()

    # Contagem total (sem paginação)
    count_sql = f"SELECT COUNT(*) FROM ({base_sql}) AS cnt"
    total = db.execute(text(count_sql), params).scalar() or 0

    # Dados paginados
    data_sql = base_sql + " ORDER BY ano DESC, trimestre DESC, municipio ASC"
    data_sql += " LIMIT :limit OFFSET :offset"
    params["limit"] = per_page
    params["offset"] = (page - 1) * per_page

    try:
        rows = db.execute(text(data_sql), params).fetchall()
    except Exception as e:
        print("Erro em levantamentos:", e)
        db.rollback()
        rows = []

    items = []
    for r in rows:
        items.append({
            "nome_programa":    r.nome_programa or "",
            "ano":              r.ano or "",
            "trimestre":        r.trimestre or "",
            "municipio":        r.municipio or "",
            "nome_propriedade": r.nome_propriedade or "",
            "tipo_imovel":      r.tipo_imovel or "",
            "cultura":          r.cultura or "",
            "latitude":         float(r.latitude) if r.latitude else None,
            "longitude":        float(r.longitude) if r.longitude else None,
            "status":           r.status or "NORMAL",
        })

    # Opções de filtro distintas (para popular os selects no frontend)
    opts_sql = """
        SELECT DISTINCT ano, trimestre, nome_programa, municipio, status
        FROM (
            SELECT
                CAST(r.ano AS CHAR) COLLATE utf8mb4_general_ci AS ano,
                CAST(r.trimestre AS CHAR) COLLATE utf8mb4_general_ci AS trimestre,
                p.nome COLLATE utf8mb4_general_ci AS nome_programa,
                r.municipio COLLATE utf8mb4_general_ci AS municipio,
                (CASE WHEN UPPER(TRIM(r.status)) = 'POSITIVO' THEN 'FOCO'
                      ELSE UPPER(TRIM(COALESCE(r.status, 'NORMAL'))) END)
                    COLLATE utf8mb4_general_ci AS status
            FROM relatorio_mapa r
            LEFT JOIN programas p ON r.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci

            UNION ALL

            SELECT
                CAST(YEAR(t.data_inspecao) AS CHAR) COLLATE utf8mb4_general_ci AS ano,
                CAST(QUARTER(t.data_inspecao) AS CHAR) COLLATE utf8mb4_general_ci AS trimestre,
                p.nome COLLATE utf8mb4_general_ci AS nome_programa,
                pr.municipio COLLATE utf8mb4_general_ci AS municipio,
                (CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END)
                    COLLATE utf8mb4_general_ci AS status
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao COLLATE utf8mb4_general_ci = t.id COLLATE utf8mb4_general_ci
            LEFT JOIN propriedades pr ON t.id_propriedade COLLATE utf8mb4_general_ci = pr.id COLLATE utf8mb4_general_ci
            LEFT JOIN programas p ON t.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci
        ) AS opts
        WHERE ano IS NOT NULL
        ORDER BY ano DESC, trimestre, nome_programa, municipio
    """
    try:
        opts_rows = db.execute(text(opts_sql)).fetchall()
    except Exception as e:
        print("Erro em filter_options:", e)
        db.rollback()
        opts_rows = []

    anos      = sorted(set(r.ano      for r in opts_rows if r.ano),      reverse=True)
    trimestres = sorted(set(r.trimestre for r in opts_rows if r.trimestre))
    programas_list = sorted(set(r.nome_programa for r in opts_rows if r.nome_programa))
    municipios_list = sorted(set(r.municipio    for r in opts_rows if r.municipio))
    statuses   = sorted(set(r.status   for r in opts_rows if r.status))

    return {
        "total":    total,
        "page":     page,
        "per_page": per_page,
        "pages":    max(1, -(-total // per_page)),  # ceil division
        "items":    items,
        "filter_options": {
            "anos":       anos,
            "trimestres": trimestres,
            "programas":  programas_list,
            "municipios": municipios_list,
            "statuses":   statuses,
        }
    }
