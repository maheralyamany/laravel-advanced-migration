<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

class SQLiteTableDefinition extends BaseTableDefinition
{
  public function __construct()
  {
    parent::__construct();
  }
  public function getListTableMetadataSQL(string $table, ?string $database = null): string
  {
    $database = $this->getDatabaseName($database);
    $_table = $this->quoteStringLiteral($table);

    return sprintf(
      <<<'SQL'
                WITH cols AS (
                    SELECT GROUP_CONCAT(name || ' ' || type) AS column_info
                    FROM pragma_table_info(%s)
                )
                SELECT 'sqlite' AS ENGINE,
                    CASE WHEN sql LIKE '%%AUTOINCREMENT%%' THEN 1 ELSE NULL END AS AUTO_INCREMENT,
                    NULL AS TABLE_COMMENT,
                    NULL AS CREATE_OPTIONS,
                    'BINARY' AS TABLE_COLLATION,
                    'UTF-8' AS CHARACTER_SET_NAME,
                    cols.column_info,
                    (SELECT COUNT(*) FROM %s) AS ROW_COUNT,
                    name AS TABLE_NAME,
                    sql AS CREATE_SQL
                FROM sqlite_master
                CROSS JOIN cols
                WHERE type = 'table' AND name = %s
                SQL,
      $_table,
      $this->quoteIdentifier($table),
      $_table
    );
  }
  protected  function quoteStringLiteral(string $str): string
  {

    return "'" . str_replace("'", "''", $str) . "'";
  }
  protected  function quoteIdentifier(string $str): string
  {
    return '"' . str_replace('"', '""', $str) . '"';
  }
  protected  function getCurrentDatabaseExpression(): string
  {
    return "''";
  }
}
