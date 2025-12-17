<?php

namespace AdvancedMigration\GeneratorManagers;

use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use Illuminate\Support\Facades\DB;

use AdvancedMigration\Generators\Postgres\ViewGenerator;
use AdvancedMigration\Generators\Postgres\TableGenerator;

class PostgresGeneratorManager extends BaseGeneratorManager implements GeneratorManagerInterface
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::PGSQL_DRIVER;
    }

    public function init()
    {
        /**
         * PostgreSQL stores tables and views in pg_catalog.
         * We filter system schemas and keep only public (or user schemas).
         */
        $rows = DB::select("
            SELECT table_name, table_type
            FROM information_schema.tables
            WHERE table_schema NOT IN ('pg_catalog', 'information_schema')
            ORDER BY table_name;
        ");

        foreach ($rows as $row) {
            $table = $row->table_name;
            $type  = strtoupper($row->table_type); // BASE TABLE, VIEW
            if (!$this->isAllowedType($table, $type)) {
                continue;
            }
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
