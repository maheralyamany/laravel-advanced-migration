<?php

namespace AdvancedMigration\Tokenizers\Postgres;

use AdvancedMigration\Tokenizers\BaseColumnTokenizer;
use Illuminate\Support\Str;

class ColumnTokenizer extends BaseColumnTokenizer
{
    protected $columnDataType;

    public function tokenize(): self
    {
        $this->definition->setColumnName($this->parseColumn($this->consume()));
        $type = $this->consume();
        $this->columnDataType = strtolower($type ?? '');
        $this->definition->setMethodName($this->columnDataType);

        while ($token = $this->consume()) {
            $u = strtoupper($token);
            if ($u === 'DEFAULT') {
                $this->definition->setDefaultValue($this->consume());
            } elseif ($u === 'NOT') {
                $this->consume(); // NULL
                $this->definition->setNullable(false);
            } elseif ($u === 'NULL') {
                $this->definition->setNullable(true);
            } elseif ($u === 'PRIMARY') {
                $this->consume(); // KEY
                $this->definition->setPrimary(true);
            } elseif ($u === 'UNIQUE') {
                $this->definition->setUnique(true);
            } elseif ($u === 'GENERATED' || $u === 'AS') {
                $this->putBack($token);
                $this->consumeGenerated();
            }
        }

        return $this;
    }

    protected function consumeGenerated()
    {
        $next = $this->consume();
        if ($next === null) return;
        if (strtoupper($next) === 'GENERATED') {
            while ($t = $this->consume()) {
                if (strtoupper($t) === 'STORED' || strtoupper($t) === 'VIRTUAL') break;
            }
        } else {
            $this->putBack($next);
        }
    }

    public static function parse(string $line)
    {
        $t = new static($line);
        $t->iniTokens();
        return $t->tokenize();
    }
}