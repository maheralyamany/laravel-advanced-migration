<?php
namespace AdvancedMigration\Generators\Postgres;

use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseTableGenerator;
use AdvancedMigration\Tokenizers\Postgres\ColumnTokenizer;
use AdvancedMigration\Tokenizers\Postgres\IndexTokenizer;
use Illuminate\Support\Str;

class TableGenerator extends BaseTableGenerator
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::PGSQL_DRIVER;
    }

    public function resolveStructure()
    {
        $table = $this->definition()->getTableName();

        $columns = DB::select("
            SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
            FROM information_schema.columns
            WHERE table_name = ?
            ORDER BY ordinal_position
        ", [$table]);

        $lines = [];
        foreach ($columns as $col) {
            $line = '"' . $col->column_name . '" ' . $col->data_type;
            if (!empty($col->character_maximum_length)) {
                $line .= '(' . $col->character_maximum_length . ')';
            }
            if ($col->column_default !== null) {
                $line .= ' DEFAULT ' . $col->column_default;
            }
            if ($col->is_nullable === 'NO') {
                $line .= ' NOT NULL';
            }
            $lines[] = $line;
        }

        $constraints = DB::select("
            SELECT tc.constraint_type, tc.constraint_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            LEFT JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name AND tc.table_name = kcu.table_name
            LEFT JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
            WHERE tc.table_name = ?
            ORDER BY tc.constraint_name
        ", [$table]);

        $grouped = [];
        foreach ($constraints as $c) {
            $grouped[$c->constraint_name][] = $c;
        }
        foreach ($grouped as $cname => $items) {
            $type = strtoupper($items[0]->constraint_type);
            if ($type === 'PRIMARY KEY') {
                $cols = array_map(fn($i) => $i->column_name, $items);
                $lines[] = "CONSTRAINT {$cname} PRIMARY KEY (" . implode(',', array_map(fn($c) => '"' . $c . '"', $cols)) . ")";
            } elseif ($type === 'UNIQUE') {
                $cols = array_map(fn($i) => $i->column_name, $items);
                $lines[] = "CONSTRAINT {$cname} UNIQUE (" . implode(',', array_map(fn($c) => '"' . $c . '"', $cols)) . ")";
            } elseif ($type === 'FOREIGN KEY') {
                $cols = array_map(fn($i) => $i->column_name, $items);
                $refTable = $items[0]->foreign_table_name;
                $refCols = array_map(fn($i) => $i->foreign_column_name, $items);
                $lines[] = "CONSTRAINT {$cname} FOREIGN KEY (" . implode(',', array_map(fn($c) => '"' . $c . '"', $cols)) . ") REFERENCES \"{$refTable}\" (" . implode(',', array_map(fn($c) => '"' . $c . '"', $refCols)) . ")";
            }
        }

        $this->rows = $lines;
    }

    protected function isColumnLine($line)
    {
        return ! Str::startsWith($line, ['CONSTRAINT', 'PRIMARY', 'UNIQUE', 'FOREIGN']);
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