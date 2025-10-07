<?php

namespace App\GeneratorManagers\Interfaces;

use App\Definitions\TableDefinition;
use App\Definitions\ViewDefinition;

interface GeneratorManagerInterface
{
    public static function driver(): string;

    public function handle(string $basePath, array $tableNames = [], array $viewNames = [],string $database='');

    public function addTableDefinition(TableDefinition $definition);

    public function addViewDefinition(ViewDefinition $definition);

    public function getTableDefinitions(): array;

    public function getViewDefinitions(): array;

    public function setCommand(\Illuminate\Console\Command|null $command): static;
}
