<?php
/**
 * Purchase storage integration checks in disposable tables within a local test shop.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
require dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';
require_once dirname(__DIR__) . '/tec_matomo.php';
require_once dirname(__DIR__) . '/classes/LegacyPurchaseConnection.php';
class PurchaseFactoryFixture extends Tec_matomo
{
    public function adapter()
    {
        $repository = $this->purchaseRepository();
        $property = new ReflectionProperty('TecMatomoPurchaseRepository', 'database');
        $property->setAccessible(true);

        return $property->getValue($repository);
    }
}
$factory = (new ReflectionClass('PurchaseFactoryFixture'))->newInstanceWithoutConstructor();
$adapters = ['legacy' => new TecMatomoLegacyPurchaseConnection()];
if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
    $adapters['modern'] = $factory->adapter();
}
$checks = 0;
$verify = function ($condition, $message) use (&$checks) {
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
};
foreach ($adapters as $name => $database) {
    $prefix = 'mtmtest_' . substr(hash('sha256', uniqid((string) getmypid(), true)), 0, 12) . '_' . $name . '_';
    $table = '`' . $prefix . 'tec_matomo_purchase`';
    $repository = new TecMatomoPurchaseRepository($database, $prefix);
    $target = str_repeat('a', 64);
    $lease = str_repeat('b', 32);
    try {
        $verify($repository->install(), 'Schema must be created.');
        $database->execute('CREATE TABLE `' . $prefix . 'orders` (id_order INT PRIMARY KEY,id_shop INT,current_state INT,date_add DATETIME)');
        $database->execute('INSERT INTO `' . $prefix . 'orders` VALUES (111,1,2,\'2026-11-14 12:00:00\'),(222,2,2,\'2026-11-14 12:00:00\'),(333,1,8,\'2026-11-14 12:00:00\'),(44,1,2,\'2026-11-13 12:00:00\')');
        $verify($repository->firstOrderId() === 334, 'The upgrade boundary must use the next order ID.');
        $missing = $repository->missingOrders(1, [2], 100, $target, 10);
        $verify(count($missing) === 1 && (int) $missing[0]['id_order'] === 111, 'Reconciliation must respect state, cutoff and shop.');
        $repository->enqueue(1, 111, $target);
        $repository->enqueue(1, 111, $target);
        $repository->enqueue(2, 222, $target);
        $rows = $repository->due(1, 10);
        $verify(count($rows) === 1 && (int) $rows[0]['id_shop'] === 1, 'Unique enqueue and explicit shop scoping.');
        $id = (int) $rows[0]['id_purchase'];
        $verify($repository->claim($id, 1, $lease), 'First worker acquires the lease.');
        $verify(!$repository->claim($id, 1, str_repeat('c', 32)), 'Second worker cannot acquire a live lease.');
        $repository->finish($id, 1, str_repeat('c', 32), 'sent', '', 0);
        $verify($database->rows('SELECT status FROM ' . $table . ' WHERE id_purchase=' . $id)[0]['status'] === 'sending', 'A stale worker cannot acknowledge another lease.');
        $repository->finish($id, 1, $lease, 'sent', '', 0);
        $repository->enqueue(1, 111, $target);
        $repository->retryFailed(1);
        $verify(!$repository->due(1, 10), 'Sent purchases must never be reset.');
        $verify(!$repository->missingOrders(1, [2], 100, str_repeat('c', 64), 10), 'A new target must not replay orders already tracked elsewhere.');
        $repository->enqueue(1, 333, $target);
        $row = $repository->due(1, 10)[0];
        $id = (int) $row['id_purchase'];
        $repository->claim($id, 1, $lease);
        $repository->finish($id, 1, $lease, 'pending', 'delivery_failed', 3600);
        $verify(!$repository->due(1, 10), 'Backoff must prevent immediate retry.');
        $database->execute('UPDATE ' . $table . ' SET status=\'sending\',attempts=10,locked_until=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE id_purchase=' . $id);
        $repository->due(1, 10);
        $verify($database->rows('SELECT status FROM ' . $table . ' WHERE id_purchase=' . $id)[0]['status'] === 'failed', 'An expired final lease must become failed.');
        $repository->retryFailed(1);
        $row = $repository->due(1, 10)[0];
        $verify((int) $row['attempts'] === 0, 'Explicit retry resets attempts only for failures.');
        $repository->claim($id, 1, $lease);
        $repository->finish($id, 1, $lease, 'skipped', 'not_eligible', 0);
        $repository->enqueue(1, 333, $target);
        $verify(count($repository->due(1, 10)) === 1, 'A newly eligible order can resume an unsent skipped entry.');
        $verify(count($repository->due(2, 10)) === 1, 'Another shop queue must remain intact.');
        $counts = $repository->counts(1);
        $verify($counts['sent'] === 1 && $counts['pending'] === 1, 'Worker health must reflect only this shop.');
        echo $name . " database adapter passed.\n";
    } finally {
        $database->execute('DROP TABLE IF EXISTS ' . $table);
        $database->execute('DROP TABLE IF EXISTS `' . $prefix . 'orders`');
    }
}
echo $checks . " database storage checks passed.\n";
