<?php

namespace AdvancedMigration\Generators\SqlServer;

use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseTableGenerator;
use AdvancedMigration\Tokenizers\SqlServer\ColumnTokenizer;
use AdvancedMigration\Tokenizers\SqlServer\IndexTokenizer;

class TableGenerator extends BaseTableGenerator
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::SQLSRV_DRIVER;
    }

    public function getResolvedStructure(): array
    {
        $table = $this->definition()->getTableName();
        $schema = $this->definition()->getSchema() ?? 'dbo';

        $columns = DB::select("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$schema, $table]);

        $lines = [];
        foreach ($columns as $c) {
            $line = "[" . $c->COLUMN_NAME . "] " . $c->DATA_TYPE;
            if ($c->CHARACTER_MAXIMUM_LENGTH !== null) {
                $line .= "(" . $c->CHARACTER_MAXIMUM_LENGTH . ")";
            }
            if ($c->COLUMN_DEFAULT !== null) {
                $line .= " DEFAULT " . $c->COLUMN_DEFAULT;
            }
            if ($c->IS_NULLABLE === 'NO') {
                $line .= " NOT NULL";
            }
            $lines[] = $line;
        }

        $pks = DB::select("
            SELECT k.COLUMN_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k ON t.CONSTRAINT_NAME = k.CONSTRAINT_NAME
            WHERE t.TABLE_SCHEMA = ? AND t.TABLE_NAME = ? AND t.CONSTRAINT_TYPE = 'PRIMARY KEY'
            ORDER BY k.ORDINAL_POSITION
        ", [$schema, $table]);

        if (count($pks)) {
            $cols = array_map(fn($i) => "[" . $i->COLUMN_NAME . "]", $pks);
            $lines[] = "CONSTRAINT pk_{$table} PRIMARY KEY (" . implode(',', $cols) . ")";
        }

        $fks = DB::select("
            SELECT fk.name as fk_name, pc.name as column_name, rt.name as referenced_table, rc.name as referenced_column
            FROM sys.foreign_key_columns fkc
            JOIN sys.foreign_keys fk ON fkc.constraint_object_id = fk.object_id
            JOIN sys.columns pc ON fkc.parent_object_id = pc.object_id AND fkc.parent_column_id = pc.column_id
            JOIN sys.columns rc ON fkc.referenced_object_id = rc.object_id AND fkc.referenced_column_id = rc.column_id
            JOIN sys.tables pt ON fkc.parent_object_id = pt.object_id
            JOIN sys.tables rt ON fkc.referenced_object_id = rt.object_id
            WHERE pt.name = ?
            ORDER BY fk.name
        ", [$table]);

        $grouped = [];
        foreach ($fks as $f) {
            $grouped[$f->fk_name][] = $f;
        }
        foreach ($grouped as $fkName => $items) {
            $cols = array_map(fn($i) => "[" . $i->column_name . "]", $items);
            $refTable = $items[0]->referenced_table;
            $refCols = array_map(fn($i) => "[" . $i->referenced_column . "]", $items);
            $lines[] = "CONSTRAINT {$fkName} FOREIGN KEY (" . implode(',', $cols) . ") REFERENCES [{$refTable}] (" . implode(',', $refCols) . ")";
        }

        return $lines;
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
