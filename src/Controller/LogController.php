<?php

declare(strict_types=1);

namespace LogViewer\Controller;

use LogViewer\Model\LogReaderFactory;
use LogViewer\Model\ProjectRepository;
use LogViewer\Service\ResponseService;

class LogController
{
    private ProjectRepository $repo;

    public function __construct()
    {
        $this->repo = new ProjectRepository();
    }

    public function view(): void
    {
        $projects = $this->repo->all();
        include __DIR__ . '/../../views/logviewer.php';
    }

    public function listFiles(): void
    {
        try {
            $projectId = $_GET['project_id'] ?? '';
            $project = $this->repo->find($projectId);
            if ($project === null) {
                ResponseService::error('Projeto não encontrado.', 404);
                return;
            }

            $reader = LogReaderFactory::create($project);
            $tree = $reader->listFiles();
            ResponseService::json(['tree' => $tree]);
        } catch (\Throwable $e) {
            $errorMsg = 'Erro ao listar arquivos: ' . $e->getMessage();
            ResponseService::error($errorMsg, 500);
        }
    }

    public function getContent(): void
    {
        try {
            $projectId = $_GET['project_id'] ?? '';
            $fileName = $_GET['file'] ?? '';
            $lines = (int)($_GET['lines'] ?? 500);

            $project = $this->repo->find($projectId);
            if ($project === null) {
                ResponseService::error('Projeto não encontrado.', 404);
                return;
            }

            $reader = LogReaderFactory::create($project);
            $content = $reader->readContent($fileName, $lines);
            ResponseService::json(['content' => $content]);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao ler o arquivo: ' . $e->getMessage(), 500);
        }
    }

    public function entries(): void
    {
        try {
            $projectId = $_GET['project_id'] ?? '';
            $fileName = $_GET['file'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = max(1, min(200, (int)($_GET['per_page'] ?? 50)));
            $level = $_GET['level'] ?? '';
            $search = trim($_GET['search'] ?? '');

            $project = $this->repo->find($projectId);
            if ($project === null) {
                ResponseService::error('Projeto não encontrado.', 404);
                return;
            }

            $reader = LogReaderFactory::create($project);
            $result = $reader->readEntries($fileName, $page, $perPage, $level, $search);
            ResponseService::json($result);
        } catch (\Throwable $e) {
            ResponseService::error('Erro ao processar log: ' . $e->getMessage(), 500);
        }
    }
}


