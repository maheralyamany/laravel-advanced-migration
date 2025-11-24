<?php
namespace AdvancedMigration\Generators\SQLite;

use AdvancedMigration\Constants;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseTableGenerator;
use AdvancedMigration\Tokenizers\SQLite\ColumnTokenizer;
use AdvancedMigration\Tokenizers\SQLite\IndexTokenizer;

class TableGenerator extends BaseTableGenerator
{
    public static function driver(): string
    {
        return Constants::SQLITE_DRIVER;
    }

    public function resolveStructure()
    {
        $table = $this->definition()->getTableName();
        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
        if (count($rows) === 0 || empty($rows[0]->sql)) {
            $this->rows = [];
            return;
        }
        $create = $rows[0]->sql;
        $lines = preg_split('/\r?\n/', $create);
        $lines = array_map(fn($l) => trim($l), $lines);
        // remove CREATE TABLE line
        array_shift($lines);
        // drop last line if it's just ')' or ');'
        if (count($lines) && preg_match('/^\)+;?$/', end($lines))) {
            array_pop($lines);
        }
        $lines = array_map(fn($l) => rtrim($l, ','), $lines);
        $this->rows = $lines;
    }

    protected function isColumnLine($line)
    {
        return !(stripos($line, 'CONSTRAINT') === 0 || stripos($line, 'PRIMARY') === 0 || stripos($line, 'UNIQUE') === 0 || stripos($line, 'FOREIGN') === 0);
    }

    public function parse()
    {
        foreach ($this->rows as $line) {
            if ($this->isColumnLine($line)) {
                $tokenizer = ColumnTokenizer::parse($line);
                $this->definition()->addColumnDefinition($tokenizer->definition());
            } else {
                $tokenizer = IndexTokenizer::parse($line);
                $this->definition()->addIndexDefinition($tokenizer->definition());
            }
        }
    }
}