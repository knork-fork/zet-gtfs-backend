<?php
declare(strict_types=1);

namespace App\Repository;

use App\System\Database\Connection;
use App\System\Database\Entity;
use PDOException;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;

/**
 * @template T of Entity
 */
abstract class AbstractRepository
{
    /**
     * @return class-string<T>
     */
    abstract protected function getEntityClass(): string;

    abstract protected function getTableName(): string;

    private Connection $connection;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    /**
     * @param T $object
     *
     * return T
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function save(object $object): object
    {
        $reflection = new ReflectionObject($object);
        $publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $properties = array_map(
            static fn (ReflectionProperty $property) => $property->getName(),
            array_filter(
                $publicProperties,
                static fn (ReflectionProperty $property) => $property->getName() !== 'id'
            )
        );

        $values = [];
        foreach ($properties as $property) {
            $values[$property] = $this->{$property};
        }

        if ($object->id === null) {
            $query = \sprintf(
                'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
                $this->getTableName(),
                implode(', ', $properties),
                implode(', ', array_map(static fn ($property) => ':' . $property, $properties))
            );
        } else {
            // todo: finish update query
            $query = 'UPDATE';
        }

        $ret = $this->connection->query($query, $values);

        if (\count($ret) !== 1 || \count($ret[0]) !== 1) {
            throw new RuntimeException('Failed to save entity');
        }

        $object->id = (int) $ret[0]['id'];

        return $object;
    }

    /**
     * @param T $object
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function delete(object $object): void
    {
        if ($object->id === null) {
            throw new RuntimeException('Entity not saved');
        }

        $query = \sprintf(
            'DELETE FROM %s WHERE id = :id',
            $this->getTableName()
        );

        $this->connection->query($query, ['id' => $object->id]);
        $object->id = null;
    }

    /**
     * @return T
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getBy(string $property, mixed $value): object
    {
        $query = \sprintf(
            'SELECT * FROM %s WHERE %s = :value',
            $this->getTableName(),
            $property
        );

        $result = $this->connection->query($query, ['value' => $value]);

        if (\count($result) !== 1) {
            throw new RuntimeException('Failed to get entity');
        }

        $entityClass = $this->getEntityClass();

        return (new $entityClass())->hydrate($result[0]);
    }

    /**
     * @return array<int, array<string, scalar>>
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getArrayBy(string $property, mixed $value): array
    {
        $query = \sprintf(
            'SELECT * FROM %s WHERE %s = :value',
            $this->getTableName(),
            $property
        );

        return $this->connection->query($query, ['value' => $value]);
    }
}
