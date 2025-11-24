<?php

namespace AdvancedMigration\Tokenizers;

use AdvancedMigration\Definitions\IndexDefinition;
use AdvancedMigration\Tokenizers\Interfaces\IndexTokenizerInterface;

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
    public function tokenize(): self{
        return $this;
     }
}
