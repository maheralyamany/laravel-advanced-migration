<?php

namespace AdvancedMigration\Generators\MySQL;

use AdvancedMigration\Constants;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\Generators\BaseViewGenerator;
use AdvancedMigration\Generators\Interfaces\ViewGeneratorInterface;

class ViewGenerator extends BaseViewGenerator implements ViewGeneratorInterface
{
    public static function driver(): string
    {
        return Constants::MYSQL_DRIVER;
    }

    public function getResolvedSchema(): string|null
    {
        $structure = DB::select('SHOW CREATE VIEW `' . $this->definition()->getViewName() . '`');
        $structure = $structure[0];
        $structure = (array) $structure;
        if (isset($structure['Create View'])) {
            return $structure['Create View'];
        }
        return null;
    }

    public function getParsedSchema(): string
    {
        $schema = parent::getParsedSchema();
        if (preg_match('/CREATE(.*?)VIEW/', $schema, $matches)) {
            $schema = str_replace($matches[1], ' ', $schema);
        }
        if (preg_match_all('/isnull\((.+?)\)/', $schema, $matches)) {
            foreach ($matches[0] as $key => $match) {
                $schema = str_replace($match, $matches[1][$key] . ' IS NULL', $schema);
            }
        }
        if (preg_match('/collate utf8mb4_unicode_ci/', $schema)) {
            $schema = str_replace('collate utf8mb4_unicode_ci', '', $schema);
        }
        return $schema;
    }
}
