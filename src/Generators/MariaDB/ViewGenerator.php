<?php

namespace AdvancedMigration\Generators\MariaDB;

use AdvancedMigration\Generators\MySQL\ViewGenerator as MySQLViewGenerator;

class ViewGenerator extends MySQLViewGenerator
{
    public static function driver(): string
    {
        return  \AdvancedMigration\Constants::MARIADB_DRIVER;
    }
}
