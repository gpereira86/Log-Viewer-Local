<?php

/** @var array<int, array<string, string>> $projects */
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Log Viewer - Logs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background-color: #121212;
            color: #e5e5e5;
        }

        .navbar-dark {
            background-color: #1f1f1f !important;
        }

        .lv-shell {
            height: calc(100vh - 56px);
            /* 56px ~ altura da navbar */
            display: flex;
            flex-direction: row;
            width: 100vw;
            overflow: hidden;
        }

        .lv-sidebar {
            background-color: #1a1a1a;
            border-right: 1px solid #2a2a2a;
            flex: 0 0 18vw;
            max-width: 280px;
            min-width: 220px;
            overflow-y: auto;
        }

        .lv-sidebar-item {
            background-color: #1f1f1f;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #e5e5e5;
        }

        .lv-sidebar-item.active {
            border-color: #0d6efd;
            box-shadow: 0 0 0 1px #0d6efd;
            background-color: #222;
        }

        .lv-file-tree {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .lv-file-tree-item {
            margin: 2px 0;
            list-style: none;
            list-style-type: none;
            padding-left: 0;
        }

        .lv-file-tree-item::marker {
            content: '';
        }

        .lv-file-tree-folder,
        .lv-file-tree-file {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #e5e5e5;
            user-select: none;
        }

        .lv-file-tree-folder:hover,
        .lv-file-tree-file:hover {
            background-color: #2a2a2a;
        }

        .lv-file-tree-file.active {
            background-color: #0d6efd;
            color: #fff;
        }

        .lv-file-tree-icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }


        .lv-file-tree-children {
            margin-left: 20px;
            margin-top: 2px;
            display: none;
            list-style: none;
            padding-left: 0;
        }

        .lv-file-tree-folder.expanded + .lv-file-tree-children,
        .lv-file-tree-folder.expanded ~ .lv-file-tree-children {
            display: block;
        }

        .lv-file-tree-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lv-center {
            flex: 1 1 auto;
            display: flex;
            flex-direction: row;
            min-width: 0;
            overflow: hidden;
            position: relative;
            /* permite painel de detalhe sobreposto */
        }

        .lv-main {
            height: 100%;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-width: 0;
        }

        .lv-toolbar {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #2a2a2a;
        }

        .lv-log-container {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .lv-table-wrapper {
            flex: 1 1 auto;
            overflow: auto;
        }

        /* Estilo da linha de detalhe (collapse abaixo da linha clicada) */
        .lv-detail-row > td {
            background-color: #000000;
            border-top: 1px solid #2a2a2a;
            border-bottom: 1px solid #2a2a2a;
        }

        /* Remove o efeito hover cinza apenas da linha de detalhe */
        .table-hover > tbody > tr.lv-detail-row:hover > *,
        .table-dark.table-hover > tbody > tr.lv-detail-row:hover > * {
            --bs-table-accent-bg: #000000;
            --bs-table-hover-bg: #000000;
            background-color: #000000 !important;
        }

        .lv-datetime {
            white-space: nowrap;
        }

        .lv-datetime-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .lv-info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            cursor: help;
            flex-shrink: 0;
        }

        .lv-tooltip {
            position: relative;
        }

        .lv-tooltip .lv-tooltip-text {
            visibility: hidden;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 4px 8px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .lv-tooltip .lv-tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .lv-tooltip:hover .lv-tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .lv-log-entry {
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
        }

        #entries-table tbody td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            vertical-align: top;
        }

        #entries-table tbody td:last-child {
            max-width: 0;
        }

        .lv-detail-header {
            color: #f8f9fa;
        }

        .lv-detail-header-bar {
            min-height: 48px;
        }

        .lv-detail-header-bar.level-error {
            background-color: rgba(220, 53, 69, 0.15) !important;
            border-bottom-color: rgba(220, 53, 69, 0.3) !important;
        }

        .lv-detail-header-bar.level-warning,
        .lv-detail-header-bar.level-warn {
            background-color: rgba(255, 193, 7, 0.15) !important;
            border-bottom-color: rgba(255, 193, 7, 0.3) !important;
        }

        .lv-detail-header-bar.level-info {
            background-color: rgba(13, 110, 253, 0.15) !important;
            border-bottom-color: rgba(13, 110, 253, 0.3) !important;
        }

        .lv-detail-header-bar.level-debug {
            background-color: rgba(108, 117, 125, 0.15) !important;
            border-bottom-color: rgba(108, 117, 125, 0.3) !important;
        }

        .divider {
            height: 1px;
            background-color: #2a2a2a;
            margin: 0.5rem 0;
        }

        #detail-toggle-size,
        #detail-close {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
        }

        #detail-toggle-size svg,
        #detail-close svg {
            display: block;
            margin: 0 auto;
        }

        #last-update {
            font-size: 0.75rem;
            white-space: nowrap;
        }

        #auto-refresh-interval:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .lv-spinner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .lv-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgb(255, 240, 194) !important;
            color: #000 !important;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 300px;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
            font-size: 0.9rem;
            border: none !important;
        }

        .lv-toast * {
            background-color: transparent !important;
            color: #000 !important;
        }

        .lv-toast-icon {
            flex-shrink: 0;
            font-size: 2rem;
            color: rgb(255, 0, 0) !important;
            display: inline-flex;
            align-items: center;
        }

        .lv-toast-icon svg {
            fill: rgb(255, 0, 0) !important;
            width: 18px;
            height: 18px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .lv-toast.hiding {
            animation: slideOutRight 0.3s ease-out forwards;
        }

        @media (max-width: 768px) {
            .lv-shell {
                flex-direction: column;
                height: auto;
            }

            .lv-center {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-0">
        <div class="container-fluid">
            <a class="navbar-brand" href="/logs">Log Viewer</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/config">Configuração</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/logs">Logs</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small" id="last-update" style="color: #adb5bd !important;"></span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" style="cursor: pointer;">
                            <label class="form-check-label text-muted small" for="auto-refresh-toggle" style="color: #adb5bd !important; cursor: pointer;">
                                Auto
                            </label>
                        </div>
                        <select id="auto-refresh-interval" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 80px;" disabled>
                            <option value="5">5s</option>
                            <option value="10" selected>10s</option>
                            <option value="30">30s</option>
                            <option value="60">60s</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0">
        <div class="lv-shell">
            <div class="lv-sidebar pt-3">
                <div class="mb-2 px-2">
                    <label class="form-label small mb-1 text-muted" style="color: #e5e5e5 !important;">Projeto</label>
                    <select class="form-select form-select-sm" id="select-project">
                        <option value="">Selecione...</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                <?= htmlspecialchars($p['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="file-list-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center px-2 mb-1">
                        <span class="small text-muted" style="color: #e5e5e5 !important;">Arquivos de log</span>
                        <button class="btn btn-link btn-sm p-0 small" id="btn-refresh-files">Atualizar</button>
                    </div>
                    <div id="file-list" class="px-2 pb-3"></div>
                </div>
            </div>
            <div class="lv-center" id="lv-center">
                <div class="lv-main" id="logs-main">
                    <div class="lv-toolbar d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small mb-0 text-muted" style="color: #e5e5e5 !important;">Nível</label>
                            <select id="level-filter" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 130px;">
                                <option value="">Todos</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                        <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary"
                            id="search" placeholder="Buscar mensagem/contexto..."
                            style="max-width: 320px;">
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <label class="form-label small mb-0 text-muted" style="color: #e5e5e5 !important;">Itens por página</label>
                            <select id="per-page" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 90px;">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                            <button class="btn btn-sm btn-outline-light" id="btn-refresh">Atualizar</button>
                            <span id="log-loading" class="text-muted small" style="display:none;">Carregando...</span>
                        </div>
                    </div>
                    <div class="lv-log-container bg-black text-light">
                        <div id="log-empty" class="text-muted small text-center mt-5">
                            Selecione um projeto e um arquivo de log na coluna à esquerda.
                        </div>
                        <div class="lv-table-wrapper" id="entries-wrapper" style="display:none; position: relative;">
                            <div id="loading-spinner" class="lv-spinner-overlay" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                            <table class="table table-dark table-hover table-sm align-top mb-0" id="entries-table">
                                <thead>
                                    <tr class="small text-muted">
                                        <th style="width: 80px;">Severity</th>
                                        <th style="width: 190px;" class="lv-datetime">Datetime</th>
                                        <th style="width: 80px;">Env</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-3 py-1 small border-top border-secondary" id="pagination-bar" style="display:none;">
                            <div>
                                <span id="entries-count"></span>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="page-prev">&laquo;</button>
                                <span class="btn btn-outline-secondary disabled" id="page-info">1 / 1</span>
                                <button type="button" class="btn btn-outline-secondary" id="page-next">&raquo;</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let currentProjectId = '';
            let currentFile = '';
            let currentPage = 1;
            let totalPages = 1;
            let autoRefreshInterval = null;
            let isAutoRefresh = false;
            
            // Throttle: máximo de 4 chamadas a cada 5 segundos
            let throttleCallHistory = [];
            const THROTTLE_MAX_CALLS = 4;
            const THROTTLE_WINDOW_MS = 5000;

            // Funções para persistência no sessionStorage (limpa ao fechar a página)
            const STORAGE_KEYS = {
                PROJECT_ID: 'logviewer_selected_project',
                FILE_PATH: 'logviewer_selected_file',
                PER_PAGE: 'logviewer_per_page'
            };

            function saveProjectSelection(projectId) {
                if (projectId) {
                    sessionStorage.setItem(STORAGE_KEYS.PROJECT_ID, projectId);
                } else {
                    sessionStorage.removeItem(STORAGE_KEYS.PROJECT_ID);
                }
            }

            function saveFileSelection(filePath) {
                if (filePath) {
                    sessionStorage.setItem(STORAGE_KEYS.FILE_PATH, filePath);
                } else {
                    sessionStorage.removeItem(STORAGE_KEYS.FILE_PATH);
                }
            }

            function savePerPageSelection(perPage) {
                if (perPage) {
                    sessionStorage.setItem(STORAGE_KEYS.PER_PAGE, perPage);
                } else {
                    sessionStorage.removeItem(STORAGE_KEYS.PER_PAGE);
                }
            }

            function loadProjectSelection() {
                return sessionStorage.getItem(STORAGE_KEYS.PROJECT_ID) || '';
            }

            function loadFileSelection() {
                return sessionStorage.getItem(STORAGE_KEYS.FILE_PATH) || '';
            }

            function loadPerPageSelection() {
                return sessionStorage.getItem(STORAGE_KEYS.PER_PAGE) || '50';
            }

            function levelBadge(level) {
                const l = (level || '').toLowerCase();
                const base = 'badge rounded-pill px-2 py-1';
                if (l === 'error') return `<span class="${base} bg-danger">Error</span>`;
                if (l === 'warning' || l === 'warn') return `<span class="${base} bg-warning text-dark">Warn</span>`;
                if (l === 'info') return `<span class="${base} bg-primary">Info</span>`;
                if (l === 'debug') return `<span class="${base} bg-secondary">Debug</span>`;
                return `<span class="${base} bg-light text-dark">${level}</span>`;
            }

            function formatDatetimeLocal(utcDatetimeStr) {
                if (!utcDatetimeStr) return '';
                
                // Tenta parsear a data UTC
                // Formato esperado: "YYYY-MM-DD HH:MM:SS" ou similar
                let date;
                try {
                    // Se a string não termina com Z, adiciona para indicar UTC
                    let isoStr = utcDatetimeStr.replace(' ', 'T');
                    if (!isoStr.endsWith('Z') && !isoStr.includes('+') && !isoStr.includes('-', 10)) {
                        isoStr += 'Z';
                    }
                    date = new Date(isoStr);
                    
                    // Verifica se a data é válida
                    if (isNaN(date.getTime())) {
                        return utcDatetimeStr;
                    }
                } catch (e) {
                    return utcDatetimeStr;
                }
                
                // Formata a data local
                const pad = (n) => String(n).padStart(2, '0');
                const localStr = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
                
                return `<span class="lv-datetime-wrapper">
                    <span>${localStr}</span>
                    <span class="lv-tooltip">
                        <span class="lv-info-icon">i</span>
                        <span class="lv-tooltip-text">UTC: ${utcDatetimeStr}</span>
                    </span>
                </span>`;
            }

            function updateLastUpdateTime() {
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const formatted = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
                const lastUpdateEl = document.getElementById('last-update');
                if (lastUpdateEl) {
                    lastUpdateEl.textContent = `Última atualização: ${formatted}`;
                }
            }

            function stopAutoRefresh() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
            }

            function startAutoRefresh() {
                stopAutoRefresh();
                
                const toggle = document.getElementById('auto-refresh-toggle');
                const intervalSelect = document.getElementById('auto-refresh-interval');
                
                if (!toggle.checked || !currentProjectId || !currentFile) {
                    return;
                }
                
                const intervalSeconds = parseInt(intervalSelect.value) || 10;
                const intervalMs = intervalSeconds * 1000;
                
                autoRefreshInterval = setInterval(() => {
                    if (currentProjectId && currentFile) {
                        isAutoRefresh = true;
                        loadEntries();
                    } else {
                        stopAutoRefresh();
                    }
                }, intervalMs);
            }

            function renderFileList(tree, selectedFilePath = null) {
                const container = document.getElementById('file-list');
                container.innerHTML = '';
                
                if (!tree || tree.length === 0) {
                    return;
                }

                const ul = document.createElement('ul');
                ul.className = 'lv-file-tree';
                tree.forEach(item => {
                    ul.appendChild(renderTreeItem(item, 0, selectedFilePath));
                });
                container.appendChild(ul);

                // Se houver um arquivo selecionado, tenta encontrá-lo e ativá-lo
                if (selectedFilePath) {
                    const fileElement = container.querySelector(`[data-file="${selectedFilePath}"]`);
                    if (fileElement) {
                        fileElement.classList.add('active');
                        // Expande todas as pastas pais
                        let current = fileElement.parentElement; // li do arquivo
                        while (current && current !== container) {
                            // Procura pela pasta pai (o li que contém uma pasta)
                            const folder = current.querySelector('.lv-file-tree-folder');
                            if (folder && !folder.classList.contains('expanded')) {
                                folder.classList.add('expanded');
                                const folderIcon = folder.querySelector('.lv-file-tree-icon span:last-child');
                                if (folderIcon) {
                                    folderIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><title>folder-open</title><path d="M19,20H4C2.89,20 2,19.1 2,18V6C2,4.89 2.89,4 4,4H10L12,6H19A2,2 0 0,1 21,8H21L4,8V18L6.14,10H23.21L20.93,18.5C20.7,19.37 19.92,20 19,20Z" /></svg>';
                                }
                            }
                            // Move para o li pai (se houver)
                            current = current.parentElement; // ul
                            if (current) {
                                current = current.parentElement; // li pai
                            }
                        }
                    }
                }
            }

            function renderTreeItem(item, depth, selectedFilePath = null) {
                const li = document.createElement('li');
                li.className = 'lv-file-tree-item';

                if (item.type === 'folder') {
                    const folder = document.createElement('div');
                    folder.className = 'lv-file-tree-folder';
                    folder.style.paddingLeft = `${depth * 12 + 8}px`;
                    
                    const iconContainer = document.createElement('span');
                    iconContainer.className = 'lv-file-tree-icon';
                    iconContainer.style.display = 'inline-flex';
                    iconContainer.style.alignItems = 'center';
                    iconContainer.style.gap = '4px';
                    iconContainer.style.marginRight = '15px';
                    
                    const arrowIcon = document.createElement('span');
                    arrowIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>';
                    
                    const folderIcon = document.createElement('span');
                    // Ícone inicial: pasta fechada
                    folderIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><title>folder</title><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" /></svg>';
                    
                    iconContainer.appendChild(arrowIcon);
                    iconContainer.appendChild(folderIcon);
                    
                    const name = document.createElement('span');
                    name.className = 'lv-file-tree-name';
                    name.textContent = item.name;
                    name.title = item.path;

                    folder.appendChild(iconContainer);
                    folder.appendChild(name);
                    
                    folder.addEventListener('click', (e) => {
                        e.stopPropagation();
                        folder.classList.toggle('expanded');
                        // Troca o ícone entre pasta fechada e aberta
                        if (folder.classList.contains('expanded')) {
                            folderIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><title>folder-open</title><path d="M19,20H4C2.89,20 2,19.1 2,18V6C2,4.89 2.89,4 4,4H10L12,6H19A2,2 0 0,1 21,8H21L4,8V18L6.14,10H23.21L20.93,18.5C20.7,19.37 19.92,20 19,20Z" /></svg>';
                        } else {
                            folderIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><title>folder</title><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" /></svg>';
                        }
                    });

                    li.appendChild(folder);

                    if (item.children && item.children.length > 0) {
                        const childrenUl = document.createElement('ul');
                        childrenUl.className = 'lv-file-tree-children';
                        item.children.forEach(child => {
                            childrenUl.appendChild(renderTreeItem(child, depth + 1, selectedFilePath));
                        });
                        li.appendChild(childrenUl);
                    }
                } else {
                    const file = document.createElement('div');
                    file.className = 'lv-file-tree-file';
                    file.dataset.file = item.path;
                    file.style.paddingLeft = `${depth * 12 + 8}px`;
                    
                    const icon = document.createElement('span');
                    icon.className = 'lv-file-tree-icon';
                    icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1z"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm10-1V2a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v1zM4 14a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"/></svg>';
                    
                    const name = document.createElement('span');
                    name.className = 'lv-file-tree-name';
                    name.textContent = item.name;
                    name.title = item.path;

                    file.appendChild(icon);
                    file.appendChild(name);

                    file.addEventListener('click', () => {
                        document.querySelectorAll('.lv-file-tree-file').forEach(f => f.classList.remove('active'));
                        file.classList.add('active');
                        currentFile = item.path;
                        saveFileSelection(item.path);
                        currentPage = 1;
                        stopAutoRefresh();
                        loadEntries();
                        // Reinicia a atualização automática se estiver ativa
                        if (document.getElementById('auto-refresh-toggle').checked) {
                            startAutoRefresh();
                        }
                    });

                    li.appendChild(file);
                }

                return li;
            }

            async function loadProjects() {
                const res = await fetch('/api/projects');
                const data = await res.json();
                const select = document.getElementById('select-project');
                select.innerHTML = '<option value="">Selecione...</option>';
                data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name;
                    select.appendChild(opt);
                });
                
                // Restaura a seleção do projeto salva
                const savedProjectId = loadProjectSelection();
                if (savedProjectId) {
                    const option = select.querySelector(`option[value="${savedProjectId}"]`);
                    if (option) {
                        select.value = savedProjectId;
                        currentProjectId = savedProjectId;
                        await loadFiles();
                    } else {
                        // Projeto não encontrado, limpa a seleção
                        saveProjectSelection('');
                    }
                }
            }

            async function loadFiles() {
                const projectId = currentProjectId;
                const fileListSection = document.getElementById('file-list-section');
                if (!projectId) {
                    renderFileList([]);
                    document.getElementById('log-empty').style.display = 'block';
                    fileListSection.style.display = 'none';
                    saveFileSelection(''); // Limpa a seleção de arquivo quando não há projeto
                    return;
                }
                fileListSection.style.display = 'block';
                const res = await fetch(`/api/log-files?project_id=${encodeURIComponent(projectId)}`);
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const tree = data.tree || [];
                
                // Restaura a seleção do arquivo salva
                const savedFilePath = loadFileSelection();
                renderFileList(tree, savedFilePath);
                
                // Se houver um arquivo salvo e ele foi encontrado, carrega as entradas
                if (savedFilePath) {
                    const fileElement = document.querySelector(`[data-file="${savedFilePath}"]`);
                    if (fileElement) {
                        currentFile = savedFilePath;
                        await loadEntries();
                    } else {
                        // Arquivo não encontrado, limpa a seleção
                        saveFileSelection('');
                        currentFile = '';
                    }
                } else {
                    currentFile = '';
                }
                
                // Verifica se há arquivos na árvore
                function hasFilesInTree(items) {
                    return items.some(item => {
                        if (item.type === 'file') return true;
                        if (item.children && item.children.length > 0) return hasFilesInTree(item.children);
                        return false;
                    });
                }
                const hasFiles = tree.length > 0 && hasFilesInTree(tree);
                document.getElementById('log-empty').style.display = hasFiles ? 'none' : 'block';
                
                // Atualiza a data da última atualização
                updateLastUpdateTime();
            }

            function showThrottleToast() {
                // Remove toast existente se houver
                const existingToast = document.querySelector('.lv-toast');
                if (existingToast) {
                    existingToast.classList.add('hiding');
                    setTimeout(() => existingToast.remove(), 300);
                }

                // Cria novo toast
                const toast = document.createElement('div');
                toast.className = 'lv-toast';
                toast.innerHTML = `
                    <span class="lv-toast-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>alert-outline</title><path d="M12,2L1,21H23M12,6L19.53,19H4.47M11,10V14H13V10M11,16V18H13V16" /></svg></span>
                    <span>Muitas requisições. Aguarde e tente novamente.</span>
                `;
                
                document.body.appendChild(toast);
                
                // Remove automaticamente após 4 segundos
                setTimeout(() => {
                    toast.classList.add('hiding');
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }

            function throttleLoadEntries() {
                const now = Date.now();
                
                // Remove chamadas antigas (fora da janela de 5 segundos)
                throttleCallHistory = throttleCallHistory.filter(timestamp => now - timestamp < THROTTLE_WINDOW_MS);
                
                // Se já atingiu o limite, bloqueia a chamada e exibe toast
                if (throttleCallHistory.length >= THROTTLE_MAX_CALLS) {
                    showThrottleToast();
                    return false;
                }
                
                // Registra a chamada atual
                throttleCallHistory.push(now);
                return true;
            }

            async function loadEntries() {
                const projectId = currentProjectId;
                const file = currentFile;
                if (!projectId || !file) {
                    return;
                }
                
                // Aplica o throttle
                if (!throttleLoadEntries()) {
                    return;
                }
                const loading = document.getElementById('log-loading');
                const emptyMsg = document.getElementById('log-empty');
                const tableWrapper = document.getElementById('entries-wrapper');
                const tableBody = document.querySelector('#entries-table tbody');
                const paginationBar = document.getElementById('pagination-bar');
                const spinner = document.getElementById('loading-spinner');

                // Se a tabela já estiver visível, usa o spinner (atualização automática ou manual)
                // Verifica tanto o estilo inline quanto o computed style
                const computedDisplay = window.getComputedStyle(tableWrapper).display;
                const inlineDisplay = tableWrapper.style.display;
                const isTableVisible = (inlineDisplay !== 'none' && inlineDisplay !== '') || 
                                     (inlineDisplay === '' && computedDisplay !== 'none');
                
                if (isTableVisible) {
                    spinner.style.display = 'flex';
                } else {
                    // Primeira vez carregando: comportamento normal
                    loading.style.display = 'inline';
                    emptyMsg.style.display = 'none';
                    tableWrapper.style.display = 'none';
                    paginationBar.style.display = 'none';
                    tableBody.innerHTML = '';
                }

                try {
                    const params = new URLSearchParams();
                    params.set('project_id', projectId);
                    params.set('file', file);
                    params.set('page', String(currentPage));
                    params.set('per_page', document.getElementById('per-page').value || '50');
                    const level = document.getElementById('level-filter').value;
                    const search = document.getElementById('search').value.trim();
                    if (level) params.set('level', level);
                    if (search) params.set('search', search);

                    const res = await fetch(`/api/log-entries?${params.toString()}`);
                    let data;
                    try {
                        data = await res.json();
                    } catch (e) {
                        const text = await res.text();
                        spinner.style.display = 'none';
                        alert('Resposta inválida do servidor ao carregar as entradas: ' + text);
                        return;
                    }
                    if (data.error) {
                        spinner.style.display = 'none';
                        alert(data.error);
                        return;
                    }

                    const entries = data.entries || [];
                    const total = data.total || 0;
                    const perPage = data.per_page || 1;
                    currentPage = data.page || 1;
                    totalPages = Math.max(1, Math.ceil(total / perPage));

                    document.getElementById('entries-count').textContent =
                        `${total} entradas (${perPage} por página)`;
                    document.getElementById('page-info').textContent = `${currentPage} / ${totalPages}`;

                    // Se a tabela já estiver visível, limpa apenas o tbody (atualização)
                    if (isTableVisible) {
                        tableBody.innerHTML = '';
                    }

                    entries.forEach(e => {
                        const tr = document.createElement('tr');
                        tr.className = 'small';
                        tr.innerHTML = `
                    <td>${levelBadge(e.level)}</td>
                    <td class="lv-datetime">${formatDatetimeLocal(e.datetime)}</td>
                    <td>${e.env || ''}</td>
                    <td class="text-truncate" title="${(e.message || '').replace(/"/g, '&quot;')}">${e.message || ''}</td>
                `;
                        tr.addEventListener('click', () => {
                            toggleDetailRow(tr, e);
                        });
                        tableBody.appendChild(tr);
                    });

                    // Se a tabela não estava visível, mostra os elementos normalmente
                    if (!isTableVisible) {
                        tableWrapper.style.display = 'block';
                        paginationBar.style.display = total > 0 ? 'flex' : 'none';
                        if (entries.length === 0) {
                            emptyMsg.style.display = 'block';
                        }
                    } else {
                        // Para atualizações (automática ou manual), apenas atualiza a paginação se necessário
                        paginationBar.style.display = total > 0 ? 'flex' : 'none';
                    }
                    
                    // Atualiza a data da última atualização
                    updateLastUpdateTime();
                    
                    // Reinicia a atualização automática se estiver ativa
                    if (document.getElementById('auto-refresh-toggle').checked) {
                        startAutoRefresh();
                    }
                } finally {
                    loading.style.display = 'none';
                    spinner.style.display = 'none';
                    isAutoRefresh = false;
                }
            }

            function buildDetailHtml(entry) {
                // Monta o conteúdo detalhado (mensagem + possível JSON) e contexto
                const headerText = `${entry.datetime || ''} ${entry.env || ''} — ${entry.message || ''}`;

                let bodyHtml = '<div class="p-2 small lv-detail-header bg-dark mt-1 mb-2">';

                if (headerText.includes('{') && headerText.includes('}')) {
                    function findJsonInBraces(text, startIndex) {
                        if (text[startIndex] !== '{') return null;
                        let depth = 0;
                        let i = startIndex;
                        let inString = false;
                        let escapeNext = false;

                        while (i < text.length) {
                            const char = text[i];
                            if (escapeNext) {
                                escapeNext = false;
                                i++;
                                continue;
                            }
                            if (char === '\\') {
                                escapeNext = true;
                                i++;
                                continue;
                            }
                            if (char === '"') {
                                inString = !inString;
                                i++;
                                continue;
                            }
                            if (!inString) {
                                if (char === '{') depth++;
                                if (char === '}') {
                                    depth--;
                                    if (depth === 0) {
                                        return {
                                            start: startIndex,
                                            end: i + 1,
                                            content: text.substring(startIndex + 1, i)
                                        };
                                    }
                                }
                            }
                            i++;
                        }
                        return null;
                    }

                    let lastIndex = 0;
                    let html = '';
                    let i = 0;

                    while (i < headerText.length) {
                        if (headerText[i] === '{') {
                            const jsonMatch = findJsonInBraces(headerText, i);
                            if (jsonMatch) {
                                if (i > lastIndex) {
                                    html += headerText.substring(lastIndex, i);
                                }
                                let formattedContent = jsonMatch.content;
                                try {
                                    const fullJson = headerText.substring(jsonMatch.start, jsonMatch.end);
                                    const jsonObj = JSON.parse(fullJson);
                                    formattedContent = JSON.stringify(jsonObj, null, 2);
                                } catch (e) {
                                    formattedContent = '{' + jsonMatch.content + '}';
                                }
                                formattedContent = formattedContent
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#039;');
                                html += `<pre class="lv-log-entry text-light m-0 mt-1 mb-1">${formattedContent}</pre>`;
                                lastIndex = jsonMatch.end;
                                i = jsonMatch.end;
                            } else {
                                i++;
                            }
                        } else {
                            i++;
                        }
                    }
                    if (lastIndex < headerText.length) {
                        html += headerText.substring(lastIndex);
                    }
                    bodyHtml += html;
                } else {
                    bodyHtml += headerText;
                }

                bodyHtml += '</div>';

                const contextText = (entry.context || '').trim() || '(sem contexto adicional)';
                const escapedContext = contextText
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                bodyHtml += `<pre class="lv-log-entry text-light m-0">${escapedContext}</pre>`;

                return bodyHtml;
            }

            function toggleDetailRow(row, entry) {
                const tbody = row.parentElement;
                if (!tbody) return;

                const next = row.nextElementSibling;
                if (next && next.classList.contains('lv-detail-row')) {
                    tbody.removeChild(next);
                    return;
                }

                tbody.querySelectorAll('.lv-detail-row').forEach(r => r.parentElement.removeChild(r));
                tbody.querySelectorAll('tr').forEach(r => r.classList.remove('table-primary'));

                const detailRow = document.createElement('tr');
                detailRow.className = 'lv-detail-row';
                const detailCell = document.createElement('td');
                detailCell.colSpan = 4;
                detailCell.innerHTML = buildDetailHtml(entry);
                detailRow.appendChild(detailCell);

                if (row.nextSibling) {
                    tbody.insertBefore(detailRow, row.nextSibling);
                } else {
                    tbody.appendChild(detailRow);
                }

                row.classList.add('table-primary');
            }

            document.getElementById('select-project').addEventListener('change', async (e) => {
                currentProjectId = e.target.value;
                saveProjectSelection(currentProjectId);
                currentPage = 1;
                stopAutoRefresh();
                const fileListSection = document.getElementById('file-list-section');
                if (!currentProjectId) {
                    fileListSection.style.display = 'none';
                    renderFileList([]);
                    document.getElementById('log-empty').style.display = 'block';
                    saveFileSelection(''); // Limpa a seleção de arquivo quando não há projeto
                } else {
                    await loadFiles();
                    // Reinicia a atualização automática se estiver ativa e houver arquivo selecionado
                    if (document.getElementById('auto-refresh-toggle').checked && currentFile) {
                        startAutoRefresh();
                    }
                }
            });

            document.getElementById('btn-refresh-files').addEventListener('click', () => {
                currentPage = 1;
                stopAutoRefresh();
                loadFiles();
                // Reinicia a atualização automática se estiver ativa e houver arquivo selecionado
                if (document.getElementById('auto-refresh-toggle').checked && currentFile) {
                    startAutoRefresh();
                }
            });
            document.getElementById('btn-refresh').addEventListener('click', () => {
                currentPage = 1;
                stopAutoRefresh();
                loadEntries();
                // Reinicia a atualização automática se estiver ativa
                if (document.getElementById('auto-refresh-toggle').checked && currentProjectId && currentFile) {
                    startAutoRefresh();
                }
            });
            document.getElementById('search').addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    currentPage = 1;
                    loadEntries();
                }
            });
            document.getElementById('level-filter').addEventListener('change', () => {
                currentPage = 1;
                loadEntries();
            });
            document.getElementById('per-page').addEventListener('change', (e) => {
                savePerPageSelection(e.target.value);
                currentPage = 1;
                loadEntries();
            });
            document.getElementById('page-prev').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadEntries();
                }
            });
            document.getElementById('page-next').addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadEntries();
                }
            });

            // Controle de atualização automática
            document.getElementById('auto-refresh-toggle').addEventListener('change', (e) => {
                const intervalSelect = document.getElementById('auto-refresh-interval');
                intervalSelect.disabled = !e.target.checked;
                
                if (e.target.checked) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });

            document.getElementById('auto-refresh-interval').addEventListener('change', () => {
                if (document.getElementById('auto-refresh-toggle').checked) {
                    startAutoRefresh();
                }
            });

            // Inicialização: restaura seleção do select renderizado pelo PHP
            (function initSelection() {
                const savedProjectId = loadProjectSelection();
                if (savedProjectId) {
                    const select = document.getElementById('select-project');
                    const option = select.querySelector(`option[value="${savedProjectId}"]`);
                    if (option) {
                        select.value = savedProjectId;
                        currentProjectId = savedProjectId;
                    }
                }
                
                // Restaura a seleção de itens por página
                const savedPerPage = loadPerPageSelection();
                if (savedPerPage) {
                    const perPageSelect = document.getElementById('per-page');
                    const perPageOption = perPageSelect.querySelector(`option[value="${savedPerPage}"]`);
                    if (perPageOption) {
                        perPageSelect.value = savedPerPage;
                    }
                }
            })();

            loadProjects();

            // Limpa o intervalo quando a página for fechada
            window.addEventListener('beforeunload', () => {
                stopAutoRefresh();
            });
        </script>
</body>

</html>