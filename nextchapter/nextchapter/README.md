# NextChapter

Calculadora de vida financeira (reforma, casa, viagem, carro, estudos, fundo de emergência, outro).
PHP + MySQL + HTML + CSS (sem JavaScript). Idiomas: PT / EN / ES. Modo claro e escuro.
Na primeira visita é obrigatório criar conta.

## Correr no XAMPP (local)

1. Copie a pasta `nextchapter` para `C:\xampp\htdocs\`.
2. Abra o XAMPP Control Panel e inicie **Apache** e **MySQL**.
3. Abra http://localhost/phpmyadmin → separador **Importar** → escolha `database.sql` → **Executar**.
4. Abra http://localhost/nextchapter/

Se a sua palavra-passe do MySQL não for vazia, defina as variáveis de ambiente em `includes/config.php`
(ou altere os valores por defeito lá).

## GitHub

```
git init
git add .
git commit -m "NextChapter"
git branch -M main
git remote add origin https://github.com/O_SEU_USER/nextchapter.git
git push -u origin main
```

## Vercel (produção)

A Vercel não tem PHP nativo; usa-se o runtime comunitário `vercel-php` (configurado em `vercel.json`).
Confirme a versão mais recente no README do projeto `juicyfx/vercel-php` e ajuste `vercel.json` se necessário.

1. Crie uma base MySQL online (o MySQL do XAMPP não é acessível pela Vercel), por exemplo TiDB Cloud, Aiven ou outro.
2. Importe `database.sql` nessa base (sem a linha `CREATE DATABASE`/`USE` se o fornecedor já der a base criada).
3. Na Vercel: **Import Project** → escolha o repositório → em **Environment Variables** defina:
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` e `DB_SSL=1` (se o fornecedor exigir TLS).
4. Deploy.

As sessões ficam guardadas na própria base de dados (tabela `sessions`), por isso o login funciona em serverless.

## Estrutura

- `index.php`, `register.php`, `login.php`, `logout.php`, `dashboard.php`, `calculator.php`, `view.php`, `set.php`
- `includes/` – base de dados, autenticação, motor de cálculo, layout
- `lang/` – traduções `pt.php`, `en.php`, `es.php`
- `assets/css/style.css` – estilos (azul claro e preto)
- `api/index.php` + `vercel.json` – só para a Vercel
