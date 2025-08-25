# Carteira Financeira - API

Este é o backend para a aplicação de Carteira Financeira, desenvolvido com Laravel. O projeto está containerizado com Docker.
## Funcionalidades

-   **Autenticação**: Registro e login de usuários com Sanctum.
-   **Carteira Digital**: Cada usuário possui uma carteira com saldo.
-   **Transações**: Suporte para depósitos, transferências entre usuários e estornos.
-   **Observabilidade**: Integração com Sentry para monitoramento de erros em tempo real.
-   **Documentação**: API documentada com Swagger (OpenAPI).
-   **Testes**: Suíte de testes funcionalidade para garantir a qualidade do código.

---

## 🚀 Requisitos

Antes de começar, garanta que você tenha o **Docker Desktop** instalado e em execução na sua máquina.

-   [Download Docker Desktop](https://www.docker.com/products/docker-desktop/)

---

## ⚙️ Configuração e Instalação

Siga estes passos para configurar e executar o ambiente de desenvolvimento.

### 1. Clone o Repositório

`git clone https://github.com/JoseDanielF/carteira-financeira`
`cd carteira-financeira`

### 2. Configure as Variáveis de Ambiente

O projeto utiliza dois arquivos `.env` para configuração: um para o Docker Compose e outro para o Laravel.

**a) Arquivo do Docker Compose:**

Crie um arquivo chamado `.env` na raiz do projeto (no mesmo nível do `docker-compose.yml`) e adicione a senha do banco de dados:

`# ./.env`
`MYSQL_ROOT_PASSWORD=root`

**b) Arquivo do Laravel:**

Copie o arquivo de exemplo `.env.example` para criar o arquivo de configuração do Laravel:

`cp .env.example .env`

Abra o novo arquivo `.env` do Laravel e configure as variáveis do banco de dados para se conectar ao container Docker:

`# ./.env (arquivo do Laravel)`
`DB_CONNECTION=mysql`
`DB_HOST=db`
`DB_PORT=3306`
`DB_DATABASE=carteira_financeira`
`DB_USERNAME=root`
`DB_PASSWORD=root`

### 3. Suba os Containers Docker

Com os arquivos de ambiente configurados, execute o seguinte comando para construir as imagens e iniciar os containers em segundo plano:

`docker-compose up -d --build`

Aguarde alguns instantes para que o container do banco de dados seja inicializado e passe na verificação de saúde. Você pode verificar o status com `docker-compose ps`.

### 4. Finalize a Instalação do Laravel

Agora, execute os seguintes comandos para instalar as dependências, gerar a chave da aplicação e rodar as migrações do banco de dados. **Todos os comandos `artisan` devem ser executados através do Docker.**

`# Instalar dependências do Composer`
`docker-compose exec app composer install`

`# Gerar a chave da aplicação (se necessário)`
`docker-compose exec app php artisan key:generate`

`# Limpar cache de configuração`
`docker-compose exec app php artisan config:clear`

`# Rodar as migrações do banco de dados`
`docker-compose exec app php artisan migrate`

Pronto! Sua aplicação está configurada e rodando.

---

## ▶️ Executando a Aplicação

-   **API**: A API estará acessível em `http://localhost:8000`
-   **Documentação da API (Swagger)**: `http://localhost:8000/api/documentation`

### Comandos Úteis do Docker

-   **Parar os containers**: `docker-compose down`
-   **Parar e remover volumes de dados (resetar o banco)**: `docker-compose down -v`
-   **Ver logs em tempo real**: `docker-compose logs -f`
-   **Acessar o terminal do container da aplicação**: `docker-compose exec app bash`

---

## ✅ Executando os Testes

Para rodar a suíte de testes (Funcionalidade), execute:

`docker-compose exec app php artisan test`

Para rodar um arquivo de teste específico, use o filtro `--filter`:

`docker-compose exec app php artisan test --filter=TransactionServiceTest`

---

## 👁️ Configurando o Sentry (Monitoramento de Erros)

O Sentry captura e reporta todos os erros da API em tempo real.

### 1. Crie uma Conta no Sentry

-   Acesse [sentry.io](https://sentry.io/) e crie uma conta gratuita.

### 2. Crie um Novo Projeto

-   Após o login, clique em **"Create Project"**.
-   Selecione a plataforma **"Laravel"**.
-   Dê um nome ao seu projeto (ex: `carteira-financeira-api`) e clique em **"Create Project"**.

### 3. Obtenha a Chave DSN

-   O Sentry irá te mostrar uma página de configuração com a sua **chave DSN (Data Source Name)**. Ela se parece com isto:
    `https://xxxxxxxxxxxxxxxxxxxxxxxx@oXXXXXX.ingest.sentry.io/XXXXXX`

### 4. Adicione a Chave ao Laravel

-   Abra o arquivo `.env` do seu projeto Laravel.
-   Encontre a linha `SENTRY_LARAVEL_DSN` e cole a sua chave:

`# ./.env (arquivo do Laravel)`
`SENTRY_LARAVEL_DSN=https://xxxxxxxxxxxxxxxxxxxxxxxx@oXXXXXX.ingest.sentry.io/XXXXXX`

-   Limpe o cache de configuração para que o Laravel leia a nova chave:

`docker-compose exec app php artisan config:clear`

Agora, todos os erros da sua API serão automaticamente reportados ao seu painel do Sentry.

