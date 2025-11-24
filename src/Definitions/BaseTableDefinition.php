<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

use Illuminate\Support\Facades\DB;

abstract class BaseTableDefinition
{
  public function __construct() {}
  protected abstract function quoteStringLiteral(string $str): string;
  public abstract function getListTableMetadataSQL(string $table, ?string $database = null): string;
  /**
   * Quotes a table/column/database identifier safely.
   *
   * @param string $str
   * @return string
   */
  protected abstract   function quoteIdentifier(string $str): string;
  protected abstract   function getCurrentDatabaseExpression(): string;
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
