<?php
/**
 * PrestaShop 1.7 database adapter for the purchase outbox.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Keep the legacy Db dependency isolated from the modern database path. */
class TecMatomoLegacyPurchaseConnection
{
    /** Execute a write or schema statement and report failures to the caller. */
    public function execute($sql)
    {
        if (!Db::getInstance()->execute($sql)) {
            throw new RuntimeException('Purchase storage unavailable.');
        }
    }

    /** Read the primary database without query caching; leases must never be stale. */
    public function rows($sql)
    {
        $rows = Db::getInstance()->executeS($sql, true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Purchase storage unavailable.');
        }

        return $rows;
    }

    /** Escape exact SQL string values on the legacy path. */
    public function quote($value)
    {
        return "'" . pSQL((string) $value) . "'";
    }
}
