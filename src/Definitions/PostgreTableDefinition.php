<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

class PostgreTableDefinition extends TableDefinition
{
   public  function getDriver(): string
  {
    return \AdvancedMigration\Constants::PGSQL_DRIVER;
  }
  public function getListTableMetadataSQL(string $table, ?string $database = null): string
  {
    $database = $this->getDatabaseName($database);
    $_table = $this->quoteStringLiteral($table);
    $_database = $this->quoteStringLiteral($database);
    return sprintf(
      <<<'SQL'
                WITH cols AS (
                    SELECT string_agg(a.attname || ' ' || format_type(a.atttypid, a.atttypmod), ', ') AS column_info
                    FROM pg_catalog.pg_class c
                    JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                    JOIN pg_catalog.pg_attribute a ON a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped
                    WHERE c.relname = %s AND n.nspname = %s
                )
                SELECT 'postgresql' AS ENGINE,
                    CASE WHEN EXISTS (
                        SELECT 1 
                        FROM pg_catalog.pg_attrdef ad
                        JOIN pg_catalog.pg_attribute a ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum
                        WHERE ad.adrelid = c.oid AND ad.adsrc LIKE '%%nextval%%'
                    ) THEN 1 ELSE NULL END AS AUTO_INCREMENT,
                    obj_description(c.oid) AS TABLE_COMMENT,
                    NULL AS CREATE_OPTIONS,
                    'UTF8' AS TABLE_COLLATION,
                    'UTF8' AS CHARACTER_SET_NAME,
                    cols.column_info,
                    c.reltuples::bigint AS ROW_COUNT,
                    c.relname AS TABLE_NAME,
                    pg_get_tabledef(c.oid) AS CREATE_SQL
                FROM pg_catalog.pg_class c
                JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                CROSS JOIN cols
                WHERE c.relkind = 'r' AND n.nspname = %s AND c.relname = %s
                SQL,
      $_table,
      $_database,
      $_database,
      $_table
    );
  }
  protected  function quoteStringLiteral(string $str): string
  {
    $str = str_replace('\\', '\\\\', $str);
    return "'" . str_replace("'", "''", $str) . "'";
  }
  protected  function quoteIdentifier(string $str): string
  {
    return '"' . str_replace('"', '""', $str) . '"';
  }
  protected  function getCurrentDatabaseExpression(): string
  {
    return 'current_database()';
  }
}
