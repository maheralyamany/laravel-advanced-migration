<?php

namespace AdvancedMigration\Tokenizers\Interfaces;

use AdvancedMigration\Definitions\ColumnDefinition;

interface ColumnTokenizerInterface
{
    public function tokenize(): self;

    public function definition(): ColumnDefinition;
}
