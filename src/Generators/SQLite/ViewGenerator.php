<?php

namespace AdvancedMigration\Generators\SQLite;

use AdvancedMigration\Constants;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseViewGenerator;

class ViewGenerator extends BaseViewGenerator
{
    public static function driver(): string
    {
        return Constants::SQLITE_DRIVER;
    }

    public function getResolvedSchema(): string|null
    {
        $view = $this->definition()->getViewName();
        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type='view' AND name = ?", [$view]);

        if (count($rows) === 0 || empty($rows[0]->sql)) {

            return null;
        }
        $sql = $rows[0]->sql;
        $schema = preg_split('/\r?\n/', trim($sql));
         return  $schema;
    }

  
}
