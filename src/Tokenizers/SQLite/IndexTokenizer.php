<?php
namespace AdvancedMigration\Tokenizers\SQLite;

use AdvancedMigration\Tokenizers\BaseIndexTokenizer;

class IndexTokenizer extends BaseIndexTokenizer
{
    public function tokenize(): self
    {
        $first = $this->consume();
        $upper = strtoupper($first);
        if ($upper === 'CONSTRAINT') {
            $name = $this->parseColumn($this->consume());
            $type = strtoupper($this->consume());
            if ($type === 'PRIMARY') {
                $this->consume();
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('primary')->setIndexName($name)->setIndexColumns($cols);
            } elseif ($type === 'UNIQUE') {
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('unique')->setIndexName($name)->setIndexColumns($cols);
            } elseif ($type === 'FOREIGN') {
                $this->consume();
                $cols = $this->columnsToArray($this->consume());
                $this->definition->setIndexType('foreign')->setIndexName($name)->setIndexColumns($cols);
                $this->consume();
                $refTable = $this->parseColumn($this->consume());
                $this->definition->setForeignReferencedTable($refTable);
                $this->definition->setForeignReferencedColumns($this->columnsToArray($this->consume()));
            }
        } else {
            $cols = $this->columnsToArray($first . ' ' . implode(' ', iterator_to_array($this->tokens(), false)));
            $this->definition->setIndexType('index')->setIndexColumns($cols);
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
