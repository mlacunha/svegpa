from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from sqlalchemy import text

from core.database import get_db
import schemas.dashboard as schemas

router = APIRouter(prefix="/api/dashboard", tags=["Dashboard"])

@router.get("/", response_model=schemas.DashboardResponse)
def get_dashboard_data(db: Session = Depends(get_db)):
    
    # 1. Total Programas
    total_programas = db.execute(text("SELECT COUNT(*) as total FROM programas")).scalar() or 0
    
    # 2. Total Municípios e Status
    try:
        # Pega as duas fontes e agrupa por município e status usando UNION ALL
        query_muns_status = """
        SELECT municipio, st FROM (
            SELECT TRIM(municipio) COLLATE utf8mb4_general_ci as municipio, UPPER(TRIM(COALESCE(status, 'NORMAL'))) COLLATE utf8mb4_general_ci as st 
            FROM relatorio_mapa 
            WHERE municipio IS NOT NULL AND TRIM(municipio) != ''

            UNION ALL

            SELECT TRIM(pr.municipio) COLLATE utf8mb4_general_ci as municipio, 
                   (CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END) COLLATE utf8mb4_general_ci AS st
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao COLLATE utf8mb4_general_ci = t.id COLLATE utf8mb4_general_ci
            LEFT JOIN propriedades pr ON t.id_propriedade COLLATE utf8mb4_general_ci = pr.id COLLATE utf8mb4_general_ci
            WHERE pr.municipio IS NOT NULL AND TRIM(pr.municipio) != ''
        ) AS all_data
        """
        
        all_st_mun = db.execute(text(query_muns_status)).fetchall()
        
        muns_unique = set()
        count_normal = 0
        count_suspeita = 0
        count_foco = 0
        
        for r in all_st_mun:
            mun, st = r[0], r[1]
            muns_unique.add(mun)
            
            # Conta os totais por status
            if st == 'NORMAL': count_normal += 1
            elif st == 'SUSPEITA': count_suspeita += 1
            elif st in ('FOCO', 'POSITIVO'): count_foco += 1
            else: count_normal += 1 # Default

        total_municipios = len(muns_unique)

    except Exception as e:
        print("Erro em total_municipios:", e)
        db.rollback()
        total_municipios = 0
        count_normal = 0
        count_suspeita = 0
        count_foco = 0

    # 3. Geo Map Points
    try:
        # Pega as duas fontes mapeando colunas para preencher o Popup no React
        mapa_pontos_raw = db.execute(text("""
            SELECT latitude, longitude, status, municipio, cultura, tipo_imovel, id_programa, nome_programa, nome_propriedade, ano, trimestre
            FROM (
                SELECT r.latitude, r.longitude, 
                       (CASE WHEN UPPER(TRIM(r.status)) = 'POSITIVO' THEN 'FOCO' ELSE UPPER(TRIM(COALESCE(r.status, 'NORMAL'))) END) COLLATE utf8mb4_general_ci AS status, 
                       r.municipio COLLATE utf8mb4_general_ci as municipio, 
                       r.cultura COLLATE utf8mb4_general_ci as cultura, 
                       r.tipo_imovel COLLATE utf8mb4_general_ci as tipo_imovel, 
                       (CAST(r.id_programa AS CHAR)) COLLATE utf8mb4_general_ci as id_programa, 
                       p.nome COLLATE utf8mb4_general_ci as nome_programa, 
                       NULL as nome_propriedade,
                       (CAST(r.ano AS CHAR)) COLLATE utf8mb4_general_ci as ano, 
                       (CAST(r.trimestre AS CHAR)) COLLATE utf8mb4_general_ci as trimestre
                FROM relatorio_mapa r
                LEFT JOIN programas p ON r.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci
                WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL
                
                UNION ALL
                
                SELECT COALESCE(a.latitude, pr.latitude) AS latitude, 
                       COALESCE(a.longitude, pr.longitude) AS longitude,
                       (CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END) COLLATE utf8mb4_general_ci AS status,
                       pr.municipio COLLATE utf8mb4_general_ci as municipio, 
                       a.especie COLLATE utf8mb4_general_ci AS cultura, 
                       COALESCE(a.tipo_area, pr.classificacao) COLLATE utf8mb4_general_ci AS tipo_imovel,
                       (CAST(t.id_programa AS CHAR)) COLLATE utf8mb4_general_ci as id_programa, 
                       p.nome COLLATE utf8mb4_general_ci AS nome_programa, 
                       pr.nome COLLATE utf8mb4_general_ci AS nome_propriedade,
                       CAST(YEAR(t.data_inspecao) as CHAR) COLLATE utf8mb4_general_ci AS ano, 
                       CAST(QUARTER(t.data_inspecao) as CHAR) COLLATE utf8mb4_general_ci AS trimestre
                FROM termo_inspecao t
                INNER JOIN area_inspecionada a ON a.id_termo_inspecao COLLATE utf8mb4_general_ci = t.id COLLATE utf8mb4_general_ci
                LEFT JOIN propriedades pr ON t.id_propriedade COLLATE utf8mb4_general_ci = pr.id COLLATE utf8mb4_general_ci
                LEFT JOIN programas p ON t.id_programa COLLATE utf8mb4_general_ci = p.id COLLATE utf8mb4_general_ci
                WHERE (a.latitude IS NOT NULL OR pr.latitude IS NOT NULL)
                  AND (a.longitude IS NOT NULL OR pr.longitude IS NOT NULL)
            ) AS combined_map
        """)).fetchall()
    except Exception as e:
        print("Erro em mapa_pontos_raw:", e)
        db.rollback()
        mapa_pontos_raw = []
        
    mapa_pontos = []
    for m in mapa_pontos_raw:
        mapa_pontos.append({
            "latitude": float(m.latitude),
            "longitude": float(m.longitude),
            "status": m.status or 'NORMAL',
            "municipio": m.municipio or 'Desconhecido',
            "cultura": m.cultura,
            "tipo_imovel": m.tipo_imovel,
            "id_programa": m.id_programa,
            "nome_programa": m.nome_programa,
            "nome_propriedade": m.nome_propriedade,
            "ano": m.ano,
            "trimestre": m.trimestre
        })

    # 4. Recents
    prog_recentes = db.execute(text("SELECT id, nome, codigo FROM programas ORDER BY criado_em DESC LIMIT 5")).fetchall()
    prop_recentes = db.execute(text("SELECT id, nome, municipio, UF FROM propriedades ORDER BY criado_em DESC LIMIT 5")).fetchall()
    prod_recentes = db.execute(text("SELECT id, nome, municipio, uf FROM produtores ORDER BY criado_em DESC LIMIT 5")).fetchall()

    return schemas.DashboardResponse(
        stats=schemas.DashboardStats(
            total_programas=total_programas,
            total_municipios=total_municipios,
            count_normal=count_normal,
            count_suspeita=count_suspeita,
            count_foco=count_foco
        ),
        mapa_pontos=mapa_pontos,
        recent_programas=[{"id": p.id, "primary": p.nome, "secondary": p.codigo or "Sem código"} for p in prog_recentes],
        recent_propriedades=[{"id": p.id, "primary": p.nome, "secondary": f"{p.municipio or 'Münicípio indisp.'} • {p.UF or 'UF'}"} for p in prop_recentes],
        recent_produtores=[{"id": p.id, "primary": p.nome, "secondary": f"{p.municipio or '-'} • {p.uf or '-'}"} for p in prod_recentes]
    )
