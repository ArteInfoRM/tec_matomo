<?php
/**
 * Purchase eligibility, shop-scoped configuration and asynchronous delivery.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/PurchasePolicy.php';
require_once __DIR__ . '/PurchaseRepository.php';

/** Integrate both supported database paths without changing the core or payment modules. */
trait TecMatomoPurchaseTracking
{
    /** Read purchase settings in the order's shop, never the worker's default shop. */
    protected function purchaseConfiguration($key, $shopId)
    {
        $shop = new Shop((int) $shopId);

        return Configuration::get($key, null, (int) $shop->id_shop_group, (int) $shopId);
    }

    /** Build the hard exclusion list using shop-scoped core state settings. */
    protected function deniedPurchaseStates($shopId)
    {
        $ids = [];
        foreach (['PS_OS_ERROR', 'PS_OS_CANCELED', 'PS_OS_REFUND', 'PS_OS_OUTOFSTOCK_UNPAID'] as $key) {
            $ids[] = (int) $this->purchaseConfiguration($key, $shopId);
        }

        return $ids;
    }

    /** Missing configuration uses states explicitly marked paid by PrestaShop. */
    protected function qualifyingPurchaseStates($shopId)
    {
        $stored = $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_STATES', $shopId);
        if ($stored !== false) {
            try {
                return TecMatomoPurchasePolicy::stateIds(json_decode((string) $stored, true));
            } catch (InvalidArgumentException $exception) {
                return [];
            }
        }
        $ids = [];
        foreach (OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')) as $state) {
            if (!empty($state['paid'])) {
                $ids[] = (int) $state['id_order_state'];
            }
        }

        return $ids;
    }

    /** Reject historical orders to avoid replaying purchases tracked before the upgrade. */
    protected function purchaseEligible($order, $state = null)
    {
        $shopId = (int) $order->id_shop;
        if ($shopId < 1 || (int) $order->id < 1
            || (int) $this->purchaseConfiguration('TEC_MATOMO_ACTIVE', $shopId) !== 1
            || (int) $this->purchaseConfiguration('TEC_MATOMO_ECOMMERCE', $shopId) !== 1) {
            return false;
        }
        $first = $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_FIRST_ORDER', $shopId);
        if ($first !== false) {
            try {
                $ids = TecMatomoPurchasePolicy::stateIds([$first]);
            } catch (InvalidArgumentException $exception) {
                return false;
            }
            if ((int) $order->id < $ids[0]) {
                return false;
            }
        }

        return TecMatomoPurchasePolicy::eligible(
            $state === null ? (int) $order->current_state : (int) $state,
            $this->qualifyingPurchaseStates($shopId),
            $this->deniedPurchaseStates($shopId)
        );
    }

    /** Hide states that cannot qualify from the merchant selection as well. */
    protected function purchaseStateOptions()
    {
        $denied = $this->deniedPurchaseStates((int) $this->context->shop->id);

        return array_values(array_filter(OrderState::getOrderStates((int) $this->context->language->id), function ($state) use ($denied) {
            return !in_array((int) $state['id_order_state'], $denied, true);
        }));
    }

    /** Validate stored order lines before looking up their current category label. */
    protected function purchasePayload($order)
    {
        TecMatomoPurchasePolicy::stateIds([$order->id_lang]);
        $payload = TecMatomoPurchasePolicy::payload($order, $order->getOrderDetailList());
        foreach ($payload['ec_items'] as &$item) {
            $productId = (int) strstr($item[0], 'v', true);
            $product = new Product($productId, false, (int) $order->id_lang);
            $category = isset($product->category) && is_string($product->category) ? $product->category : '';
            if (strlen($category) > 1024 || preg_match('//u', $category) !== 1 || preg_match('/[\x00-\x1f\x7f]/', $category)) {
                throw new InvalidArgumentException('Invalid purchase product category.');
            }
            $item[2] = strip_tags($category);
        }
        unset($item);

        return $payload;
    }

