<?php

namespace App\Generators;

use App\Definitions\TableDefinition;
use App\Generators\Concerns\CleansUpMorphColumns;
use App\Generators\Concerns\CleansUpColumnIndices;
use App\Generators\Concerns\CleansUpTimestampsColumn;
use App\Generators\Concerns\CleansUpForeignKeyIndices;
use App\Generators\Interfaces\TableGeneratorInterface;

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
        $this->definition = new TableDefinition([
            'driver'    => static::driver(),
            'tableName' => $tableName
        ]);
        $this->rows = $rows;
    }

    public function definition(): TableDefinition
    {
        return $this->definition;
    }

    abstract public function resolveStructure();

    abstract public function parse();

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

        if (! config('migration-generator.definitions.use_defined_datatype_on_timestamp')) {
            $this->cleanUpTimestampsColumn();
        }

        $this->cleanUpColumnsWithIndices();
    }
}
