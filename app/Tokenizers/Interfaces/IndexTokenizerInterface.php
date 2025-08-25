<?php

namespace App\Tokenizers\Interfaces;

use App\Definitions\IndexDefinition;

interface IndexTokenizerInterface
{
    public function tokenize(): self;

    public function definition(): IndexDefinition;
}
