<?php

declare(strict_types=1);

namespace LogViewer\Controller;

use LogViewer\Model\ProjectRepository;

class ConfigController
{
    private ProjectRepository $repo;

    public function __construct()
    {
        $this->repo = new ProjectRepository();
    }

    public function index(): void
    {
        $projects = $this->repo->all();
        include __DIR__ . '/../../views/config.php';
    }
}


