<?php

namespace App\Http\Exports;

interface ISimpleCsvExporter{
    public function getHeadings(): array;
    public function getRows($entities): array;
    public function getCount(array $arParams = []): int;
    public function query(int $chunkSize, int $offset, array $arParams = []);
    public function execute(string $exportName, array $arParams = [], string $fileName = null, int $chunkSize = 500, int $timeLimit = 10);
}
