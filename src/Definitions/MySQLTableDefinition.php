<?php

declare(strict_types=1);

namespace AdvancedMigration\Definitions;

class MySQLTableDefinition extends BaseTableDefinition
{
  public function __construct()
  {
    parent::__construct();
  }
  public function getListTableMetadataSQL(string $table, ?string $database = null): string
  {
    $database = $this->getDatabaseName($database);

    return sprintf(
      <<<'SQL'
                SELECT t.ENGINE,
                    t.AUTO_INCREMENT,
                    t.TABLE_COMMENT,
                    t.CREATE_OPTIONS,
                    t.TABLE_COLLATION,
                    ccsa.CHARACTER_SET_NAME,
                    GROUP_CONCAT(CONCAT(c.COLUMN_NAME,' ', c.COLUMN_TYPE)) AS COLUMN_INFO,
                    (SELECT COUNT(*) FROM %s.%s) AS ROW_COUNT,
                    t.TABLE_NAME, t.CREATE_OPTIONS AS CREATE_SQL
                FROM information_schema.TABLES t
                INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY ccsa
                        ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
                LEFT JOIN information_schema.COLUMNS c 
                        ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
                WHERE t.TABLE_TYPE = 'BASE TABLE'
                AND t.TABLE_SCHEMA = %s
                AND t.TABLE_NAME = %s
                GROUP BY t.TABLE_NAME
                SQL,
      $this->quoteIdentifier($database),
      $this->quoteIdentifier($table),
      $this->getDatabaseNameSQL($database),
      $this->quoteStringLiteral($table)
    );
  }
  protected  function quoteStringLiteral(string $str): string
  {
    $str = str_replace('\\', '\\\\', $str);
    return "'" . str_replace("'", "''", $str) . "'";
  }
  protected  function quoteIdentifier(string $str): string
  {
    return "`" . str_replace("`", "``", $str) . "`";
  }
  protected  function getCurrentDatabaseExpression(): string
  {
    return 'DATABASE()';
  }
}
