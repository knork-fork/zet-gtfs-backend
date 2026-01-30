<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use App\System\Database\Entity;
use PDOException;
use RuntimeException;

/**
 * @template T of Entity
 */
interface AbstractRepositoryInterface
{
    /**
     * @param T $object
     *
     * @return T
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function save(object $object, bool $forceInsert = false): object;

    /**
     * @param T $object
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function delete(object $object): void;

    /**
     * @param int[]|string[] $ids
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function deleteByIds(array $ids): void;

    /**
     * @return T
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getBy(string $property, mixed $value): object;

    /**
     * @return array<int, array<string, scalar>>
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getArrayBy(string $property, mixed $value): array;

    /**
     * @return array<int, array<string, scalar>>
     *
     * @throws PDOException
     * @throws RuntimeException
     */
    public function getAll(): array;
}
