from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from fastapi.security import OAuth2PasswordRequestForm
from core.database import get_db
from models.usuarios import Usuario
from core.security import verify_password, create_access_token

router = APIRouter()

@router.post("/token")
def login_for_access_token(db: Session = Depends(get_db), form_data: OAuth2PasswordRequestForm = Depends()):
    # OAuth2 specifies 'username', but we allow email or cpf in this field based on PWA.
    user = db.query(Usuario).filter(
        (Usuario.email == form_data.username) | (Usuario.cpf == form_data.username)
    ).first()
    
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciais incorretas (usuário não encontrado).",
            headers={"WWW-Authenticate": "Bearer"},
        )
        
    if not user.ativo:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Usuário inativo.",
        )
        
    if not verify_password(form_data.password, user.senha_hash):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciais incorretas (senha inválida).",
            headers={"WWW-Authenticate": "Bearer"},
        )
        
    access_token = create_access_token(
        data={"sub": str(user.id), "role": user.role, "orgao": user.id_orgao}
    )
    
    return {"access_token": access_token, "token_type": "bearer", "user": {
        "id": user.id,
        "nome": user.nome,
        "email": user.email,
        "role": user.role,
        "id_orgao": user.id_orgao
    }}
