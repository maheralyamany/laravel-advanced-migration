<?php
namespace AdvancedMigration\GeneratorManagers;

use AdvancedMigration\Constants;
use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\MySQL\ViewGenerator;
use AdvancedMigration\Generators\MySQL\TableGenerator;
class MySQLGeneratorManager extends BaseGeneratorManager implements GeneratorManagerInterface
{
    public static function driver(): string
    {
        return Constants::MYSQL_DRIVER;
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
