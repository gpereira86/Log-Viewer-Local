<?php

declare(strict_types=1);

namespace LogViewer\Service;

class ValidationService
{
    /**
     * Valida dados de um projeto
     * @param array<string, mixed> $data
     * @return array<string, string> Array de erros (vazio se válido)
     */
    public static function validateProject(array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = 'Nome é obrigatório.';
        }

        $type = trim($data['type'] ?? 'local');

        if ($type === 'local') {
            $path = trim($data['path'] ?? '');
            if (empty($path)) {
                $errors['path'] = 'Caminho é obrigatório para projetos locais.';
            }
        } elseif ($type === 'ssh') {
            $host = trim($data['ssh_host'] ?? '');
            $user = trim($data['ssh_user'] ?? '');
            $path = trim($data['path'] ?? '');

            if (empty($host)) {
                $errors['ssh_host'] = 'Host é obrigatório para projetos SSH.';
            }

            if (empty($user)) {
                $errors['ssh_user'] = 'Usuário é obrigatório para projetos SSH.';
            }

            if (empty($path)) {
                $errors['path'] = 'Caminho é obrigatório para projetos SSH.';
            } elseif (!str_starts_with($path, '/')) {
                $errors['path'] = 'O caminho deve ser absoluto (começar com /). Exemplo: /var/www/...';
            }

            $port = (int)($data['ssh_port'] ?? 22);
            if ($port < 1 || $port > 65535) {
                $errors['ssh_port'] = 'Porta deve estar entre 1 e 65535.';
            }

            // Valida que pelo menos uma forma de autenticação foi fornecida
            $password = trim($data['ssh_password'] ?? '');
            $privateKey = trim($data['ssh_private_key'] ?? '');
            if (empty($password) && empty($privateKey)) {
                $errors['auth'] = 'É necessário fornecer senha ou chave privada SSH.';
            }
        } elseif ($type === 'url') {
            $url = trim($data['url'] ?? '');
            if (empty($url)) {
                $errors['url'] = 'URL é obrigatória para projetos via URL.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['url'] = 'URL inválida.';
            }
        }

        return $errors;
    }

    /**
     * Sanitiza dados de entrada
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } elseif (is_int($value) || is_float($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
