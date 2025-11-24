<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

class SqlServerTableDefinition extends BaseTableDefinition
{
  public function __construct()
  {
    parent::__construct();
  }
  public function getListTableMetadataSQL(string $table, ?string $database = null): string
  {
    $database = $this->getDatabaseName($database);
    $_table = $this->quoteStringLiteral($table);
    $_database = $this->quoteStringLiteral($database);
    return sprintf(
      <<<'SQL'
            WITH cols AS (
                SELECT STRING_AGG(c.name + ' ' + TYPE_NAME(c.user_type_id), ', ') AS column_info
                FROM sys.columns c
                JOIN sys.tables t ON t.object_id = c.object_id
                WHERE t.name = %s AND SCHEMA_NAME(t.schema_id) = %s
            )
            SELECT 'sql_server' AS ENGINE,
                CASE WHEN c.is_identity = 1 THEN 1 ELSE NULL END AS AUTO_INCREMENT,
                ep.value AS TABLE_COMMENT,
                NULL AS CREATE_OPTIONS,
                collation_name AS TABLE_COLLATION,
                collation_name AS CHARACTER_SET_NAME,
                cols.column_info,
                (SELECT COUNT(*) FROM [%s].[%s]) AS ROW_COUNT,
                t.name AS TABLE_NAME,
                NULL AS CREATE_SQL
            FROM sys.tables t
            LEFT JOIN sys.columns c ON t.object_id = c.object_id
            LEFT JOIN sys.extended_properties ep 
                ON ep.major_id = t.object_id AND ep.minor_id = 0 AND ep.name = 'MS_Description'
            CROSS JOIN cols
            WHERE t.name = %s AND SCHEMA_NAME(t.schema_id) = %s
            SQL,
      $_table,
      $_database,
      $this->quoteIdentifier($database),
      $this->quoteIdentifier($table),
      $_table,
      $_database
    );
  }
  protected  function quoteStringLiteral(string $str): string
  {
    $str = str_replace('\\', '\\\\', $str);
    return "'" . str_replace("'", "''", $str) . "'";
  }
  protected  function quoteIdentifier(string $str): string
  {
    return "[" . str_replace("]", "]]", $str) . "]";
  }
  protected  function getCurrentDatabaseExpression(): string
  {
    return 'DB_NAME()';
  }
}
