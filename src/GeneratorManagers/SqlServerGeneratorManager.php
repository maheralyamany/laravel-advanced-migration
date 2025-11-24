<?php

namespace AdvancedMigration\GeneratorManagers;

use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\SqlServer\TableGenerator;
use AdvancedMigration\Generators\SqlServer\ViewGenerator;

class SqlServerGeneratorManager extends BaseGeneratorManager implements GeneratorManagerInterface
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::SQLSRV_DRIVER;
    }

    public function init()
    {
        $rows = DB::select("
            SELECT TABLE_NAME, TABLE_TYPE
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = 'dbo'
            ORDER BY TABLE_NAME;
        ");

        foreach ($rows as $row) {
            $table = $row->TABLE_NAME;
            $type  = strtoupper($row->TABLE_TYPE); // BASE TABLE أو VIEW

            if ($type === 'BASE TABLE') {
                $this->addTableName($table);
                $this->addTableDefinition(
                    TableGenerator::init($table)->definition()
                );

            } elseif ($type === 'VIEW') {
                $this->addViewDefinition(
                    ViewGenerator::init($table)->definition()
                );
            }
        }
    }
}
