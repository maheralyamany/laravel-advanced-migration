<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions\Interfaces;
use AdvancedMigration\Constants;
use AdvancedMigration\Definitions\ColumnDefinition;
use AdvancedMigration\Definitions\IndexDefinition;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Formatters\TableFormatter;
interface TableDefinitionInterface
{
  public function getDriver(): string;
  public function getPresentableTableName(): string;
  public function getTableName(): string;
  public function setTableName(string $tableName);
  public function getColumnDefinitions(): array;
  public function setColumnDefinitions(array $columnDefinitions);
  public function addColumnDefinition(ColumnDefinition $definition);
  public function getIndexDefinitions(): array;
  public function getForeignKeyDefinitions(): array;
  public function setIndexDefinitions(array $indexDefinitions);
  public function addIndexDefinition(IndexDefinition $definition);
  public function removeIndexDefinition(IndexDefinition $definition);
  public function getPrimaryKey(): array;
  public function getTableMetadata();
  public function getTableComment();
  public function getSoftDeletes();
  public function getListTableMetadataSQL(string $table, ?string $database = null): string;
  public function formatter(): TableFormatter;
}
