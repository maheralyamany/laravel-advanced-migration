<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Constants;

use AdvancedMigration\Formatters\TableFormatter;

abstract class TableDefinition 
{
  //MySQLTableDefinition
  protected string $tableName;

  /** @var array<ColumnDefinition> */
  protected array $columnDefinitions = [];
  protected array $indexDefinitions = [];
  public function __construct($attributes = [])
  {
    foreach ($attributes as $attribute => $value) {
      if (property_exists($this, $attribute)) {
        $this->$attribute = $value;
      }
    }
  }
  protected abstract function quoteStringLiteral(string $str): string;
  public abstract function getListTableMetadataSQL(string $table, ?string $database = null): string;
  /**
   * Quotes a table/column/database identifier safely.
   *
   * @param string $str
   * @return string
   */
  protected abstract function quoteIdentifier(string $str): string;
  protected abstract function getCurrentDatabaseExpression(): string;
  public abstract function getDriver(): string;

  public static function newDefinition( array $attributes = []): self
  {
    $driver = $attributes['driver'] ?? Constants::MYSQL_DRIVER;
    $definition = match ($driver) {
      Constants::MYSQL_DRIVER => new MySQLTableDefinition($attributes),
      Constants::PGSQL_DRIVER => new PostgreTableDefinition($attributes),
      Constants::SQLITE_DRIVER => new SQLiteTableDefinition($attributes),
      Constants::SQLSRV_DRIVER => new SqlServerTableDefinition($attributes),
      'sqlsrv' =>  new SqlServerTableDefinition($attributes),
      default => throw new \RuntimeException("Unsupported Table Definition driver: {$driver}")
    };
    return $definition;
  }

  public function getPresentableTableName(): string
  {
    if (count($this->getColumnDefinitions()) === 0) {
      if (count($definitions = $this->getIndexDefinitions()) > 0) {
        $first = collect($definitions)->first();
        //a fk only table from dependency resolution
        return $this->getTableName() . '_' . $first->getIndexName();
      }
    }
    return $this->getTableName();
  }
  /**
   * @return string
   */
  public function getTableName(): string
  {
    return $this->tableName;
  }
  public function setTableName(string $tableName)
  {
    $this->tableName = $tableName;
    return $this;
  }
  public function getColumnDefinitions(): array
  {
    return $this->columnDefinitions;
  }
  public function setColumnDefinitions(array $columnDefinitions)
  {
    $this->columnDefinitions = $columnDefinitions;
    return $this;
  }
  public function addColumnDefinition(ColumnDefinition $definition)
  {
    $this->columnDefinitions[] = $definition;
    return $this;
  }
  /**
   * @return array<IndexDefinition>
   */
  public function getIndexDefinitions(): array
  {
    return $this->indexDefinitions;
  }
  /** @return array<IndexDefinition> */
  public function getForeignKeyDefinitions(): array
  {
    return collect($this->getIndexDefinitions())->filter(function ($indexDefinition) {
      return $indexDefinition->getIndexType() == IndexDefinition::TYPE_FOREIGN;
    })->toArray();
  }
  public function setIndexDefinitions(array $indexDefinitions)
  {
    $this->indexDefinitions = $indexDefinitions;
    return $this;
  }
  public function addIndexDefinition(IndexDefinition $definition)
  {
    $this->indexDefinitions[] = $definition;
    return $this;
  }
  public function removeIndexDefinition(IndexDefinition $definition)
  {
    foreach ($this->indexDefinitions as $key => $indexDefinition) {
      if ($definition->getIndexName() == $indexDefinition->getIndexName()) {
        unset($this->indexDefinitions[$key]);
        break;
      }
    }
    return $this;
  }
  public function getPrimaryKey(): array
  {
    return collect($this->getColumnDefinitions())
      ->filter(function (ColumnDefinition $columnDefinition) {
        return $columnDefinition->isPrimary();
      })->toArray();
  }
  public function getTableMetadata()
  {
    $query = $this->getListTableMetadataSQL($this->getTableName(), null);
    return collect(DB::select($query))->first();
  }
  public function getTableComment()
  {
    $query = $this->getListTableMetadataSQL($this->getTableName(), null);
    $comment = collect(DB::select($query))->first()->TABLE_COMMENT ?? '';
    if (!empty($comment)) {
      return sprintf("\$table->comment('%s'); ", $comment);
    }
    return '';
  }
  public function getSoftDeletes()
  {
    return "\$table->softDeletes(); ";
  }





  /* private function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped
        $c = $this->getStringLiteralQuoteCharacter();
        return $c . str_replace($c, $c . $c, $str) . $c;
    } */




  private function supportsColumnLengthIndexes(): bool
  {
    return true;
  }

  /**
   * Quotes a single identifier (no dot chain separation).
   *
   * @param string $str The identifier name to be quoted.
   *
   * @return string The quoted identifier string.
   */
  private function quoteSingleIdentifier($str)
  {
    $c = $this->getIdentifierQuoteCharacter();
    return $c . str_replace($c, $c . $c, $str) . $c;
  }
  /**
   * Gets the character used for identifier quoting.
   *
   *
   * @return string
   */
  private function getIdentifierQuoteCharacter()
  {
    return '"';
  }

  public function formatter(): TableFormatter
  {
    return new TableFormatter($this);
  }

  protected function getDatabaseName($database = null): string
  {
    if ($database == null) {
      return DB::getDatabaseName();
    }
    return $database;
  }
  /**
   * Returns quoted database name or default expression.
   *
   * @param string|null $databaseName
   * @return string
   */
  protected function getDatabaseNameSQL(?string $databaseName): string
  {
    if ($databaseName !== null) {
      return $this->quoteStringLiteral($databaseName);
    }

    return $this->getCurrentDatabaseExpression();
  }
  protected function getStringLiteralQuoteCharacter()
  {
    return "'";
  }
}
