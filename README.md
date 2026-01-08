# LogViewer - Sistema de Visualização de Logs

Sistema web standalone para visualização, análise e gerenciamento centralizado de arquivos de log. Permite acessar logs de múltiplas fontes (locais, SSH/SFTP e URLs) através de uma interface única, intuitiva e responsiva.

**Objetivo**: Fornecer uma solução independente que organize e apresente logs de forma estruturada e visual, facilitando a leitura, busca e análise de informações de diferentes sistemas e servidores, sem depender de frameworks ou dependências externas complexas.

## 🚀 Características

- **Múltiplas Fontes de Logs**: Suporte para arquivos locais, SSH/SFTP e URLs
- **Segurança**: Criptografia AES-256-CBC para senhas e dados sensíveis
- **Interface Moderna**: Interface web responsiva e intuitiva
- **Organização**: Estrutura de código limpa e organizada
- **Validação Robusta**: Sistema de validação centralizado

## 📋 Requisitos

- PHP >= 8.0
- Extensões PHP:
  - `openssl` (para criptografia)
  - `json`
  - `ssh2` (opcional, para conexões SSH)

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/gpereira86/Log-Viewer-Local.git
cd LogViewer
```

2. Instale as dependências (se usar Composer):
```bash
composer install
```

3. Configure as permissões:
```bash
chmod 700 config/
chmod 600 data/
chmod 600 config/.encryption_key  # Será criado automaticamente
```

**Nota**: A criptografia de dados sensíveis é automática. Novos projetos terão suas senhas e chaves criptografadas automaticamente ao serem salvos.

## 🔐 Segurança

### Criptografia de Dados Sensíveis

O sistema agora criptografa automaticamente os seguintes campos:
- `ssh_password`
- `ssh_private_key`
- `ssh_private_key_passphrase`
- `url_password`
- `url_api_key`

A criptografia usa **AES-256-CBC** com uma chave única gerada automaticamente na primeira execução.

### Proteção de Arquivos

- O diretório `data/` está protegido contra acesso direto via `.htaccess`
- O diretório `config/` está protegido contra acesso direto
- Arquivos de dados têm permissões restritivas (600)

## 📁 Estrutura do Projeto

```
LogViewer/
├── config/              # Configurações (protegido)
│   ├── .htaccess
│   └── .encryption_key  # Chave de criptografia (gerada automaticamente)
├── data/                # Dados da aplicação (protegido)
│   ├── .htaccess
│   └── projects.json    # Projetos (criptografado)
├── public/              # Ponto de entrada público
│   └── index.php
├── src/                 # Código fonte
│   ├── Config/          # Configurações
│   │   └── AppConfig.php
│   ├── Controller/      # Controladores
│   │   ├── ConfigController.php
│   │   ├── LogController.php
│   │   └── ProjectController.php
│   ├── Model/           # Modelos e repositórios
│   │   ├── LocalLogReader.php
│   │   ├── LogReaderFactory.php
│   │   ├── LogReaderInterface.php
│   │   ├── ProjectRepository.php
│   │   ├── SshLogReader.php
│   │   └── UrlLogReader.php
│   ├── Routing/         # Roteamento
│   │   └── Router.php
│   ├── Security/        # Segurança
│   │   └── EncryptionService.php
│   ├── Service/         # Serviços
│   │   ├── ResponseService.php
│   │   └── ValidationService.php
│   └── bootstrap.php    # Autoloader
├── views/               # Views
│   ├── config.php
│   └── logviewer.php
├── .gitignore
├── composer.json
└── README.md
```

## 🏗️ Arquitetura

### Camadas

1. **Controller**: Recebe requisições e coordena a lógica
2. **Service**: Serviços auxiliares (validação, resposta, etc.)
3. **Model**: Lógica de negócio e acesso a dados
4. **Security**: Serviços de segurança e criptografia
5. **Config**: Configuração centralizada

### Princípios Aplicados

- **Separação de Responsabilidades**: Cada classe tem uma responsabilidade única
- **DRY (Don't Repeat Yourself)**: Código reutilizável através de serviços
- **Segurança por Padrão**: Dados sensíveis são sempre criptografados
- **Validação Centralizada**: Validação de dados em um único lugar
- **Tratamento de Erros**: Respostas consistentes e informativas

## 📝 Uso

### Adicionar um Projeto Local

1. Acesse `/config`
2. Clique em "Adicionar Projeto"
3. Selecione "Local"
4. Informe o nome e caminho do diretório de logs
5. Use o botão de navegação (📁) para selecionar o diretório visualmente

**⚠️ Nota para Docker**: Se estiver usando Docker, você **DEVE** mapear os volumes no `docker-compose.yml` antes de poder navegar e encontrar logs locais. Sem o mapeamento, o navegador de diretórios não terá acesso aos diretórios do seu sistema. Veja a seção [Docker - Configuração de Volumes](#configuração-de-volumes-para-logs-locais) abaixo.

### Adicionar um Projeto SSH

1. Acesse `/config`
2. Clique em "Adicionar Projeto"
3. Selecione "SSH"
4. Preencha:
   - Host
   - Porta (padrão: 22)
   - Usuário
   - Senha ou Chave Privada
   - Caminho remoto

### Adicionar um Projeto URL

1. Acesse `/config`
2. Clique em "Adicionar Projeto"
3. Selecione "URL"
4. Informe a URL e credenciais (se necessário)

## 🛡️ Boas Práticas de Segurança

1. **Nunca commite** o arquivo `config/.encryption_key` no Git
2. **Mantenha permissões restritivas** nos diretórios `config/` e `data/`
3. **Faça backup regular** do arquivo de chave de criptografia
4. **Use HTTPS** em produção
5. **Mantenha o PHP atualizado** para correções de segurança

## 🐳 Docker

### Instalação com Docker

1. **Construa a imagem**:
```bash
docker-compose build
```

2. **Inicie o container**:
```bash
docker-compose up -d
```

3. **Acesse a aplicação**: `http://localhost:8080`

