<?php

namespace AdvancedMigration\Generators\SqlServer;

use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseViewGenerator;

class ViewGenerator extends BaseViewGenerator
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::SQLSRV_DRIVER;
    }

    public function getResolvedSchema(): string|null
    {
        $schema = $this->definition()->getSchema() ?? 'dbo';
        $view = $this->definition()->getViewName();
        $row = DB::selectOne("
            SELECT definition FROM sys.sql_modules m
            JOIN sys.views v ON m.object_id = v.object_id
            WHERE OBJECT_SCHEMA_NAME(v.object_id) = ? AND OBJECT_NAME(v.object_id) = ?
        ", [$schema, $view]);

        if (! $row || empty($row->definition)) {

            return null;
        }
        $schema = preg_split('/\r?\n/', $row->definition);
        return  $schema;
    }
}
