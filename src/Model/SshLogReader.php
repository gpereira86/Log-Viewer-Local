<?php

declare(strict_types=1);

namespace LogViewer\Model;

class SshLogReader implements LogReaderInterface
{
    private string $host;
    private int $port;
    private string $user;
    private string $basePath;
    private ?string $password;
    private ?string $privateKey;
    private ?string $privateKeyPassphrase;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $basePath,
        ?string $password = null,
        ?string $privateKey = null,
        ?string $privateKeyPassphrase = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->basePath = rtrim($basePath, '/');
        $this->password = $password;
        $this->privateKey = $privateKey;
        $this->privateKeyPassphrase = $privateKeyPassphrase;
    }

    public function listFiles(): array
    {
        // Tenta primeiro com SFTP (mais confiável, não precisa de sshpass)
        if (function_exists('ssh2_connect') && function_exists('ssh2_sftp')) {
            $files = $this->listFilesViaSftp();
            if ($files !== null) {
                return $files;
            }
        }
        
        // Fallback para SSH com comandos
        // Tenta primeiro obter arquivos com suas datas de modificação usando -printf
        $command = sprintf(
            'cd %s && find . -type f -name "*.log" -printf "%%T@|%%p\\n" 2>/dev/null',
            escapeshellarg($this->basePath)
        );
        
        $output = $this->executeCommand($command);
        
        // Se o comando falhar ou não retornar nada, tenta sem -printf (fallback)
        if ($output === null || trim($output) === '' || str_contains($output, 'unknown predicate')) {
            $command = sprintf(
                'cd %s && find . -type f -name "*.log" 2>/dev/null',
                escapeshellarg($this->basePath)
            );
            $output = $this->executeCommand($command);
        }
        
        // Se não retornar nada, tenta verificar se o diretório existe
        if ($output === null || trim($output) === '') {
            // Tenta verificar se o diretório existe
            $checkCommand = sprintf('test -d %s && echo OK || echo FAIL', escapeshellarg($this->basePath));
            $checkOutput = $this->executeCommand($checkCommand);
            
            if ($checkOutput === null || trim($checkOutput) !== 'OK') {
                throw new \RuntimeException('Diretório não encontrado ou sem permissão: ' . $this->basePath);
            }
            
            // Se o diretório existe mas não há arquivos, retorna vazio
            return [];
        }

        $output = trim($output);
        
        // Remove avisos sobre ssh: not found se houver conteúdo útil
        if (str_contains($output, 'ssh: not found')) {
            $lines = explode("\n", $output);
            $cleanLines = array_filter($lines, function($line) {
                $line = trim($line);
                return !empty($line) && !str_contains($line, 'ssh: not found') && !str_contains($line, 'sh: 1: ssh:');
            });
            $output = implode("\n", $cleanLines);
        }
        
        if (empty($output)) {
            return [];
        }

        return $this->buildFileTreeFromList($output);
    }
    
    /**
     * Lista arquivos .log recursivamente via SFTP
     * @return array|null Retorna null se SFTP não estiver disponível ou falhar
     */
    private function listFilesViaSftp(): ?array
    {
        if (!function_exists('ssh2_connect') || !function_exists('ssh2_sftp')) {
            return null;
        }
        
        try {
            $ssh2_connect = 'ssh2_connect';
            $ssh2_auth_pubkey_file = 'ssh2_auth_pubkey_file';
            $ssh2_auth_password = 'ssh2_auth_password';
            $ssh2_sftp = 'ssh2_sftp';
            $ssh2_sftp_stat = 'ssh2_sftp_stat';
            
            $connection = @$ssh2_connect($this->host, $this->port);
            if (!$connection) {
                return null;
            }
            
            // Autenticação
            $authenticated = false;
            
            if ($this->privateKey !== null && $this->privateKey !== '') {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                if ($keyFile !== false) {
                    file_put_contents($keyFile, $this->privateKey);
                    chmod($keyFile, 0600);
                    if (function_exists($ssh2_auth_pubkey_file)) {
                        $authenticated = @$ssh2_auth_pubkey_file($connection, $this->user, null, $keyFile, $this->privateKeyPassphrase);
                    }
                    @unlink($keyFile);
                }
            }
            
            if (!$authenticated && $this->password !== null && $this->password !== '') {
                if (function_exists($ssh2_auth_password)) {
                    $authenticated = @$ssh2_auth_password($connection, $this->user, $this->password);
                }
            }
            
            if (!$authenticated) {
                return null;
            }
            
            // Conecta via SFTP
            $sftp = @$ssh2_sftp($connection);
            if (!$sftp) {
                return null;
            }
            
            // Normaliza o caminho
            $basePath = rtrim($this->basePath, '/');
            if ($basePath === '') {
                $basePath = '/';
            }
            
            // Lista arquivos recursivamente com informações de data
            $logFiles = [];
            $dirs = [];
            $this->scanDirectoryViaSftp($sftp, $basePath, $basePath, $logFiles, $dirs);
            
            // Constrói a árvore de arquivos
            return $this->buildFileTreeFromSftpList($logFiles, $dirs, $basePath, $sftp);
            
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Escaneia diretório recursivamente via SFTP procurando arquivos .log
     */
    private function scanDirectoryViaSftp($sftp, string $basePath, string $currentPath, array &$logFiles, array &$dirs): void
    {
        $handle = @opendir("ssh2.sftp://{$sftp}{$currentPath}");
        if (!$handle) {
            return;
        }
        
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $fullPath = $currentPath === '/' ? '/' . $file : $currentPath . '/' . $file;
            
            // Verifica se é diretório ou arquivo
            $testHandle = @opendir("ssh2.sftp://{$sftp}{$fullPath}");
            if ($testHandle) {
                // É um diretório, escaneia recursivamente
                closedir($testHandle);
                $dirs[$fullPath] = $fullPath;
                $this->scanDirectoryViaSftp($sftp, $basePath, $fullPath, $logFiles, $dirs);
            } else {
                // É um arquivo, verifica se termina com .log
                if (str_ends_with(strtolower($file), '.log')) {
                    $logFiles[] = $fullPath;
                }
            }
        }
        
        closedir($handle);
    }
    
    /**
     * Constrói árvore de arquivos a partir da lista SFTP
     */
    private function buildFileTreeFromSftpList(array $files, array $dirs, string $basePath, $sftp): array
    {
        $tree = [];
        $pathMap = [];
        
        // Obtém datas de modificação dos arquivos
        $fileDates = [];
        $dirDates = [];
        
        if (function_exists('ssh2_sftp_stat')) {
            $ssh2_sftp_stat = 'ssh2_sftp_stat';
            foreach ($files as $filePath) {
                $stat = @$ssh2_sftp_stat($sftp, $filePath);
                $mtime = $stat !== false && isset($stat['mtime']) ? $stat['mtime'] : false;
                $fileDates[$filePath] = $mtime;
            }
            
            // Obtém datas de modificação dos diretórios
            foreach ($dirs as $dirPath) {
                $stat = @$ssh2_sftp_stat($sftp, $dirPath);
                $mtime = $stat !== false && isset($stat['mtime']) ? $stat['mtime'] : false;
                $dirDates[$dirPath] = $mtime;
            }
        }
        
        foreach ($files as $filePath) {
            // Remove o basePath do início
            $relativePath = $filePath;
            if (str_starts_with($filePath, $basePath)) {
                $relativePath = substr($filePath, strlen($basePath));
                if (str_starts_with($relativePath, '/')) {
                    $relativePath = substr($relativePath, 1);
                }
            }
            
            if ($relativePath === '' || $relativePath === '.') {
                continue;
            }
            
            $parts = explode('/', $relativePath);
            $current = &$tree;
            
            foreach ($parts as $i => $part) {
                $isLast = ($i === count($parts) - 1);
                $pathSoFar = implode('/', array_slice($parts, 0, $i + 1));
                $fullPath = $basePath === '/' ? '/' . $pathSoFar : $basePath . '/' . $pathSoFar;
                
                if (!isset($current[$part])) {
                    if ($isLast) {
                        $current[$part] = [
                            'name' => $part,
                            'path' => $relativePath,
                            'type' => 'file',
                            'mtime' => $fileDates[$filePath] ?? false,
                            'children' => []
                        ];
                    } else {
                        $current[$part] = [
                            'name' => $part,
                            'path' => implode('/', array_slice($parts, 0, $i + 1)),
                            'type' => 'folder',
                            'mtime' => $dirDates[$fullPath] ?? false,
                            'children' => []
                        ];
                    }
                }
                
                $current = &$current[$part]['children'];
            }
        }
        
        // Converte para array indexado e ordena
        return array_values($this->convertTreeToArray($tree, true));
    }
    
    /**
     * Converte árvore associativa para array indexado e ordena por data
     */
    private function convertTreeToArray(array $tree, bool $sortByDate = false): array
    {
        $result = [];
        foreach ($tree as $item) {
            $converted = [
                'name' => $item['name'],
                'path' => $item['path'],
                'type' => ($item['type'] === 'directory' || $item['type'] === 'folder') ? 'folder' : $item['type']
            ];
            
            // Preserva mtime se existir
            if (isset($item['mtime'])) {
                $converted['mtime'] = $item['mtime'];
            }
            
            if (!empty($item['children'])) {
                $converted['children'] = $this->convertTreeToArray($item['children'], $sortByDate);
            }
            
            $result[] = $converted;
        }
        
        // Ordena por data de modificação (mais recente primeiro) ou pelo nome
        if ($sortByDate) {
            usort($result, function($a, $b) {
                $mtimeA = $a['mtime'] ?? false;
                $mtimeB = $b['mtime'] ?? false;
                
                // Se conseguir obter as datas, ordena por data (mais recente primeiro)
                if ($mtimeA !== false && $mtimeB !== false) {
                    return $mtimeB <=> $mtimeA; // Descendente (mais recente primeiro)
                }
                
                // Se não conseguir obter as datas, ordena pelo nome (que pode conter a data)
                return strnatcasecmp($b['name'], $a['name']); // Descendente (ordem reversa do nome)
            });
            
            // Remove mtime do resultado final (não é necessário no frontend)
            foreach ($result as &$item) {
                unset($item['mtime']);
            }
        }
        
        return $result;
    }

    public function readContent(string $filePath, int $lines): string
    {
        // Tenta primeiro com SFTP (mais confiável)
        if (function_exists('ssh2_connect') && function_exists('ssh2_sftp')) {
            $content = $this->readContentViaSftp($filePath, $lines);
            if ($content !== null && $content !== '') {
                return $content;
            }
        }
        
        // Fallback para SSH com comandos (NUNCA lê localmente - sempre usa executeCommand que executa SSH)
        // Garante que o caminho seja absoluto
        if (!str_starts_with($filePath, '/')) {
            $fullPath = $this->basePath . '/' . ltrim($filePath, '/');
        } else {
            $fullPath = $filePath;
        }
        // Sempre faz cd para o diretório base antes de executar o comando
        // Usa bash -c para garantir execução correta
        $command = sprintf('cd %s && tail -n %d %s 2>&1', escapeshellarg(dirname($fullPath)), $lines, escapeshellarg(basename($fullPath)));
        
        $output = $this->executeCommand($command);
        if ($output === null || trim($output) === '') {
            throw new \RuntimeException('Erro ao ler arquivo via SSH. Verifique se o arquivo existe no servidor remoto: ' . $fullPath);
        }

        return rtrim($output, "\n");
    }
    
    /**
     * Lê conteúdo do arquivo via SFTP
     */
    private function readContentViaSftp(string $filePath, int $lines): ?string
    {
        if (!function_exists('ssh2_connect') || !function_exists('ssh2_sftp')) {
            return null;
        }
        
        try {
            $sftp = $this->getSftpConnection();
            if ($sftp === null) {
                return null;
            }
            
            // O filePath vem relativo ao basePath (ex: "laravel.log" ou "subdir/laravel.log")
            // Precisamos construir o caminho completo
            if (str_starts_with($filePath, '/')) {
                // Se já é absoluto, usa como está (mas verifica se está dentro do basePath)
                $fullPath = $filePath;
            } else {
                // Caminho relativo: combina basePath + filePath
                $fullPath = rtrim($this->basePath, '/') . '/' . ltrim($filePath, '/');
            }
            
            // Normaliza o caminho (remove barras duplicadas)
            $fullPath = preg_replace('/\/+/', '/', $fullPath);
            
            // Lê o arquivo via SFTP
            $sftpUrl = "ssh2.sftp://{$sftp}{$fullPath}";
            $content = @file_get_contents($sftpUrl);
            if ($content === false) {
                // Se falhar, tenta verificar se o arquivo existe
                $testHandle = @fopen($sftpUrl, 'r');
                if ($testHandle === false) {
                    // Arquivo não existe ou sem permissão
                    return null;
                }
                fclose($testHandle);
                // Se conseguiu abrir mas não ler, tenta novamente
                $content = @file_get_contents($sftpUrl);
                if ($content === false) {
                    return null;
                }
            }
            
            // Pega as últimas N linhas
            $allLines = explode("\n", $content);
            $lastLines = array_slice($allLines, -$lines);
            
            return implode("\n", $lastLines);
            
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Obtém conexão SFTP (cria nova conexão a cada vez, pois conexões estáticas podem expirar)
     */
    private function getSftpConnection()
    {
        $ssh2_connect = 'ssh2_connect';
        $ssh2_auth_pubkey_file = 'ssh2_auth_pubkey_file';
        $ssh2_auth_password = 'ssh2_auth_password';
        $ssh2_sftp = 'ssh2_sftp';
        
        $connection = @$ssh2_connect($this->host, $this->port);
        if (!$connection) {
            return null;
        }
        
        // Autenticação
        $authenticated = false;
        
        if ($this->privateKey !== null && $this->privateKey !== '') {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            if ($keyFile !== false) {
                file_put_contents($keyFile, $this->privateKey);
                chmod($keyFile, 0600);
                if (function_exists($ssh2_auth_pubkey_file)) {
                    $authenticated = @$ssh2_auth_pubkey_file($connection, $this->user, null, $keyFile, $this->privateKeyPassphrase);
                }
                @unlink($keyFile);
            }
        }
        
        if (!$authenticated && $this->password !== null && $this->password !== '') {
            if (function_exists($ssh2_auth_password)) {
                $authenticated = @$ssh2_auth_password($connection, $this->user, $this->password);
            }
        }
        
        if (!$authenticated) {
            return null;
        }
        
        // Conecta via SFTP
        $sftp = @$ssh2_sftp($connection);
        if (!$sftp) {
            return null;
        }
        
        return $sftp;
    }

    public function readEntries(string $filePath, int $page, int $perPage, string $levelFilter, string $search): array
    {
        // Tenta primeiro com SFTP (mais confiável)
        if (function_exists('ssh2_connect') && function_exists('ssh2_sftp')) {
            $content = $this->readFullContentViaSftp($filePath);
            if ($content !== null && $content !== '') {
                // Parseia o conteúdo em memória (sempre recebe string, nunca caminho de arquivo)
                return $this->parseLaravelLogEntries($content, $page, $perPage, $levelFilter, $search);
            }
        }
        
        // Fallback para SSH com comandos (NUNCA lê localmente - sempre usa executeCommand que executa SSH)
        // Garante que o caminho seja absoluto
        if (!str_starts_with($filePath, '/')) {
            $fullPath = $this->basePath . '/' . ltrim($filePath, '/');
        } else {
            $fullPath = $filePath;
        }
        
        // Sempre faz cd para o diretório base antes de executar o comando
        // Usa bash -c para garantir execução correta
        $command = sprintf('cd %s && cat %s 2>&1', escapeshellarg(dirname($fullPath)), escapeshellarg(basename($fullPath)));
        $content = $this->executeCommand($command);
        
        if ($content === null || trim($content) === '') {
            throw new \RuntimeException('Erro ao ler arquivo via SSH. Verifique se o arquivo existe no servidor remoto: ' . $fullPath);
        }

        // Parseia o conteúdo em memória (sempre recebe string, nunca caminho de arquivo)
        return $this->parseLaravelLogEntries($content, $page, $perPage, $levelFilter, $search);
    }
    
    /**
     * Lê conteúdo completo do arquivo via SFTP
     */
    private function readFullContentViaSftp(string $filePath): ?string
    {
        if (!function_exists('ssh2_connect') || !function_exists('ssh2_sftp')) {
            return null;
        }
        
        try {
            $sftp = $this->getSftpConnection();
            if ($sftp === null) {
                return null;
            }
            
            // O filePath vem relativo ao basePath (ex: "laravel.log" ou "subdir/laravel.log")
            // Precisamos construir o caminho completo
            if (str_starts_with($filePath, '/')) {
                // Se já é absoluto, usa como está (mas verifica se está dentro do basePath)
                $fullPath = $filePath;
            } else {
                // Caminho relativo: combina basePath + filePath
                $fullPath = rtrim($this->basePath, '/') . '/' . ltrim($filePath, '/');
            }
            
            // Normaliza o caminho (remove barras duplicadas)
            $fullPath = preg_replace('/\/+/', '/', $fullPath);
            
            // Lê o arquivo via SFTP
            $sftpUrl = "ssh2.sftp://{$sftp}{$fullPath}";
            $content = @file_get_contents($sftpUrl);
            if ($content === false) {
                return null;
            }
            
            return $content;
            
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Encontra o caminho do executável SSH
     */
    private function findSshPath(): string
    {
        // Tenta encontrar ssh no PATH primeiro
        $check = shell_exec('where ssh 2>&1'); // Windows
        if ($check === null || trim($check) === '' || str_contains($check, 'not found')) {
            $check = shell_exec('which ssh 2>&1'); // Linux/Git Bash
        }
        
        if ($check !== null && trim($check) !== '' && !str_contains($check, 'not found')) {
            $paths = explode("\n", trim($check));
            if (!empty($paths[0]) && file_exists(trim($paths[0]))) {
                return trim($paths[0]);
            }
        }

        // Locais comuns no Windows
        $commonPaths = [
            'C:\\Program Files\\Git\\usr\\bin\\ssh.exe',
            'C:\\Program Files\\Git\\bin\\ssh.exe',
            'C:\\Windows\\System32\\OpenSSH\\ssh.exe',
            'C:\\Program Files (x86)\\Git\\usr\\bin\\ssh.exe',
            'C:\\Program Files (x86)\\Git\\bin\\ssh.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Se não encontrar, retorna 'ssh' e deixa o sistema tentar
        return 'ssh';
    }

    private function executeCommand(string $command): ?string
    {
        $sshPath = $this->findSshPath();
        
        // Wraps command in bash -c to ensure proper execution on Linux
        $wrappedCommand = sprintf('bash -c %s', escapeshellarg($command));
        
        $sshCommand = sprintf(
            '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s %s 2>&1',
            escapeshellarg($sshPath),
            $this->port,
            escapeshellarg($this->user),
            escapeshellarg($this->host),
            escapeshellarg($wrappedCommand)
        );

        // Se houver chave privada, usa ela
        $keyFile = null;
        if ($this->privateKey !== null && $this->privateKey !== '') {
            $keyFile = $this->createTempKeyFile();
            if ($keyFile !== null) {
                $sshCommand = sprintf(
                    '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -i %s -p %d %s@%s %s 2>&1',
                    escapeshellarg($sshPath),
                    escapeshellarg($keyFile),
                    $this->port,
                    escapeshellarg($this->user),
                    escapeshellarg($this->host),
                    escapeshellarg($wrappedCommand)
                );
            }
        }

        // Se houver senha, tenta usar sshpass (se disponível)
        // Se não estiver disponível, tenta sem ele (pode funcionar se houver chave configurada)
        if ($this->password !== null && $this->password !== '' && $keyFile === null) {
            // Verifica se sshpass está disponível
            $sshpassCheck = shell_exec('which sshpass 2>&1');
            if ($sshpassCheck !== null && trim($sshpassCheck) !== '' && !str_contains($sshpassCheck, 'not found')) {
                $sshCommand = sprintf(
                    'sshpass -p %s %s',
                    escapeshellarg($this->password),
                    $sshCommand
                );
            }
            // Se sshpass não estiver disponível, tenta sem ele
            // Isso pode funcionar se houver chave SSH configurada no sistema
        }

        $output = shell_exec($sshCommand);
        
        // Se falhar, tenta com exec() como fallback
        if ($output === null) {
            $outputLines = [];
            $returnVar = 0;
            exec($sshCommand, $outputLines, $returnVar);
            $output = implode("\n", $outputLines);
        }
        
        // Limpa arquivo temporário de chave se criado
        if (isset($keyFile) && $keyFile !== null && file_exists($keyFile)) {
            @unlink($keyFile);
        }

        return $output;
    }

    private function createTempKeyFile(): ?string
    {
        if ($this->privateKey === null || $this->privateKey === '') {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
        if ($tempFile === false) {
            return null;
        }

        file_put_contents($tempFile, $this->privateKey);
        chmod($tempFile, 0600);

        return $tempFile;
    }

    private function buildFileTreeFromList(string $output): array
    {
        $lines = explode("\n", $output);
        $fileData = [];
        $hasTimestamp = false;
        
        // Verifica se o formato inclui timestamp (primeira linha)
        if (!empty($lines)) {
            $firstLine = trim($lines[0]);
            $hasTimestamp = str_contains($firstLine, '|') && is_numeric(explode('|', $firstLine)[0] ?? '');
        }
        
        // Processa linhas no formato "timestamp|caminho" ou apenas "caminho"
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            
            $mtime = false;
            $filePath = $line;
            
            // Se o formato inclui timestamp, extrai
            if ($hasTimestamp) {
                $parts = explode('|', $line, 2);
                if (count($parts) === 2) {
                    $mtime = (float) $parts[0];
                    $filePath = $parts[1];
                }
            }
            
            // Se a linha começa com ./, remove isso (resultado do find .)
            if (str_starts_with($filePath, './')) {
                $filePath = substr($filePath, 2);
            }
            
            // Se a linha começa com /, verifica se está dentro do basePath
            if (str_starts_with($filePath, '/')) {
                if (!str_starts_with($filePath, $this->basePath)) {
                    continue;
                }
                $relativePath = substr($filePath, strlen($this->basePath) + 1);
            } else {
                // Caminho relativo (do find .)
                $relativePath = $filePath;
            }

            if ($relativePath === '' || $relativePath === '.') {
                continue;
            }
            
            $fileData[] = [
                'path' => $relativePath,
                'mtime' => $mtime
            ];
        }
        
        // Ordena por data de modificação (mais recente primeiro) ou pelo nome
        usort($fileData, function($a, $b) {
            $mtimeA = $a['mtime'];
            $mtimeB = $b['mtime'];
            
            // Se conseguir obter as datas, ordena por data (mais recente primeiro)
            if ($mtimeA !== false && $mtimeB !== false) {
                return $mtimeB <=> $mtimeA; // Descendente (mais recente primeiro)
            }
            
            // Se não conseguir obter as datas, ordena pelo nome (que pode conter a data)
            return strnatcasecmp($b['path'], $a['path']); // Descendente (ordem reversa do nome)
        });
        
        $tree = [];
        $pathMap = [];

        foreach ($fileData as $fileInfo) {
            $relativePath = $fileInfo['path'];
            $mtime = $fileInfo['mtime'];

            $parts = array_filter(explode('/', $relativePath));
            if (empty($parts)) {
                continue;
            }

            $current = &$tree;

            foreach ($parts as $i => $part) {
                $pathSoFar = implode('/', array_slice($parts, 0, $i + 1));
                $key = $pathSoFar;
                $isFile = ($i === count($parts) - 1) && str_ends_with($part, '.log');

                if (!isset($pathMap[$key])) {
                    $item = [
                        'type' => $isFile ? 'file' : 'folder',
                        'name' => $part,
                        'path' => $pathSoFar,
                        'mtime' => $isFile ? $mtime : false
                    ];

                    if (!$isFile) {
                        $item['children'] = [];
                    }

                    $pathMap[$key] = $item;
                    $current[] = &$pathMap[$key];
                }

                if ($pathMap[$key]['type'] === 'folder') {
                    $current = &$pathMap[$key]['children'];
                } else {
                    break;
                }
            }
        }
        
        // Ordena recursivamente a árvore por data
        return $this->sortTreeByDate($tree);
    }
    
    /**
     * Ordena árvore recursivamente por data de modificação
     */
    private function sortTreeByDate(array $tree): array
    {
        // Ordena os itens do nível atual
        usort($tree, function($a, $b) {
            $mtimeA = $a['mtime'] ?? false;
            $mtimeB = $b['mtime'] ?? false;
            
            // Se conseguir obter as datas, ordena por data (mais recente primeiro)
            if ($mtimeA !== false && $mtimeB !== false) {
                return $mtimeB <=> $mtimeA; // Descendente (mais recente primeiro)
            }
            
            // Se não conseguir obter as datas, ordena pelo nome (que pode conter a data)
            return strnatcasecmp($b['name'], $a['name']); // Descendente (ordem reversa do nome)
        });
        
        // Ordena recursivamente os filhos
        foreach ($tree as &$item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->sortTreeByDate($item['children']);
            }
            // Remove mtime do resultado final (não é necessário no frontend)
            unset($item['mtime']);
        }
        
        return $tree;
    }

    private function parseLaravelLogEntries(string $content, int $page, int $perPage, string $levelFilter, string $search): array
    {
        $lines = explode("\n", $content);
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(.*?)\]\s+([^.]+)\.([A-Z]+):\s(.*)$/', $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = [
                    'datetime' => $m[1],
                    'env' => $m[2],
                    'level' => strtolower($m[3]),
                    'message' => trim($m[4]),
                    'context' => '',
                ];
            } else {
                if ($current !== null) {
                    $current['context'] .= $line . "\n";
                }
            }
        }
        if ($current !== null) {
            $entries[] = $current;
        }

        $entries = array_reverse($entries);

        if ($levelFilter !== '') {
            $entries = array_values(array_filter($entries, function ($e) use ($levelFilter) {
                return strtolower((string)$e['level']) === strtolower($levelFilter);
            }));
        }

        if ($search !== '') {
            $entries = array_values(array_filter($entries, function ($e) use ($search) {
                $haystack = strtolower((string)$e['message'] . ' ' . (string)$e['context']);
                return str_contains($haystack, strtolower($search));
            }));
        }

        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($entries, $offset, $perPage);

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'entries' => $slice,
        ];
    }
}

