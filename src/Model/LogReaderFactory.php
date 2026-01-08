<?php

declare(strict_types=1);

namespace LogViewer\Model;

class LogReaderFactory
{
    /**
     * Cria um leitor de logs apropriado baseado no tipo de projeto
     * @param array<string, mixed> $project Dados do projeto
     * @return LogReaderInterface Leitor de logs
     */
    public static function create(array $project): LogReaderInterface
    {
        // Compatibilidade: se não tiver tipo definido, assume local
        $type = $project['type'] ?? 'local';
        
        // Se for local mas não tiver path, tenta inferir do campo 'path'
        if ($type === 'local' && empty($project['path'])) {
            throw new \RuntimeException('Caminho não especificado para projeto local.');
        }

        switch ($type) {
            case 'ssh':
                // Normaliza o caminho (remove protocolos se houver)
                $path = $project['path'] ?? '';
                $path = preg_replace('/^(sftp|ssh|http|https):\/\//i', '', $path);
                if (preg_match('/^[^@]+@[^\/:]+(?::\d+)?\/(.+)$/', $path, $matches)) {
                    $path = '/' . $matches[1];
                }
                if ($path !== '' && !str_starts_with($path, '/')) {
                    $path = '/' . ltrim($path, '/');
                }
                $path = preg_replace('/\/+/', '/', $path);
                $path = rtrim($path, '/');
                if ($path === '') {
                    $path = '/';
                }
                
                return new SshLogReader(
                    $project['ssh_host'] ?? '',
                    (int)($project['ssh_port'] ?? 22),
                    $project['ssh_user'] ?? '',
                    $path,
                    $project['ssh_password'] ?? null,
                    $project['ssh_private_key'] ?? null,
                    $project['ssh_private_key_passphrase'] ?? null
                );

            case 'url':
                return new UrlLogReader(
                    $project['url'] ?? '',
                    $project['url_username'] ?? null,
                    $project['url_password'] ?? null,
                    $project['url_api_key'] ?? null,
                    $project['url_api_key_header'] ?? null
                );

            case 'local':
            default:
                return new LocalLogReader($project['path'] ?? '');
        }
    }
}

