<?php

namespace AdvancedMigration\Tokenizers\Interfaces;

use AdvancedMigration\Definitions\IndexDefinition;

interface IndexTokenizerInterface
{
    public function tokenize(): self;

    public function definition(): IndexDefinition;
}
