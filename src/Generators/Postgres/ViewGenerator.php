<?php

namespace AdvancedMigration\Generators\Postgres;

use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseViewGenerator;

class ViewGenerator extends BaseViewGenerator
{
    public static function driver(): string
    {
        return \AdvancedMigration\Constants::PGSQL_DRIVER;
    }

    public function getResolvedSchema(): string|null
    {
        $view = $this->definition()->getViewName();
        $rows = DB::select("SELECT pg_get_viewdef(?, true) AS definition", [$view]);
        if (count($rows) === 0 || empty($rows[0]->definition)) {

            return null;
        }
        $schema = preg_split('/\r?\n/', $rows[0]->definition);
        return  $schema;
    }
}
