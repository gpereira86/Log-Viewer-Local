<?php

declare(strict_types=1);

namespace LogViewer\Config;

/**
 * Mapeia caminhos locais para caminhos dentro do container Docker
 * Permite configurar volumes do Docker via variáveis de ambiente
 */
class PathMapper
{
    private static ?array $volumeMappings = null;

    /**
     * Carrega mapeamentos de volumes do ambiente
     * Formato esperado: LOGVIEWER_VOLUMES="/host/path:/container/path,/host2:/container2"
     */
    private static function loadVolumeMappings(): void
    {
        if (self::$volumeMappings !== null) {
            return;
        }

        self::$volumeMappings = [];

        // Lê variável de ambiente LOGVIEWER_VOLUMES
        $volumesEnv = getenv('LOGVIEWER_VOLUMES');
        if ($volumesEnv !== false && !empty($volumesEnv)) {
            $volumes = explode(',', $volumesEnv);
            foreach ($volumes as $volume) {
                $parts = explode(':', trim($volume), 2);
                if (count($parts) === 2) {
                    $hostPath = trim($parts[0]);
                    $containerPath = trim($parts[1]);
                    if (!empty($hostPath) && !empty($containerPath)) {
                        self::$volumeMappings[$hostPath] = $containerPath;
                    }
                }
            }
        }

        // Também verifica variáveis individuais LOGVIEWER_VOLUME_* no $_SERVER
        // (útil quando variáveis são passadas via docker-compose ou Apache)
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'LOGVIEWER_VOLUME_') && is_string($value)) {
                $parts = explode(':', $value, 2);
                if (count($parts) === 2) {
                    $hostPath = trim($parts[0]);
                    $containerPath = trim($parts[1]);
                    if (!empty($hostPath) && !empty($containerPath)) {
                        self::$volumeMappings[$hostPath] = $containerPath;
                    }
                }
            }
        }
    }

    /**
     * Mapeia um caminho do host para o caminho dentro do container
     * Se não houver mapeamento, retorna o caminho original
     */
    public static function mapPath(string $path): string
    {
        self::loadVolumeMappings();

        // Se não houver mapeamentos, retorna o caminho original
        if (empty(self::$volumeMappings)) {
            return $path;
        }

        // Normaliza o caminho
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedPath = rtrim($normalizedPath, '/');

        // Tenta encontrar um mapeamento que corresponda
        foreach (self::$volumeMappings as $hostPath => $containerPath) {
            $normalizedHost = str_replace('\\', '/', $hostPath);
            $normalizedHost = rtrim($normalizedHost, '/');

            // Verifica se o caminho começa com o caminho do host
            if (str_starts_with($normalizedPath, $normalizedHost)) {
                // Substitui o caminho do host pelo caminho do container
                $relativePath = substr($normalizedPath, strlen($normalizedHost));
                $mappedPath = rtrim($containerPath, '/') . $relativePath;
                return $mappedPath;
            }
        }

        // Se não encontrou mapeamento, retorna o caminho original
        return $path;
    }

    /**
     * Obtém todos os mapeamentos configurados
     * @return array<string, string> Array com hostPath => containerPath
     */
    public static function getMappings(): array
    {
        self::loadVolumeMappings();
        return self::$volumeMappings;
    }
}
