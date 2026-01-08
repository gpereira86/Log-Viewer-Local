<?php

declare(strict_types=1);

namespace LogViewer\Config;

class AppConfig
{
    private static ?array $config = null;

    /**
     * Carrega a configuração do arquivo ou variáveis de ambiente
     */
    public static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        $configFile = __DIR__ . '/../../config/app.php';
        
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            // Valores padrão
            self::$config = [
                'data_dir' => __DIR__ . '/../../data',
                'encryption_key' => self::getEncryptionKey(),
                'encryption_method' => 'AES-256-CBC',
            ];
        }
    }

    /**
     * Obtém uma chave de criptografia
     * Gera uma nova se não existir ou usa a existente
     */
    private static function getEncryptionKey(): string
    {
        $keyFile = __DIR__ . '/../../config/.encryption_key';
        
        if (file_exists($keyFile)) {
            $key = trim(file_get_contents($keyFile));
            if (strlen($key) >= 32) {
                return $key;
            }
        }

        // Gera uma nova chave
        $key = bin2hex(random_bytes(32));
        
        // Garante que o diretório existe
        $configDir = dirname($keyFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }
        
        file_put_contents($keyFile, $key);
        chmod($keyFile, 0600);
        
        return $key;
    }

    /**
     * Obtém um valor de configuração
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Define um valor de configuração
     */
    public static function set(string $key, mixed $value): void
    {
        self::load();
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }

    /**
     * Obtém o caminho do diretório de dados
     */
    public static function getDataDir(): string
    {
        return self::get('data_dir', __DIR__ . '/../../data');
    }

    /**
     * Obtém a chave de criptografia
     */
    public static function getEncryptionKeyValue(): string
    {
        return self::get('encryption_key', '');
    }

    /**
     * Obtém o método de criptografia
     */
    public static function getEncryptionMethod(): string
    {
        return self::get('encryption_method', 'AES-256-CBC');
    }
}
