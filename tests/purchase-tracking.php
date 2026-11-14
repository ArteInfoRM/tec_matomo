<?php
/**
 * Isolated purchase regression checks; never contact Matomo or write shop data.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
define('_PS_VERSION_', '1.7.5.0');
class Module
{
    public $smarty;
    public $context;
    public $hooks = [];
    public $registrationResult = true;
    public function l($text) { return $text; }
    public function registerHook($name, $shops = null) { $this->hooks[$name] = $shops; return $this->registrationResult; }
    public function display($file, $template) { return 'purchase'; }
}
class Configuration
{
    public static $values = [];
    public static $globals = [];
    public static function getGlobalValue($key) { return isset(self::$globals[$key]) ? self::$globals[$key] : false; }
    public static function updateGlobalValue($key, $value) { self::$globals[$key] = $value; return true; }
    public static function get($key, $language = null, $group = null, $shop = null)
    {
        return isset(self::$values[(int) $shop][$key]) ? self::$values[(int) $shop][$key] : false;
    }
}
class Shop
{
    public $id;
    public $id_shop_group = 1;
    public $active = true;
    public function __construct($id) { $this->id = $id; }
    public static function getShops($active, $group, $ids) { return [1, 2]; }
}
class OrderState
{
    public static function getOrderStates($language)
    {
        return [['id_order_state' => 2, 'paid' => 1], ['id_order_state' => 5, 'paid' => 1], ['id_order_state' => 8, 'paid' => 0], ['id_order_state' => 10, 'paid' => 0], ['id_order_state' => 12, 'paid' => 0]];
    }
}
class Tools
{
    public static $input = [];
    public static function getValue($key, $default = false) { return isset(self::$input[$key]) ? self::$input[$key] : $default; }
}
class Validate
{
    public static function isLoadedObject($object) { return $object->id > 0; }
}
class Product
{
    public $category = 'Fixture category';
    public function __construct($id, $full, $language) {}
}
class PrestaShopLogger
{
    public static function addLog($message, $level) {}
}
class CaptureSmarty
{
    public $values;
    public function assign($values) { $this->values = $values; }
}
class Order
{
    public static $orders = [];
    public $id;
    public $id_shop = 1;
    public $id_lang = 1;
    public $current_state = 2;
    public $reference = 'FIXTURE';
    public $date_add = '2026-11-14 12:00:00';
    public $total_paid = 31.60;
    public $total_paid_real = 0;
    public $total_paid_tax_excl = 25.90;
    public $total_products = 25.90;
    public $total_shipping = 0;
    public $total_discounts = 0;
    public function __construct($id)
    {
        $this->id = $id;
        if (isset(self::$orders[$id])) {
            foreach (get_object_vars(self::$orders[$id]) as $key => $value) { $this->$key = $value; }
        }
    }
    public function getOrderDetailList()
    {
        return [['product_id' => 1, 'product_attribute_id' => 0, 'product_name' => 'Fixture product', 'unit_price_tax_incl' => $this->total_paid, 'product_quantity' => 1]];
    }
}
class MemoryOutbox
{
    public $rows = [];
    public $claimAllowed = true;
    public $missing = [];
    public $boundary = 42;
    public function install() { return true; }
    public function firstOrderId() { return $this->boundary; }
    public function enqueue($shop, $order, $hash)
    {
        foreach ($this->rows as &$row) {
            if ($row['id_shop'] === $shop && $row['id_order'] === $order && $row['target_hash'] === $hash) {
                if ($row['status'] === 'skipped') { $row['status'] = 'pending'; $row['attempts'] = 0; }
                return;
            }
        }
        $id = count($this->rows) + 1;
        $this->rows[$id] = ['id_purchase' => $id, 'id_shop' => $shop, 'id_order' => $order, 'target_hash' => $hash, 'status' => 'pending', 'attempts' => 0];
    }
    public function missingOrders($shop, array $states, $since, $target, $limit)
    {
        return $this->missing;
    }
    public function due($shop, $limit)
    {
        return array_slice(array_values(array_filter($this->rows, function ($row) use ($shop) { return $row['id_shop'] === $shop && $row['status'] === 'pending'; })), 0, $limit);
    }
    public function claim($id, $shop, $lease)
    {
        if (!$this->claimAllowed || $this->rows[$id]['status'] !== 'pending') { return false; }
        $this->rows[$id]['status'] = 'sending';
        $this->rows[$id]['lease'] = $lease;
        ++$this->rows[$id]['attempts'];
        return true;
    }
    public function finish($id, $shop, $lease, $status, $error, $delay)
    {
        if ($this->rows[$id]['lease'] === $lease) { $this->rows[$id]['status'] = $status; $this->rows[$id]['delay'] = $delay; }
    }
    public function counts($shop)
    {
        $result = ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($this->rows as $row) {
            if ($row['id_shop'] === $shop) { ++$result[$row['status']]; }
        }
        return $result;
    }
    public function retryFailed($shop)
    {
        foreach ($this->rows as &$row) {
            if ($row['id_shop'] === $shop && $row['status'] === 'failed') { $row['status'] = 'pending'; $row['attempts'] = 0; }
        }
    }
}
require dirname(__DIR__) . '/tec_matomo.php';
class PurchaseModuleFixture extends Tec_matomo
{
    public $outbox;
    public $sent = [];
    public $deliver = true;
    protected function purchaseRepository() { return $this->outbox; }
    protected function sendPurchase(array $target, array $payload, $order)
    {
        $this->sent[] = ['target' => $target, 'payload' => $payload];
        return $this->deliver;
    }
    public function validateForm() { return $this->validatePurchaseForm(); }
}
$checks = 0;
$verify = function ($condition, $message) use (&$checks) {
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
};
$make = function () {
    $module = (new ReflectionClass('PurchaseModuleFixture'))->newInstanceWithoutConstructor();
    $module->smarty = new CaptureSmarty();
    $module->context = (object) ['shop' => new Shop(1), 'language' => (object) ['id' => 1]];
    $module->outbox = new MemoryOutbox();
    return $module;
};
$base = ['TEC_MATOMO_ACTIVE' => 1, 'TEC_MATOMO_ECOMMERCE' => 1, 'TEC_MATOMO_PRIVACY_MODE' => 'cookieless', 'TEC_MATOMO_CONSENT_MANAGER' => 'disabled', 'TEC_MATOMO_SITEID' => 1, 'TEC_MATOMO_URL' => 'https://matomo.example/', 'TEC_MATOMO_PURCHASE_FIRST_ORDER' => 1, 'PS_OS_ERROR' => 8, 'PS_OS_CANCELED' => 6, 'PS_OS_REFUND' => 7, 'PS_OS_OUTOFSTOCK_UNPAID' => 12];
Configuration::$values = [1 => $base, 2 => array_replace($base, ['TEC_MATOMO_SITEID' => 2])];
foreach ([8 => false, 12 => false, 10 => false, 2 => true, 5 => true] as $state => $expected) {
    $module = $make(); $order = new Order(1); $order->current_state = $state;
    $result = $module->hookDisplayOrderConfirmation(['order' => $order]);
    $verify(($result === 'purchase') === $expected, 'Default eligibility must follow paid states, not the order amount.');
}
Configuration::$values[1]['TEC_MATOMO_PURCHASE_STATES'] = '[2,8,10,12]';
foreach ([8 => false, 12 => false, 10 => true, 2 => true] as $state => $expected) {
    $module = $make(); $order = new Order(1); $order->current_state = $state;
    $verify(($module->hookDisplayOrderConfirmation(['order' => $order]) === 'purchase') === $expected, 'Hard exclusions override selected states; explicit pending methods remain configurable.');
}
Configuration::$values[1] = $base;
$module = $make(); $order = new Order(1); $order->total_paid = 0; $order->total_paid_tax_excl = 0; $order->total_products = 0;
$verify($module->hookDisplayOrderConfirmation(['order' => $order]) === 'purchase', 'A valid free order must remain trackable.');
$verify($module->smarty->values['order_id'] === 'FIXTURE-s1-o1', 'Purchase IDs must include shop and order identity.');
$verify($module->hookDisplayOrderConfirmation(['order' => $order]) === '', 'Duplicate hooks in the same request must not emit twice.');
Configuration::$values[1]['TEC_MATOMO_PURCHASE_FIRST_ORDER'] = 2;
$verify($make()->hookDisplayOrderConfirmation(['order' => $order]) === '', 'Historical orders must not be replayed with new IDs.');
Configuration::$values[1]['TEC_MATOMO_PURCHASE_FIRST_ORDER'] = 1;
Configuration::$values[1]['TEC_MATOMO_PURCHASE_SERVER'] = 1;
$order = new Order(1); $order->current_state = 8; Order::$orders[1] = clone $order;
$module = $make();
$verify($module->hookDisplayOrderConfirmation(['order' => $order]) === '' && !$module->outbox->rows, 'Failed orders must not be queued or rendered.');
$order->current_state = 2; Order::$orders[1] = clone $order;
$module->hookActionOrderStatusPostUpdate(['id_order' => 1, 'newOrderStatus' => (object) ['id' => 2]]);
$module->hookActionValidateOrder(['order' => $order]);
$verify(count($module->outbox->rows) === 1 && !$module->sent, 'Payment hooks enqueue once without network calls.');
$verify($module->hookDisplayOrderConfirmation(['order' => $order]) === '', 'Server mode replaces the browser purchase event.');
$module->deliver = false;
$stats = $module->processPurchaseQueue(1);
$verify($stats['retried'] === 1 && $module->outbox->rows[1]['delay'] === 60, 'Delivery failures must remain retryable with backoff.');
$module->deliver = true;
$stats = $module->processPurchaseQueue(1);
$verify($stats['sent'] === 1 && count($module->sent) === 2, 'An asynchronous payment can be delivered without a confirmation page.');
$module->hookActionValidateOrder(['order' => $order]);
$verify($module->processPurchaseQueue(1)['sent'] === 0 && count($module->sent) === 2, 'An acknowledged order must not be sent again.');
$order2 = new Order(2); $order2->id_shop = 2; Order::$orders[2] = clone $order2;
Configuration::$values[2]['TEC_MATOMO_PURCHASE_SERVER'] = 1;
$module->hookActionValidateOrder(['order' => $order2]);
$verify($module->processPurchaseQueue(1)['sent'] === 0, 'Workers must not process another shop.');
$verify($module->processPurchaseQueue(2)['sent'] === 1 && end($module->sent)['target']['idsite'] === 2, 'Use the order shop Matomo settings.');
$module = $make(); $module->hookActionValidateOrder(['order' => $order]); $module->outbox->claimAllowed = false;
$verify($module->processPurchaseQueue(1)['sent'] === 0 && !$module->sent, 'A lost claim must not send a request.');
$module->outbox->claimAllowed = true; $module->deliver = false; $module->outbox->rows[1]['attempts'] = 9;
$verify($module->processPurchaseQueue(1)['failed'] === 1, 'Retries must stop at the configured bound.');
$module->deliver = true;
$verify($module->processPurchaseQueue(1, 10, true)['sent'] === 1, 'An explicit operator retry can resume a failed delivery.');
$module = $make(); $module->hookActionValidateOrder(['order' => $order]);
Configuration::$values[1]['TEC_MATOMO_SITEID'] = 3;
$verify($module->processPurchaseQueue(1)['skipped'] === 1 && !$module->sent, 'A target change must not misroute a queued purchase.');
Configuration::$values[1]['TEC_MATOMO_SITEID'] = 1;
Configuration::$values[1]['TEC_MATOMO_PRIVACY_MODE'] = 'consent';
$module = $make(); $module->hookActionValidateOrder(['order' => $order]);
$verify(!$module->outbox->rows && !$module->sent, 'Server delivery must not bypass consent-controlled tracking.');
Configuration::$values[1] = $base;
Configuration::$values[1]['TEC_MATOMO_PURCHASE_SERVER'] = 1;
$module = $make(); $module->outbox->missing = [['id_order' => 1]];
$verify($module->processPurchaseQueue(1)['sent'] === 1, 'The worker must recover a missed payment hook.');
Configuration::$values[1] = $base;
Tools::$input = ['TEC_MATOMO_PURCHASE_STATES' => ['2', '10'], 'TEC_MATOMO_PURCHASE_SERVER' => '1', 'TEC_MATOMO_PRIVACY_MODE' => 'cookieless', 'TEC_MATOMO_CONSENT_MANAGER' => 'disabled', 'TEC_MATOMO_URL' => 'https://matomo.example/', 'TEC_MATOMO_SITEID' => '1'];
$module = $make();
$verify($module->validateForm()['states'] === '[2,10]', 'Supported pending states can be explicitly selected.');
foreach (['TEC_MATOMO_PURCHASE_STATES' => [8], 'TEC_MATOMO_PURCHASE_SERVER' => 'yes', 'TEC_MATOMO_PRIVACY_MODE' => 'consent', 'TEC_MATOMO_CONSENT_MANAGER' => 'cookiebot', 'TEC_MATOMO_URL' => 'file:///tmp/invalid', 'TEC_MATOMO_SITEID' => '1foo', 'TEC_MATOMO_PURCHASE_TOKEN' => 'invalid'] as $key => $value) {
    $old = Tools::$input; Tools::$input[$key] = $value; $rejected = false;
    try { $module->validateForm(); } catch (InvalidArgumentException $exception) { $rejected = true; }
    $verify($rejected, 'Reject unsafe settings before saving: ' . $key); Tools::$input = $old;
}
Configuration::$values[1]['TEC_MATOMO_PURCHASE_TOKEN'] = str_repeat('a', 32);
foreach (['', '********aaaa'] as $unchanged) {
    Tools::$input['TEC_MATOMO_PURCHASE_TOKEN'] = $unchanged;
    $verify($module->validateForm()['token'] === str_repeat('a', 32), 'Preserve stored tokens on empty or masked input.');
}
foreach ([[], ['1;DROP TABLE'], [0], ['0002'], [true], [2147483648]] as $invalid) {
    if ($invalid === []) { continue; }
    $rejected = false;
    try { TecMatomoPurchasePolicy::stateIds($invalid); } catch (InvalidArgumentException $exception) { $rejected = true; }
    $verify($rejected, 'Invalid IDs must be rejected.');
}
foreach (['javascript:alert(1)', 'https://user:secret@example.org/', 'https://example.org/?url=other', "https://example.org/\n", 'https://example.org/#fragment'] as $url) {
    $rejected = false;
    try { TecMatomoPurchasePolicy::baseUrl($url); } catch (InvalidArgumentException $exception) { $rejected = true; }
    $verify($rejected, 'Invalid targets must be rejected.');
}
foreach (['1;alert(1)', NAN, INF, -1] as $amount) {
    $rejected = false;
    try { TecMatomoPurchasePolicy::amount($amount); } catch (InvalidArgumentException $exception) { $rejected = true; }
    $verify($rejected, 'Unsafe amounts must not enter JavaScript or API requests.');
}
require dirname(__DIR__) . '/upgrade/upgrade-1.3.0.php';
$module = $make();
$verify(upgrade_module_1_3_0($module), 'The purchase upgrade must create storage and register hooks.');
$verify(Configuration::$globals['TEC_MATOMO_PURCHASE_FIRST_ORDER'] === 42, 'The first upgrade records the replay boundary.');
$verify($module->hooks === ['actionOrderStatusPostUpdate' => [1, 2], 'actionValidateOrder' => [1, 2]], 'Lifecycle hooks must be associated across shop scopes.');
$module->outbox->boundary = 100;
$verify(upgrade_module_1_3_0($module) && Configuration::$globals['TEC_MATOMO_PURCHASE_FIRST_ORDER'] === 42, 'An upgrade retry must preserve the original replay boundary.');
$module->registrationResult = false;
$verify(!upgrade_module_1_3_0($module), 'A failed hook registration must fail the upgrade.');
$module->setPurchaseWorkerShop(new Shop(2));
$verify($module->context->shop->id === 2, 'The CLI runner must bind the selected shop through the module context.');
$inactive = new Shop(2); $inactive->active = false; $rejected = false;
try { $module->setPurchaseWorkerShop($inactive); } catch (InvalidArgumentException $exception) { $rejected = true; }
$verify($rejected, 'An inactive worker shop must be rejected.');
Configuration::$globals['TEC_MATOMO_PURCHASE_FIRST_ORDER'] = '';
$module->registrationResult = true;
$verify(upgrade_module_1_3_0($module) && Configuration::$globals['TEC_MATOMO_PURCHASE_FIRST_ORDER'] === 100, 'A missing or empty string checkpoint must initialize correctly.');
echo $checks . " purchase tracking checks passed.\n";
