@echo off
REM Script para construir a imagem Docker da aplicacao LogViewer (Windows)

set IMAGE_NAME=logviewer
set IMAGE_TAG=latest
set DOCKERFILE=build\docker\Dockerfile.prod

echo Construindo imagem Docker: %IMAGE_NAME%:%IMAGE_TAG%

docker build -f %DOCKERFILE% -t %IMAGE_NAME%:%IMAGE_TAG% .

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Imagem construida com sucesso!
    echo.
    echo Para executar a imagem:
    echo   docker run -d -p 8080:80 --name logviewer %IMAGE_NAME%:%IMAGE_TAG%
    echo.
    echo Para executar com volume para dados persistentes:
    echo   docker run -d -p 8080:80 -v logviewer-data:/var/www/html/data --name logviewer %IMAGE_NAME%:%IMAGE_TAG%
    echo.
    echo Para executar com acesso a logs locais (opcional):
    echo   docker run -d -p 8080:80 -v C:\caminho\para\logs:/htdocs --name logviewer %IMAGE_NAME%:%IMAGE_TAG%
    echo.
    echo Para salvar a imagem em um arquivo:
    echo   docker save %IMAGE_NAME%:%IMAGE_TAG% ^| gzip ^> logviewer.tar.gz
    echo.
    echo Para carregar a imagem de um arquivo:
    echo   docker load ^< logviewer.tar.gz
) else (
    echo ERRO: Falha ao construir a imagem
    exit /b 1
)

