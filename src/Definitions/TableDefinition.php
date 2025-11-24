<?php

namespace AdvancedMigration\Definitions;

use AdvancedMigration\Constants;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Formatters\TableFormatter;

class TableDefinition
{
    //MySQLTableDefinition
    protected string $tableName;
    protected string $driver;
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
    public function getDriver(): string
    {
        return $this->driver;
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
    public function getListTableMetadataMySqlQuery(string $table, ?string $database = null): string
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
    public function getListTableMetadataPostgreQuery(string $table, ?string $database = null): string
    {
        $database = $this->getDatabaseName($database);
        $_table=$this->quoteStringLiteral($table);
        $_database=$this->quoteStringLiteral($database);
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
    public function getListTableMetadataSQLiteQuery(string $table, ?string $database = null): string
    {
        $database = $this->getDatabaseName($database);
         $_table=$this->quoteStringLiteral($table);
       
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
    public function getListTableMetadataSqlServerQuery(string $table, ?string $database = null): string
    {
        $database = $this->getDatabaseName($database);
         $_table=$this->quoteStringLiteral($table);
        $_database=$this->quoteStringLiteral($database);
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
    public function getListTableMetadataSQL(string $table, ?string $database = null): string
    {
       
        $query = match ($this->getDriver()) {
            Constants::MYSQL_DRIVER => $this->getListTableMetadataMySqlQuery($table, $database),
            Constants::PGSQL_DRIVER => $this->getListTableMetadataPostgreQuery($table, $database),
           Constants::SQLITE_DRIVER => $this->getListTableMetadataSQLiteQuery($table, $database),
            Constants::SQLSRV_DRIVER => $this->getListTableMetadataSqlServerQuery($table, $database),
            'sqlsrv' => $this->getListTableMetadataSqlServerQuery($table, $database),
            default => throw new \RuntimeException("Unsupported database driver: {$this->getDriver()}")
        };
       
        return  $query;
    }


    private function getDatabaseName($database = null): string
    {
        if ($database == null) {
            return DB::getDatabaseName();
        }
        return $database;
    }

    /* private function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped
        $c = $this->getStringLiteralQuoteCharacter();
        return $c . str_replace($c, $c . $c, $str) . $c;
    } */
    /**
     * {@inheritDoc}
     */
    private function quoteStringLiteral(string $str): string
    {
        switch ($this->getDriver()) {
            case  Constants::MYSQL_DRIVER:
                return $this->quoteStringLiteralMySQL($str);
            case Constants::PGSQL_DRIVER:
                return $this->quoteStringLiteralPgSQL($str);
            case Constants::SQLITE_DRIVER:
                return $this->quoteStringLiteralSQLite($str);
            case 'sqlsrv':
            case  Constants::SQLSRV_DRIVER:
                return $this->quoteStringLiteralSQLServer($str);
            default:
                throw new \RuntimeException("Unsupported database driver: {$this->getDriver()}");
        }
    }

    // MySQL
    private function quoteStringLiteralMySQL(string $str): string
    {
        $str = str_replace('\\', '\\\\', $str);
        return "'" . str_replace("'", "''", $str) . "'";
    }

    // PostgreSQL
    private function quoteStringLiteralPgSQL(string $str): string
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }

    // SQLite
    private function quoteStringLiteralSQLite(string $str): string
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }

    // SQL Server
    private function quoteStringLiteralSQLServer(string $str): string
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }
    /**
     * Quotes a table/column/database identifier safely.
     *
     * @param string $str
     * @return string
     */
    private function quoteIdentifier(string $str): string
    {
        switch ($this->getDriver()) {
            case  Constants::MYSQL_DRIVER:
                return $this->quoteIdentifierMySQL($str);
            case Constants::PGSQL_DRIVER:
                return $this->quoteIdentifierPgSQL($str);
            case Constants::SQLITE_DRIVER:
                return $this->quoteIdentifierSQLite($str);
            case 'sqlsrv':
            case  Constants::SQLSRV_DRIVER:
                return $this->quoteIdentifierSQLServer($str);
            default:
                throw new \RuntimeException("Unsupported database driver: {$this->getDriver()}");
        }
    }

    // MySQL uses backticks
    private function quoteIdentifierMySQL(string $str): string
    {
        return "`" . str_replace("`", "``", $str) . "`";
    }

    // PostgreSQL uses double quotes
    private function quoteIdentifierPgSQL(string $str): string
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // SQLite uses double quotes
    private function quoteIdentifierSQLite(string $str): string
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // SQL Server uses square brackets
    private function quoteIdentifierSQLServer(string $str): string
    {
        return "[" . str_replace("]", "]]", $str) . "]";
    }
    /**
     * Returns quoted database name or default expression.
     *
     * @param string|null $databaseName
     * @return string
     */
    private function getDatabaseNameSQL(?string $databaseName): string
    {
        if ($databaseName !== null) {
            return $this->quoteStringLiteral($databaseName);
        }

        return $this->getCurrentDatabaseExpression();
    }

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
    private function getStringLiteralQuoteCharacter()
    {
        return "'";
    }
    private function getCurrentDatabaseExpression(): string
    {
        switch ($this->getDriver()) {
            case  Constants::MYSQL_DRIVER:
                return 'DATABASE()';
            case  Constants::PGSQL_DRIVER:
                return 'current_database()';
            case Constants::SQLITE_DRIVER:
                return "''"; // SQLite لا يدعم أكثر من قاعدة بيانات في الاتصال الحالي
            case 'sqlsrv':
            case  Constants::SQLSRV_DRIVER:
                return 'DB_NAME()';
            default:
                throw new \RuntimeException("Unsupported database driver: {$this->getDriver()}");
        }
    }
    public function formatter(): TableFormatter
    {
        return new TableFormatter($this);
    }
}
