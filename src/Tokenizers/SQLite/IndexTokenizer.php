<?php

namespace AdvancedMigration\Tokenizers\SQLite;

use AdvancedMigration\Tokenizers\BaseIndexTokenizer;

class IndexTokenizer extends BaseIndexTokenizer
{
    protected $line;
    public function __construct(string $line)
    {
        $this->line = trim($line);
        parent::__construct($line);
    }
    public function tokenize(): self
    {
        $this->consumeIndexType();
        if ($this->definition->getIndexType() === 'foreign') {
            $this->consumeForeignKey();
        }elseif ($this->definition->getIndexType() === 'primary') {
            $this->consumePrimaryKey();
        } else {
            //$this->consumeIndexColumns();
        }


        return $this;
    }
    private function consumeIndexType()
    {
        $piece = $this->consume();
        $upper = strtoupper($piece);
        if (in_array($upper, ['PRIMARY', 'UNIQUE', 'FULLTEXT'])) {
            $this->definition->setIndexType(strtolower($piece));
            //$this->consume(); //just the word KEY
        } elseif ($upper === 'KEY') {
            $this->definition->setIndexType('index');
        } elseif ($upper === 'FOREIGN') {

            $this->definition->setIndexType('foreign');
        }
         $this->putBack($piece);
    }
    private function consumeIndexColumns()
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
            $cols = $this->columnsToArray($first . ' ' . implode(' ', iterator_to_array($this->tokens, false)));
            $this->definition->setIndexType('index')->setIndexColumns($cols);
        }
    }
    private function consumePrimaryKey(){

    }
    private function consumeForeignKey()
    {
        if (preg_match('/foreign key\s*\(([^)]+)\) references\s+"?(\w+)"?\s*\(([^)]+)\)(.*)/i', $this->line, $f)) {
            $columns = array_map('trim', array_map(fn($v) => trim($v, '"'), explode(',', $f[1])));
            $refTable = $f[2];
            $refColumns = array_map('trim', array_map(fn($v) => trim($v, '"'), explode(',', $f[3])));
            $on_delete = preg_match('/on delete (cascade|set null|set default|restrict)/i', $f[4], $d) ? strtolower($d[1]) : null;
            $on_update = preg_match('/on update (cascade|set null|set default|restrict)/i', $f[4], $u) ? strtolower($u[1]) : null;
            $this->definition->setIndexColumns($columns);
            $this->definition->setForeignReferencedTable($refTable);
            $this->definition->setForeignReferencedColumns($refColumns);
            $this->consumeConstraintActions(['delete' => $on_delete, 'update' => $on_update]);

        }

    }
    private function consumeConstraintActions(array $actions)
    {

        foreach ($actions as $actionType => $actionMethod) {
            if (is_null($actionMethod))
                continue;
            $currentActions = $this->definition->getConstraintActions();
            $currentActions[$actionType] = $actionMethod;
            $this->definition->setConstraintActions($currentActions);
        }
    }
    public static function parse(string $line)
    {
        $tokenizer = new static($line);
        $tokenizer->iniTokens();
        return $tokenizer->tokenize();
    }
}
