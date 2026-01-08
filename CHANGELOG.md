# Changelog - Melhorias

## [2.0.0] - 2026

### 🔐 Segurança

#### Adicionado
- **Criptografia AES-256-CBC** para todos os dados sensíveis
  - Senhas SSH são criptografadas antes de serem salvas
  - Chaves privadas SSH são criptografadas
  - Senhas e API keys de URLs são criptografadas
- **Proteção de diretórios** com `.htaccess`
  - Diretório `data/` protegido contra acesso direto
  - Diretório `config/` protegido contra acesso direto
- **Gerenciamento seguro de chaves**
  - Chave de criptografia gerada automaticamente
  - Chave armazenada com permissões restritivas (600)
  - Chave excluída do controle de versão

#### Modificado
- Permissões de arquivos ajustadas para 600 (apenas proprietário)
- Permissões de diretórios ajustadas para 700

### 🏗️ Arquitetura

#### Adicionado
- **Sistema de Configuração Centralizado** (`AppConfig`)
  - Gerenciamento centralizado de configurações
  - Suporte a arquivo de configuração customizado
  - Valores padrão seguros
- **Serviços Auxiliares**
  - `ValidationService`: Validação centralizada de dados
  - `ResponseService`: Respostas JSON padronizadas
  - `EncryptionService`: Serviço de criptografia
- **Composer**
  - `composer.json` para gerenciamento de dependências
  - Autoloader PSR-4

#### Modificado
- **ProjectRepository**
  - Agora criptografa/descriptografa automaticamente
  - Usa configuração centralizada
  - Melhor tratamento de erros
- **Controllers**
  - Uso de serviços para validação e resposta
  - Código mais limpo e organizado
  - Tratamento de erros consistente

### 📁 Organização

#### Adicionado
- Estrutura de diretórios profissional:
  - `src/Config/` - Configurações
  - `src/Security/` - Segurança
  - `src/Service/` - Serviços
  - `src/Exception/` - Exceções
- Documentação:
  - `README.md` - Documentação completa
  - `CHANGELOG.md` - Este arquivo

#### Modificado
- `.gitignore` atualizado para proteger arquivos sensíveis
- Estrutura de código mais organizada e modular

### 🔄 Compatibilidade

- **100% compatível** com versões anteriores
- Dados antigos são automaticamente migrados (se necessário)
- API permanece inalterada
- Frontend não requer mudanças

### 📝 Melhorias de Código

- Código mais limpo e manutenível
- Separação de responsabilidades
- Reutilização de código através de serviços
- Validação centralizada
- Tratamento de erros consistente
- Type hints em todos os métodos
- Documentação PHPDoc melhorada

### ⚠️ Breaking Changes

Nenhum! A atualização é totalmente compatível com versões anteriores.

### 🔧 Requisitos

- PHP >= 8.0 (mantido)
- Extensão `openssl` (necessária para criptografia)

**Nota**: A criptografia de dados sensíveis é automática. Novos projetos terão suas senhas e chaves criptografadas automaticamente ao serem salvos.
