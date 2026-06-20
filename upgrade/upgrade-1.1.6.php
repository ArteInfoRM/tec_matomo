<?php
/**
 *  2009-2026 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2026 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.1.6
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_6($object)
{
    if (method_exists($object, 'installStatsTab') && !$object->installStatsTab()) {
        return false;
    }

    $hooks = [
        'displayBackOfficeHeader',
        'actionDispatcherBefore',
        'dashboardZoneTwo',
        'displayAdminProductsExtra',
    ];

    foreach ($hooks as $hook) {
        if (!$object->registerHook($hook)) {
            return false;
        }
    }

    $manager = (string) Configuration::get('TEC_MATOMO_CONSENT_MANAGER');
    if (!in_array($manager, ['disabled', 'lg', 'artcookie'], true)) {
        if ((int) Configuration::get('TEC_MATOMO_LG_ENABLE') === 1) {
            $manager = 'lg';
        } elseif ((int) Configuration::get('TEC_MATOMO_ARTCOOKIE_ENABLE') === 1) {
            $manager = 'artcookie';
        } else {
            $manager = 'disabled';
        }
    }

    Configuration::updateValue('TEC_MATOMO_CONSENT_MANAGER', $manager);
    Configuration::updateValue('TEC_MATOMO_LG_ENABLE', $manager === 'lg' ? 1 : 0);
    Configuration::updateValue('TEC_MATOMO_ARTCOOKIE_ENABLE', $manager === 'artcookie' ? 1 : 0);

    $purpose = (int) Configuration::get('TEC_MATOMO_LG_PURPOSE');
    if ($purpose <= 0) {
        Configuration::updateValue('TEC_MATOMO_LG_PURPOSE', 3);
    }

    return true;
}
