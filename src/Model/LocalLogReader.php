<?php

declare(strict_types=1);

namespace LogViewer\Model;

use LogViewer\Config\PathMapper;

class LocalLogReader implements LogReaderInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        // Mapeia automaticamente o caminho se estiver rodando em Docker
        $mappedPath = PathMapper::mapPath($basePath);
        $this->basePath = rtrim($mappedPath, DIRECTORY_SEPARATOR);
    }

    public function listFiles(): array
    {
        return $this->buildFileTree($this->basePath, $this->basePath);
    }

    public function readContent(string $filePath, int $lines): string
    {
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $filePath;
        
        // Validação de segurança
        $realBasePath = realpath($this->basePath);
        $realFilePath = realpath($fullPath);
        if ($realBasePath === false || $realFilePath === false || !str_starts_with($realFilePath, $realBasePath)) {
            throw new \RuntimeException('Acesso negado: caminho inválido.');
        }

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            throw new \RuntimeException('Arquivo não encontrado.');
        }

        if (!is_readable($fullPath)) {
            throw new \RuntimeException('Arquivo não pode ser lido.');
        }

        return $this->tailFile($fullPath, $lines);
    }

    public function readEntries(string $filePath, int $page, int $perPage, string $levelFilter, string $search): array
    {
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $filePath;
        
        // Validação de segurança
        $realBasePath = realpath($this->basePath);
        $realFilePath = realpath($fullPath);
        if ($realBasePath === false || $realFilePath === false || !str_starts_with($realFilePath, $realBasePath)) {
            throw new \RuntimeException('Acesso negado: caminho inválido.');
        }

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            throw new \RuntimeException('Arquivo não encontrado.');
        }

        return $this->parseLaravelLogEntries($fullPath, $page, $perPage, $levelFilter, $search);
    }

    private function buildFileTree(string $basePath, string $currentPath): array
    {
        $tree = [];
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $currentPath = rtrim($currentPath, DIRECTORY_SEPARATOR);

        if (!is_dir($currentPath) || !is_readable($currentPath)) {
            return $tree;
        }

        $items = scandir($currentPath);
        if ($items === false) {
            return $tree;
        }

        $dirs = [];
        $files = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $dirs[] = $item;
            } elseif (is_file($itemPath) && str_ends_with($item, '.log')) {
                $files[] = $item;
            }
        }

        // Ordena pastas por data de modificação (mais recente primeiro)
        usort($dirs, function($a, $b) use ($currentPath) {
            $pathA = $currentPath . DIRECTORY_SEPARATOR . $a;
            $pathB = $currentPath . DIRECTORY_SEPARATOR . $b;
            
            $mtimeA = @filemtime($pathA);
            $mtimeB = @filemtime($pathB);
            
            // Se conseguir obter as datas, ordena por data (mais recente primeiro)
            if ($mtimeA !== false && $mtimeB !== false) {
                return $mtimeB <=> $mtimeA; // Descendente (mais recente primeiro)
            }
            
            // Se não conseguir obter as datas, ordena pelo nome (que pode conter a data)
            return strnatcasecmp($b, $a); // Descendente (ordem reversa do nome)
        });
        
        // Ordena arquivos por data de criação (mais recente primeiro)
        usort($files, function($a, $b) use ($currentPath) {
            $pathA = $currentPath . DIRECTORY_SEPARATOR . $a;
            $pathB = $currentPath . DIRECTORY_SEPARATOR . $b;
            
            $mtimeA = @filemtime($pathA);
            $mtimeB = @filemtime($pathB);
            
            // Se conseguir obter as datas, ordena por data (mais recente primeiro)
            if ($mtimeA !== false && $mtimeB !== false) {
                return $mtimeB <=> $mtimeA; // Descendente (mais recente primeiro)
            }
            
            // Se não conseguir obter as datas, ordena pelo nome (que pode conter a data)
            return strnatcasecmp($b, $a); // Descendente (ordem reversa do nome)
        });

        foreach ($dirs as $dir) {
            $dirPath = $currentPath . DIRECTORY_SEPARATOR . $dir;
            $subTree = $this->buildFileTree($basePath, $dirPath);
            
            $normalizedBasePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);
            $normalizedDirPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirPath);
            $relativePath = str_replace($normalizedBasePath . DIRECTORY_SEPARATOR, '', $normalizedDirPath);
            
            $tree[] = [
                'type' => 'folder',
                'name' => $dir,
                'path' => $relativePath,
                'children' => $subTree
            ];
        }

        foreach ($files as $file) {
            $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;
            $normalizedBasePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);
            $normalizedFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
            $relativePath = str_replace($normalizedBasePath . DIRECTORY_SEPARATOR, '', $normalizedFilePath);
            
            $tree[] = [
                'type' => 'file',
                'name' => $file,
                'path' => $relativePath
            ];
        }

        return $tree;
    }

    private function tailFile(string $filePath, int $lines): string
    {
        $lines = max(1, $lines);

        $cmd = sprintf('tail -n %d %s 2>&1', $lines, escapeshellarg($filePath));
        $output = shell_exec($cmd);

        if ($output === null) {
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            $start = max(0, $lastLine - $lines);
            $out = [];
            for ($i = $start; $i <= $lastLine; $i++) {
                $file->seek($i);
                $out[] = rtrim((string) $file->current(), "\r\n");
            }
            return implode("\n", $out);
        }

        return rtrim($output, "\n");
    }

    private function parseLaravelLogEntries(string $filePath, int $page, int $perPage, string $levelFilter, string $search): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o arquivo de log.');
        }

        $entries = [];
        $current = null;

        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\[(.*?)\]\s+([^.]+)\.([A-Z]+):\s(.*)$/', $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = [
                    'datetime' => $m[1],
                    'env' => $m[2],
                    'level' => strtolower($m[3]),
                    'message' => trim($m[4]),
                    'context' => '',
                ];
            } else {
                if ($current !== null) {
                    $current['context'] .= $line;
                }
            }
        }
        if ($current !== null) {
            $entries[] = $current;
        }

        fclose($handle);

        $entries = array_reverse($entries);

        if ($levelFilter !== '') {
            $entries = array_values(array_filter($entries, function ($e) use ($levelFilter) {
                return strtolower((string)$e['level']) === strtolower($levelFilter);
            }));
        }

        if ($search !== '') {
            $entries = array_values(array_filter($entries, function ($e) use ($search) {
                $haystack = strtolower((string)$e['message'] . ' ' . (string)$e['context']);
                return str_contains($haystack, strtolower($search));
            }));
        }

        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($entries, $offset, $perPage);

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'entries' => $slice,
        ];
    }
}

