# Sistema de Agendamentos

Aplicação web para registro e gerenciamento de agendamentos de atendimentos entre
clientes e atendentes de uma empresa.

## Stack

- **Backend:** PHP 8.3 (padrão MVC, API REST) com PDO
- **Banco:** PostgreSQL 16 (SQL puro, sem ORM)
- **Frontend:** HTML, CSS e JavaScript vanilla
- **Ambiente:** Docker (Apache + PHP em um container, PostgreSQL em outro)

## Funcionalidades

- **Autenticação** por sessão, com dois tipos de usuário: administrador e atendente.
- **Módulo de Usuários:** listagem, cadastro, edição e exclusão, com permissões por tipo
  (apenas admin cadastra/exclui; atendente edita apenas o próprio usuário) e validações
  de e-mail (formato e unicidade), senha e confirmação.
- **Módulo de Agendamentos:**
  - Cadastro da disponibilidade de cada atendente por dia da semana (apenas admin).
  - Consulta de horários disponíveis: ao escolher atendente e data, o sistema mostra a
    grade de horários (slots de 1h), marcando os ocupados.
  - Criação e cancelamento de agendamentos.

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

## Usuários de exemplo

O banco já sobe com usuários para teste:

| Tipo      | E-mail              | Senha          |
| --------- | ------------------- | -------------- |
| Admin     | `admin@admin.com`   | `admin123`     |
| Atendente | `ana@empresa.com`   | `atendente123` |
| Atendente | `bruno@empresa.com` | `atendente123` |

## Fluxo de uso

1. Acesse **http://localhost:8000** e faça login como administrador.
2. Você cai na tela de **Agendamentos**. Escolha um atendente e expanda as seções:
   - **Disponibilidade:** cadastre janelas de atendimento por dia da semana.
   - **Horários disponíveis:** selecione uma data, veja os slots livres (verde) e
     ocupados (vermelho), e clique em um livre para agendar um cliente.
   - **Agenda do atendente:** veja os agendamentos e cancele quando necessário.
3. Na tela de **Usuários**, gerencie os usuários do sistema.
4. Faça login como atendente (`ana@empresa.com`) para ver as permissões reduzidas.

## Estrutura do projeto

```
app/
  Core/           infraestrutura (Router, Response, Database, Auth, Request)
  Controllers/    controllers da API
  Models/         acesso a dados (SQL puro via PDO)
database/         schema e seed (SQL, executados na criação do banco)
docker/           configuração do Apache
public/           front controller (index.php), .htaccess e o frontend (HTML/CSS/JS)
postman/          coleção da API para testes
DECISOES.md       decisões de arquitetura e requisitos não especificados resolvidos
```

## Decisões de projeto

As principais decisões de arquitetura e a resolução dos requisitos que o enunciado deixou
em aberto estão documentadas em **(DECISOES.md)**.

## API (Postman)

A coleção com todas as rotas está em **(postman/)**. Importe no Postman, rode o
request **Login** e as demais requisições já reaproveitam a sessão automaticamente.
