<?php

namespace AdvancedMigration\GeneratorManagers;

use AdvancedMigration\Definitions\TableDefinition;
use AdvancedMigration\Definitions\ViewDefinition;
use AdvancedMigration\Helpers\Dependency;
use AdvancedMigration\Helpers\ConfigResolver;
use AdvancedMigration\Helpers\DependencyResolver;

use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use AdvancedMigration\Helpers\DependencyResolverV2;
use AdvancedMigration\Helpers\DependencyResolverV3;

abstract class BaseGeneratorManager implements GeneratorManagerInterface
{
    /**
     *
     * @var array<TableDefinition>
     */
    protected  $tableDefinitions = [];
    protected array $tableNames = [];
    protected array $allowedTables = [];
    protected array $allowedViewes = [];

    protected array $viewDefinitions = [];


    /**
     * Undocumented variable
     *
     * @var \Illuminate\Console\Command|null
     */
    protected $command;
    abstract public function init();

    public function createMissingDirectory($basePath)
    {
        if (! is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }
    }

    public function setCommand(\Illuminate\Console\Command|null $command): static
    {
        $this->command = $command;
        return $this;
    }

    /**
     * @return array<TableDefinition>
     */
    public function getTableDefinitions(): array
    {
        return $this->tableDefinitions;
    }
    /**
     * @return array<string>
     */
    public function getTableNames(): array
    {
        return $this->tableNames;
    }

    /**
     * @return array<ViewDefinition>
     */
    public function getViewDefinitions(): array
    {
        return $this->viewDefinitions;
    }

    public function addTableDefinition(TableDefinition $tableDefinition): BaseGeneratorManager
    {
        $this->tableDefinitions[] = $tableDefinition;
        return $this;
    }
    public function addTableName(string $tableName): BaseGeneratorManager
    {
        $this->tableNames[] = $tableName;
        return $this;
    }
    public function isAllowedType($name, $type): bool
    {
        $type = strtoupper($type);
        if ($type === 'TABLE' || $type === 'BASE TABLE') {
            return empty($this->allowedTables) || in_array($name, $this->allowedTables);
        } elseif ($type === 'VIEW') {
            return empty($this->allowedViewes) || in_array($name, $this->allowedViewes);
        }
        return false;
    }

    public function addViewDefinition(ViewDefinition $definition): BaseGeneratorManager
    {
        $this->viewDefinitions[] = $definition;

        return $this;
    }

    public function handle(string $basePath, array $tableNames = [], array $viewNames = [])
    {
        $this->allowedTables = $tableNames;
        $this->allowedViewes = $viewNames;
        $this->init();


        $tableDefinitions = collect($this->getTableDefinitions());
        $viewDefinitions = collect($this->getViewDefinitions());
        $this->createMissingDirectory($basePath);

        if (count($tableNames) > 0) {
            $tableDefinitions = $tableDefinitions->filter(function ($tableDefinition) use ($tableNames) {
                return in_array($tableDefinition->getTableName(), $tableNames);
            });
        }
        if (count($viewNames) > 0) {
            $viewDefinitions = $viewDefinitions->filter(function ($viewGenerator) use ($viewNames) {
                return in_array($viewGenerator->getViewName(), $viewNames);
            });
        }

        $tableDefinitions = $tableDefinitions->filter(function ($tableDefinition) {
            return ! $this->skipTable($tableDefinition->getTableName());
        });

        $viewDefinitions = $viewDefinitions->filter(function ($viewDefinition) {
            return ! $this->skipView($viewDefinition->getViewName());
        });

        $sorted = $this->sortTables($tableDefinitions->toArray());

        $this->writeTableMigrations($sorted, $basePath);

        $this->writeViewMigrations($viewDefinitions->toArray(), $basePath, count($sorted));
    }
    public  function callCommandOutput($string, $style = 'info', $verbosity = null): void
    {
        if (!is_null($this->command)) {
            $this->command->line($string, $style, $verbosity);
        }
    }
    /**
     * @param array<TableDefinition> $tableDefinitions
     * @return array<TableDefinition>
     */
    public function sortTables(array $tableDefinitions): array
    {
        if (count($tableDefinitions) <= 1) {
            return $tableDefinitions;
        }

        if (config('advanced-migration-generator.sort_mode') == 'foreign_key') {
            return (new DependencyResolver($tableDefinitions))->getDependencyOrder();
        }

        return $tableDefinitions;
    }

    /**
     * @param array<TableDefinition> $tableDefinitions
     * @param $basePath
     */
    public function writeTableMigrations($tableDefinitions, $basePath)
    {

        // dd($tableDefinitions);
        foreach ($tableDefinitions as $key => $tableDefinition) {
            $tableName = $tableDefinition->getPresentableTableName();
            $filepath = $tableDefinition->formatter()->write($basePath, $key);

            $this->callCommandOutput(sprintf("✅ Table %s Migration generated at \n %s",  $tableName, $filepath));
        }
    }

    /**
     * @param array<ViewDefinition> $viewDefinitions
     * @param $basePath
     */
    public function writeViewMigrations(array $viewDefinitions, $basePath, $tableCount = 0)
    {
        foreach ($viewDefinitions as $key => $view) {
            $view->formatter()->write($basePath, $tableCount + $key);
        }
    }

    /**
     * @return array<string>
     */
    public function skippableTables(): array
    {
        return ConfigResolver::skippableTables(static::driver());
    }

    public function skipTable($table): bool
    {
        return in_array($table, $this->skippableTables());
    }

    /**
     * @return array<string>
     */
    public function skippableViews(): array
    {
        return ConfigResolver::skippableViews(static::driver());
    }

    public function skipView($view): bool
    {
        $skipViews = config('advanced-migration-generator.skip_views');
        if ($skipViews) {
            return true;
        }

        return in_array($view, $this->skippableViews());
    }
}
