# 📦 Criar Pacote para Distribuição

Este guia explica como criar um pacote completo da aplicação LogViewer que pode ser distribuído sem o código-fonte.

## 🎯 O que é o Pacote?

O pacote contém:
- ✅ Imagem Docker completa e pronta para uso
- ✅ Todas as dependências instaladas
- ✅ Aplicação funcional
- ❌ **NÃO contém código-fonte** (protegido)

## 📋 Pré-requisitos

- Docker instalado e funcionando
- Acesso ao diretório do projeto

## 🚀 Criar o Pacote

### Windows
```bash
package-build.bat
```

### Linux/Mac
```bash
chmod +x package-build.sh
./package-build.sh
```

## 📤 O que será Gerado

Após executar o script, você terá:

1. **`logviewer-package.tar.gz`** - Imagem Docker comprimida (pronta para distribuição)
2. **`INSTRUCOES.txt`** - Instruções de instalação para o usuário final

## 📨 Distribuir o Pacote

Envie ambos os arquivos para a pessoa:
- `logviewer-package.tar.gz`
- `INSTRUCOES.txt`

**Importante:** O código-fonte não está incluído no pacote. A pessoa só terá acesso à aplicação funcionando, não ao código.

## 👤 Instruções para o Usuário Final

A pessoa que receber o pacote deve:

1. **Carregar a imagem:**
   ```bash
   # Windows (PowerShell)
   docker load -i logviewer-package.tar.gz
   
   # Linux/Mac
   docker load < logviewer-package.tar.gz
   ```

2. **Executar a aplicação:**
   ```bash
   docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data --name logviewer logviewer:latest
   ```

3. **Acessar:**
   Abrir no navegador: http://localhost:8080

## 🔒 Segurança

- ✅ Código-fonte não está incluído no pacote
- ✅ Apenas a aplicação compilada/empacotada
- ✅ Impossível extrair o código-fonte da imagem Docker
- ✅ A pessoa só pode usar a aplicação, não ver o código

## 📊 Tamanho do Pacote

O pacote geralmente tem entre 200-500 MB (dependendo das dependências).

## 💡 Dicas

- Teste o pacote em uma máquina limpa antes de distribuir
- Verifique se o Docker está funcionando no ambiente de destino
- O usuário precisa ter Docker instalado para usar o pacote

