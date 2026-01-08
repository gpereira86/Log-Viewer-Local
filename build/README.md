# 📦 Build e Distribuição - LogViewer

Esta pasta contém todos os arquivos relacionados à construção e distribuição da aplicação LogViewer.

## 📁 Estrutura

```
build/
├── scripts/          # Scripts de build e empacotamento
│   ├── docker-build.sh      # Build simples da imagem (Linux/Mac)
│   ├── docker-build.bat      # Build simples da imagem (Windows)
│   ├── package-build.sh      # Cria pacote completo (Linux/Mac)
│   └── package-build.bat     # Cria pacote completo (Windows)
│
├── docker/           # Arquivos Docker de produção
│   ├── Dockerfile.prod       # Dockerfile para produção
│   └── docker-compose.prod.yml  # Docker Compose para produção
│
├── docs/             # Documentação
│   ├── README-DOCKER.md      # Guia de uso do Docker
│   └── README-PACOTE.md      # Guia de criação de pacotes
│
└── dist/             # Pacotes gerados (criado automaticamente)
    ├── logviewer-package.tar.gz
    └── INSTRUCOES.txt
```

## 🚀 Uso Rápido

### Construir apenas a imagem Docker

**Windows:**
```bash
build\scripts\docker-build.bat
```

**Linux/Mac:**
```bash
chmod +x build/scripts/docker-build.sh
./build/scripts/docker-build.sh
```

### Criar pacote completo para distribuição

**Windows:**
```bash
build\scripts\package-build.bat
```

**Linux/Mac:**
```bash
chmod +x build/scripts/package-build.sh
./build/scripts/package-build.sh
```

O pacote será gerado em `build/dist/` com:
- `logviewer-package.tar.gz` - Imagem Docker comprimida
- `INSTRUCOES.txt` - Instruções de instalação

## 📚 Documentação

- [Guia Docker](docs/README-DOCKER.md) - Como usar Docker com a aplicação
- [Guia de Pacotes](docs/README-PACOTE.md) - Como criar e distribuir pacotes

## 🔒 Segurança

Os pacotes gerados **NÃO contêm código-fonte**. Apenas a aplicação compilada/empacotada está incluída.

