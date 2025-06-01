<?php
declare(strict_types=1);

namespace App\System\Database;

use KnorkFork\LoadEnvironment\Environment;
use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?Connection $instance = null;

    private PDO $pdo;

    public function __construct()
    {
        $host = Environment::getStringEnv('DB_HOST');
        $port = Environment::getStringEnv('DB_PORT');
        $name = Environment::getStringEnv('DB_NAME');
        $user = Environment::getStringEnv('DB_USER');
        $password = Environment::getStringEnv('DB_PASSWORD');

        $this->pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$name}",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /**
     * @param mixed[] $params
     *
     * @return mixed[]
     *
     * @throws PDOException
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare statement');
        }

        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to fetch results: ' . $e->getMessage());
        }

        return $stmt->fetchAll();
    }

    public static function getInstance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self();

        return self::$instance;
    }
}