    /** Server delivery is explicit and limited to the module's cookieless configuration. */
    protected function serverPurchasesEnabled($shopId)
    {
        return (int) $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_SERVER', $shopId) === 1
            && $this->purchaseConfiguration('TEC_MATOMO_PRIVACY_MODE', $shopId) === 'cookieless'
            && in_array($this->purchaseConfiguration('TEC_MATOMO_CONSENT_MANAGER', $shopId), ['disabled', false, ''], true)
            && !(int) $this->purchaseConfiguration('TEC_MATOMO_LG_ENABLE', $shopId)
            && !(int) $this->purchaseConfiguration('TEC_MATOMO_ARTCOOKIE_ENABLE', $shopId);
    }

    /** Match the browser and server IDs while separating split orders and shops. */
    protected function purchaseIdentifier($order)
    {
        $first = $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_FIRST_ORDER', (int) $order->id_shop);

        return $order->reference . ($first === false ? '' : '-s' . (int) $order->id_shop . '-o' . (int) $order->id);
    }

    /** Resolve a fixed target; endpoint and site changes create a separate outbox identity. */
    protected function purchaseTarget($shopId)
    {
        $base = TecMatomoPurchasePolicy::baseUrl($this->purchaseConfiguration('TEC_MATOMO_URL', $shopId));
        $ids = TecMatomoPurchasePolicy::stateIds([$this->purchaseConfiguration('TEC_MATOMO_SITEID', $shopId)]);

        return ['url' => $base . 'matomo.php', 'idsite' => $ids[0], 'hash' => hash('sha256', $base . '|' . $ids[0])];
    }

    /** Inject DBAL on PS 8+ and isolate Db to the PS 1.7 adapter. */
    protected function purchaseRepository()
    {
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            require_once dirname(__DIR__) . '/src/Modern/PurchaseConnection.php';
            $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            if ($container && $container->has('doctrine.dbal.default_connection')) {
                $connection = $container->get('doctrine.dbal.default_connection');
            } else {
                $configuration = require _PS_ROOT_DIR_ . '/app/config/parameters.php';
                $parameters = $configuration['parameters'];
                $connection = \Doctrine\DBAL\DriverManager::getConnection([
                    'driver' => 'pdo_mysql',
                    'host' => $parameters['database_host'],
                    'port' => !empty($parameters['database_port']) ? (int) $parameters['database_port'] : 3306,
                    'dbname' => $parameters['database_name'],
                    'user' => $parameters['database_user'],
                    'password' => $parameters['database_password'],
                    'charset' => 'utf8',
                ]);
            }
            $adapter = new \TecMatomo\Modern\PurchaseConnection($connection);
        } else {
            require_once __DIR__ . '/LegacyPurchaseConnection.php';
            $adapter = new TecMatomoLegacyPurchaseConnection();
        }

