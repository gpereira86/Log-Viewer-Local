<?php

declare(strict_types=1);

namespace LogViewer\Controller;

use LogViewer\Model\ProjectRepository;
use LogViewer\Service\ValidationService;
use LogViewer\Service\ResponseService;

class ProjectController
{
    private ProjectRepository $repo;

    public function __construct()
    {
        $this->repo = new ProjectRepository();
    }

    public function list(): void
    {
        try {
            $projects = $this->repo->all();
            ResponseService::json($projects);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao listar projetos: ' . $e->getMessage(), 500);
        }
    }

    public function save(): void
    {
        try {
            $input = ValidationService::sanitize($_POST);
            $type = trim($input['type'] ?? 'local');
            
            $project = [
                'id' => $input['id'] ?? '',
                'name' => trim($input['name'] ?? ''),
                'type' => $type,
            ];

            // Normaliza caminho SSH se necessário
            if ($type === 'ssh') {
                $rawPath = trim($input['path'] ?? '');
                $project['path'] = $this->normalizeSshPath($rawPath);
                
                $project['ssh_host'] = trim($input['ssh_host'] ?? '');
                $project['ssh_port'] = (int)($input['ssh_port'] ?? 22);
                $project['ssh_user'] = trim($input['ssh_user'] ?? '');
                $project['ssh_password'] = trim($input['ssh_password'] ?? '') ?: null;
                $project['ssh_private_key'] = trim($input['ssh_private_key'] ?? '') ?: null;
                $project['ssh_private_key_passphrase'] = trim($input['ssh_private_key_passphrase'] ?? '') ?: null;
            } elseif ($type === 'url') {
                $project['url'] = trim($input['url'] ?? '');
                $project['url_username'] = trim($input['url_username'] ?? '') ?: null;
                $project['url_password'] = trim($input['url_password'] ?? '') ?: null;
                $project['url_api_key'] = trim($input['url_api_key'] ?? '') ?: null;
                $project['url_api_key_header'] = trim($input['url_api_key_header'] ?? 'X-API-Key') ?: 'X-API-Key';
            } else {
                // Normaliza caminho local (converte Windows para formato compatível)
                $rawPath = trim($input['path'] ?? '');
                $project['path'] = $this->normalizeLocalPath($rawPath);
            }

            // Valida os dados
            $errors = ValidationService::validateProject($project);
            if (!empty($errors)) {
                ResponseService::error('Dados inválidos', 400, ['errors' => $errors]);
                return;
            }
            
            // Validação adicional para projetos locais: verifica se o caminho existe e é acessível
            if ($type === 'local' && !empty($project['path']) && is_string($project['path'])) {
                $testPath = (string)$project['path'];
                
                // Tenta mapear o caminho se estiver em Docker
                if (class_exists('LogViewer\Config\PathMapper')) {
                    $testPath = \LogViewer\Config\PathMapper::mapPath($testPath);
                }
                
                // Verifica se o caminho existe e é um diretório
                if (!is_dir($testPath)) {
                    $errors['path'] = 'O caminho especificado não existe ou não é um diretório acessível. Use o botão "Navegar" ao lado do campo para selecionar um caminho válido da lista.';
                    ResponseService::error('Dados inválidos', 400, ['errors' => $errors]);
                    return;
                }
                
                // Verifica se o diretório é legível
                if (!is_readable($testPath)) {
                    $errors['path'] = 'O diretório especificado não tem permissão de leitura. Use o botão "Navegar" para selecionar um diretório acessível.';
                    ResponseService::error('Dados inválidos', 400, ['errors' => $errors]);
                    return;
                }
            }

            $this->repo->save($project);
            
            // Remove campos sensíveis da resposta
            $responseProject = $project;
            unset($responseProject['ssh_password'], $responseProject['ssh_private_key'], 
                  $responseProject['ssh_private_key_passphrase'], $responseProject['url_password'], 
                  $responseProject['url_api_key']);
            
            ResponseService::success(['project' => $responseProject], 'Projeto salvo com sucesso.');
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao salvar projeto: ' . $e->getMessage(), 500);
        }
    }

