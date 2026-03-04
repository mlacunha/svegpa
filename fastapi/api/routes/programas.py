from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from typing import List

from core.database import get_db
import models.programas as models
import schemas.programas as schemas
import uuid

router = APIRouter(prefix="/api/programas", tags=["Programas"])

@router.get("/", response_model=List[schemas.ProgramaResponse])
def get_programas(skip: int = 0, limit: int = 100, db: Session = Depends(get_db)):
    programas = db.query(models.Programa).offset(skip).limit(limit).all()
    return programas

@router.get("/{programa_id}", response_model=schemas.ProgramaResponse)
def get_programa(programa_id: str, db: Session = Depends(get_db)):
    programa = db.query(models.Programa).filter(models.Programa.id == programa_id).first()
    if not programa:
        raise HTTPException(status_code=404, detail="Programa não encontrado")
    return programa

@router.post("/", response_model=schemas.ProgramaResponse)
def create_programa(programa: schemas.ProgramaCreate, db: Session = Depends(get_db)):
    new_id = str(uuid.uuid4())
    db_programa = models.Programa(**programa.model_dump(), id=new_id)
    db.add(db_programa)
    db.commit()
    db.refresh(db_programa)
    return db_programa