### Configuração de Volumes para Logs Locais

**⚠️ IMPORTANTE**: Para acessar logs locais dentro do container Docker, você **DEVE** mapear os diretórios do host para dentro do container no arquivo `docker-compose.yml`. Sem esse mapeamento, o navegador de diretórios não conseguirá acessar os logs do seu sistema.

Existem duas formas de configurar:

#### Opção 1: Via docker-compose.yml (Recomendado)

**Edite o arquivo `docker-compose.yml` e adicione volumes na seção `volumes`** para cada diretório onde você quer navegar e encontrar logs:

```yaml
services:
  log-viewer:
    volumes:
      - .:/var/www/html
      # OBRIGATÓRIO: Mapeie os diretórios onde estão seus logs
      # Windows - Mapeia htdocs do XAMPP
      - C:/xampp/htdocs:/htdocs
      # Linux/Mac - Exemplos (descomente e ajuste conforme necessário)
      # - /var/log:/var/log
      # - /home/usuario/projetos:/projetos
      # - /opt/aplicacoes:/opt/aplicacoes
```

**⚠️ Sem mapear os volumes, o navegador de diretórios não conseguirá acessar os logs!**

**Ao adicionar um projeto local**, use o caminho **dentro do container**:
- Se mapeou `C:/xampp/htdocs:/htdocs`, use `/htdocs/caminho/para/logs`
- Se mapeou `/var/log:/var/log`, use `/var/log/caminho/para/logs`

**Dica**: Use o botão de navegação na interface para ver quais diretórios estão acessíveis. Apenas diretórios mapeados no `docker-compose.yml` aparecerão.

#### Opção 2: Via Variável de Ambiente (Automático)

Configure a variável `LOGVIEWER_VOLUMES` no `docker-compose.yml`:

```yaml
services:
  log-viewer:
    environment:
      LOGVIEWER_VOLUMES: "C:/xampp/htdocs:/htdocs,/var/log:/var/log"
```

Com essa configuração, você pode usar o caminho do **host** ao adicionar projetos, e o sistema mapeará automaticamente para o caminho do container.

**Exemplo**:
- Volume mapeado: `C:/xampp/htdocs:/htdocs`
- Ao adicionar projeto, use: `C:/xampp/htdocs/projeto/logs` ou `/htdocs/projeto/logs` (ambos funcionam)

### Exemplos de Uso

**Windows com XAMPP**:
```yaml
volumes:
  - C:/xampp/htdocs:/htdocs
```
No projeto, use: `/htdocs/nome-projeto/storage/logs`

**Linux**:
```yaml
volumes:
  - /var/log:/var/log
  - /home/usuario/projetos:/projetos
```
No projeto, use: `/var/log` ou `/projetos/nome-projeto/logs`

Para mais detalhes, consulte `INSTRUCOES.txt` ou `build/docs/README-DOCKER.md`.

## 📄 Licença

MIT

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, abra uma issue ou pull request.
