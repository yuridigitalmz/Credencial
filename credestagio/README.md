# CredEstágio — credenciais de estágio (PHP 8.1+ e MySQL/MariaDB)

## Instalar (instalação nova)
1. Se já importou uma versão anterior e só tem dados de demonstração, apague a base `credestagio`.
2. Importe `sql/database.sql` (phpMyAdmin, ou `mysql -u root -p < sql/database.sql`).
3. Ajuste `config.php` (acesso ao MySQL e, em produção, `SITE_URL`). Copie a pasta para `htdocs/` e abra `http://localhost/credestagio/`.
4. A pasta `uploads/assinaturas/` precisa de permissão de escrita (Linux: `chmod -R 775 uploads`).

**Já tem a versão anterior com dados reais?** Faça cópia de segurança e execute uma vez `sql/atualizar_v3.sql`.
Depois registe as disciplinas e notas (ou ponha `EXIGIR_DISCIPLINAS = false` em `config.php`).

## Contas de demonstração
- Secretaria: `admin` / `admin2026`
- Chefes de departamento: `chefe.mat` e `chefe.fis` / `chefe2026`
- Estudantes (senha `estagio123`):
  - Podem pedir: 04.0021.2023 (Ana), 04.0034.2023 (Carlos), 05.0028.2022 (Rosinda)
  - Não podem: 04.0057.2024 (2.º ano), 05.0012.2023 (propinas em dívida), 06.0009.2025 (1.º ano), 06.0015.2023 (reprovou a Óptica)

**Mude estas senhas antes de usar a sério.** Contas criadas ou repostas pela secretaria obrigam a trocar a senha no primeiro acesso.

## Fluxo
Estudante pede → o sistema valida (3.º ano ou mais, propinas em dia, cadeiras do 1.º ao 3.º ano aprovadas) →
pedidos válidos ficam *Pendentes* → secretaria ou chefe do departamento aprova/rejeita → o estudante recebe aviso no painel
(e por e-mail, se tiver) → imprime a credencial, com código QR de verificação em `verificar.php`.

## Configuração útil (`config.php`)
- `LOCAL_EMISSAO` — localidade impressa na data da credencial (por omissão «Dondo»).
- `EXIGIR_DISCIPLINAS` — exigir cadeiras do 1.º ao 3.º ano aprovadas.
- `SITE_URL` — endereço público do site (e-mails e código QR). O fuso horário é o de Moçambique (Africa/Maputo).

## Importar estudantes em massa
Estudantes → *Importar vários estudantes (CSV)*. Descarregue o modelo, preencha no Excel, guarde como CSV e carregue.
O sistema mostra as senhas temporárias uma só vez (com ficheiro para descarregar) e explica as linhas com erro.

## E-mail
O sistema usa a função `mail()` do PHP e guarda sempre uma cópia em *Registo > E-mails enviados*
(útil sem SMTP, por exemplo para copiar a ligação de recuperação de senha). Em produção configure o SMTP no `php.ini`.

## Segurança
bcrypt, consultas preparadas (PDO), escape de saída, CSRF, sessão renovada no login, cookies HttpOnly/SameSite,
expiração por inactividade (20 min), bloqueio após 8 tentativas falhadas, recuperação de senha com ligação de uso único
(guardada com hash) válida 1 hora, validação real do tipo das imagens de assinatura, CSP sem scripts inline,
`.htaccess` a bloquear `inc/`, `sql/`, `uploads/` e `config.php` (Apache; em Nginx bloqueie essas pastas na configuração).

## Antes de usar a sério
Use HTTPS; crie um utilizador MySQL próprio (não o `root`); mude as senhas de demonstração; faça cópias de segurança da base de dados e de `uploads/`.
