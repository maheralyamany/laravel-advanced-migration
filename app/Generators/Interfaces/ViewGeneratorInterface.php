<?php

namespace App\Generators\Interfaces;

interface ViewGeneratorInterface
{
    public static function driver(): string;

    public function parse();

    public function resolveSchema();
}
