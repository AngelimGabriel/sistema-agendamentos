# Sistema de Agendamentos

Aplicação web para registro e gerenciamento de agendamentos de atendimentos entre
clientes e atendentes de uma empresa.

## Stack

- **Backend:** PHP 8.3 (padrão MVC, API REST) com PDO
- **Banco:** PostgreSQL 16 (SQL puro, sem ORM)
- **Frontend:** HTML, CSS e JavaScript vanilla
- **Ambiente:** Docker (Apache + PHP em um container, PostgreSQL em outro)

## Como rodar

Pré-requisito: Docker e Docker Compose instalados.

```bash
docker compose up --build
```

A aplicação fica disponível em **http://localhost:8000**.

### Conflito de porta

Se a porta `8000` (ou a `5432` do banco) já estiver em uso na máquina, defina outra
antes de subir — sem alterar nenhum arquivo:

```bash
APP_PORT=8080 DB_HOST_PORT=5433 docker compose up --build -d
```
