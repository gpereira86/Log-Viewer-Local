<?php

declare(strict_types=1);

namespace LogViewer\Security;

use LogViewer\Config\AppConfig;

class EncryptionService
{
    private string $key;
    private string $method;

    public function __construct()
    {
        AppConfig::load();
        $this->key = AppConfig::getEncryptionKeyValue();
        $this->method = AppConfig::getEncryptionMethod();
        
        if (empty($this->key)) {
            throw new \RuntimeException('Chave de criptografia não configurada.');
        }
    }

    /**
     * Criptografa um valor
     */
    public function encrypt(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $ivLength = openssl_cipher_iv_length($this->method);
        if ($ivLength === false) {
            throw new \RuntimeException('Método de criptografia inválido.');
        }

        $iv = openssl_random_pseudo_bytes($ivLength);
        if ($iv === false) {
            throw new \RuntimeException('Falha ao gerar IV para criptografia.');
        }

        // Deriva uma chave da chave principal usando hash
        $derivedKey = hash('sha256', $this->key, true);

        $encrypted = openssl_encrypt($value, $this->method, $derivedKey, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Falha ao criptografar valor.');
        }

        // Combina IV + dados criptografados e codifica em base64
        $combined = $iv . $encrypted;
        return base64_encode($combined);
    }

    /**
     * Descriptografa um valor
     */
    public function decrypt(string $encryptedValue): string
    {
        if (empty($encryptedValue)) {
            return '';
        }

        $data = base64_decode($encryptedValue, true);
        if ($data === false) {
            throw new \RuntimeException('Valor criptografado inválido.');
        }

        $ivLength = openssl_cipher_iv_length($this->method);
        if ($ivLength === false || strlen($data) < $ivLength) {
            throw new \RuntimeException('Valor criptografado corrompido.');
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Deriva a mesma chave
        $derivedKey = hash('sha256', $this->key, true);

        $decrypted = openssl_decrypt($encrypted, $this->method, $derivedKey, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new \RuntimeException('Falha ao descriptografar valor.');
        }

        return $decrypted;
    }

    /**
     * Criptografa campos sensíveis de um array
     */
    public function encryptSensitiveFields(array $data): array
    {
        $sensitiveFields = [
            'ssh_password',
            'ssh_private_key',
            'ssh_private_key_passphrase',
            'url_password',
            'url_api_key',
        ];

        $encrypted = $data;
        foreach ($sensitiveFields as $field) {
            if (isset($encrypted[$field]) && $encrypted[$field] !== null && $encrypted[$field] !== '') {
                $encrypted[$field] = $this->encrypt((string)$encrypted[$field]);
            }
        }

        return $encrypted;
    }

    /**
     * Descriptografa campos sensíveis de um array
     */
    public function decryptSensitiveFields(array $data): array
    {
        $sensitiveFields = [
            'ssh_password',
            'ssh_private_key',
            'ssh_private_key_passphrase',
            'url_password',
            'url_api_key',
        ];

        $decrypted = $data;
        foreach ($sensitiveFields as $field) {
            if (isset($decrypted[$field]) && $decrypted[$field] !== null && $decrypted[$field] !== '') {
                try {
                    $decrypted[$field] = $this->decrypt((string)$decrypted[$field]);
                } catch (\Throwable $e) {
                    // Se falhar ao descriptografar, pode ser que não esteja criptografado
                    // (compatibilidade com dados antigos)
                    // Mantém o valor original
                }
            }
        }

        return $decrypted;
    }
}
