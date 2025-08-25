<?php

namespace App\Tokenizers\Interfaces;

use App\Definitions\ColumnDefinition;

interface ColumnTokenizerInterface
{
    public function tokenize(): self;

    public function definition(): ColumnDefinition;
}
