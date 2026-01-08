<?php

declare(strict_types=1);

namespace LogViewer\Model;

class UrlLogReader implements LogReaderInterface
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private ?string $apiKey;
    private ?string $apiKeyHeader;

    public function __construct(
        string $baseUrl,
        ?string $username = null,
        ?string $password = null,
        ?string $apiKey = null,
        ?string $apiKeyHeader = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->apiKey = $apiKey;
        $this->apiKeyHeader = $apiKeyHeader ?? 'X-API-Key';
    }

    public function listFiles(): array
    {
        // Para URLs, assumimos que há um endpoint que retorna a lista de arquivos
        // Formato esperado: {baseUrl}/api/log-files
        $url = $this->baseUrl . '/api/log-files';
        
        $response = $this->makeRequest($url);
        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['tree'])) {
            return [];
        }

        return $data['tree'];
    }

    public function readContent(string $filePath, int $lines): string
    {
        // Formato esperado: {baseUrl}/api/log-content?file={filePath}&lines={lines}
        $url = $this->baseUrl . '/api/log-content?' . http_build_query([
            'file' => $filePath,
            'lines' => $lines
        ]);

        $response = $this->makeRequest($url);
        if ($response === null) {
            throw new \RuntimeException('Erro ao ler arquivo via URL.');
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['content'])) {
            throw new \RuntimeException('Resposta inválida do servidor.');
        }

        return $data['content'];
    }

    public function readEntries(string $filePath, int $page, int $perPage, string $levelFilter, string $search): array
    {
        // Formato esperado: {baseUrl}/api/log-entries?file={filePath}&page={page}&per_page={perPage}&level={level}&search={search}
        $params = [
            'file' => $filePath,
            'page' => $page,
            'per_page' => $perPage
        ];

        if ($levelFilter !== '') {
            $params['level'] = $levelFilter;
        }

        if ($search !== '') {
            $params['search'] = $search;
        }

        $url = $this->baseUrl . '/api/log-entries?' . http_build_query($params);

        $response = $this->makeRequest($url);
        if ($response === null) {
            throw new \RuntimeException('Erro ao ler entradas via URL.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Resposta inválida do servidor.');
        }

        return $data;
    }

    private function makeRequest(string $url): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false, // Pode ser configurável no futuro
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        // Autenticação básica
        if ($this->username !== null && $this->password !== null) {
            $options[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }

        // API Key no header
        if ($this->apiKey !== null && $this->apiKey !== '') {
            $options[CURLOPT_HTTPHEADER] = [
                $this->apiKeyHeader . ': ' . $this->apiKey
            ];
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            if ($error !== '') {
                throw new \RuntimeException('Erro na requisição: ' . $error);
            }
            throw new \RuntimeException('Erro HTTP: ' . $httpCode);
        }

        return $response;
    }
}

