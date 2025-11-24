<?php

namespace AdvancedMigration\Generators\Concerns;

use AdvancedMigration\Generators\BaseTableGenerator;

/**
 * Trait CleansUpForeignKeyIndices
 * @package AdvancedMigration\Generators\Concerns
 * @mixin BaseTableGenerator
 */
trait CleansUpForeignKeyIndices
{
    protected function cleanUpForeignKeyIndices(): void
    {
        $indexDefinitions = $this->definition()->getIndexDefinitions();
       // dd( $indexDefinitions);
        foreach ($indexDefinitions as $index) {
            /** @var \AdvancedMigration\Definitions\IndexDefinition $index */
            if ($index->getIndexType() === 'index') {
                //look for corresponding foreign key for this index
                $columns = $index->getIndexColumns();
                $indexName = $index->getIndexName();

                foreach ($indexDefinitions as $innerIndex) {
                    /** @var \AdvancedMigration\Definitions\IndexDefinition $innerIndex */
                    if ($innerIndex->getIndexName() !== $indexName) {
                        if ($innerIndex->getIndexType() === 'foreign') {
                            $cols = $innerIndex->getIndexColumns();
                            if (count(array_intersect($columns, $cols)) === count($columns)) {
                                //has same columns
                                $index->markAsWritable(false);

                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}
