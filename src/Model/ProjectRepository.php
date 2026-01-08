<?php

declare(strict_types=1);

namespace LogViewer\Model;

use LogViewer\Config\AppConfig;
use LogViewer\Security\EncryptionService;

class ProjectRepository
{
    private string $filePath;
    private EncryptionService $encryption;

    public function __construct(?string $filePath = null)
    {
        AppConfig::load();
        $dataDir = AppConfig::getDataDir();
        $this->filePath = $filePath ?? $dataDir . '/projects.json';
        $this->encryption = new EncryptionService();
        
        if (!file_exists(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0700, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
            chmod($this->filePath, 0600);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $content = file_get_contents($this->filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        // Descriptografa campos sensíveis de cada projeto
        return array_map(
            fn($project) => $this->encryption->decryptSensitiveFields($project),
            $data
        );
    }

    /**
     * @param array<string, mixed> $project
     */
    public function save(array $project): void
    {
        $projects = $this->all();
        if (empty($project['id'])) {
            $project['id'] = uniqid('proj_', true);
        }

        // Criptografa campos sensíveis antes de salvar
        $encryptedProject = $this->encryption->encryptSensitiveFields($project);

        $found = false;
        foreach ($projects as $index => $existing) {
            if (($existing['id'] ?? '') === $project['id']) {
                // Descriptografa o existente para comparar, depois criptografa novamente
                $projects[$index] = $encryptedProject;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $projects[] = $encryptedProject;
        }

        // Salva com permissões restritivas
        file_put_contents($this->filePath, json_encode($projects, JSON_PRETTY_PRINT));
        chmod($this->filePath, 0600);
    }

    public function delete(string $id): void
    {
        $projects = array_values(array_filter(
            $this->all(),
            fn ($p) => ($p['id'] ?? '') !== $id
        ));
        
        // Criptografa novamente antes de salvar
        $encryptedProjects = array_map(
            fn($project) => $this->encryption->encryptSensitiveFields($project),
            $projects
        );
        
        file_put_contents($this->filePath, json_encode($encryptedProjects, JSON_PRETTY_PRINT));
        chmod($this->filePath, 0600);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $project) {
            if (($project['id'] ?? '') === $id) {
                return $project;
            }
        }
        return null;
    }
}

