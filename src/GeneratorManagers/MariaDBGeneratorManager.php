<?php

declare(strict_types=1);

namespace AdvancedMigration\GeneratorManagers;



class MariaDBGeneratorManager extends MySQLGeneratorManager
{
  public static function driver(): string
  {
    return \AdvancedMigration\Constants::MARIADB_DRIVER;
  }
}
