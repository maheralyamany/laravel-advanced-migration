<?php

namespace AdvancedMigration\Tokenizers;

use AdvancedMigration\Definitions\ColumnDefinition;
use AdvancedMigration\Tokenizers\Interfaces\ColumnTokenizerInterface;

abstract class BaseColumnTokenizer extends BaseTokenizer implements ColumnTokenizerInterface
{
    protected ColumnDefinition $definition;

    public function __construct(string $value)
    {
        $this->definition = new ColumnDefinition();
        parent::__construct($value);
    }

    public function definition(): ColumnDefinition
    {
        return $this->definition;
    }
    public function tokenize(): self{
        return $this;
     }
}
