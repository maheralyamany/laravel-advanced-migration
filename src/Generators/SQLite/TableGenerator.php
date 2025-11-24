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
        // استخراج الجزء داخل الأقواس
        preg_match('/CREATE TABLE.*?\((.*)\)/is', $sql, $m);
        $inner = trim($m[1]);
        // تقسيم الأعمدة مع احترام الأقواس الداخلية
        $parts = preg_split('/,(?![^()]*\))/m', $inner);
        $columns = [];
        foreach ($parts as $part) {
            $part = trim($part);
            // تجاهل القيود مثل PRIMARY KEY..
            if (!preg_match('/^"([^"]+)"/', $part)) {
                continue;
            }
            preg_match('/"([^"]+)"\s+(.+)/', $part, $col);
            /* $columns[] = [
                'name' => ,
                'definition' => $col[2], // النوع + الخصائص
            ]; */
            $columns[] = $col[1] . ' ' . $col[2] . ',';
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
