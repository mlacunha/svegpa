# Documentação da API Sanveg

## Swagger / OpenAPI

- **openapi.yaml** – especificação OpenAPI 3.0
- **swagger-ui.html** – interface interativa (abrir no navegador após servir o projeto)

Para visualizar o Swagger:
1. Inicie o servidor (Apache/Nginx ou `php -S localhost:8000`)
2. Acesse: `http://localhost/sanveg/api/docs/swagger-ui.html` (ou ajuste a URL conforme seu ambiente)

## Postman

1. Abra o Postman
2. **Importar Collection:** File → Import → escolha `postman/Sanveg-API.postman_collection.json`
3. **Importar Environment:** File → Import → escolha `postman/Sanveg-Local.postman_environment.json`
4. Selecione o ambiente "Sanveg - Local" no canto superior direito
5. Execute **Auth → Login** e copie o `token` da resposta
6. Coloque o token na variável `token` do ambiente (Editar Environment → token)

Os endpoints protegidos usarão automaticamente `{{token}}` via Bearer Auth.
