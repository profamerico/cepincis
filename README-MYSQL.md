# CEPIN-CIS — Railway / MySQL

Esta versão usa o MySQL do Railway como fonte de dados para usuários, projetos, parceiros, documentos, colaboradores, convites, timeline e notificações.

## Railway

No serviço PHP, mantenha `DATABASE_URL` apontando para o MySQL do Railway.

Para executar a migration pelo navegador, crie temporariamente uma variável:

`CEPIN_MIGRATION_KEY=uma-chave-temporaria`

Depois do deploy, abra:

`https://SEU-DOMINIO/migrate.php?key=uma-chave-temporaria`

O resultado esperado começa com `MIGRATION_OK`.

Após a execução, remova `migrate.php` do repositório e remova `CEPIN_MIGRATION_KEY` das Variables do Railway.

## Fluxos implementados

- Usuários e autenticação em MySQL.
- Projetos em MySQL.
- Parceiros em MySQL, com seed dos seis parceiros institucionais existentes.
- Criação de projeto sem exigir documentação.
- Upload de documentação PDF/DOCX pelo workspace, com limite de 10 MB.
- Autenticação documental administrativa.
- Colaboradores e convites.
- Timeline e histórico.
- Notificações e contador no header.
- Modo claro/escuro persistente em `localStorage`.
- Carrossel de parceiros com navegação por setas, cards, pontos, teclado e toque.

Os JSONs de usuários, projetos e parceiros foram removidos. `content_blocks.json`, `content_layouts.json`, `orientations.json`, `role_requests.json` e `user_profiles.json` permanecem porque esses módulos ainda não possuem equivalentes MySQL completos nesta versão.
