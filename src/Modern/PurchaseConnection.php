<?php
/**
 * Doctrine DBAL adapter for PrestaShop 8 and later.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
declare(strict_types=1);

namespace TecMatomo\Modern;

if (!defined('_PS_VERSION_')) {
    exit;
}

/** Provide the outbox repository with an injected Doctrine connection. */
final class PurchaseConnection
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** Retain PHP 7.2 syntax for the earliest PrestaShop 8 runtimes. */
    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        $this->connection = $connection;
    }

    /** Support the DBAL versions supplied by the supported PrestaShop branches. */
    public function execute(string $sql): void
    {
        if (method_exists($this->connection, 'executeStatement')) {
            $this->connection->executeStatement($sql);
        } else {
            $this->connection->executeUpdate($sql);
        }
    }

    /** Fetch associative rows with the installed DBAL API. */
    public function rows(string $sql): array
    {
        if (method_exists($this->connection, 'fetchAllAssociative')) {
            return $this->connection->fetchAllAssociative($sql);
        }

        return $this->connection->fetchAll($sql);
    }

    /** Quote a validated string value with the active database driver. */
    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }
}
