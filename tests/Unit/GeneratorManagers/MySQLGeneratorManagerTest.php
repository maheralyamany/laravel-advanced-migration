<?php

namespace Tests\Unit\GeneratorManagers;

use AdvancedMigration\Constants;
use AdvancedMigration\Definitions\TableDefinition;
use Tests\TestCase;
use Mockery\MockInterface;
use AdvancedMigration\Definitions\IndexDefinition;

use AdvancedMigration\Definitions\ColumnDefinition;
use AdvancedMigration\GeneratorManagers\MySQLGeneratorManager;

class MySQLGeneratorManagerTest extends TestCase
{
    protected function getManagerMock(array $tableDefinitions)
    {
        return $this->partialMock(MySQLGeneratorManager::class, function (MockInterface $mock) use ($tableDefinitions) {
            $mock->shouldReceive('init', 'createMissingDirectory', 'writeTableMigrations', 'writeViewMigrations');
            $mock->shouldReceive('createMissingDirectory');

            $mock->shouldReceive('getTableDefinitions')->andReturn($tableDefinitions);
        });
    }

    public function test_can_sort_tables()
    {
        /** @var MySQLGeneratorManager $mocked */
        $mocked = $this->getManagerMock([
            TableDefinition::newDefinition([
                'tableName'         => 'tests',
                'driver'            => Constants::MYSQL_DRIVER,
                'columnDefinitions' => [
                    (new ColumnDefinition())->setColumnName('id')->setMethodName('id')->setAutoIncrementing(true)->setPrimary(true),
                    (new ColumnDefinition())->setColumnName('test_item_id')->setMethodName('bigInteger')->setNullable(false)->setUnsigned(true),
                ],
                'indexDefinitions' => [
                    (new IndexDefinition())->setIndexName('fk_test_item_id')->setIndexColumns(['test_item_id'])->setIndexType('foreign')->setForeignReferencedColumns(['id'])->setForeignReferencedTable('test_items')
                ],
            ]),
            TableDefinition::newDefinition([
                'tableName'         => 'test_items',
                'driver'            => Constants::MYSQL_DRIVER,
                'columnDefinitions' => [
                    (new ColumnDefinition())->setColumnName('id')->setMethodName('id')->setAutoIncrementing(true)->setPrimary(true),
                    (new ColumnDefinition())->setColumnName('test_id')->setMethodName('bigInteger')->setNullable(false)->setUnsigned(true),
                ],
                'indexDefinitions' => [
                    (new IndexDefinition())->setIndexName('fk_test_id')->setIndexColumns(['test_id'])->setIndexType('foreign')->setForeignReferencedColumns(['id'])->setForeignReferencedTable('tests')
                ],
            ])
        ]);
        $sorted = $mocked->sortTables($mocked->getTableDefinitions());
        $this->assertCount(4, $sorted);
        $this->assertStringContainsString('$table->dropForeign', $sorted[3]->formatter()->stubTableDown());
    }
}
