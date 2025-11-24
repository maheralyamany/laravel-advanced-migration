<?php

namespace AdvancedMigration\GeneratorManagers;

use AdvancedMigration\Constants;
use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\SQLite\TableGenerator;
use AdvancedMigration\Generators\SQLite\ViewGenerator;

class SQLiteGeneratorManager extends BaseGeneratorManager implements GeneratorManagerInterface
{
    public static function driver(): string
    {
        return Constants::SQLITE_DRIVER;
    }

    public function init()
    {
        $rows = DB::select("
            SELECT name, type
            FROM sqlite_master
            WHERE type IN ('table', 'view')
              AND name NOT LIKE 'sqlite_%'
            ORDER BY name;
        ");

        foreach ($rows as $row) {
            $name = $row->name;
            $type = strtolower($row->type);

            if ($type === 'table') {
                $this->addTableName($name);
                $this->addTableDefinition(
                    TableGenerator::init($name)->definition()
                );

            } elseif ($type === 'view') {
                $this->addViewDefinition(
                    ViewGenerator::init($name)->definition()
                );
            }
        }
    }
}
