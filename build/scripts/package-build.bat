@echo off
REM Script para criar pacote completo da aplicacao LogViewer (Windows)
REM Gera uma imagem Docker e salva em arquivo para distribuicao

set IMAGE_NAME=logviewer
set IMAGE_TAG=latest
set PACKAGE_NAME=logviewer-package.tar.gz
set DIST_DIR=build\dist
set DOCKERFILE=build\docker\Dockerfile.prod

echo Criando pacote completo da aplicacao LogViewer
echo.

REM Cria diretorio de distribuicao se nao existir
if not exist "%DIST_DIR%" mkdir "%DIST_DIR%"

REM Passo 1: Construir a imagem
echo 1. Construindo imagem Docker...
docker build -f %DOCKERFILE% -t %IMAGE_NAME%:%IMAGE_TAG% .

if %ERRORLEVEL% NEQ 0 (
    echo ERRO: Falha ao construir a imagem
    exit /b 1
)

echo Imagem construida com sucesso!
echo.

REM Passo 2: Salvar a imagem em arquivo
echo 2. Salvando imagem em arquivo...
docker save %IMAGE_NAME%:%IMAGE_TAG% | gzip > %DIST_DIR%\%PACKAGE_NAME%

if %ERRORLEVEL% NEQ 0 (
    echo ERRO: Falha ao salvar a imagem
    exit /b 1
)

echo Imagem salva em: %DIST_DIR%\%PACKAGE_NAME%
echo.

REM Passo 3: Criar arquivo de instruções
echo 3. Criando arquivo de instrucoes...
echo ================================================================ > %DIST_DIR%\INSTRUCOES.txt
echo   LOGVIEWER - Instrucoes de Instalacao >> %DIST_DIR%\INSTRUCOES.txt
echo ================================================================ >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo 1. CARREGAR A IMAGEM DOCKER: >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Windows (PowerShell): >> %DIST_DIR%\INSTRUCOES.txt
echo    docker load -i logviewer-package.tar.gz >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Linux/Mac: >> %DIST_DIR%\INSTRUCOES.txt
echo    gunzip -c logviewer-package.tar.gz ^| docker load >> %DIST_DIR%\INSTRUCOES.txt
echo    Ou: docker load ^< logviewer-package.tar.gz >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo 2. EXECUTAR A APLICACAO: >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Opcao A - Execucao simples: >> %DIST_DIR%\INSTRUCOES.txt
echo    docker run -d -p 8080:80 --name logviewer logviewer:latest >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Opcao B - Com dados persistentes (recomendado): >> %DIST_DIR%\INSTRUCOES.txt
echo    docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data --name logviewer logviewer:latest >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Opcao C - Com acesso a logs locais: >> %DIST_DIR%\INSTRUCOES.txt
echo    docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data -v C:\caminho\para\logs:/htdocs --name logviewer logviewer:latest >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Opcao D - Com mapeamento automático via variável de ambiente: >> %DIST_DIR%\INSTRUCOES.txt
echo    docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data \ >> %DIST_DIR%\INSTRUCOES.txt
echo      -v C:\xampp\htdocs:/htdocs \ >> %DIST_DIR%\INSTRUCOES.txt
echo      -e LOGVIEWER_VOLUMES="C:/xampp/htdocs:/htdocs" \ >> %DIST_DIR%\INSTRUCOES.txt
echo      --name logviewer logviewer:latest >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    NOTA: Com a Opção D, você pode usar tanto o caminho do host (C:/xampp/htdocs/...) >> %DIST_DIR%\INSTRUCOES.txt
echo    quanto o caminho do container (/htdocs/...) ao adicionar projetos locais. >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo ================================================================ >> %DIST_DIR%\INSTRUCOES.txt
echo   IMPORTANTE - MAPEAMENTO DE VOLUMES PARA LOGS LOCAIS >> %DIST_DIR%\INSTRUCOES.txt
echo ================================================================ >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo ⚠️ ATENÇÃO: Para poder navegar e encontrar logs locais usando o navegador >> %DIST_DIR%\INSTRUCOES.txt
echo    de diretórios na interface web, você DEVE mapear os diretórios no >> %DIST_DIR%\INSTRUCOES.txt
echo    docker-compose.yml ou via comando docker run. >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Sem mapear os volumes, o container não terá acesso aos diretórios do >> %DIST_DIR%\INSTRUCOES.txt
echo    seu sistema e o navegador não conseguirá listar os logs. >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Exemplo no docker-compose.yml: >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    volumes: >> %DIST_DIR%\INSTRUCOES.txt
echo      - .:/var/www/html >> %DIST_DIR%\INSTRUCOES.txt
echo      # Mapeie cada diretório onde você quer navegar: >> %DIST_DIR%\INSTRUCOES.txt
echo      - C:/xampp/htdocs:/htdocs >> %DIST_DIR%\INSTRUCOES.txt
echo      - C:/outro/caminho:/outro >> %DIST_DIR%\INSTRUCOES.txt
echo      # Linux/Mac: >> %DIST_DIR%\INSTRUCOES.txt
echo      # - /var/log:/var/log >> %DIST_DIR%\INSTRUCOES.txt
echo      # - /home/usuario/projetos:/projetos >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Depois de mapear, reinicie o container: >> %DIST_DIR%\INSTRUCOES.txt
echo    docker-compose down >> %DIST_DIR%\INSTRUCOES.txt
echo    docker-compose up -d >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo ================================================================ >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo 3. ACESSAR A APLICACAO: >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    Abra no navegador: http://localhost:8080 >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo 4. PARAR A APLICACAO: >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo    docker stop logviewer >> %DIST_DIR%\INSTRUCOES.txt
echo    docker rm logviewer >> %DIST_DIR%\INSTRUCOES.txt
echo. >> %DIST_DIR%\INSTRUCOES.txt
echo ================================================================ >> %DIST_DIR%\INSTRUCOES.txt

echo Arquivo de instrucoes criado: %DIST_DIR%\INSTRUCOES.txt
echo.

echo ================================================================
echo   PACOTE CRIADO COM SUCESSO!
echo ================================================================
echo.
echo Arquivos gerados em: %DIST_DIR%\
echo    - %PACKAGE_NAME% (imagem Docker comprimida)
echo    - INSTRUCOES.txt (instrucoes de instalacao)
echo.
echo Para distribuir, envie ambos os arquivos da pasta %DIST_DIR%\
echo O codigo-fonte NAO esta incluido no pacote.
echo.

