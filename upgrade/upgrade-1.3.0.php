<?php
/**
 * Install purchase eligibility and the asynchronous outbox.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Keep the first installation cutoff and existing delivery records on retry. */
function upgrade_module_1_3_0($module)
{
    return $module->installPurchaseTracking();
}
