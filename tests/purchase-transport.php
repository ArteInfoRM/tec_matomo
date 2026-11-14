<?php
/**
 * Local HTTP transport fixture.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
define('_PS_VERSION_', '1.7.8.6');
define('_COOKIE_KEY_', 'test-only-key');
class Module {}
require dirname(__DIR__) . '/tec_matomo.php';
class TransportProbe extends Tec_matomo
{
    public $token = '';
    protected function purchaseConfiguration($key, $shop) { return $this->token; }
    public function send($url) {
        return $this->sendPurchase(['url' => $url, 'idsite' => 1], ['ec_id' => 'FIXTURE-s1-o1', 'revenue' => 18.3, 'ec_items' => [['1v0','Fixture','',18.3,1]]], (object) ['id' => 1, 'id_shop' => 1]);
    }
}
$m = (new ReflectionClass('TransportProbe'))->newInstanceWithoutConstructor();
$m->token = isset($argv[2]) ? $argv[2] : '';
echo $m->send($argv[1]) ? 'accepted' : 'rejected';
