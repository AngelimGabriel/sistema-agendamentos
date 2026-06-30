# Decisões de Projeto

Registro das decisões tomadas em pontos que o case deixou em aberto, sempre
alinhadas aos requisitos. Cada item aponta o requisito relacionado.

---

# Backend

## Estrutura e roteamento REST

As rotas seguem o estilo REST (`GET /users`, `POST /users`, `GET /users/{id}`). Um `.htaccess`
com mod_rewrite manda toda requisição para o `public/index.php` (front controller), que despacha
para o controller certo conforme o método HTTP + caminho. Mantém uma porta única de entrada.

## Autoloader próprio, sem Composer

Carrego as classes com um autoloader próprio (`spl_autoload_register`), sem Composer. O projeto
não tem dependências externas, então um autoloader de poucas linhas resolve e mantém o setup
simples (só `docker compose up`, sem `composer install`).

## Padronização das respostas HTTP

Toda resposta da API retorna o código correto (requisito não funcional RQNF2): 200, 201, 400, 401, 403, 404, 500,
com corpo JSON. Erros internos não vazam detalhes ao cliente, mas sim uma mensagem amigável para o
usuário e detalhe técnico apenas no log do servidor (requisito não funcional RQNF3).

## Autenticação por sessão

O case não descreve uma tela de login, mas as permissões por tipo de usuário (requisito funcional RQF1) e os status
401/403 (requisitos não funcional RQNF2) a exigem. Implementei login com sessão do PHP e senha em hash bcrypt.

## Duração do agendamento: slots de 1 hora

O case (requisito funcional RQF2.3) não especifica a duração do atendimento. Adotei slots fixos de 1 hora (tipa agenda de dentistas).

- A janela de disponibilidade continua **livre e definida pelo admin** por dia da semana
  (reuqisito funcional RQF2.2: Hora Inicial / Hora Final), como o case exige — não há teto fixo de horário.
- Os horários são dividos de 1 em 1 hora dentro da janela definida pelo admin.
- Restrição leve nas janelas em horas cheias (12:00 por exemplo), para a divisão de 1h ficar limpo.

## Disponibilidade é derivada, não armazenada

Não guardo "horários livres". Guardo apenas os agendamentos (ocupados) na tabela
`appointments`. Os horários disponíveis são calculados sob demanda:
(janela do atendente, fatiada em 1h) − (agendamentos existentes).

- O cálculo fica no **backend** (regra de negocio do backend); a API entrega a grade pronta para o front.
- Atende ao requisito funcional RQF2.3, horários ocupados não são oferecidos como opção válida.

## Disponibilidades não se sobrepõem

O case permite várias janelas por dia, mas não trata janelas sobrepostas ou duplicadas (ex: Segunda 08–12
cadastrada duas vezes, ou 08–12 junto de 09–12), que gerariam horários redundantes. Apoiado na OBSERVAÇÃO do
case, bloqueio a criação/edição de janela que se sobreponha a outra do mesmo atendente no mesmo dia (responde 400).
Janelas encostadas (ex: 09–12 e 12–18) são permitidas, pois não se sobrepõem.

## Campo Nome obrigatório

No case (requisito funcional RQF1.2), Nome não tem asterisco, pelo requisito não funcional RQNF4, apenas os campos com asterisco são
obrigatórios. Mantive **Nome** como obrigatorio por integridade, um usuário sem nome não
faz sentido na listagem. As validações com asterisco do case (senha, confirme a senha, email)
seguem implementadas exatamente como descrito.

## Edição não altera email nem senha

requisito funcional RQF1.3: "editar os mesmos campos da inserção, exceto email e senha". Na tela de edição,
esses dois campos não são editáveis por nenhum tipo de usuário.

## Ninguém altera o próprio tipo de usuário

O case (requisito funcional RQF1.3) permite editar os mesmos campos da inserção, o que inclui o Tipo de Usuário,
mas não trata o caso de um usuário alterar o próprio tipo. Permitir isso traria dois problemas: um atendente
poderia se auto-promover a admin (furo de segurança) e um admin poderia se auto-rebaixar e perder o próprio
acesso. Apoiado na OBSERVAÇÃO do case ("é provável que você se depare com algum requisito não especificado...
sinta-se à vontade para definir a melhor solução"), defini a regra: ninguém altera o próprio tipo de usuário.
Só o admin altera o tipo, e apenas o de outro usuário; ao editar a si mesmo, o tipo é mantido.

## Garante ao menos um administrador

O case (requisito funcional RQF1.1) exige que ao menos um usuário seja administrador. Duas regras garantem isso:
ninguém altera o próprio tipo de usuário (então o último admin não consegue se rebaixar) e a exclusão do último
administrador ativo é bloqueada (responde 400). Como rebaixar outro admin só é possível havendo ao menos dois,
a base nunca fica sem administrador.

## Login com mensagem genérica

No login, e-mail inexistente e senha errada retornam a mesma resposta ("E-mail ou senha inválidos", 401).
Não revelar qual dos dois falhou dificulta a enumeração de usuários válidos.

---

# Frontend

## Camada de acesso à API centralizada

Todas as chamadas ao backend passam por um único módulo JS (ex: `api.js`), que concentra o `fetch`,
a leitura do status HTTP e o parse do erro. Evita `fetch` espalhado e centraliza o tratamento de resposta.

## Tratamento de erro amigável

O front nunca mostra o erro cru do backend. Cada resposta de erro vira uma mensagem amigável para o
usuário (requisito não funcional RQNF3), e os campos obrigatórios (requisito não funcional RQNF4) são validados antes de enviar.

## Exibição de horários ocupados

Na consulta de horários, mostro a grade completa do dia com os horários ocupados desabilitados
(em cinza), não selecionáveis. Atende o requisito funcional RQF2.3 ("ocupado não é opção válida")
e melhora a usabilidade: o usuário vê o dia inteiro e entende por que um horário não está livre,
em vez de ele simplesmente sumir.

---

# Banco de Dados

## Exclusão de usuários: soft delete

O case (requisito funcional RQF1.1) fala em "remover o registro". Optei por soft delete (coluna `deleted_at`):
o registro some das listagens, mas preserva o histórico de agendamentos de um atendente removido (otimo para auditorias e relatorios).

## Email único com soft delete: índice parcial

o requisito funcional RQF1.2 exige email único na base. Com soft delete, um UNIQUE comum impediria recadastrar o
email de um usuário excluído. Usei índice único parcial (`WHERE deleted_at IS NULL`), isso garante que os emails sejam 
únicos apenas entre os registros ativos.

## updated_at atualizado pela aplicação

O `updated_at` é atualizado pelo PHP no próprio UPDATE, não por trigger. É mais simples de seguir lendo
o código do que uma trigger escondida no banco.

## Integridade também no banco (CHECK)

Além da validação no PHP, o banco reforça regras via CHECK: role válido, day_of_week entre 0 e 6,
end_time maior que start_time e status válido. É defesa em profundidade — mesmo com um bug na aplicação,
o banco rejeita dado inconsistente.

## Agendamento não referencia a disponibilidade

Um agendamento aponta direto para `attendant_id` + `date`, e não para um `availability_id`. A relação com
a janela de disponibilidade é lógica de consulta (calculada na hora), não de persistência — assim a agenda
não quebra se o admin alterar a disponibilidade depois.
