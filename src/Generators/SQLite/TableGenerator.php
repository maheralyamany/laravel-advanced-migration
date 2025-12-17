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
    public function getResolvedStructure(): array
    {
        $table = $this->definition()->getTableName();
        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
        if (count($rows) === 0 || empty($rows[0]->sql)) {
            return [];
        }
        $create = $rows[0]->sql;
        $lines = $this->parseColumnsFromCreateSQL($create);
        return $lines;
    }
    protected function parseColumnsFromCreateSQL(string $sql): array
    {
        preg_match('/CREATE TABLE.*?\((.*)\)/is', $sql, $m);
        $body = trim($m[1]);
        // split by commas NOT inside parentheses
        $parts = preg_split('/,(?![^()]*\))/m', $body);
        $columns = [];
        $primaryKeys = [];
        foreach ($parts as $part) {
            $line = trim($part);
            // foreign key (single or composite)
            if (preg_match('/foreign key\s*\(([^)]+)\) references\s+"?(\w+)"?\s*\(([^)]+)\)(.*)/i', $line, $f)) {
                $columns[] = $line;
                continue;
            }
            if (preg_match('/primary\s+key\s*\(([^)]+)\)/i', $line, $pk)) {
                $primaryKeys = array_map('trim', array_map(fn($v) => str_replace('"', "", $v), explode(',', $pk[1])));
                continue;
            }
            if (!preg_match('/^"([^"]+)"/', $line)) {
                continue;
            }
            preg_match('/"([^"]+)"\s+(.+)/', $line, $col);
            $columns[] = $col[1] . ' ' . $col[2] . ',';
        }
        if (!empty($primaryKeys)) {
            foreach ($primaryKeys as $key) {
                $autoIncrement = str_contains(\strtoupper($key), 'AUTOINCREMENT');
                $column = trim(\str_replace(['AUTOINCREMENT', 'autoincrement'], "", $key));
                $columns = collect($columns)->mapWithKeys((function ($line, $i) use ($autoIncrement, $column) {
                    if (\str_starts_with($line, $column . " ")) {
                        $split = explode(" ", $line);
                        $line = \sprintf("%s %s primary key %s,", $split[0], $split[1], $autoIncrement ? 'autoincrement' : '');
                    }
                    return [$i => $line];
                }))->all();
            }
        }
        return $columns;
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
