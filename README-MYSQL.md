# CEPIN-CIS — versão MySQL/Railway

Esta versão encerra a dependência dos JSONs de **usuários, projetos e parceiros**. Esses dados passam a ser lidos e gravados exclusivamente no MySQL.

## Railway

1. Mantenha as variáveis de conexão MySQL do Railway (`DATABASE_URL` ou `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`).
2. Faça o deploy.
3. Abra `migrate.php` uma única vez para criar/verificar as tabelas e registrar as migrations.
4. Depois da execução, remova `migrate.php` do servidor ou bloqueie seu acesso público.

A versão usa as tabelas `users`, `projects`, `partners`, `project_documents`, `project_collaborators`, `project_collaboration_invites`, `project_authentication_history`, `project_timeline_events`, `project_timeline_history` e `notifications`.

## Importante

O arquivo `.env` não é incluído no pacote. O Railway deve fornecer as credenciais por variáveis de ambiente.

Os JSONs de conteúdo institucional e orientações ainda são mantidos porque não existe, neste pacote, uma tabela MySQL equivalente para esses dois módulos. Eles não participam da persistência de usuários, projetos ou parceiros.
