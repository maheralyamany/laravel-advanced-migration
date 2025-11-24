<?php
namespace AdvancedMigration\Tokenizers\SQLite;
use AdvancedMigration\Tokenizers\BaseColumnTokenizer;
use Illuminate\Support\Str;

class ColumnTokenizer extends BaseColumnTokenizer
{
    public function tokenize(): self
    {
        $this->definition->setColumnName($this->parseColumn($this->consume()));
        $type = $this->consume();
        if ($type !== null) {
            $this->definition->setMethodName(strtolower($type));
        }

        while ($token = $this->consume()) {
            $u = strtoupper($token);
            if ($u === 'NOT') {
                $this->consume();
                $this->definition->setNullable(false);
            } elseif ($u === 'NULL') {
                $this->definition->setNullable(true);
            } elseif ($u === 'DEFAULT') {
                $val = $this->consume();
                $this->definition->setDefaultValue(trim($val, "'\""));
            } elseif ($u === 'PRIMARY') {
                $this->consume();
                $this->definition->setPrimary(true);
            } elseif (stripos($u, 'AUTOINCREMENT') !== false) {
                $this->definition->setAutoIncrementing(true);
            }
        }

        return $this;
    }

    public static function parse(string $line)
    {
        $tokenizer = new static($line);
        $tokenizer->iniTokens();
        return $tokenizer->tokenize();
    }
}
