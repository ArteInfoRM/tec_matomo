<?php
/**
 * Scheduled purchase outbox runner. Never accepts HTTP requests or URL secrets.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';
if (!defined('_PS_VERSION_')) {
    exit;
}
$options = getopt('', ['shop:', 'limit:', 'retry-failed']);
$shopId = isset($options['shop']) ? $options['shop'] : '';
$limit = isset($options['limit']) ? $options['limit'] : '10';
if (!is_string($shopId) || !preg_match('/^[1-9][0-9]{0,8}$/D', $shopId)
    || !is_string($limit) || !preg_match('/^[1-9][0-9]?$/D', $limit) || (int) $limit > 20) {
    fwrite(STDERR, "Usage: php modules/tec_matomo/bin/process-purchases.php --shop=1 --limit=10\n");
    exit(1);
}
$shop = new Shop((int) $shopId);
if (!Validate::isLoadedObject($shop) || !$shop->active) {
    fwrite(STDERR, "Invalid or inactive shop.\n");
    exit(1);
}
Shop::setContext(Shop::CONTEXT_SHOP, (int) $shopId);
Context::getContext()->shop = $shop;
if (!Module::isEnabled('tec_matomo')) {
    fwrite(STDERR, "Module disabled in this shop.\n");
    exit(1);
}
try {
    $module = Module::getInstanceByName('tec_matomo');
    $result = $module->processPurchaseQueue((int) $shopId, (int) $limit, isset($options['retry-failed']));
    echo json_encode($result) . "\n";
    if ($result['failed'] > 0) {
        exit(2);
    }
} catch (Exception $exception) {
    fwrite(STDERR, "Purchase worker failed. Check module configuration and database availability.\n");
    exit(1);
}
