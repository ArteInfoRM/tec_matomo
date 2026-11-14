<?php
/**
 * Persistent purchase outbox with atomic leases and bounded retries.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** SQL operations use the injected version-specific database adapter. */
class TecMatomoPurchaseRepository
{
    /** @var object Version-specific database adapter. */
    private $database;
    /** @var string Validated table identifier. */
    private $table;
    /** @var string Core order table, read only. */
    private $orders;

    /** The adapter exposes execute, rows and quote without runtime credentials. */
    public function __construct($database, $prefix)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $prefix)) {
            throw new InvalidArgumentException('Invalid table prefix.');
        }
        $this->database = $database;
        $this->table = '`' . $prefix . 'tec_matomo_purchase`';
        $this->orders = '`' . $prefix . 'orders`';
    }

    /** Installation and upgrades run schema creation; request hooks never perform DDL. */
    public function install()
    {
        $this->database->execute('CREATE TABLE IF NOT EXISTS ' . $this->table . ' (
            id_purchase BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_shop INT UNSIGNED NOT NULL,
            id_order INT UNSIGNED NOT NULL,
            target_hash CHAR(64) NOT NULL,
            status VARCHAR(12) NOT NULL DEFAULT \'pending\',
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            next_attempt DATETIME NOT NULL,
            locked_until DATETIME NULL,
            lease CHAR(32) NULL,
            error_code VARCHAR(32) NOT NULL DEFAULT \'\',
            date_add DATETIME NOT NULL,
            date_upd DATETIME NOT NULL,
            PRIMARY KEY (id_purchase),
            UNIQUE KEY order_target (id_shop, id_order, target_hash),
            KEY due (id_shop, status, next_attempt)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        return true;
    }

    /** A sent purchase is never requeued for the same shop/order/target. */
    public function enqueue($shop, $order, $target)
    {
        if ($shop < 1 || $order < 1 || !preg_match('/^[a-f0-9]{64}$/D', $target)) {
            throw new InvalidArgumentException('Invalid purchase identity.');
        }
        $this->database->execute('INSERT IGNORE INTO ' . $this->table
            . ' (id_shop,id_order,target_hash,next_attempt,date_add,date_upd) VALUES ('
            . (int) $shop . ',' . (int) $order . ',' . $this->database->quote($target) . ',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())');
        $this->database->execute('UPDATE ' . $this->table . ' SET status=\'pending\',attempts=0,error_code=\'\',next_attempt=UTC_TIMESTAMP()'
            . ' WHERE id_shop=' . (int) $shop . ' AND id_order=' . (int) $order
            . ' AND target_hash=' . $this->database->quote($target) . ' AND status=\'skipped\'');
    }

    /** Capture an order-ID boundary independently of shop timezones and clock changes. */
    public function firstOrderId()
    {
        $rows = $this->database->rows('SELECT COALESCE(MAX(id_order),0)+1 AS first_order FROM ' . $this->orders);
        $ids = TecMatomoPurchasePolicy::stateIds([$rows[0]['first_order']]);

        return $ids[0];
    }

    /** Recover missed hooks without replaying acknowledged or historical orders. */
    public function missingOrders($shop, array $states, $firstOrder, $target, $limit)
    {
        if ($shop < 1 || $limit < 1 || $limit > 20 || !preg_match('/^[a-f0-9]{64}$/D', $target)
            || !is_scalar($firstOrder)) {
            throw new InvalidArgumentException('Invalid purchase reconciliation.');
        }
        $first = TecMatomoPurchasePolicy::stateIds([$firstOrder]);
        $states = TecMatomoPurchasePolicy::stateIds($states);
        if (!$states) {
            return [];
        }

        return $this->database->rows('SELECT DISTINCT o.id_order FROM ' . $this->orders . ' o LEFT JOIN ' . $this->table
            . ' p ON p.id_shop=o.id_shop AND p.id_order=o.id_order WHERE o.id_shop=' . (int) $shop
            . ' AND o.id_order>=' . $first[0] . ' AND o.current_state IN (' . implode(',', $states) . ')'
            . ' AND (p.id_purchase IS NULL OR (p.status=\'skipped\' AND p.target_hash=' . $this->database->quote($target) . '))'
            . ' ORDER BY o.id_order LIMIT ' . (int) $limit);
    }

    /** Select only this shop, including expired leases after a worker crash. */
    public function due($shop, $limit)
    {
        if ($shop < 1 || $limit < 1 || $limit > 20) {
            throw new InvalidArgumentException('Invalid purchase batch.');
        }

        $this->database->execute('UPDATE ' . $this->table . ' SET status=\'failed\',error_code=\'lease_expired\',lease=NULL,locked_until=NULL'
            . ' WHERE id_shop=' . (int) $shop . ' AND status=\'sending\' AND attempts>=10 AND locked_until<UTC_TIMESTAMP()');

        return $this->database->rows('SELECT * FROM ' . $this->table . ' WHERE id_shop=' . (int) $shop
            . ' AND attempts<10 AND ((status=\'pending\' AND next_attempt<=UTC_TIMESTAMP())'
            . ' OR (status=\'sending\' AND locked_until<UTC_TIMESTAMP())) ORDER BY id_purchase LIMIT ' . (int) $limit);
    }

    /** A conditional update gives exactly one worker ownership of an attempt. */
    public function claim($id, $shop, $lease)
    {
        $this->database->execute('UPDATE ' . $this->table . ' SET status=\'sending\', attempts=attempts+1,'
            . ' lease=' . $this->database->quote($lease) . ', locked_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 SECOND),date_upd=UTC_TIMESTAMP()'
            . ' WHERE id_purchase=' . (int) $id . ' AND id_shop=' . (int) $shop . ' AND attempts<10'
            . ' AND ((status=\'pending\' AND next_attempt<=UTC_TIMESTAMP()) OR (status=\'sending\' AND locked_until<UTC_TIMESTAMP()))');
        $rows = $this->database->rows('SELECT id_purchase FROM ' . $this->table . ' WHERE id_purchase=' . (int) $id
            . ' AND id_shop=' . (int) $shop . ' AND lease=' . $this->database->quote($lease) . ' AND status=\'sending\'');

        return count($rows) === 1;
    }

    /** Save only a fixed diagnostic code, never payloads, tokens or API responses. */
    public function finish($id, $shop, $lease, $status, $error, $delay)
    {
        if (!in_array($status, ['pending', 'sent', 'failed', 'skipped'], true)
            || !preg_match('/^[a-z_]{0,32}$/D', $error) || $delay < 0 || $delay > 3600) {
            throw new InvalidArgumentException('Invalid purchase result.');
        }
        $this->database->execute('UPDATE ' . $this->table . ' SET status=' . $this->database->quote($status)
            . ',error_code=' . $this->database->quote($error) . ',lease=NULL,locked_until=NULL,date_upd=UTC_TIMESTAMP(),'
            . 'next_attempt=DATE_ADD(UTC_TIMESTAMP(),INTERVAL ' . (int) $delay . ' SECOND) WHERE id_purchase=' . (int) $id
            . ' AND id_shop=' . (int) $shop . ' AND lease=' . $this->database->quote($lease));
    }
    /** Explicit operator retry never resets purchases already acknowledged by Matomo. */
    public function retryFailed($shop)
    {
        if (!is_int($shop) || $shop < 1) {
            throw new InvalidArgumentException('Invalid shop.');
        }
        $this->database->execute('UPDATE ' . $this->table . ' SET status=\'pending\',attempts=0,error_code=\'\',next_attempt=UTC_TIMESTAMP()'
            . ' WHERE id_shop=' . $shop . ' AND status=\'failed\'');
    }

    /** Expose aggregate worker health without order identifiers or payloads. */
    public function counts($shop)
    {
        $counts = ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($this->database->rows('SELECT status,COUNT(*) AS total FROM ' . $this->table . ' WHERE id_shop=' . (int) $shop . ' GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }

        return $counts;
    }

}
