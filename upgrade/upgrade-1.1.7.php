<?php
/**
 *  2009-2026 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2026 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.1.7
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_7($object)
{
    $manager = (string) Configuration::get('TEC_MATOMO_CONSENT_MANAGER');

    if (!in_array($manager, ['disabled', 'lg', 'artcookie', 'iubenda', 'cookiebot', 'cookieyes'], true)) {
        $manager = 'disabled';
    }

    Configuration::updateValue('TEC_MATOMO_CONSENT_MANAGER', $manager);

    $purpose = (int) Configuration::get('TEC_MATOMO_IUBENDA_PURPOSE');
    if ($purpose <= 0) {
        Configuration::updateValue('TEC_MATOMO_IUBENDA_PURPOSE', 4);
    }

    return $object->registerHook('actionFrontControllerSetMedia');
}
