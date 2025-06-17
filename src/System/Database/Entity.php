<?php
declare(strict_types=1);

namespace App\System\Database;

use ReflectionNamedType;
use ReflectionObject;
use ReflectionProperty;
use ReflectionType;

abstract class Entity
{
    public ?int $id = null;

    /**
     * @param mixed[] $data
     */
    public function hydrate(array $data): static
    {
        $reflection = new ReflectionObject($this);
        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($publicProperties as $property) {
            $name = $property->getName();

            if (\array_key_exists($name, $data)) {
                $value = $this->castToPropertyType($data[$name], $property->getType());
                $this->{$name} = $value;
            }
        }

        return $this;
    }

    private function castToPropertyType(mixed $value, ?ReflectionType $propertyType): mixed
    {
        if ($propertyType === null || !($propertyType instanceof ReflectionNamedType)) {
            return $value;
        }

        if ($propertyType->getName() === 'int') {
            return (int) $value;
        }

        return $value;
    }
}