        return new TecMatomoPurchaseRepository($adapter, _DB_PREFIX_);
    }

    /** Create the outbox and register asynchronous order lifecycle hooks on upgrade. */
    public function installPurchaseTracking()
    {
        try {
            if (!$this->purchaseRepository()->install()) {
                return false;
            }
            if (Configuration::getGlobalValue('TEC_MATOMO_PURCHASE_FIRST_ORDER') === false
                && !Configuration::updateGlobalValue('TEC_MATOMO_PURCHASE_FIRST_ORDER', $this->purchaseRepository()->firstOrderId())) {
                return false;
            }

            $shops = Shop::getShops(false, null, true);

            return $this->registerHook('actionOrderStatusPostUpdate', $shops) && $this->registerHook('actionValidateOrder', $shops);
        } catch (Exception $exception) {
            return false;
        }
    }

    /** Enqueue eligible orders without making network calls during checkout. */
    protected function enqueuePurchase($order, $state = null)
    {
        if (!$this->purchaseEligible($order, $state) || !$this->serverPurchasesEnabled((int) $order->id_shop)) {
            return;
        }
        $target = $this->purchaseTarget((int) $order->id_shop);
        $this->purchaseRepository()->enqueue((int) $order->id_shop, (int) $order->id, $target['hash']);
    }

    /** Track a later payment confirmation even if no confirmation page is visited. */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (empty($params['id_order']) || !is_scalar($params['id_order'])
            || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $params['id_order'])
            || empty($params['newOrderStatus']->id)) {
            return;
        }
        try {
            $order = new Order((int) $params['id_order']);
            if (Validate::isLoadedObject($order)) {
                $this->enqueuePurchase($order, (int) $params['newOrderStatus']->id);
            }
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('tec_matomo: purchase enqueue failed.', 3);
        }
    }

    /** Cover order creation flows that do not repeat the normal status hook. */
    public function hookActionValidateOrder($params)
    {
        if (empty($params['order']) || !($params['order'] instanceof Order) || !Validate::isLoadedObject($params['order'])) {
            return;
        }
        try {
            $this->enqueuePurchase($params['order']);
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('tec_matomo: purchase enqueue failed.', 3);
        }
    }

    /** Mask the optional tracking token; do not reuse a reporting-only API token. */
    protected function maskedPurchaseToken($shopId)
    {
        $token = (string) $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_TOKEN', $shopId);

        return $token === '' ? '' : '********' . substr($token, -4);
    }

    /** Send purchases without browser identifiers, redirects or upstream response logging. */
    protected function sendPurchase(array $target, array $payload, $order)
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        $items = $payload['ec_items'];
        $payload['ec_items'] = json_encode($items);
        if ($payload['ec_items'] === false) {
            return false;
        }
        $payload = array_merge($payload, [
            'idsite' => $target['idsite'], 'rec' => 1, 'apiv' => 1, 'idgoal' => 0,
            'send_image' => 0, 'cookie' => 0, 'bots' => 1,
            '_id' => substr(hash_hmac('sha256', (int) $order->id_shop . ':' . (int) $order->id, _COOKIE_KEY_), 0, 16),
        ]);
        $token = (string) $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_TOKEN', (int) $order->id_shop);
        if ($token !== '') {
            if (!preg_match('/^[A-Za-z0-9_-]{16,256}$/D', $token)
                || parse_url($target['url'], PHP_URL_SCHEME) !== 'https') {
                return false;
            }
            $payload['token_auth'] = $token;
        }
        $handle = curl_init($target['url']);
        if ($handle === false) {
            return false;
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($payload, '', '&'),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'TecMatomoPurchase/1.3',
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return $response !== false && $status === 204;
    }

    /** Process a bounded shop-specific batch; the CLI runner supplies retry scheduling. */
    public function processPurchaseQueue($shopId, $limit = 10, $retryFailed = false)
    {
        if (!is_int($shopId) || $shopId < 1 || !is_int($limit) || $limit < 1 || $limit > 20 || !is_bool($retryFailed)) {
            throw new InvalidArgumentException('Invalid purchase batch.');
        }
        $stats = ['sent' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 0];
        if (!$this->serverPurchasesEnabled($shopId)
            || (int) $this->purchaseConfiguration('TEC_MATOMO_ACTIVE', $shopId) !== 1
            || (int) $this->purchaseConfiguration('TEC_MATOMO_ECOMMERCE', $shopId) !== 1) {
            $stats['paused'] = true;

            return $stats;
        }
        $repository = $this->purchaseRepository();
        $target = $this->purchaseTarget($shopId);
        if ($retryFailed) {
            $repository->retryFailed($shopId);
        }
        $states = array_values(array_diff($this->qualifyingPurchaseStates($shopId), $this->deniedPurchaseStates($shopId)));
        $first = $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_FIRST_ORDER', $shopId);
        foreach ($repository->missingOrders($shopId, $states, $first, $target['hash'], $limit) as $candidate) {
            $order = new Order((int) $candidate['id_order']);
            if (Validate::isLoadedObject($order) && (int) $order->id_shop === $shopId && $this->purchaseEligible($order)) {
                $repository->enqueue($shopId, (int) $order->id, $target['hash']);
            }
        }
        foreach ($repository->due($shopId, $limit) as $row) {
            if (function_exists('random_bytes')) {
                $bytes = random_bytes(16);
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $strong = false;
                $bytes = openssl_random_pseudo_bytes(16, $strong);
                if (!$strong) {
                    throw new RuntimeException('Purchase worker entropy unavailable.');
                }
            } else {
                throw new RuntimeException('Purchase worker entropy unavailable.');
            }
            if (!is_string($bytes) || strlen($bytes) !== 16) {
                throw new RuntimeException('Purchase worker entropy unavailable.');
            }
            $lease = bin2hex($bytes);
            if (!$repository->claim((int) $row['id_purchase'], $shopId, $lease)) {
                continue;
            }
            $status = 'pending';
            $error = 'delivery_failed';
            try {
                $order = new Order((int) $row['id_order']);
                if (!Validate::isLoadedObject($order) || (int) $order->id_shop !== $shopId
                    || !$this->purchaseEligible($order) || !hash_equals($target['hash'], $row['target_hash'])) {
                    $status = 'skipped';
                    $error = 'not_eligible';
                } else {
                    $payload = $this->purchasePayload($order);
                    $payload['ec_id'] = $this->purchaseIdentifier($order);
                    if ($this->sendPurchase($target, $payload, $order)) {
                        $status = 'sent';
                        $error = '';
                    }
                }
            } catch (InvalidArgumentException $exception) {
                $status = 'failed';
                $error = 'invalid_payload';
            } catch (Exception $exception) {
                $error = 'delivery_failed';
            }
            $attempt = (int) $row['attempts'] + 1;
            if ($status === 'pending' && $attempt >= 10) {
                $status = 'failed';
            }
            $repository->finish((int) $row['id_purchase'], $shopId, $lease, $status, $error, min(3600, 60 * pow(2, $attempt - 1)));
            ++$stats[$status === 'pending' ? 'retried' : $status];
        }

        $stats['queue'] = $repository->counts($shopId);

        return $stats;
    }

    /** Validate the entire new configuration group before writing any setting. */
    protected function validatePurchaseForm()
    {
        $shopId = (int) $this->context->shop->id;
        $states = TecMatomoPurchasePolicy::stateIds(Tools::getValue('TEC_MATOMO_PURCHASE_STATES', []));
        $available = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id) as $state) {
            $available[] = (int) $state['id_order_state'];
        }
        if (array_diff($states, $available) || array_intersect($states, $this->deniedPurchaseStates($shopId))) {
            throw new InvalidArgumentException('Invalid qualifying order states.');
        }
        $server = Tools::getValue('TEC_MATOMO_PURCHASE_SERVER', 0);
        if (!in_array($server, [0, 1, '0', '1'], true)) {
            throw new InvalidArgumentException('Invalid server purchase setting.');
        }
        if ((int) $server === 1) {
            if ($this->purchaseConfiguration('TEC_MATOMO_PURCHASE_FIRST_ORDER', $shopId) === false) {
                throw new InvalidArgumentException('Run the module upgrade before enabling server purchase delivery.');
            }
            if (Tools::getValue('TEC_MATOMO_PRIVACY_MODE') !== 'cookieless'
                || Tools::getValue('TEC_MATOMO_CONSENT_MANAGER') !== 'disabled') {
                throw new InvalidArgumentException('Server purchases require Cookieless mode with the consent manager disabled.');
            }
            TecMatomoPurchasePolicy::baseUrl(Tools::getValue('TEC_MATOMO_URL'));
            TecMatomoPurchasePolicy::stateIds([Tools::getValue('TEC_MATOMO_SITEID')]);
        }
        $token = Tools::getValue('TEC_MATOMO_PURCHASE_TOKEN', '');
        if (!is_string($token) || strlen($token) > 256) {
            throw new InvalidArgumentException('Invalid purchase tracking token.');
        }
        $current = (string) $this->purchaseConfiguration('TEC_MATOMO_PURCHASE_TOKEN', $shopId);
        if ($token === '' || $token === $this->maskedPurchaseToken($shopId)) {
            $token = $current;
        } elseif (!preg_match('/^[A-Za-z0-9_-]{16,256}$/D', $token)) {
            throw new InvalidArgumentException('Invalid purchase tracking token.');
        }
        if ($token !== '' && !preg_match('/^[A-Za-z0-9_-]{16,256}$/D', $token)) {
            throw new InvalidArgumentException('Invalid purchase tracking token.');
        }
        if ($token !== '' && (int) $server === 1 && strpos(Tools::getValue('TEC_MATOMO_URL'), 'https://') !== 0) {
            throw new InvalidArgumentException('A purchase tracking token requires an HTTPS Matomo URL.');
        }

        return ['states' => json_encode($states), 'server' => (int) $server, 'token' => $token];
    }

}
