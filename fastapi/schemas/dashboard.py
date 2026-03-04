from pydantic import BaseModel
from typing import Optional

class DashboardStats(BaseModel):
    total_programas: int
    total_municipios: int
    count_normal: int
    count_suspeita: int
    count_foco: int
    
class RecentItem(BaseModel):
    id: str
    primary: str
    secondary: str

class MapPoint(BaseModel):
    latitude: float
    longitude: float
    status: str
    municipio: str
    cultura: Optional[str] = None
    tipo_imovel: Optional[str] = None
    id_programa: Optional[str] = None
    nome_programa: Optional[str] = None
    nome_propriedade: Optional[str] = None
    ano: Optional[str] = None
    trimestre: Optional[str] = None

class DashboardResponse(BaseModel):
    stats: DashboardStats
    mapa_pontos: list[MapPoint]
    recent_programas: list[RecentItem]
    recent_propriedades: list[RecentItem]
    recent_produtores: list[RecentItem]
