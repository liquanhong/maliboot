<?php

declare(strict_types=1);

namespace MaliBoot\Lombok\Ast\Generator;

use MaliBoot\Lombok\Ast\AbstractClassVisitor;
use MaliBoot\Lombok\contract\GetterAnnotationInterface;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use ReflectionAttribute;

class GetterGenerator extends AbstractClassVisitor
{
    protected function handle(): void
    {
        foreach ($this->class_->getProperties() as $property_) {
            $this->isStmtBuild($property_) && $this->buildStmt($property_);
        }
    }

    protected function enable(): bool
    {
        return true;
    }

    protected function isStmtBuild(Property $property_): bool
    {
        $fieldName = $property_->props[0]->name->name;
        // 不覆盖已存在的方法
        if ($this->reflectionClass->hasMethod('get' . ucfirst($fieldName))) {
            return false;
        }

        // 类注解
        $attributes = $this->reflectionClass->getAttributes(GetterAnnotationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (! empty($attributes)) {
            return true;
        }

        // 类属性注解
        $reflectionProperty = $this->reflectionClass->getProperty($fieldName);
        $attributes = $reflectionProperty->getAttributes(GetterAnnotationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (! empty($attributes)) {
            return true;
        }
        return false;
    }

    protected function buildStmt(Property $property_): void
    {
        $fieldName = $property_->props[0]->name->name;
        $fieldType = $property_->type;
        $fun = new ClassMethod('get' . ucfirst($fieldName));
        $fun->returnType = $fieldType;
        $fun->stmts[] = new Return_(
            new PropertyFetch(
                new Variable('this'),
                new Identifier($fieldName)
            ),
        );
        $this->class_->stmts = array_merge($this->class_->stmts, [$fun]);
    }
}