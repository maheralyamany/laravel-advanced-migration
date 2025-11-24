<?php

namespace AdvancedMigration\Definitions;

use Illuminate\Support\Facades\DB;
use AdvancedMigration\Formatters\TableFormatter;

class TableDefinition
{
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
       ccsa.CHARACTER_SET_NAME
FROM information_schema.TABLES t
    INNER JOIN information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` ccsa
        ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = %s AND TABLE_NAME = %s
SQL,
            $this->getDatabaseNameSQL($database),
            $this->quoteStringLiteral($table),
        );
    }
    private function getDatabaseName($database = null): string
    {
        if ($database == null) {

            return DB::getDatabaseName();
        }
        return $database;
    }
    /**
     * {@inheritDoc}
     */
    private function quoteStringLiteral($str)
    {
        $str = str_replace('\\', '\\\\', $str); // MySQL requires backslashes to be escaped
        $c = $this->getStringLiteralQuoteCharacter();
        return $c . str_replace($c, $c . $c, $str) . $c;
    }
    private function supportsColumnLengthIndexes(): bool
    {
        return true;
    }
    private function getDatabaseNameSQL(?string $databaseName): string
    {
        if ($databaseName !== null) {
            return $this->quoteStringLiteral($databaseName);
        }
        return $this->getCurrentDatabaseExpression();
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
        return 'DATABASE()';
    }
    public function formatter(): TableFormatter
    {
        return new TableFormatter($this);
    }


}
