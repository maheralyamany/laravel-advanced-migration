<?php

namespace AdvancedMigration\Generators;

use AdvancedMigration\Definitions\ViewDefinition;
use AdvancedMigration\Generators\Interfaces\ViewGeneratorInterface;

abstract class BaseViewGenerator implements ViewGeneratorInterface
{
    protected ViewDefinition $definition;

    public function __construct(string $viewName, ?string $schema = null)
    {
        $this->definition = new ViewDefinition([
            'driver'   => static::driver(),
            'viewName' => $viewName,
            'schema'   => $schema
        ]);
    }
    public abstract function getResolvedSchema(): string|null;
    public function definition(): ViewDefinition
    {
        return $this->definition;
    }

    public static function init(string $viewName, ?string $schema = null)
    {
        $obj = new static($viewName, $schema);
        if ($schema === null) {
            $obj->resolveSchema();
        }
        $obj->parse();

        return $obj;
    }

    public function getParsedSchema(): string
    {
        $schema = $this->definition()->getSchema();
        return $schema;
    }
    public function resolveSchema()
    {
        $schema = $this->getResolvedSchema();
        if (is_null($schema) || empty($schema))
            return;
        $this->definition()->setSchema($schema);
    }
    public function parse()
    {
        $schema = $this->getParsedSchema();

        $this->definition()->setSchema($schema);
    }
}
