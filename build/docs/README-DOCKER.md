# Docker - LogViewer

Este documento explica como criar e usar a imagem Docker da aplicação LogViewer.

## 📦 Construir a Imagem

### Windows
```bash
docker-build.bat
```

### Linux/Mac
```bash
chmod +x docker-build.sh
./docker-build.sh
```

### Manual
```bash
docker build -f Dockerfile.prod -t logviewer:latest .
```

## 🚀 Executar a Aplicação

### Opção 1: Execução Simples
```bash
docker run -d -p 8080:80 --name logviewer logviewer:latest
```

Acesse: http://localhost:8080

### Opção 2: Com Volume para Dados Persistentes
```bash
docker run -d -p 8080:80 \
  -v logviewer-data:/var/www/html/data \
  --name logviewer \
  logviewer:latest
```

### Opção 3: Com Acesso a Logs Locais (Opcional)
```bash
# Windows
docker run -d -p 8080:80 \
  -v logviewer-data:/var/www/html/data \
  -v C:/xampp/htdocs:/htdocs \
  --name logviewer \
  logviewer:latest

# Linux/Mac
docker run -d -p 8080:80 \
  -v logviewer-data:/var/www/html/data \
  -v /caminho/para/logs:/htdocs \
  --name logviewer \
  logviewer:latest
```

### Opção 4: Usando Docker Compose
```bash
docker-compose -f docker-compose.prod.yml up -d
```

## 💾 Salvar e Carregar Imagem

### Salvar a imagem em um arquivo
```bash
# Linux/Mac
docker save logviewer:latest | gzip > logviewer.tar.gz

# Windows (PowerShell)
docker save logviewer:latest -o logviewer.tar
```

### Carregar a imagem de um arquivo
```bash
# Linux/Mac
docker load < logviewer.tar.gz

# Windows (PowerShell)
docker load -i logviewer.tar
```

## 📋 Estrutura da Imagem

A imagem contém:
- ✅ PHP 8.2 com Apache
- ✅ Extensão SSH2 para conexões SSH/SFTP
- ✅ Todas as dependências necessárias
- ✅ Aplicação completa (sem necessidade de código-fonte)

**Não inclui:**
- ❌ Código-fonte original
- ❌ Arquivos de desenvolvimento
- ❌ Histórico Git

## 🔧 Configuração

### Porta
Por padrão, a aplicação roda na porta 80 do container, mapeada para 8080 do host.

Para alterar a porta:
```bash
docker run -d -p 3000:80 --name logviewer logviewer:latest
```

### Dados Persistentes
Os projetos salvos são armazenados em `/var/www/html/data/projects.json`.

Use um volume nomeado para persistir os dados:
```bash
docker volume create logviewer-data
docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data logviewer:latest
```

## 🛑 Parar e Remover

```bash
# Parar o container
docker stop logviewer

# Remover o container
docker rm logviewer

# Remover a imagem
docker rmi logviewer:latest

# Remover volume (cuidado: apaga os dados!)
docker volume rm logviewer-data
```

## 📝 Notas

- A imagem é autocontida e não precisa do código-fonte para funcionar
- Todos os arquivos necessários estão incluídos na imagem
- Use volumes para persistir dados entre reinicializações
- O diretório `data` precisa de permissões de escrita (já configurado)

