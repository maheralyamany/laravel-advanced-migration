<?php

namespace App\Generators\MySQL;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Generators\BaseTableGenerator;
use App\Tokenizers\MySQL\ColumnTokenizer;
use App\Tokenizers\MySQL\IndexTokenizer;

/**
 * Class TableGenerator
 * @package App\Generators\MySQL
 */
class TableGenerator extends BaseTableGenerator
{
    public static function driver(): string
    {
        return 'mysql';
    }

    public function resolveStructure()
    {
        $structure = DB::select('SHOW CREATE TABLE `' . $this->definition()->getTableName() . '`');
        $structure = $structure[0];
        $structure = (array) $structure;
        if (isset($structure['Create Table'])) {
            $lines = explode("\n", $structure['Create Table']);

            array_shift($lines); //get rid of first line
            array_pop($lines); //get rid of last line

            $lines = array_map(fn($item) => trim($item), $lines);
            $this->rows = $lines;
        } else {
            $this->rows = [];
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
