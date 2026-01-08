<?php

declare(strict_types=1);

namespace LogViewer\Service;

class ResponseService
{
    /**
     * Envia uma resposta JSON
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Envia uma resposta de erro JSON
     */
    public static function error(string $message, int $statusCode = 400, array $additional = []): void
    {
        $response = array_merge(['error' => $message], $additional);
        self::json($response, $statusCode);
    }

    /**
     * Envia uma resposta de sucesso JSON
     */
    public static function success(mixed $data = null, string $message = 'Operação realizada com sucesso.'): void
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        self::json($response, 200);
    }
}
