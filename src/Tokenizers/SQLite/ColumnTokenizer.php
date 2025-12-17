<?php
namespace AdvancedMigration\Tokenizers\SQLite;
use AdvancedMigration\Tokenizers\BaseColumnTokenizer;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Builder;
class ColumnTokenizer extends BaseColumnTokenizer
{
    protected $columnDataType;
    protected $line;
    public function __construct(string $line)
    {
        $this->line = trim($line);
        parent::__construct($line);
    }
    public function tokenize(): self
    {
        $this->consumeColumnName();
        $this->consumeAutoIncrement();
        $this->consumeColumnType();
        if (!$this->definition->isAutoIncrementing()) {
            while ($token = $this->consume()) {
                $u = strtoupper($token);
                if ($u === 'NOT') {
                    $this->consume();
                    $this->definition->setNullable(false);
                } elseif ($u === 'NULL') {
                    $this->definition->setNullable(true);
                } elseif ($u === 'DEFAULT') {
                    $val = $this->consume();
                    $this->definition->setDefaultValue(trim($val, "'\""));
                } elseif ($u === 'PRIMARY') {
                    $this->consume();
                    $this->definition->setPrimary(true);
                } elseif ($u === 'UNIQUE') {
                    $this->definition->setUnique(true);
                }
            }
        }
        return $this;
    }
    protected function consumeColumnName()
    {
        $this->definition->setColumnName($this->parseColumn($this->consume()));
    }
    protected function consumeColumnType()
    {
        $originalColumnType = $columnType = strtolower($this->consume());
        $hasConstraints = Str::contains($columnType, '(');
        //
        if ($hasConstraints) {
            $columnType = explode('(', $columnType)[0];
        }
        $this->columnDataType = $columnType;
        $this->resolveColumnMethod();
        if (!$this->definition->isAutoIncrementing() && $hasConstraints) {
            preg_match("/\((.+?)\)/", $originalColumnType, $constraintMatches);
            $matches = explode(',', $constraintMatches[1]);
            $this->resolveColumnConstraints($matches);
        }
    }
    private function consumeAutoIncrement()
    {
        if (str_contains(strtoupper($this->line), 'AUTOINCREMENT')) {
            $this->definition->setAutoIncrementing(true);
            return;
        }
    }
    private function resolveColumnConstraints(array $constraints)
    {
        if ($this->columnDataType === 'char' && count($constraints) === 1 && $constraints[0] == 36) {
            //uuid for mysql
            $this->definition->setIsUUID(true);
            return;
        }
        if ($this->isArrayType()) {
            $this->definition->setMethodParameters([array_map(fn($item) => trim($item, '\''), $constraints)]);
        } else {
            if (Str::contains(strtoupper($this->columnDataType), 'INT')) {
                $this->definition->setMethodParameters([]); //laravel does not like display field widths
            } else {
                if ($this->definition->getMethodName() === 'string') {
                    if (count($constraints) === 1) {
                        //has a width set
                        if ($constraints[0] == Builder::$defaultStringLength) {
                            $this->definition->setMethodParameters([]);
                            return;
                        }
                    }
                }
                $this->definition->setMethodParameters(array_map(fn($item) => (int) $item, $constraints));
            }
        }
    }
    private function resolveColumnMethod()
    {
        $mapped = [
            'int' => 'integer',
            'tinyint' => 'tinyInteger',
            'smallint' => 'smallInteger',
            'mediumint' => 'mediumInteger',
            'bigint' => 'bigInteger',
            'varchar' => 'string',
            'tinytext' => 'string', //tinytext is not a valid Blueprint method currently
            'mediumtext' => 'mediumText',
            'longtext' => 'longText',
            'blob' => 'binary',
            'datetime' => 'dateTime',
            'geometrycollection' => 'geometryCollection',
            'linestring' => 'lineString',
            'multilinestring' => 'multiLineString',
            'multipolygon' => 'multiPolygon',
            'multipoint' => 'multiPoint',
        ];
        if ($this->definition->isAutoIncrementing() && str_contains($this->line, 'primary')/* && $this->definition->getColumnName() === 'id' */) {
           /*  if (preg_match('/^id\s+integer\s+primary\s+key\s+autoincrement/i', $this->line)) {
            } */
            $this->definition->setPrimary(true);
            $this->definition->setAutoIncrementing(true);
            $this->definition->setMethodName('id');
            return;
        }
        if (preg_match('/check\s*\([^)]*in\s*\(([^)]+)\)/i', $this->line, $e)) {
            preg_match_all("/'([^']+)'/", $e[1], $vals);
            $prams = $vals[1] ?? [];
            $this->definition->setMethodName('enum');
            $this->definition->setMethodParameters([array_map(fn($item) => trim($item, '\''), $prams)]);
            return;
        }
        if (isset($mapped[$this->columnDataType])) {
            $this->definition->setMethodName($mapped[$this->columnDataType]);
        } else {
            //do some custom resolution
            $this->definition->setMethodName($this->columnDataType);
        }
    }
    protected function isArrayType()
    {
        return Str::contains($this->columnDataType, ['enum', 'set']);
    }
    public static function parse(string $line)
    {
        $tokenizer = new static($line);
        $tokenizer->iniTokens();
        $tokenize =  $tokenizer->tokenize();
        return $tokenize;
    }
    /**
     * @return mixed
     */
    public function getColumnDataType()
    {
        return $this->columnDataType;
    }
}