    public function delete(): void
    {
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                ResponseService::error('ID é obrigatório.', 400);
                return;
            }
            
            $this->repo->delete($id);
            ResponseService::success(null, 'Projeto excluído com sucesso.');
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao excluir projeto: ' . $e->getMessage(), 500);
        }
    }

    public function testSsh(): void
    {
        try {
            $input = ValidationService::sanitize($_POST);
            
            $host = trim($input['ssh_host'] ?? '');
            $port = (int)($input['ssh_port'] ?? 22);
            $user = trim($input['ssh_user'] ?? '');
            $password = trim($input['ssh_password'] ?? '') ?: null;
            $privateKey = trim($input['ssh_private_key'] ?? '') ?: null;
            $privateKeyPassphrase = trim($input['ssh_private_key_passphrase'] ?? '') ?: null;
            $path = trim($input['path'] ?? '');

            if ($host === '' || $user === '') {
                ResponseService::error('Host e usuário são obrigatórios.', 400);
                return;
            }

            $result = $this->testSshConnection($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $path);
            ResponseService::json($result);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao testar conexão SSH: ' . $e->getMessage(), 500);
        }
    }

    public function browseSsh(): void
    {
        try {
            $input = ValidationService::sanitize($_POST);
            
            $host = trim($input['ssh_host'] ?? '');
            $port = (int)($input['ssh_port'] ?? 22);
            $user = trim($input['ssh_user'] ?? '');
            $password = trim($input['ssh_password'] ?? '') ?: null;
            $privateKey = trim($input['ssh_private_key'] ?? '') ?: null;
            $privateKeyPassphrase = trim($input['ssh_private_key_passphrase'] ?? '') ?: null;
            $path = trim($input['path'] ?? '/var/www');

            if ($host === '' || $user === '') {
                ResponseService::error('Host e usuário são obrigatórios.', 400);
                return;
            }

            $result = $this->listSshDirectories($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $path);
            ResponseService::json($result);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao listar diretórios: ' . $e->getMessage(), 500);
        }
    }

    public function browseLocal(): void
    {
        try {
            $input = ValidationService::sanitize($_POST);
            $path = trim($input['path'] ?? '/');
            
            // Normaliza o caminho
            $path = str_replace('\\', '/', $path);
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
            
            // Validação de segurança: apenas permite caminhos absolutos
            if (!str_starts_with($path, '/')) {
                ResponseService::error('Apenas caminhos absolutos são permitidos.', 400);
                return;
            }

            // Verifica se o caminho existe e é um diretório
            if (!is_dir($path)) {
                // Se não existe, retorna erro mas com sugestões de diretórios comuns
                $commonDirs = $this->getCommonAccessibleDirectories();
                ResponseService::json([
                    'success' => false,
                    'message' => 'Diretório não encontrado ou não mapeado no Docker: ' . $path,
                    'current_path' => $path,
                    'items' => [],
                    'suggestions' => $commonDirs,
                    'hint' => 'Este diretório não está acessível. Verifique se foi mapeado no docker-compose.yml ou use um dos diretórios sugeridos.'
                ]);
                return;
            }

            if (!is_readable($path)) {
                ResponseService::error('Sem permissão para ler o diretório: ' . $path, 403);
                return;
            }

            $items = [];
            $handle = @opendir($path);
            if ($handle === false) {
                ResponseService::error('Não foi possível abrir o diretório.', 500);
                return;
            }

            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $fullPath = $path === '/' ? '/' . $file : $path . '/' . $file;
                $isDir = @is_dir($fullPath);

                $items[] = [
                    'name' => $file,
                    'path' => $fullPath,
                    'type' => $isDir ? 'directory' : 'file',
                    'accessible' => $isDir ? @is_readable($fullPath) : true
                ];
            }

            closedir($handle);

            // Ordena: diretórios primeiro, depois arquivos
            usort($items, function($a, $b) {
                if ($a['type'] === $b['type']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $a['type'] === 'directory' ? -1 : 1;
            });

            // Se estiver na raiz, adiciona diretórios comuns como sugestões
            $suggestions = [];
            if ($path === '/') {
                $suggestions = $this->getCommonAccessibleDirectories();
            }

            ResponseService::json([
                'success' => true,
                'current_path' => $path,
                'items' => $items,
                'suggestions' => $suggestions
            ]);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao listar diretórios: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retorna lista de diretórios comuns que existem e são acessíveis
     * @return array<int, array<string, string>>
     */
    private function getCommonAccessibleDirectories(): array
    {
        $commonPaths = [
            '/htdocs',
            '/var/log',
            '/var/www',
            '/home',
            '/opt',
            '/usr/local',
            '/tmp',
            '/root',
            '/mnt',
            '/media',
            '/srv',
            '/data',
            '/logs',
            '/app',
            '/workspace',
            '/projects',
        ];

        $accessible = [];
        foreach ($commonPaths as $dir) {
            if (@is_dir($dir) && @is_readable($dir)) {
                $accessible[] = [
                    'name' => basename($dir) ?: $dir,
                    'path' => $dir,
                    'type' => 'directory',
                    'description' => $this->getDirectoryDescription($dir)
                ];
            }
        }

        return $accessible;
    }

    /**
     * Retorna descrição amigável para um diretório comum
     */
    private function getDirectoryDescription(string $path): string
    {
        $descriptions = [
            '/htdocs' => 'Diretório comum do XAMPP/WAMP mapeado',
            '/var/log' => 'Logs do sistema Linux',
            '/var/www' => 'Diretório web padrão',
            '/home' => 'Diretório home dos usuários',
            '/opt' => 'Software opcional',
            '/usr/local' => 'Programas locais',
            '/tmp' => 'Arquivos temporários',
            '/root' => 'Diretório home do root',
            '/mnt' => 'Pontos de montagem',
            '/media' => 'Mídia removível',
            '/srv' => 'Dados de serviços',
            '/data' => 'Dados da aplicação',
            '/logs' => 'Logs da aplicação',
            '/app' => 'Aplicação',
            '/workspace' => 'Workspace',
            '/projects' => 'Projetos',
        ];

        return $descriptions[$path] ?? 'Diretório acessível';
    }

    /**
     * Lista diretórios via SFTP (mais confiável que SSH para navegação)
     * @return array<string, mixed>
     */
    private function listSshDirectories(
        string $host,
        int $port,
        string $user,
        ?string $password,
        ?string $privateKey,
        ?string $privateKeyPassphrase,
        string $path
    ): array {
        // Tenta primeiro com SFTP (mais confiável para listar arquivos e não precisa de sshpass)
        if (function_exists('ssh2_connect')) {
            $result = $this->listSftpDirectories($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $path);
            if ($result !== null) {
                // Se SFTP funcionou, retorna
                if (isset($result['success']) && $result['success']) {
                    return $result;
                }
                // Se SFTP falhou mas retornou um erro específico, tenta SSH como fallback
                // mas mantém a mensagem de erro do SFTP se for mais informativa
            }
        }
        
        // Fallback para SSH com comandos (pode falhar se sshpass não estiver disponível)
        $result = $this->listSshDirectoriesViaCommand($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $path);
        
        // Adiciona informação sobre SFTP se falhar
        if (!$result['success']) {
            if (!function_exists('ssh2_connect')) {
                $result['message'] = ($result['message'] ?? 'Erro desconhecido') . ' IMPORTANTE: Para usar SSH com senha no Windows, instale a extensão PHP ssh2 (não precisa de sshpass).';
            } else {
                // SFTP está disponível mas falhou, então o problema pode ser autenticação ou caminho
                $result['message'] = ($result['message'] ?? 'Erro desconhecido') . ' Dica: Verifique se o caminho está correto e se há permissão de acesso.';
            }
        }
        
        return $result;
    }

    /**
     * Lista diretórios via SFTP usando ssh2
     * @return array<string, mixed>|null
     */
    private function listSftpDirectories(
        string $host,
        int $port,
        string $user,
        ?string $password,
        ?string $privateKey,
        ?string $privateKeyPassphrase,
        string $path
    ): ?array {
        if (!function_exists('ssh2_connect') || !function_exists('ssh2_sftp')) {
            return null; // Extensão ssh2 não disponível
        }

        try {
            // Usa namespace global para funções ssh2
            $ssh2_connect = 'ssh2_connect';
            $ssh2_auth_pubkey_file = 'ssh2_auth_pubkey_file';
            $ssh2_auth_password = 'ssh2_auth_password';
            $ssh2_sftp = 'ssh2_sftp';
            $ssh2_sftp_stat = 'ssh2_sftp_stat';
            
            $connection = @$ssh2_connect($host, $port);
            if (!$connection) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível conectar ao servidor SSH',
                    'items' => [],
                    'current_path' => $path
                ];
            }

            // Autenticação
            $authenticated = false;
            $authError = '';
            
            if ($privateKey !== null && $privateKey !== '') {
                // Salva chave em arquivo temporário
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                if ($keyFile !== false) {
                    file_put_contents($keyFile, $privateKey);
                    chmod($keyFile, 0600);
                    // Tenta autenticar com chave privada
                    if (function_exists($ssh2_auth_pubkey_file)) {
                        $authenticated = @$ssh2_auth_pubkey_file($connection, $user, null, $keyFile, $privateKeyPassphrase);
                        if (!$authenticated) {
                            $authError = 'Falha na autenticação com chave privada';
                        }
                    }
                    @unlink($keyFile);
                }
            }
            
            if (!$authenticated && $password !== null && $password !== '') {
                // Tenta autenticar com senha
                if (function_exists($ssh2_auth_password)) {
                    $authenticated = @$ssh2_auth_password($connection, $user, $password);
                    if (!$authenticated) {
                        $authError = 'Falha na autenticação com senha';
                    }
                }
            }

            if (!$authenticated) {
                return [
                    'success' => false,
                    'message' => $authError ?: 'Falha na autenticação SSH',
                    'items' => [],
                    'current_path' => $path
                ];
            }

            // Conecta via SFTP
            $sftp = @$ssh2_sftp($connection);
            if (!$sftp) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível inicializar a conexão SFTP',
                    'items' => [],
                    'current_path' => $path
                ];
            }

            // Normaliza o caminho
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }

            // Lista arquivos via SFTP
            $handle = @opendir("ssh2.sftp://{$sftp}{$path}");
            if (!$handle) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível abrir o diretório: ' . $path . '. Verifique se o caminho está correto.',
                    'items' => [],
                    'current_path' => $path
                ];
            }

            $items = [];
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $fullPath = $path === '/' ? '/' . $file : $path . '/' . $file;
                $fileInfo = false;
                if (function_exists($ssh2_sftp_stat)) {
                    $fileInfo = @$ssh2_sftp_stat($sftp, $fullPath);
                }
                
                $isDir = false;
                if ($fileInfo !== false && isset($fileInfo['mode'])) {
                    $isDir = ($fileInfo['mode'] & 0040000) === 0040000;
                } else {
                    // Fallback: tenta abrir como diretório
                    $testHandle = @opendir("ssh2.sftp://{$sftp}{$fullPath}");
                    if ($testHandle) {
                        $isDir = true;
                        closedir($testHandle);
                    }
                }

                $items[] = [
                    'name' => $file,
                    'path' => $fullPath,
                    'type' => $isDir ? 'directory' : 'file'
                ];
            }

            closedir($handle);

            // Ordena: diretórios primeiro, depois arquivos
            usort($items, function($a, $b) {
                if ($a['type'] === $b['type']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $a['type'] === 'directory' ? -1 : 1;
            });

            return [
                'success' => true,
                'current_path' => $path,
                'items' => $items
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erro SFTP: ' . $e->getMessage(),
                'items' => [],
                'current_path' => $path
            ];
        }
    }

    /**
     * Lista diretórios via SSH com comandos (fallback)
     * @return array<string, mixed>
     */
    private function listSshDirectoriesViaCommand(
        string $host,
        int $port,
        string $user,
        ?string $password,
        ?string $privateKey,
        ?string $privateKeyPassphrase,
        string $path
    ): array {
        // Normaliza o caminho
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Tenta listar o diretório diretamente - se funcionar, o diretório existe e tem permissão
        // Não fazemos verificação prévia para evitar falsos negativos

        // Tenta primeiro com ls -la (mais confiável)
        // Cada comando SSH inicia na raiz, então sempre fazemos cd primeiro
        // Usa ; ao invés de && para garantir execução mesmo se houver algum problema
        $command = sprintf('cd %s ; ls -la 2>&1', escapeshellarg($path));
        $output = $this->executeSshCommand($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $command);
        
        // Remove avisos sobre "ssh: not found" ANTES de verificar erros
        if ($output !== null && str_contains($output, 'ssh: not found')) {
            $lines = explode("\n", $output);
            $cleanLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignora linhas que são apenas o aviso
                if (!empty($line) && !str_contains($line, 'ssh: not found') && !str_contains($line, 'sh: 1: ssh:')) {
                    $cleanLines[] = $line;
                }
            }
            if (!empty($cleanLines)) {
                $output = implode("\n", $cleanLines);
            } else {
                $output = null; // Se só tinha avisos, trata como vazio
            }
        }
        
        // Se falhar, tenta ls simples
        if ($output === null || trim($output) === '' || (str_contains($output, 'No such file') && !str_contains($output, 'total'))) {
            $command = sprintf('cd %s ; ls -1 2>&1', escapeshellarg($path));
            $output2 = $this->executeSshCommand($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $command);
            
            // Remove avisos também do segundo comando
            if ($output2 !== null && str_contains($output2, 'ssh: not found')) {
                $lines = explode("\n", $output2);
                $cleanLines = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && !str_contains($line, 'ssh: not found') && !str_contains($line, 'sh: 1: ssh:')) {
                        $cleanLines[] = $line;
                    }
                }
                if (!empty($cleanLines)) {
                    $output2 = implode("\n", $cleanLines);
                } else {
                    $output2 = null;
                }
            }
            
            if ($output2 !== null && trim($output2) !== '' && !str_contains($output2, 'No such file') && !str_contains($output2, 'cannot access')) {
                $output = $output2;
            }
        }

        if ($output === null) {
            return [
                'success' => false,
                'message' => 'Não foi possível executar o comando de listagem. Verifique se o SSH está instalado e acessível.',
                'items' => [],
                'current_path' => $path
            ];
        }

        $output = trim($output);
        
        // Se o output está vazio após limpeza, retorna erro
        if ($output === '' || strlen($output) < 5) {
            return [
                'success' => false,
                'message' => 'Comando executado mas retornou vazio. Verifique se o diretório existe e se há permissão de acesso.',
                'items' => [],
                'current_path' => $path
            ];
        }
        
        // Se houver erro no output
        if (str_contains($output, 'Permission denied') || str_contains($output, 'permission denied')) {
            return [
                'success' => false,
                'message' => 'Permissão negada para acessar o diretório: ' . $path,
                'items' => [],
                'current_path' => $path
            ];
        }
        
        // Verifica se é realmente um erro de "não encontrado" (não apenas parte de outro texto)
        if ((str_contains($output, 'No such file') || str_contains($output, 'cannot access')) && 
            !str_contains($output, 'total') && 
            !str_contains($output, 'd') && 
            !str_contains($output, '-')) {
            return [
                'success' => false,
                'message' => 'Diretório não encontrado: ' . $path,
                'items' => [],
                'current_path' => $path
            ];
        }

        $lines = explode("\n", $output);
        $items = [];
        $currentPath = $path;

        // Detecta se é formato detalhado (ls -la) - procura por "total" ou linhas que começam com d ou -
        $isDetailedList = str_contains($output, 'total') || preg_match('/^[d-]([r-][w-][x-]){3}\s+\d+/m', $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'total')) {
                continue;
            }

            $isDir = false;
            $name = '';

            // Formato DIR: ou FILE: (do comando otimizado)
            if (str_starts_with($line, 'DIR:') || str_starts_with($line, 'FILE:')) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $isDir = $parts[0] === 'DIR';
                    $name = $parts[1];
                } else {
                    continue;
                }
            }
            // Formato ls -la (detalhado) - mais comum no Linux/Debian
            else if ($isDetailedList && preg_match('/^([d-])([r-][w-][x-]){3}\s+\d+/', $line)) {
                $firstChar = $line[0];
                $isDir = $firstChar === 'd';
                
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 9) {
                    // Formato padrão: perms links owner group size month day time name
                    // O nome pode ter espaços, então pegamos tudo a partir do 9º campo
                    $name = implode(' ', array_slice($parts, 8));
                } else if (count($parts) >= 4) {
                    // Formato alternativo - pega o último campo como nome
                    $name = end($parts);
                } else {
                    continue;
                }
            }
            // Formato simples (fallback)
            else {
                $name = $line;
                // Se não soubermos, verifica
                $fullPath = $currentPath === '/' ? '/' . $name : $currentPath . '/' . $name;
                // Sempre faz cd antes de testar, pois cada comando SSH inicia na raiz
                $checkCommand = sprintf('cd %s ; test -d %s && echo "DIR" || echo "FILE"', escapeshellarg($currentPath), escapeshellarg($name));
                $checkOutput = $this->executeSshCommand($host, $port, $user, $password, $privateKey, $privateKeyPassphrase, $checkCommand);
                if ($checkOutput !== null) {
                    $checkOutput = trim($checkOutput);
                    // Remove avisos
                    $checkOutput = preg_replace('/.*?ssh:\s*not\s*found[^\n]*\n?/i', '', $checkOutput);
                    $checkOutput = trim($checkOutput);
                    $isDir = ($checkOutput === 'DIR');
                }
            }

            // Ignora . e ..
            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $fullPath = $currentPath === '/' ? '/' . $name : $currentPath . '/' . $name;

            $items[] = [
                'name' => $name,
                'path' => $fullPath,
                'type' => $isDir ? 'directory' : 'file'
            ];
        }

        // Ordena: diretórios primeiro, depois arquivos
        usort($items, function($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcmp($a['name'], $b['name']);
            }
            return $a['type'] === 'directory' ? -1 : 1;
        });

        return [
            'success' => true,
            'current_path' => $currentPath,
            'items' => $items
        ];
    }

    /**
     * Normaliza caminho local (converte Windows para Unix quando possível)
     */
    private function normalizeLocalPath(string $path): string
    {
        $path = trim($path);
        
        if ($path === '') {
            return '';
        }
        
        // Normaliza barras para /
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        $path = rtrim($path, '/');
        
        // Converte caminhos Windows comuns para formato Unix
        // Exemplo: C:/xampp/htdocs/... -> /htdocs/...
        if (preg_match('/^[A-Za-z]:\/xampp\/htdocs\/(.+)$/i', $path, $matches)) {
            return '/htdocs/' . $matches[1];
        }
        
        // Converte outros caminhos Windows comuns
        // Exemplo: C:/htdocs/... -> /htdocs/...
        if (preg_match('/^[A-Za-z]:\/htdocs\/(.+)$/i', $path, $matches)) {
            return '/htdocs/' . $matches[1];
        }
        
        // Se for caminho Windows que não corresponde aos padrões acima,
        // tenta converter para formato Unix genérico (remove a letra do drive)
        // Exemplo: C:/outro/caminho -> /outro/caminho
        if (preg_match('/^[A-Za-z]:\/(.+)$/i', $path, $matches)) {
            return '/' . $matches[1];
        }
        
        // Se já está em formato Unix, apenas retorna
        return $path;
    }

    /**
     * Normaliza o caminho SSH removendo protocolos e informações de conexão
     */
    private function normalizeSshPath(string $path): string
    {
        $path = trim($path);
        
        if ($path === '') {
            return '';
        }
        
        // Remove protocolos (sftp://, ssh://, http://, https://)
        $path = preg_replace('/^(sftp|ssh|http|https):\/\//i', '', $path);
        
        // Remove informações de usuário@host se houver
        // Formato: user@host/path ou user@host:port/path
        if (preg_match('/^[^@]+@[^\/:]+(?::\d+)?\/(.+)$/', $path, $matches)) {
            $path = '/' . $matches[1];
        }
        
        // Se ainda tiver @, pode ser que o caminho esteja mal formatado
        // Tenta extrair apenas a parte do caminho
        if (str_contains($path, '@') && str_contains($path, '/')) {
            $parts = explode('/', $path);
            $pathParts = [];
            $foundSlash = false;
            foreach ($parts as $part) {
                if ($foundSlash || (!str_contains($part, '@') && $part !== '')) {
                    $foundSlash = true;
                    if ($part !== '') {
                        $pathParts[] = $part;
                    }
                }
            }
            if (!empty($pathParts)) {
                $path = '/' . implode('/', $pathParts);
            }
        }
        
        // Garante que comece com /
        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }
        
        // Remove barras duplicadas e normaliza
        $path = preg_replace('/\/+/', '/', $path);
        $path = rtrim($path, '/');
        
        // Se ficou vazio, retorna /
        if ($path === '') {
            $path = '/';
        }
        
        return $path;
    }

    /**
     * Encontra o caminho do executável SSH
     */
    private function findSshPath(): string
    {
        // Tenta encontrar ssh no PATH primeiro
        $sshPath = 'ssh';
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

    /**
     * Executa um comando SSH
     */
    private function executeSshCommand(
        string $host,
        int $port,
        string $user,
        ?string $password,
        ?string $privateKey,
        ?string $privateKeyPassphrase,
        string $command
    ): ?string {
        $sshPath = $this->findSshPath();
        
        $sshCommand = sprintf(
            '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -p %d %s@%s %s 2>&1',
            escapeshellarg($sshPath),
            $port,
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($command)
        );

        // Se houver chave privada, usa ela
        $keyFile = null;
        if ($privateKey !== null && $privateKey !== '') {
            $keyFile = $this->createTempKeyFile($privateKey);
            if ($keyFile !== null) {
                $sshCommand = sprintf(
                    '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -i %s -p %d %s@%s %s 2>&1',
                    escapeshellarg($sshPath),
                    escapeshellarg($keyFile),
                    $port,
                    escapeshellarg($user),
                    escapeshellarg($host),
                    escapeshellarg($command)
                );
            }
        }

        // Se houver senha, tenta usar sshpass (se disponível)
        // Se não estiver disponível, tenta sem ele (pode funcionar se houver chave configurada)
        if ($password !== null && $password !== '' && $keyFile === null) {
            // Verifica se sshpass está disponível
            $sshpassCheck = shell_exec('which sshpass 2>&1');
            if ($sshpassCheck !== null && trim($sshpassCheck) !== '' && !str_contains($sshpassCheck, 'not found')) {
                $sshCommand = sprintf(
                    'sshpass -p %s %s',
                    escapeshellarg($password),
                    $sshCommand
                );
            }
            // Se sshpass não estiver disponível, tenta sem ele
            // Isso pode funcionar se houver chave SSH configurada no sistema
        }

        // Executa o comando e captura tanto stdout quanto stderr
        // Usa exec() que é mais confiável que shell_exec()
        $outputLines = [];
        $returnVar = 0;
        exec($sshCommand, $outputLines, $returnVar);
        $output = implode("\n", $outputLines);
        
        // Se ainda estiver vazio, tenta shell_exec como fallback
        if (empty($output)) {
            $output = shell_exec($sshCommand);
        }

        // Limpa arquivo temporário de chave se criado
        if ($keyFile !== null && file_exists($keyFile)) {
            @unlink($keyFile);
        }

        return $output;
    }

    /**
     * Testa a conexão SSH
     * @return array<string, mixed>
     */
    private function testSshConnection(
        string $host,
        int $port,
        string $user,
        ?string $password,
        ?string $privateKey,
        ?string $privateKeyPassphrase,
        string $path
    ): array {
        // Comando simples para testar conexão e verificar se o diretório existe
        $testCommand = 'echo "OK"';
        if ($path !== '') {
            $testCommand = sprintf('test -d %s && echo "OK" || echo "DIR_NOT_FOUND"', escapeshellarg($path));
        }

        $sshPath = $this->findSshPath();
        
        $sshCommand = sprintf(
            '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -p %d %s@%s %s 2>&1',
            escapeshellarg($sshPath),
            $port,
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($testCommand)
        );

        // Se houver chave privada, usa ela
        $keyFile = null;
        if ($privateKey !== null && $privateKey !== '') {
            $keyFile = $this->createTempKeyFile($privateKey);
            if ($keyFile !== null) {
                $sshCommand = sprintf(
                    '%s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -i %s -p %d %s@%s %s 2>&1',
                    escapeshellarg($sshPath),
                    escapeshellarg($keyFile),
                    $port,
                    escapeshellarg($user),
                    escapeshellarg($host),
                    escapeshellarg($testCommand)
                );
            }
        }

        // Se houver senha, usa sshpass (se disponível)
        $output = null;
        if ($password !== null && $password !== '' && $keyFile === null) {
            // Verifica se sshpass está disponível (pode não estar no Windows)
            $sshpassCheck = shell_exec('which sshpass 2>&1');
            if ($sshpassCheck !== null && trim($sshpassCheck) !== '') {
                $sshCommand = sprintf(
                    'sshpass -p %s %s',
                    escapeshellarg($password),
                    $sshCommand
                );
                $output = shell_exec($sshCommand);
            } else {
                // No Windows, tenta sem sshpass (pode funcionar se houver chave configurada)
                $output = shell_exec($sshCommand);
                if ($output === null || str_contains($output, 'Permission denied') || str_contains($output, 'password:')) {
                    return [
                        'success' => false,
                        'message' => 'Para usar autenticação por senha, é necessário instalar o sshpass. No Windows, você pode usar chave privada SSH ou instalar sshpass via WSL/Git Bash.'
                    ];
                }
            }
        } else {
            $output = shell_exec($sshCommand);
        }

        // Limpa arquivo temporário de chave se criado
        if ($keyFile !== null && file_exists($keyFile)) {
            @unlink($keyFile);
        }

        if ($output === null) {
            return [
                'success' => false,
                'message' => 'Não foi possível conectar ao servidor SSH. Verifique se o SSH está instalado e se as credenciais estão corretas.'
            ];
        }

        $output = trim($output);

        // Verifica se a conexão foi bem-sucedida
        if (str_contains($output, 'Permission denied') || str_contains($output, 'Authentication failed') || str_contains($output, 'password:')) {
            return [
                'success' => false,
                'message' => 'Falha na autenticação. Verifique usuário, senha ou chave privada. Se estiver usando senha, pode ser necessário instalar sshpass.'
            ];
        }

        if (str_contains($output, 'Connection refused') || str_contains($output, 'Could not resolve hostname') || str_contains($output, 'Name or service not known')) {
            return [
                'success' => false,
                'message' => 'Não foi possível conectar ao host. Verifique o endereço e a porta.'
            ];
        }

        if (str_contains($output, 'Connection timed out') || str_contains($output, 'Network is unreachable')) {
            return [
                'success' => false,
                'message' => 'Timeout na conexão. Verifique se o servidor está acessível e se a porta está correta.'
            ];
        }

        if (str_contains($output, 'DIR_NOT_FOUND')) {
            return [
                'success' => false,
                'message' => 'Conexão SSH bem-sucedida, mas o diretório especificado não foi encontrado: ' . $path
            ];
        }

        if ($output === 'OK' || str_contains($output, 'OK')) {
            $message = 'Conexão SSH testada com sucesso!';
            if ($path !== '') {
                $message .= ' O diretório foi encontrado.';
            }
            return [
                'success' => true,
                'message' => $message
            ];
        }

        // Se chegou aqui, a conexão funcionou mas pode ter algum aviso
        return [
            'success' => true,
            'message' => 'Conexão SSH estabelecida. Aviso: ' . $output
        ];
    }

    private function createTempKeyFile(string $privateKey): ?string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
        if ($tempFile === false) {
            return null;
        }

        file_put_contents($tempFile, $privateKey);
        chmod($tempFile, 0600);

        return $tempFile;
    }
}


