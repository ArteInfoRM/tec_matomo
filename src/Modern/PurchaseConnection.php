<?php
/**
 * Doctrine DBAL adapter for PrestaShop 8 and later.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
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

    /** Use the statement API available in the DBAL versions supplied by PS 8+. */
    public function execute(string $sql): void
    {
        $this->connection->executeStatement($sql);
    }

    /** Fetch associative rows through the shared DBAL 2.13 and 3.x API. */
    public function rows(string $sql): array
    {
        return $this->connection->fetchAllAssociative($sql);
    }

    /** Quote a validated string value with the active database driver. */
    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }
}
