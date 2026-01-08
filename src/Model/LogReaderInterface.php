<?php

declare(strict_types=1);

namespace LogViewer\Model;

interface LogReaderInterface
{
    /**
     * Lista arquivos de log disponíveis em uma estrutura de árvore
     * @return array<string, mixed> Estrutura hierárquica de arquivos e pastas
     */
    public function listFiles(): array;

    /**
     * Lê o conteúdo de um arquivo de log
     * @param string $filePath Caminho relativo do arquivo
     * @param int $lines Número de linhas a ler (tail)
     * @return string Conteúdo do arquivo
     */
    public function readContent(string $filePath, int $lines): string;

    /**
     * Lê e parseia entradas de log Laravel
     * @param string $filePath Caminho relativo do arquivo
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @param string $levelFilter Filtro por nível
     * @param string $search Filtro por busca
     * @return array<string, mixed> Resultado com entradas, total, página, etc.
     */
    public function readEntries(string $filePath, int $page, int $perPage, string $levelFilter, string $search): array;
}

