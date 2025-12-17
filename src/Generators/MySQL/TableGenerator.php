<?php

namespace AdvancedMigration\Generators\MySQL;

use AdvancedMigration\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use AdvancedMigration\Generators\BaseTableGenerator;
use AdvancedMigration\Tokenizers\MySQL\ColumnTokenizer;
use AdvancedMigration\Tokenizers\MySQL\IndexTokenizer;

/**
 * Class TableGenerator
 * @package AdvancedMigration\Generators\MySQL
 */
class TableGenerator extends BaseTableGenerator
{
    public static function driver(): string
    {
        return Constants::MYSQL_DRIVER;
    }

    public function getResolvedStructure(): array
    {
         $table = $this->definition()->getTableName();
        $structure = DB::select('SHOW CREATE TABLE `' . $table . '`');
        $structure = $structure[0];
        $structure = (array) $structure;
        if (isset($structure['Create Table'])) {
            $lines = explode("\n", $structure['Create Table']);
            array_shift($lines); //get rid of first line
            array_pop($lines); //get rid of last line
            $lines = array_map(fn($item) => trim($item), $lines);

           return $lines;
        } else {
            return [];
        }
    }

    protected function isColumnLine($line)
    {
        return !Str::startsWith($line, ['KEY', 'PRIMARY', 'UNIQUE', 'FULLTEXT', 'CONSTRAINT']);
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
