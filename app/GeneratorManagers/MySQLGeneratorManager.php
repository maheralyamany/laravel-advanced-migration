<?php

namespace App\GeneratorManagers;

use Illuminate\Support\Facades\DB;
use App\Generators\MySQL\ViewGenerator;
use App\Generators\MySQL\TableGenerator;
use App\GeneratorManagers\Interfaces\GeneratorManagerInterface;

class MySQLGeneratorManager extends BaseGeneratorManager /* implements GeneratorManagerInterface */
{
    public static function driver(): string
    {
        return 'mysql';
    }

    public function init()
    {

        $tables = DB::select('SHOW FULL TABLES');

        foreach ($tables as $rowNumber => $table) {
            $tableData = (array) $table;
            $table = $tableData[array_key_first($tableData)];

            $tableType = $tableData['Table_type'];
            if ($tableType === 'BASE TABLE') {
                $this->addTableName($table);
                $this->addTableDefinition(TableGenerator::init($table)->definition());
            } elseif ($tableType === 'VIEW') {
                $this->addViewDefinition(ViewGenerator::init($table)->definition());
            }
        }
    }
}
