<?php
/**
 *  2009-2025 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2025 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */

function upgrade_module_1_1_3($object)
{
    $object->registerHook('actionFrontControllerSetMedia');

    Configuration::updateValue('TEC_MATOMO_PRIVACY_MODE', 'cookieless');
    Configuration::updateValue('TEC_MATOMO_SECURECOOKIE', false);
    Configuration::updateValue('TEC_MATOMO_SUBDOMAINS', false);
    Configuration::updateValue('TEC_MATOMO_BASEDOMAIN', '');
    Configuration::updateValue('TEC_MATOMO_CROSSDOMAIN', false);
    Configuration::updateValue('TEC_MATOMO_CROSSDOMAIN_DOMAINS', '');
    Configuration::updateValue('TEC_MATOMO_TITLE_DOMAIN', false);
    Configuration::updateValue('TEC_MATOMO_CAMPAIGN_NAMEKEY', '');
    Configuration::updateValue('TEC_MATOMO_CAMPAIGN_TERMKEY', '');
    Configuration::updateValue('TEC_MATOMO_HEARTBEAT_ENABLE', false);
    Configuration::updateValue('TEC_MATOMO_HEARTBEAT_SEC', 15);
    Configuration::updateValue('TEC_MATOMO_LG_ENABLE', false);
    Configuration::updateValue('TEC_MATOMO_LG_PURPOSE', 3);

    return true;

}
