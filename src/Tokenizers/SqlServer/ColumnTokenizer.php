<?php

namespace AdvancedMigration\Tokenizers\SqlServer;

use AdvancedMigration\Tokenizers\BaseColumnTokenizer;

class ColumnTokenizer extends BaseColumnTokenizer
{
    protected $columnDataType;

    public function tokenize(): self
    {
        $this->definition->setColumnName($this->parseColumn($this->consume()));
        $type = $this->consume();
        $this->columnDataType = strtolower($type ?? '');
        $this->definition->setMethodName($this->columnDataType);

        while ($t = $this->consume()) {
            $u = strtoupper($t);
            if ($u === 'DEFAULT') {
                $this->definition->setDefaultValue($this->consume());
            } elseif ($u === 'NOT') {
                $this->consume();
                $this->definition->setNullable(false);
            } elseif ($u === 'NULL') {
                $this->definition->setNullable(true);
            } elseif ($u === 'PRIMARY') {
                $this->consume();
                $this->definition->setPrimary(true);
            } elseif ($u === 'IDENTITY' || stripos($t, 'IDENTITY') !== false) {
                $this->definition->setAutoIncrementing(true);
            }
        }

        return $this;
    }

    public static function parse(string $line)
    {
        $t = new static($line);
        $t->iniTokens();
        return $t->tokenize();
    }
}
