<?php

namespace AdvancedMigration\Generators;

use AdvancedMigration\Definitions\TableDefinition;;

use AdvancedMigration\Generators\Concerns\CleansUpMorphColumns;
use AdvancedMigration\Generators\Concerns\CleansUpColumnIndices;
use AdvancedMigration\Generators\Concerns\CleansUpTimestampsColumn;
use AdvancedMigration\Generators\Concerns\CleansUpForeignKeyIndices;
use AdvancedMigration\Generators\Interfaces\TableGeneratorInterface;

abstract class BaseTableGenerator implements TableGeneratorInterface
{
    use CleansUpForeignKeyIndices;
    use CleansUpMorphColumns;
    use CleansUpTimestampsColumn;
    use CleansUpColumnIndices;

    protected array $rows = [];

    protected TableDefinition $definition;

    public function __construct(string $tableName, array $rows = [])
    {
        $this->definition = TableDefinition::newDefinition([
            'driver' => static::driver(),
            'tableName' => $tableName,
        ]);

        $this->rows = $rows;
    }

    public function definition(): TableDefinition
    {
        return $this->definition;
    }
    public abstract function getResolvedStructure(): array;
    //abstract public function resolveStructure();

    abstract public function parse();
    public function resolveStructure()
    {
        $rows = $this->getResolvedStructure();

        if (empty($rows))
            return;
        $this->rows = $rows;
    }
    public static function init(string $tableName, array $rows = [])
    {
        $instance = (new static($tableName, $rows));

        if ($instance->shouldResolveStructure()) {
            $instance->resolveStructure();
        }

        $instance->parse();
        $instance->cleanUp();

        return $instance;
    }

    public function shouldResolveStructure(): bool
    {
        return count($this->rows) === 0;
    }

    public function cleanUp(): void
    {
        $this->cleanUpForeignKeyIndices();

        $this->cleanUpMorphColumns();

        if (! config('advanced-migration-generator.definitions.use_defined_datatype_on_timestamp')) {
            $this->cleanUpTimestampsColumn();
        }

        $this->cleanUpColumnsWithIndices();
    }
}
