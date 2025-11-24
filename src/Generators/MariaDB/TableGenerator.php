<?php

namespace AdvancedMigration\Generators\MariaDB;

use AdvancedMigration\Generators\MySQL\TableGenerator as MySQLTableGenerator;

class TableGenerator extends MySQLTableGenerator
{
    public static function driver(): string
    {
        return  \AdvancedMigration\Constants::MARIADB_DRIVER;
    }
}
