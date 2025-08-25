<?php

namespace App\Tokenizers;

use App\Definitions\IndexDefinition;
use App\Tokenizers\Interfaces\IndexTokenizerInterface;

abstract class BaseIndexTokenizer extends BaseTokenizer implements IndexTokenizerInterface
{
    protected IndexDefinition $definition;

    public function __construct(string $value)
    {
        $this->definition = new IndexDefinition();
        parent::__construct($value);
    }

    public function definition(): IndexDefinition
    {
        return $this->definition;
    }
}
