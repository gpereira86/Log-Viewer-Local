#!/bin/bash

# Script para criar pacote completo da aplicação LogViewer
# Gera uma imagem Docker e salva em arquivo para distribuição

IMAGE_NAME="logviewer"
IMAGE_TAG="latest"
PACKAGE_NAME="logviewer-package.tar.gz"
DIST_DIR="build/dist"
DOCKERFILE="build/docker/Dockerfile.prod"

echo "📦 Criando pacote completo da aplicação LogViewer"
echo ""

# Cria diretório de distribuição se não existir
mkdir -p "${DIST_DIR}"

# Passo 1: Construir a imagem
echo "1️⃣  Construindo imagem Docker..."
docker build -f "${DOCKERFILE}" -t ${IMAGE_NAME}:${IMAGE_TAG} .

if [ $? -ne 0 ]; then
    echo "❌ Erro ao construir a imagem"
    exit 1
fi

echo "✅ Imagem construída com sucesso!"
echo ""

# Passo 2: Salvar a imagem em arquivo
echo "2️⃣  Salvando imagem em arquivo..."
docker save ${IMAGE_NAME}:${IMAGE_TAG} | gzip > "${DIST_DIR}/${PACKAGE_NAME}"

if [ $? -ne 0 ]; then
    echo "❌ Erro ao salvar a imagem"
    exit 1
fi

# Obter tamanho do arquivo
FILE_SIZE=$(du -h "${DIST_DIR}/${PACKAGE_NAME}" | cut -f1)

echo "✅ Imagem salva em: ${DIST_DIR}/${PACKAGE_NAME} (${FILE_SIZE})"
echo ""

# Passo 3: Criar arquivo de instruções
echo "3️⃣  Criando arquivo de instruções..."
cat > "${DIST_DIR}/INSTRUCOES.txt" << 'EOF'
═══════════════════════════════════════════════════════════════
  LOGVIEWER - Instruções de Instalação
═══════════════════════════════════════════════════════════════

1. CARREGAR A IMAGEM DOCKER:
   
   Linux/Mac:
   gunzip -c logviewer-package.tar.gz | docker load
   
   Ou:
   docker load < logviewer-package.tar.gz
   
   Windows (PowerShell):
   docker load -i logviewer-package.tar.gz

2. EXECUTAR A APLICAÇÃO:

   Opção A - Execução simples:
   docker run -d -p 8080:80 --name logviewer logviewer:latest

   Opção B - Com dados persistentes (recomendado):
   docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data --name logviewer logviewer:latest

   Opção C - Com acesso a logs locais:
   docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data -v /caminho/para/logs:/htdocs --name logviewer logviewer:latest

3. ACESSAR A APLICAÇÃO:
   
   Abra no navegador: http://localhost:8080

4. PARAR A APLICAÇÃO:
   
   docker stop logviewer
   docker rm logviewer

═══════════════════════════════════════════════════════════════
EOF

echo "✅ Arquivo de instruções criado: ${DIST_DIR}/INSTRUCOES.txt"
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "  ✅ PACOTE CRIADO COM SUCESSO!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📦 Arquivos gerados em: ${DIST_DIR}/"
echo "   - ${PACKAGE_NAME} (imagem Docker comprimida)"
echo "   - INSTRUCOES.txt (instruções de instalação)"
echo ""
echo "📤 Para distribuir, envie ambos os arquivos da pasta ${DIST_DIR}/"
echo "   O código-fonte NÃO está incluído no pacote."
echo ""

