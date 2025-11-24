<?php

namespace AdvancedMigration\Tokenizers\SqlServer;

use AdvancedMigration\Tokenizers\BaseIndexTokenizer;

class IndexTokenizer extends BaseIndexTokenizer
{
    public function tokenize(): self
    {
        $first = $this->consume();
        if (strtoupper($first) === 'CONSTRAINT') {
            $name = $this->parseColumn($this->consume());
            $type = strtoupper($this->consume());
            if ($type === 'PRIMARY') {
                $this->consume();
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('primary')->setIndexName($name)->setIndexColumns($cols);
            } elseif ($type === 'FOREIGN') {
                $this->consume();
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('foreign')->setIndexName($name)->setIndexColumns($cols);
                $this->consume();
                $refTable = $this->parseColumn($this->consume());
                $this->definition->setForeignReferencedTable($refTable);
                $this->definition->setForeignReferencedColumns($this->columnsToArray($this->consume()));
            } elseif ($type === 'UNIQUE') {
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('unique')->setIndexName($name)->setIndexColumns($cols);
            }
        } else {
            $this->putBack($first);
            $cols = $this->columnsToArray($this->consume());
            $this->definition->setIndexType('index')->setIndexColumns($cols);
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
