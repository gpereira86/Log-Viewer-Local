<?php
/** @var array<int, array<string, string>> $projects */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Log Viewer - Configuração</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e5e5e5;
        }

        .navbar-dark {
            background-color: #1f1f1f !important;
        }

        .card {
            background-color: #1a1a1a;
            border-color: #2a2a2a;
            color: #e5e5e5;
        }

        .form-label {
            color: #e5e5e5 !important;
        }

        .form-control {
            background-color: #1f1f1f;
            border-color: #2a2a2a;
            color: #e5e5e5;
        }

        .form-control:focus {
            background-color: #1f1f1f;
            border-color: #0d6efd;
            color: #e5e5e5;
        }

        .form-text {
            color: #adb5bd !important;
        }

        .list-group-item {
            background-color: #1a1a1a;
            border-color: #2a2a2a;
            color: #e5e5e5;
        }

        .list-group-item strong {
            color: #e5e5e5;
        }

        .text-muted {
            color: #adb5bd !important;
        }

        .type-fields {
            border-left: 3px solid #0d6efd;
            padding-left: 1rem;
            margin-left: 0.5rem;
        }

        .form-check-input {
            background-color: #1f1f1f;
            border-color: #2a2a2a;
        }

        .form-check-label {
            color: #e5e5e5 !important;
        }

        .ssh-browser-item {
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            margin: 2px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ssh-browser-item .flex-grow-1 {
            flex-grow: 1;
        }

        .ssh-browser-item:hover {
            background-color: #2a2a2a;
        }

        .ssh-browser-item.directory {
            color: #0dcaf0;
        }

        .ssh-browser-item.file {
            color: #e5e5e5;
        }

        .ssh-browser-item.selected {
            background-color: #0d6efd;
            color: #fff;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/logs">Log Viewer</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/config">Configuração</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logs">Logs</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mb-4">
    <div class="row">
        <div class="col-md-6 col-lg-5">
            <h5 style="color: #e5e5e5 !important;">Configuração de Projetos</h5>
            <form id="project-form" class="card card-body mb-3 shadow-sm">
                <input type="hidden" name="id" id="project-id">
                <div class="mb-3">
                    <label class="form-label" for="project-name">Nome do Projeto</label>
                    <input class="form-control" type="text" id="project-name" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="project-type">Tipo de Fonte</label>
                    <select class="form-select" id="project-type" name="type" required>
                        <option value="local">Local (arquivos no servidor)</option>
                        <option value="ssh">SSH (servidor remoto)</option>
                        <option value="url">URL (API/endpoint HTTP)</option>
                    </select>
                </div>
                
                <!-- Campos para Local -->
                <div id="fields-local" class="type-fields">
                    <div class="mb-3">
                        <label class="form-label" for="project-path">Pasta dos Logs</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="project-path" name="path" placeholder="/htdocs/... ou /var/log/...">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-browse-local" title="Navegar diretórios">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.5 0a.5.5 0 0 1 .5.5v15a.5.5 0 0 1-.5.5h15a.5.5 0 0 1-.5-.5V.5a.5.5 0 0 1 .5-.5h-15zm1 1v13h13V1H1.5z"/>
                                    <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-9z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="form-text">
                            Caminho absoluto onde estão os arquivos de log. Use o botão ao lado para navegar pelos diretórios. Ex: <code>/htdocs/TTY/PRODEB/...</code> ou <code>/var/log/...</code>
                        </div>
                    </div>
                    <div id="local-browser" class="mb-3" style="display: none;">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <div class="flex-grow-1">
                                    <strong>Navegador de Diretórios</strong>
                                    <small class="text-muted d-block" id="local-browser-path">/</small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-sm btn-success" id="local-browser-select" title="Selecionar este diretório">
                                        Selecionar
                                    </button>
                                    <button type="button" class="btn-close btn-close-white btn-sm" id="local-browser-close"></button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                                <div id="local-browser-content" class="small">
                                    <div class="text-center text-muted py-3">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Carregando...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campos para SSH -->
                <div id="fields-ssh" class="type-fields" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label" for="ssh-host">Host SSH</label>
                        <input class="form-control" type="text" id="ssh-host" name="ssh_host" placeholder="exemplo.com ou 192.168.1.1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-port">Porta SSH</label>
                        <input class="form-control" type="number" id="ssh-port" name="ssh_port" value="22" min="1" max="65535">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-user">Usuário SSH</label>
                        <input class="form-control" type="text" id="ssh-user" name="ssh_user" placeholder="usuario">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-password">Senha (opcional, se usar chave privada)</label>
                        <input class="form-control" type="password" id="ssh-password" name="ssh_password" placeholder="Deixe vazio se usar chave privada">
                        <div class="form-text">
                            Use senha OU chave privada. Se ambos forem fornecidos, a chave privada terá prioridade.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-private-key">Chave Privada SSH (opcional)</label>
                        <textarea class="form-control" id="ssh-private-key" name="ssh_private_key" rows="4" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----"></textarea>
                        <div class="form-text">
                            Cole aqui o conteúdo completo da chave privada SSH.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-private-key-passphrase">Senha da Chave Privada (opcional)</label>
                        <input class="form-control" type="password" id="ssh-private-key-passphrase" name="ssh_private_key_passphrase" placeholder="Se a chave privada tiver senha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssh-path">Caminho Remoto dos Logs</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="ssh-path" name="path" placeholder="/var/www/...">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-browse-ssh" title="Navegar diretórios">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.5 0a.5.5 0 0 1 .5.5v15a.5.5 0 0 1-.5.5h15a.5.5 0 0 1-.5-.5V.5a.5.5 0 0 1 .5-.5h-15zm1 1v13h13V1H1.5z"/>
                                    <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-9z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="form-text">
                            Caminho absoluto no servidor remoto onde estão os arquivos de log. Use o botão ao lado para navegar pelos diretórios. Clique em diretórios para navegar, ou Ctrl+Clique/Duplo-clique para selecionar.
                        </div>
                    </div>
                    <div id="ssh-browser" class="mb-3" style="display: none;">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <div class="flex-grow-1">
                                    <strong>Navegador SSH</strong>
                                    <small class="text-muted d-block" id="ssh-browser-path">/var/www</small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-sm btn-success" id="ssh-browser-select" title="Selecionar este diretório">
                                        Selecionar
                                    </button>
                                    <button type="button" class="btn-close btn-close-white btn-sm" id="ssh-browser-close"></button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                                <div id="ssh-browser-content" class="small">
                                    <div class="text-center text-muted py-3">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Carregando...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-info btn-sm" id="btn-test-ssh">
                            <span id="btn-test-ssh-text">Testar Conexão SSH</span>
                            <span id="btn-test-ssh-spinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status"></span>
                        </button>
                        <div id="ssh-test-result" class="mt-2 small" style="display: none;"></div>
                    </div>
                </div>

                <!-- Campos para URL -->
                <div id="fields-url" class="type-fields" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label" for="url-base">URL Base</label>
                        <input class="form-control" type="url" id="url-base" name="url" placeholder="https://api.exemplo.com">
                        <div class="form-text">
                            URL base do servidor que fornece os logs. Deve ter endpoints: <code>/api/log-files</code>, <code>/api/log-content</code>, <code>/api/log-entries</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Autenticação</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="url_auth_type" id="url-auth-none" value="none" checked>
                            <label class="form-check-label" for="url-auth-none">Nenhuma</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="url_auth_type" id="url-auth-basic" value="basic">
                            <label class="form-check-label" for="url-auth-basic">HTTP Basic Auth</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="url_auth_type" id="url-auth-api" value="api">
                            <label class="form-check-label" for="url-auth-api">API Key (Header)</label>
                        </div>
                    </div>
                    <div id="url-basic-auth" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label" for="url-username">Usuário</label>
                            <input class="form-control" type="text" id="url-username" name="url_username" placeholder="usuario">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="url-password">Senha</label>
                            <input class="form-control" type="password" id="url-password" name="url_password" placeholder="senha">
                        </div>
                    </div>
                    <div id="url-api-auth" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label" for="url-api-key">API Key</label>
                            <input class="form-control" type="text" id="url-api-key" name="url_api_key" placeholder="chave-api">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="url-api-key-header">Nome do Header</label>
                            <input class="form-control" type="text" id="url-api-key-header" name="url_api_key_header" value="X-API-Key" placeholder="X-API-Key">
                            <div class="form-text">
                                Nome do header HTTP onde a API Key será enviada.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="submit">Salvar Projeto</button>
                    <button class="btn btn-secondary btn-sm" type="button" id="project-clear">Limpar</button>
                </div>
            </form>
        </div>
        <div class="col-md-6 col-lg-7">
            <h5 style="color: #e5e5e5 !important;">Projetos Cadastrados</h5>
            <ul id="project-list" class="list-group shadow-sm small"></ul>
        </div>
    </div>
</div>

<script>
    async function loadProjects() {
        const res = await fetch('/api/projects');
        const data = await res.json();
        const list = document.getElementById('project-list');
        list.innerHTML = '';
        data.forEach(p => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            const type = p.type || 'local';
            const typeLabel = type === 'ssh' ? 'SSH' : type === 'url' ? 'URL' : 'Local';
            const pathInfo = type === 'url' ? (p.url || '') : (p.path || '');
            li.innerHTML = `
                <span>
                    <strong>${p.name}</strong> <span class="badge bg-secondary">${typeLabel}</span><br>
                    <small class="text-muted">${pathInfo}</small>
                </span>
                <span>
                    <button class="btn btn-sm btn-outline-secondary me-1" data-action="edit" data-id="${p.id}">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${p.id}">Excluir</button>
                </span>
            `;
            list.appendChild(li);
        });
    }

    document.getElementById('project-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('/api/projects', {
            method: 'POST',
            body: formData
        });
        if (!res.ok) {
            let msg = 'Erro ao salvar projeto';
            try {
                const err = await res.json();
                msg = err.error || msg;
            } catch (_) {}
            alert(msg);
            return;
        }
        e.target.reset();
        document.getElementById('project-id').value = '';
        await loadProjects();
    });

    function toggleTypeFields() {
        const type = document.getElementById('project-type').value;
        document.querySelectorAll('.type-fields').forEach(el => el.style.display = 'none');
        
        if (type === 'local') {
            document.getElementById('fields-local').style.display = 'block';
        } else if (type === 'ssh') {
            document.getElementById('fields-ssh').style.display = 'block';
        } else if (type === 'url') {
            document.getElementById('fields-url').style.display = 'block';
        }
    }

    function toggleUrlAuth() {
        const authType = document.querySelector('input[name="url_auth_type"]:checked')?.value || 'none';
        document.getElementById('url-basic-auth').style.display = authType === 'basic' ? 'block' : 'none';
        document.getElementById('url-api-auth').style.display = authType === 'api' ? 'block' : 'none';
    }

    document.getElementById('project-type').addEventListener('change', toggleTypeFields);
    document.querySelectorAll('input[name="url_auth_type"]').forEach(radio => {
        radio.addEventListener('change', toggleUrlAuth);
    });

    document.getElementById('project-clear').addEventListener('click', () => {
        document.getElementById('project-form').reset();
        document.getElementById('project-id').value = '';
        toggleTypeFields();
        toggleUrlAuth();
    });

    document.getElementById('project-list').addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');

        if (action === 'delete') {
            if (!confirm('Tem certeza que deseja excluir este projeto?')) return;
            const form = new FormData();
            form.append('id', id);
            const res = await fetch('/api/projects/delete', {
                method: 'POST',
                body: form
            });
            if (!res.ok) {
                let msg = 'Erro ao excluir';
                try {
                    const err = await res.json();
                    msg = err.error || msg;
                } catch (_) {}
                alert(msg);
                return;
            }
            await loadProjects();
        }

        if (action === 'edit') {
            const res = await fetch('/api/projects');
            const data = await res.json();
            const proj = data.find(p => p.id === id);
            if (!proj) return;
            
            document.getElementById('project-id').value = proj.id;
            document.getElementById('project-name').value = proj.name || '';
            document.getElementById('project-type').value = proj.type || 'local';
            toggleTypeFields();
            
            if (proj.type === 'local') {
                document.getElementById('project-path').value = proj.path || '';
            } else if (proj.type === 'ssh') {
                document.getElementById('ssh-host').value = proj.ssh_host || '';
                document.getElementById('ssh-port').value = proj.ssh_port || '22';
                document.getElementById('ssh-user').value = proj.ssh_user || '';
                document.getElementById('ssh-password').value = proj.ssh_password || '';
                document.getElementById('ssh-private-key').value = proj.ssh_private_key || '';
                document.getElementById('ssh-private-key-passphrase').value = proj.ssh_private_key_passphrase || '';
                document.getElementById('ssh-path').value = proj.path || '';
            } else if (proj.type === 'url') {
                document.getElementById('url-base').value = proj.url || '';
                document.getElementById('url-username').value = proj.url_username || '';
                document.getElementById('url-password').value = proj.url_password || '';
                document.getElementById('url-api-key').value = proj.url_api_key || '';
                document.getElementById('url-api-key-header').value = proj.url_api_key_header || 'X-API-Key';
                
                // Determina tipo de autenticação
                if (proj.url_api_key) {
                    document.getElementById('url-auth-api').checked = true;
                } else if (proj.url_username) {
                    document.getElementById('url-auth-basic').checked = true;
                } else {
                    document.getElementById('url-auth-none').checked = true;
                }
                toggleUrlAuth();
            }
        }
    });

    // Navegador de diretórios SSH
    let currentBrowsePath = '/var/www';

    async function browseSshDirectories(path = null) {
        const host = document.getElementById('ssh-host').value.trim();
        const port = document.getElementById('ssh-port').value.trim() || '22';
        const user = document.getElementById('ssh-user').value.trim();
        const password = document.getElementById('ssh-password').value;
        const privateKey = document.getElementById('ssh-private-key').value.trim();
        const privateKeyPassphrase = document.getElementById('ssh-private-key-passphrase').value;

        if (!host || !user) {
            alert('Preencha pelo menos o Host e o Usuário antes de navegar.');
            return;
        }

        const browser = document.getElementById('ssh-browser');
        const browserContent = document.getElementById('ssh-browser-content');
        const browserPath = document.getElementById('ssh-browser-path');

        browser.style.display = 'block';
        browserContent.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Carregando...</div>';

        if (path !== null) {
            currentBrowsePath = path;
        }

        try {
            const formData = new FormData();
            formData.append('ssh_host', host);
            formData.append('ssh_port', port);
            formData.append('ssh_user', user);
            if (password) formData.append('ssh_password', password);
            if (privateKey) formData.append('ssh_private_key', privateKey);
            if (privateKeyPassphrase) formData.append('ssh_private_key_passphrase', privateKeyPassphrase);
            formData.append('path', currentBrowsePath);

            const res = await fetch('/api/projects/browse-ssh', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!data.success) {
                const errorMsg = data.message || data.error || 'Erro ao listar diretórios';
                browserContent.innerHTML = `<div class="text-danger small p-2">
                    <strong>Erro:</strong> ${errorMsg}
                    <br><small class="text-muted">Verifique as credenciais SSH e se o diretório existe.</small>
                </div>`;
                return;
            }

            browserPath.textContent = data.current_path || currentBrowsePath;
            currentBrowsePath = data.current_path || currentBrowsePath;

            if (data.items.length === 0) {
                browserContent.innerHTML = `<div class="text-muted small text-center py-3">
                    <div>Diretório vazio</div>
                    <small class="text-muted">Caminho: ${data.current_path || currentBrowsePath}</small>
                </div>`;
                return;
            }

            let html = '';
            
            // Botão para voltar (se não estiver na raiz)
            if (currentBrowsePath !== '/' && currentBrowsePath !== '') {
                const parentPath = currentBrowsePath.split('/').slice(0, -1).join('/') || '/';
                html += `<div class="ssh-browser-item directory" data-path="${parentPath}" data-type="directory">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                    </svg>
                    <span>.. (voltar)</span>
                </div>`;
            }

            data.items.forEach(item => {
                const icon = item.type === 'directory' 
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 3l.04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9.81a2 2 0 0 0 1.991-1.819l.637-7a1.99 1.99 0 0 0-.342-1.311L12.5 3H.5zm1.217-.456A1.5 1.5 0 0 1 3.5 2.5h9a1.5 1.5 0 0 1 1.283.757L14.5 3H1.5l.217-.456z"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1v13H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h8z"/></svg>';
                
                const selectBtn = item.type === 'directory' 
                    ? '<button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); selectSshDirectory(\'' + item.path + '\');" title="Selecionar este diretório">Selecionar</button>'
                    : '';
                
                html += `<div class="ssh-browser-item ${item.type}" data-path="${item.path}" data-type="${item.type}">
                    ${icon}
                    <span class="flex-grow-1">${item.name}</span>
                    ${selectBtn}
                </div>`;
            });

            browserContent.innerHTML = html;

            // Adiciona event listeners
            browserContent.querySelectorAll('.ssh-browser-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    // Ignora cliques em botões
                    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                        return;
                    }
                    
                    const itemPath = item.dataset.path;
                    const itemType = item.dataset.type;

                    if (itemType === 'directory') {
                        // Remove seleção anterior
                        document.querySelectorAll('.ssh-browser-item').forEach(i => i.classList.remove('selected'));
                        // Destaca o item clicado
                        item.classList.add('selected');
                        // Atualiza o caminho atual para o botão "Selecionar" do header
                        currentBrowsePath = itemPath;
                        // Clique simples navega para dentro
                        browseSshDirectories(itemPath);
                    } else {
                        // Seleciona o arquivo (mas para logs, geralmente queremos o diretório pai)
                        // Se for arquivo .log, seleciona o diretório pai
                        if (itemPath.endsWith('.log')) {
                            const dirPath = itemPath.substring(0, itemPath.lastIndexOf('/'));
                            document.getElementById('ssh-path').value = dirPath;
                        } else {
                            document.getElementById('ssh-path').value = itemPath;
                        }
                        document.querySelectorAll('.ssh-browser-item').forEach(i => i.classList.remove('selected'));
                        item.classList.add('selected');
                    }
                });
            });
        } catch (error) {
            browserContent.innerHTML = `<div class="text-danger small">Erro: ${error.message}</div>`;
        }
    }

    // Função para selecionar um diretório SSH
    function selectSshDirectory(path) {
        document.getElementById('ssh-path').value = path;
        document.getElementById('ssh-browser').style.display = 'none';
        // Remove todas as seleções visuais
        document.querySelectorAll('.ssh-browser-item').forEach(i => i.classList.remove('selected'));
    }

    document.getElementById('btn-browse-ssh').addEventListener('click', () => {
        browseSshDirectories();
    });

    document.getElementById('ssh-browser-close').addEventListener('click', () => {
        document.getElementById('ssh-browser').style.display = 'none';
    });

    // Botão "Selecionar" no header do navegador (usa event delegation pois o elemento pode não existir ainda)
    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'ssh-browser-select') {
            selectSshDirectory(currentBrowsePath);
        }
    });

    // Teste de conexão SSH
    document.getElementById('btn-test-ssh').addEventListener('click', async () => {
        const host = document.getElementById('ssh-host').value.trim();
        const port = document.getElementById('ssh-port').value.trim() || '22';
        const user = document.getElementById('ssh-user').value.trim();
        const password = document.getElementById('ssh-password').value;
        const privateKey = document.getElementById('ssh-private-key').value.trim();
        const privateKeyPassphrase = document.getElementById('ssh-private-key-passphrase').value;
        const path = document.getElementById('ssh-path').value.trim();

        if (!host || !user) {
            alert('Preencha pelo menos o Host e o Usuário antes de testar.');
            return;
        }

        const btn = document.getElementById('btn-test-ssh');
        const btnText = document.getElementById('btn-test-ssh-text');
        const spinner = document.getElementById('btn-test-ssh-spinner');
        const resultDiv = document.getElementById('ssh-test-result');

        // Desabilita botão e mostra spinner
        btn.disabled = true;
        btnText.textContent = 'Testando...';
        spinner.style.display = 'inline-block';
        resultDiv.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('ssh_host', host);
            formData.append('ssh_port', port);
            formData.append('ssh_user', user);
            if (password) formData.append('ssh_password', password);
            if (privateKey) formData.append('ssh_private_key', privateKey);
            if (privateKeyPassphrase) formData.append('ssh_private_key_passphrase', privateKeyPassphrase);
            if (path) formData.append('path', path);

            const res = await fetch('/api/projects/test-ssh', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();
            
            // Mostra resultado
            resultDiv.style.display = 'block';
            if (data.success) {
                resultDiv.className = 'mt-2 small text-success';
                resultDiv.innerHTML = `<strong>✓ Sucesso:</strong> ${data.message}`;
            } else {
                resultDiv.className = 'mt-2 small text-danger';
                resultDiv.innerHTML = `<strong>✗ Erro:</strong> ${data.message || data.error || 'Erro desconhecido'}`;
            }
        } catch (error) {
            resultDiv.style.display = 'block';
            resultDiv.className = 'mt-2 small text-danger';
            resultDiv.innerHTML = `<strong>✗ Erro:</strong> Não foi possível testar a conexão: ${error.message}`;
        } finally {
            // Reabilita botão e esconde spinner
            btn.disabled = false;
            btnText.textContent = 'Testar Conexão SSH';
            spinner.style.display = 'none';
        }
    });

    // Navegador de diretórios Local
    let currentLocalBrowsePath = '/';

    async function browseLocalDirectories(path = null) {
        const browser = document.getElementById('local-browser');
        const browserContent = document.getElementById('local-browser-content');
        const browserPath = document.getElementById('local-browser-path');

        browser.style.display = 'block';
        browserContent.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Carregando...</div>';

        if (path !== null) {
            currentLocalBrowsePath = path;
        }

        try {
            const formData = new FormData();
            formData.append('path', currentLocalBrowsePath);

            const res = await fetch('/api/projects/browse-local', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!data.success) {
                const errorMsg = data.message || data.error || 'Erro ao listar diretórios';
                let html = `<div class="text-warning small p-2 mb-2">
                    <strong>⚠️ Atenção:</strong> ${errorMsg}
                </div>`;
                
                // Mostra sugestões se disponíveis
                if (data.suggestions && data.suggestions.length > 0) {
                    html += `<div class="mb-2"><strong class="small">Diretórios acessíveis sugeridos:</strong></div>`;
                    data.suggestions.forEach(item => {
                        html += `<div class="ssh-browser-item directory" data-path="${item.path}" data-type="directory" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M.5 3l.04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9.81a2 2 0 0 0 1.991-1.819l.637-7a1.99 1.99 0 0 0-.342-1.311L12.5 3H.5zm1.217-.456A1.5 1.5 0 0 1 3.5 2.5h9a1.5 1.5 0 0 1 1.283.757L14.5 3H1.5l.217-.456z"/>
                            </svg>
                            <span class="flex-grow-1">
                                <strong>${item.name}</strong>
                                <br><small class="text-muted">${item.path}</small>
                                ${item.description ? '<br><small class="text-muted">' + item.description + '</small>' : ''}
                            </span>
                            <button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); selectLocalDirectory('${item.path}');" title="Selecionar este diretório">Selecionar</button>
                        </div>`;
                    });
                }
                
                if (data.hint) {
                    html += `<div class="text-info small p-2 mt-2 border border-info rounded">
                        <strong>💡 Dica:</strong> ${data.hint}
                    </div>`;
                }
                
                browserContent.innerHTML = html;
                return;
            }

            browserPath.textContent = data.current_path || currentLocalBrowsePath;
            currentLocalBrowsePath = data.current_path || currentLocalBrowsePath;

            let html = '';
            
            // Mostra sugestões de diretórios comuns se estiver na raiz
            if (data.suggestions && data.suggestions.length > 0 && currentLocalBrowsePath === '/') {
                html += `<div class="mb-3 p-2 bg-dark border border-secondary rounded">
                    <strong class="small d-block mb-2">📁 Diretórios acessíveis comuns:</strong>`;
                data.suggestions.forEach(item => {
                    html += `<div class="ssh-browser-item directory mb-1" data-path="${item.path}" data-type="directory" style="cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M.5 3l.04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9.81a2 2 0 0 0 1.991-1.819l.637-7a1.99 1.99 0 0 0-.342-1.311L12.5 3H.5zm1.217-.456A1.5 1.5 0 0 1 3.5 2.5h9a1.5 1.5 0 0 1 1.283.757L14.5 3H1.5l.217-.456z"/>
                        </svg>
                        <span class="flex-grow-1">
                            <strong>${item.name}</strong>
                            <br><small class="text-muted">${item.path}</small>
                            ${item.description ? '<br><small class="text-muted">' + item.description + '</small>' : ''}
                        </span>
                        <button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); browseLocalDirectories('${item.path}');" title="Navegar para este diretório">Abrir</button>
                    </div>`;
                });
                html += `</div><hr class="my-2">`;
            }
            
            if (data.items.length === 0) {
                html += `<div class="text-muted small text-center py-3">
                    <div>Diretório vazio</div>
                    <small class="text-muted">Caminho: ${data.current_path || currentLocalBrowsePath}</small>
                </div>`;
                browserContent.innerHTML = html;
                return;
            }
            
            // Botão para voltar (se não estiver na raiz)
            if (currentLocalBrowsePath !== '/' && currentLocalBrowsePath !== '') {
                const parentPath = currentLocalBrowsePath.split('/').slice(0, -1).join('/') || '/';
                html += `<div class="ssh-browser-item directory" data-path="${parentPath}" data-type="directory">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                    </svg>
                    <span>.. (voltar)</span>
                </div>`;
            }

            data.items.forEach(item => {
                const icon = item.type === 'directory' 
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 3l.04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9.81a2 2 0 0 0 1.991-1.819l.637-7a1.99 1.99 0 0 0-.342-1.311L12.5 3H.5zm1.217-.456A1.5 1.5 0 0 1 3.5 2.5h9a1.5 1.5 0 0 1 1.283.757L14.5 3H1.5l.217-.456z"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1v13H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h8z"/></svg>';
                
                // Mostra aviso se diretório não for acessível
                const accessibilityWarning = (item.type === 'directory' && item.accessible === false) 
                    ? '<small class="text-warning d-block">⚠️ Pode não estar acessível</small>'
                    : '';
                
                const selectBtn = item.type === 'directory' 
                    ? '<button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); selectLocalDirectory(\'' + item.path + '\');" title="Selecionar este diretório">Selecionar</button>'
                    : '';
                
                html += `<div class="ssh-browser-item ${item.type}" data-path="${item.path}" data-type="${item.type}" ${item.accessible === false ? 'style="opacity: 0.6;"' : ''}>
                    ${icon}
                    <span class="flex-grow-1">
                        ${item.name}
                        ${accessibilityWarning}
                    </span>
                    ${selectBtn}
                </div>`;
            });

            browserContent.innerHTML = html;

            // Adiciona event listeners
            browserContent.querySelectorAll('.ssh-browser-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    // Ignora cliques em botões
                    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                        return;
                    }
                    
                    const itemPath = item.dataset.path;
                    const itemType = item.dataset.type;

                    if (itemType === 'directory') {
                        // Remove seleção anterior
                        document.querySelectorAll('#local-browser .ssh-browser-item').forEach(i => i.classList.remove('selected'));
                        // Destaca o item clicado
                        item.classList.add('selected');
                        // Atualiza o caminho atual para o botão "Selecionar" do header
                        currentLocalBrowsePath = itemPath;
                        // Clique simples navega para dentro
                        browseLocalDirectories(itemPath);
                    } else {
                        // Seleciona o arquivo (mas para logs, geralmente queremos o diretório pai)
                        // Se for arquivo .log, seleciona o diretório pai
                        if (itemPath.endsWith('.log')) {
                            const dirPath = itemPath.substring(0, itemPath.lastIndexOf('/'));
                            document.getElementById('project-path').value = dirPath;
                        } else {
                            document.getElementById('project-path').value = itemPath;
                        }
                        document.querySelectorAll('#local-browser .ssh-browser-item').forEach(i => i.classList.remove('selected'));
                        item.classList.add('selected');
                    }
                });
            });
        } catch (error) {
            browserContent.innerHTML = `<div class="text-danger small">Erro: ${error.message}</div>`;
        }
    }

    // Função para selecionar um diretório local
    function selectLocalDirectory(path) {
        document.getElementById('project-path').value = path;
        document.getElementById('local-browser').style.display = 'none';
        // Remove todas as seleções visuais
        document.querySelectorAll('#local-browser .ssh-browser-item').forEach(i => i.classList.remove('selected'));
    }

    document.getElementById('btn-browse-local').addEventListener('click', () => {
        // Inicia na raiz ou no caminho atual se houver
        const currentPath = document.getElementById('project-path').value.trim() || '/';
        browseLocalDirectories(currentPath);
    });

    document.getElementById('local-browser-close').addEventListener('click', () => {
        document.getElementById('local-browser').style.display = 'none';
    });

    // Botão "Selecionar" no header do navegador local
    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'local-browser-select') {
            selectLocalDirectory(currentLocalBrowsePath);
        }
    });

    // Inicialização
    toggleTypeFields();
    toggleUrlAuth();
    loadProjects();
</script>
</body>
</html>


