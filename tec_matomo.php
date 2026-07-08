<?php
/**
 *  2009-2026 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2026 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.1.9
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tec_matomo extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tec_matomo';
        $this->tab = 'analytics_stats';
        $this->version = '1.1.9';
        $this->author = 'Tecnoacquisti.com';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Matomo Analytics for PrestaShop, with ecommerce tracking');
        $this->description = $this->l('Matomo is Google Analytics alternative that protects your data and your customers\' privacy');

        $this->ps_versions_compliancy = ['min' => '1.7.5', 'max' => _PS_VERSION_];
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('TEC_MATOMO_ACTIVE', false);
        Configuration::updateValue('TEC_MATOMO_URL', '');
        Configuration::updateValue('TEC_MATOMO_SITEID', '');
        Configuration::updateValue('TEC_MATOMO_TOKEN', '');
        Configuration::updateValue('TEC_MATOMO_ECOMMERCE', false);
        Configuration::updateValue('TEC_MATOMO_USERID', false);
        Configuration::updateValue('TEC_MATOMO_JS', false);
        Configuration::updateValue('TEC_MATOMO_DNTRACK', false);

        // New variants for Matomo 5.X
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
        Configuration::updateValue('TEC_MATOMO_CONSENT_MANAGER', 'disabled');
        Configuration::updateValue('TEC_MATOMO_LG_ENABLE', false);
        Configuration::updateValue('TEC_MATOMO_LG_PURPOSE', 3); // default purpose ID for Analytics in LG Cookies Law
        Configuration::updateValue('TEC_MATOMO_ARTCOOKIE_ENABLE', false);
        Configuration::updateValue('TEC_MATOMO_IUBENDA_PURPOSE', 4);

        return parent::install() &&
            $this->installStatsTab() &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayAfterBodyOpeningTag') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionDispatcherBefore') &&
            $this->registerHook('dashboardZoneTwo') &&
            $this->registerHook('displayAdminProductsExtra');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TEC_MATOMO_ACTIVE');
        Configuration::deleteByName('TEC_MATOMO_URL');
        Configuration::deleteByName('TEC_MATOMO_SITEID');
        Configuration::deleteByName('TEC_MATOMO_TOKEN');
        Configuration::deleteByName('TEC_MATOMO_ECOMMERCE');
        Configuration::deleteByName('TEC_MATOMO_USERID');
        Configuration::deleteByName('TEC_MATOMO_JS');
        Configuration::deleteByName('TEC_MATOMO_DNTRACK');
        Configuration::deleteByName('TEC_MATOMO_PRIVACY_MODE');
        Configuration::deleteByName('TEC_MATOMO_SECURECOOKIE');
        Configuration::deleteByName('TEC_MATOMO_SUBDOMAINS');
        Configuration::deleteByName('TEC_MATOMO_BASEDOMAIN');
        Configuration::deleteByName('TEC_MATOMO_CROSSDOMAIN');
        Configuration::deleteByName('TEC_MATOMO_CROSSDOMAIN_DOMAINS');
        Configuration::deleteByName('TEC_MATOMO_TITLE_DOMAIN');
        Configuration::deleteByName('TEC_MATOMO_CAMPAIGN_NAMEKEY');
        Configuration::deleteByName('TEC_MATOMO_CAMPAIGN_TERMKEY');
        Configuration::deleteByName('TEC_MATOMO_HEARTBEAT_ENABLE');
        Configuration::deleteByName('TEC_MATOMO_HEARTBEAT_SEC');
        Configuration::deleteByName('TEC_MATOMO_CONSENT_MANAGER');
        Configuration::deleteByName('TEC_MATOMO_LG_ENABLE');
        Configuration::deleteByName('TEC_MATOMO_LG_PURPOSE');
        Configuration::deleteByName('TEC_MATOMO_ARTCOOKIE_ENABLE');
        Configuration::deleteByName('TEC_MATOMO_IUBENDA_PURPOSE');

        return $this->uninstallStatsTab() && parent::uninstall();
    }

    public function installStatsTab()
    {
        $idTab = $this->getTabIdByClassName('AdminTecMatomoStats');
        if ($idTab > 0) {
            return true;
        }

        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'AdminTecMatomoStats';
        $tab->module = $this->name;
        $tab->id_parent = $this->getStatsSiblingParentId();
        $tab->icon = 'analytics';

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'Matomo Analytics';
        }

        return (bool) $tab->add();
    }

    public function uninstallStatsTab()
    {
        $idTab = $this->getTabIdByClassName('AdminTecMatomoStats');
        if ($idTab <= 0) {
            return true;
        }

        return (bool) (new Tab($idTab))->delete();
    }

    protected function getStatsSiblingParentId()
    {
        $idStats = $this->getTabIdByClassName('AdminStats');
        if ($idStats <= 0) {
            return 0;
        }

        return (int) (new Tab($idStats))->id_parent;
    }

    protected function getTabIdByClassName($className)
    {
        $className = trim((string) $className);
        if ($className === '') {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_tab`
            FROM `' . _DB_PREFIX_ . 'tab`
            WHERE `class_name` = "' . pSQL($className) . '"'
        );
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';
        $useSsl = (bool)Configuration::get('PS_SSL_ENABLED_EVERYWHERE') || (bool)Configuration::get('PS_SSL_ENABLED');
        $shop_base_url = $this->context->link->getBaseLink((int)$this->context->shop->id, $useSsl);

        if (((bool)Tools::isSubmit('submitTec_matomoModule')) == true) {
            $output .= $this->postProcess();
        }

        $this->smarty->assign(array(
            'shop_base_url' => $shop_base_url,
            'mtm_stats_url' => $this->context->link->getAdminLink('AdminTecMatomoStats'),
        ));

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->controller->addJS($this->_path . 'views/js/back.js');

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->renderForm();
        $output .= $this->display(__FILE__, 'views/templates/admin/copyright.tpl');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTec_matomoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input' => array(
                    // === BASE ===
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable monitoring'),
                        'name' => 'TEC_MATOMO_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Active monitoring of statistics with Matomo'),
                        'values' => array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Matomo URL'),
                        'desc'  => $this->l('Enter Matomo URL, e.g. https://stat.example.net/ (MUST end with /)'),
                        'name'  => 'TEC_MATOMO_URL',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Matomo SiteID'),
                        'name'  => 'TEC_MATOMO_SITEID',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Matomo API Token'),
                        'desc'  => $this->l('Optional: import and/or show data in PrestaShop back office'),
                        'name'  => 'TEC_MATOMO_TOKEN',
                    ),

                    // === FEATURES ===
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Ecommerce tracking'),
                        'name'  => 'TEC_MATOMO_ECOMMERCE',
                        'is_bool' => true,
                        'desc'  => $this->l('Track orders, carts and product impressions'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('UserID tracking'),
                        'name'  => 'TEC_MATOMO_USERID',
                        'is_bool' => true,
                        'desc'  => $this->l('Associate a persistent identifier (may require consent under GDPR)'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Track users with JavaScript disabled (noscript)'),
                        'name'  => 'TEC_MATOMO_JS',
                        'is_bool' => true,
                        'desc'  => $this->l('Adds the noscript tracking pixel'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),

                    // === PRIVACY ===
                    array(
                        'type'  => 'radio',
                        'label' => $this->l('Privacy mode'),
                        'name'  => 'TEC_MATOMO_PRIVACY_MODE',
                        'desc'  => $this->l('Choose one: None (default), Cookieless, or Require consent'),
                        'values'=> array(
                            array('id'=>'privacy_none','value'=>'none','label'=>$this->l('None')),
                            array('id'=>'privacy_cookieless','value'=>'cookieless','label'=>$this->l('Cookieless (disableCookies)')),
                            array('id'=>'privacy_consent','value'=>'consent','label'=>$this->l('Require cookie consent')),
                        ),
                    ),
                    [
                        'type' => 'select',
                        'label' => $this->l('Consent manager integration'),
                        'name' => 'TEC_MATOMO_CONSENT_MANAGER',
                        'desc' => $this->l('Choose which consent banner should control Matomo cookie consent.'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => 'disabled',
                                    'name' => $this->l('Disabled')
                                ],
                                [
                                    'id' => 'lg',
                                    'name' => $this->l('LG Cookies Law (Linea Grafica)')
                                ],
                                [
                                    'id' => 'artcookie',
                                    'name' => $this->l('Art Cookie Choices Pro')
                                ],
                                [
                                    'id' => 'iubenda',
                                    'name' => $this->l('iubenda Cookie Solution')
                                ],
                                [
                                    'id' => 'cookiebot',
                                    'name' => $this->l('Cookiebot')
                                ],
                                [
                                    'id' => 'cookieyes',
                                    'name' => $this->l('CookieYes')
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('LG Cookies Law purpose ID'),
                        'name' => 'TEC_MATOMO_LG_PURPOSE',
                        'class' => 'fixed-width-xs',
                        'form_group_class' => 'tec-matomo-lg-purpose-row',
                        'desc' => $this->l('Numeric ID of the LG purpose for Analytics (default is 3, corresponding to lgcookieslaw_purpose_3).'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('iubenda Analytics purpose ID'),
                        'name' => 'TEC_MATOMO_IUBENDA_PURPOSE',
                        'class' => 'fixed-width-xs',
                        'form_group_class' => 'tec-matomo-iubenda-purpose-row',
                        'desc' => $this->l('Numeric ID of the iubenda purpose used for Analytics consent. Default is 4.'),
                    ],
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Secure cookie (HTTPS only)'),
                        'name'  => 'TEC_MATOMO_SECURECOOKIE',
                        'is_bool' => true,
                        'desc'  => $this->l('Use secure cookies when site runs on HTTPS'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),

                    // === SUBDOMAINS & CROSS-DOMAIN ===
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Track visitors across all subdomains'),
                        'name'  => 'TEC_MATOMO_SUBDOMAINS',
                        'is_bool' => true,
                        'desc'  => $this->l('Will set cookie domain to the base domain'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Base domain for cookies'),
                        'name'  => 'TEC_MATOMO_BASEDOMAIN',
                        'desc'  => $this->l('Example: .example.it (leading dot recommended)'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable cross-domain linking'),
                        'name'  => 'TEC_MATOMO_CROSSDOMAIN',
                        'is_bool' => true,
                        'desc'  => $this->l('Preserve visitor ID when navigating across your domains'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Domains list for linking'),
                        'name'  => 'TEC_MATOMO_CROSSDOMAIN_DOMAINS',
                        'desc'  => $this->l('Comma-separated list, e.g. *.example.it,shop.example.it,*.other.it'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Prepend domain to page title'),
                        'name'  => 'TEC_MATOMO_TITLE_DOMAIN',
                        'is_bool' => true,
                        'desc'  => $this->l('Use document.domain + "/" + document.title'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),

                    // === CAMPAIGNS ===
                    array(
                        'type' => 'text',
                        'label' => $this->l('Extra campaign name key'),
                        'name'  => 'TEC_MATOMO_CAMPAIGN_NAMEKEY',
                        'desc'  => $this->l('Optional. Adds a custom campaign name parameter (besides utm_campaign/pk_campaign)'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Extra campaign keyword key'),
                        'name'  => 'TEC_MATOMO_CAMPAIGN_TERMKEY',
                        'desc'  => $this->l('Optional. Adds a custom campaign term/keyword parameter'),
                    ),

                    // === ENGAGEMENT ===
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Heartbeat timer'),
                        'name'  => 'TEC_MATOMO_HEARTBEAT_ENABLE',
                        'is_bool' => true,
                        'desc'  => $this->l('Improves “time on page” accuracy'),
                        'values'=> array(
                            array('id'=>'active_on','value'=>true,'label'=>$this->l('Enabled')),
                            array('id'=>'active_off','value'=>false,'label'=>$this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Heartbeat interval (seconds)'),
                        'name'  => 'TEC_MATOMO_HEARTBEAT_SEC',
                        'desc'  => $this->l('Minimum 5 seconds (recommended 10–30)'),
                    ),

                    // === (RIMOSSO) DoNotTrack ===
                    // Campo DNT rimosso perché non più consigliato da Matomo
                ),
                'submit' => array('title' => $this->l('Save')),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            // BASE
            'TEC_MATOMO_ACTIVE'   => Tools::getValue('TEC_MATOMO_ACTIVE', Configuration::get('TEC_MATOMO_ACTIVE')),
            'TEC_MATOMO_URL'      => Tools::getValue('TEC_MATOMO_URL', Configuration::get('TEC_MATOMO_URL')),
            'TEC_MATOMO_SITEID'   => Tools::getValue('TEC_MATOMO_SITEID', Configuration::get('TEC_MATOMO_SITEID')),
            'TEC_MATOMO_TOKEN'    => Tools::getValue('TEC_MATOMO_TOKEN', Configuration::get('TEC_MATOMO_TOKEN')),

            // FEATURES
            'TEC_MATOMO_ECOMMERCE'=> Tools::getValue('TEC_MATOMO_ECOMMERCE', Configuration::get('TEC_MATOMO_ECOMMERCE')),
            'TEC_MATOMO_USERID'   => Tools::getValue('TEC_MATOMO_USERID', Configuration::get('TEC_MATOMO_USERID')),
            'TEC_MATOMO_JS'       => Tools::getValue('TEC_MATOMO_JS', Configuration::get('TEC_MATOMO_JS')),

            // PRIVACY
            'TEC_MATOMO_PRIVACY_MODE' => Tools::getValue('TEC_MATOMO_PRIVACY_MODE', Configuration::get('TEC_MATOMO_PRIVACY_MODE')),
            'TEC_MATOMO_SECURECOOKIE' => Tools::getValue('TEC_MATOMO_SECURECOOKIE', Configuration::get('TEC_MATOMO_SECURECOOKIE')),

            // SUBDOMAINS & CROSS-DOMAIN
            'TEC_MATOMO_SUBDOMAINS'        => Tools::getValue('TEC_MATOMO_SUBDOMAINS', Configuration::get('TEC_MATOMO_SUBDOMAINS')),
            'TEC_MATOMO_BASEDOMAIN'        => Tools::getValue('TEC_MATOMO_BASEDOMAIN', Configuration::get('TEC_MATOMO_BASEDOMAIN')),
            'TEC_MATOMO_CROSSDOMAIN'       => Tools::getValue('TEC_MATOMO_CROSSDOMAIN', Configuration::get('TEC_MATOMO_CROSSDOMAIN')),
            'TEC_MATOMO_CROSSDOMAIN_DOMAINS' => Tools::getValue('TEC_MATOMO_CROSSDOMAIN_DOMAINS', Configuration::get('TEC_MATOMO_CROSSDOMAIN_DOMAINS')),
            'TEC_MATOMO_TITLE_DOMAIN'      => Tools::getValue('TEC_MATOMO_TITLE_DOMAIN', Configuration::get('TEC_MATOMO_TITLE_DOMAIN')),

            // CAMPAIGNS
            'TEC_MATOMO_CAMPAIGN_NAMEKEY'  => Tools::getValue('TEC_MATOMO_CAMPAIGN_NAMEKEY', Configuration::get('TEC_MATOMO_CAMPAIGN_NAMEKEY')),
            'TEC_MATOMO_CAMPAIGN_TERMKEY'  => Tools::getValue('TEC_MATOMO_CAMPAIGN_TERMKEY', Configuration::get('TEC_MATOMO_CAMPAIGN_TERMKEY')),

            // ENGAGEMENT
            'TEC_MATOMO_HEARTBEAT_ENABLE'  => Tools::getValue('TEC_MATOMO_HEARTBEAT_ENABLE', Configuration::get('TEC_MATOMO_HEARTBEAT_ENABLE')),
            'TEC_MATOMO_HEARTBEAT_SEC'     => Tools::getValue('TEC_MATOMO_HEARTBEAT_SEC', Configuration::get('TEC_MATOMO_HEARTBEAT_SEC')),

            // LEGAL/GDPR
            'TEC_MATOMO_CONSENT_MANAGER' => Tools::getValue('TEC_MATOMO_CONSENT_MANAGER', $this->getConsentManagerMode()),
            'TEC_MATOMO_LG_PURPOSE' => (int) Tools::getValue('TEC_MATOMO_LG_PURPOSE', (int)Configuration::get('TEC_MATOMO_LG_PURPOSE')),
            'TEC_MATOMO_IUBENDA_PURPOSE' => (int) Tools::getValue('TEC_MATOMO_IUBENDA_PURPOSE', (int)Configuration::get('TEC_MATOMO_IUBENDA_PURPOSE')),
        );
    }

    protected function getConsentManagerMode()
    {
        $manager = (string) Configuration::get('TEC_MATOMO_CONSENT_MANAGER');

        if (in_array($manager, ['disabled', 'lg', 'artcookie', 'iubenda', 'cookiebot', 'cookieyes'], true)) {
            return $manager;
        }

        if ((int) Configuration::get('TEC_MATOMO_LG_ENABLE') === 1) {
            return 'lg';
        }

        if ((int) Configuration::get('TEC_MATOMO_ARTCOOKIE_ENABLE') === 1) {
            return 'artcookie';
        }

        return 'disabled';
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';
        $this->_errors = array();

        // Leggo i valori già “merge-ati” con Configuration
        $form_values = $this->getConfigFormValues();

        // Enum privacy consentiti
        $allowedPrivacy = array('none', 'cookieless', 'consent');
        $allowedConsentManagers = ['disabled', 'lg', 'artcookie', 'iubenda', 'cookiebot', 'cookieyes'];

        foreach (array_keys($form_values) as $key) {

            switch ($key) {

                case 'TEC_MATOMO_CODE':
                    // se lo usi in futuro come campo libero
                    Configuration::updateValue($key, Tools::getValue($key), true);
                    break;

                case 'TEC_MATOMO_URL':
                    $url = trim((string)Tools::getValue($key));
                    if (substr($url, -1) !== '/') {
                        $url .= '/';
                    }
                    if (!Validate::isAbsoluteUrl($url)) {
                        $this->_errors[] = sprintf('%s %s', $url, $this->l('is not a valid URL'));
                    } else {
                        Configuration::updateValue($key, $url);
                    }
                    break;

                case 'TEC_MATOMO_SITEID':
                    $idSite = (int)Tools::getValue($key);
                    if ($idSite < 1) {
                        $this->_errors[] = sprintf('%s %s', $idSite, $this->l('is not a valid ID'));
                    } else {
                        Configuration::updateValue($key, $idSite);
                    }
                    break;

                case 'TEC_MATOMO_TOKEN':
                    // opzionale: nessuna validazione forte
                    Configuration::updateValue($key, (string)Tools::getValue($key));
                    break;

                // --- PRIVACY MODE (enum) ---
                case 'TEC_MATOMO_PRIVACY_MODE':
                    $mode = (string)Tools::getValue($key, 'none');
                    if (!in_array($mode, $allowedPrivacy, true)) {
                        $mode = 'none';
                    }
                    Configuration::updateValue($key, $mode);
                    break;

                case 'TEC_MATOMO_CONSENT_MANAGER':
                    $manager = (string) Tools::getValue($key, 'disabled');
                    if (!in_array($manager, $allowedConsentManagers, true)) {
                        $manager = 'disabled';
                    }
                    Configuration::updateValue('TEC_MATOMO_CONSENT_MANAGER', $manager);
                    Configuration::updateValue('TEC_MATOMO_LG_ENABLE', $manager === 'lg' ? 1 : 0);
                    Configuration::updateValue('TEC_MATOMO_ARTCOOKIE_ENABLE', $manager === 'artcookie' ? 1 : 0);
                    break;

                // --- BOOLEANS (switch/radio) ---
                case 'TEC_MATOMO_ACTIVE':
                case 'TEC_MATOMO_ECOMMERCE':
                case 'TEC_MATOMO_USERID':
                case 'TEC_MATOMO_JS':
                case 'TEC_MATOMO_SECURECOOKIE':
                case 'TEC_MATOMO_SUBDOMAINS':
                case 'TEC_MATOMO_CROSSDOMAIN':
                case 'TEC_MATOMO_TITLE_DOMAIN':
                case 'TEC_MATOMO_HEARTBEAT_ENABLE':
                case 'TEC_MATOMO_ARTCOOKIE_ENABLE':
                    Configuration::updateValue($key, (int)(bool)Tools::getValue($key));
                    break;

                // --- BASE DOMAIN (per sottodomini) ---
                case 'TEC_MATOMO_BASEDOMAIN':
                    $base = trim((string)Tools::getValue($key));
                    if ($base !== '') {
                        // rimuovo eventuale protocollo/path accidentalmente inseriti
                        $base = preg_replace('#^https?://#i', '', $base);
                        $base = preg_replace('#/.*$#', '', $base);
                        // forzo il leading dot consigliato: .example.it
                        if ($base[0] !== '.') {
                            $base = '.' . $base;
                        }
                        // validazione semplice dominio
                        if (!preg_match('/^\.[A-Za-z0-9.-]+$/', $base)) {
                            $this->_errors[] = sprintf('%s %s', $base, $this->l('is not a valid base domain'));
                            break;
                        }
                    }
                    Configuration::updateValue($key, $base);
                    break;

                // --- DOMINI CROSS-DOMAIN (CSV) ---
                case 'TEC_MATOMO_CROSSDOMAIN_DOMAINS':
                    $raw = (string)Tools::getValue($key);
                    if (trim($raw) === '') {
                        Configuration::updateValue($key, '');
                        break;
                    }
                    $parts = array_filter(array_map('trim', explode(',', $raw)), function ($v) {
                        return $v !== '';
                    });
                    // normalizzo: niente protocollo, niente slash finali
                    $norm = array();
                    foreach ($parts as $p) {
                        $p = preg_replace('#^https?://#i', '', $p);
                        $p = rtrim($p, '/');
                        // permetto wildcard *.dominio.tld
                        if (!preg_match('/^\*?\.[A-Za-z0-9.-]+$|^[A-Za-z0-9.-]+$/', $p)) {
                            $this->_errors[] = sprintf('%s %s', $p, $this->l('is not a valid domain entry'));
                            // continuo ma non lo aggiungo
                            continue;
                        }
                        $norm[] = $p;
                    }
                    $norm = array_values(array_unique($norm));
                    Configuration::updateValue($key, implode(',', $norm));
                    break;

                // --- CHIAVI CAMPAGNA PERSONALIZZATE ---
                case 'TEC_MATOMO_CAMPAIGN_NAMEKEY':
                case 'TEC_MATOMO_CAMPAIGN_TERMKEY':
                    $v = trim((string)Tools::getValue($key));
                    if ($v !== '' && !preg_match('/^[A-Za-z0-9_\-]+$/', $v)) {
                        $this->_errors[] = sprintf('%s %s', $v, $this->l('contains invalid characters (allowed: A–Z, a–z, 0–9, _ , -)'));
                        // except empty for safety
                        $v = '';
                    }
                    Configuration::updateValue($key, $v);
                    break;

                // --- HEARTBEAT SEC ---
                case 'TEC_MATOMO_HEARTBEAT_SEC':
                    $sec = (int)Tools::getValue($key);
                    if ($sec && $sec < 5) {
                        $this->_errors[] = $this->l('Heartbeat interval must be ≥ 5 seconds; value adjusted to 5.');
                        $sec = 5;
                    }
                    if ($sec === 0) {
                        // if not valued, default setting 15
                        $sec = 15;
                    }
                    Configuration::updateValue($key, $sec);
                    break;

                case 'TEC_MATOMO_LG_ENABLE':
                    Configuration::updateValue($key, (int)(bool)Tools::getValue($key));
                    break;

                case 'TEC_MATOMO_LG_PURPOSE':
                    $p = (int) Tools::getValue($key);
                    if ($p <= 0) { $p = 3; }
                    Configuration::updateValue($key, $p);
                    break;

                case 'TEC_MATOMO_IUBENDA_PURPOSE':
                    $p = (int) Tools::getValue($key);
                    if ($p <= 0) { $p = 4; }
                    Configuration::updateValue($key, $p);
                    break;

                // generic fallback (for future fields)
                default:
                    Configuration::updateValue($key, Tools::getValue($key));
                    break;
            }
        }

        if (!count($this->_errors)) {
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        } else {
            // Accumulo errori in un solo messaggio
            $output .= $this->displayError(implode('<br>', $this->_errors));
        }

        return $output;
    }

    public function hookActionFrontControllerSetMedia($params)
    {

        if ((int)Configuration::get('TEC_MATOMO_ACTIVE') !== 1) {
            return;
        }

        // =======================
        // Bridge LG Cookies Law
        // =======================
        $consentManager = $this->getConsentManagerMode();

        if ($consentManager === 'lg') {
            $purpose = (int) Configuration::get('TEC_MATOMO_LG_PURPOSE');
            if ($purpose <= 0) { $purpose = 3; }

            // Variabile JS globale con l'ID dello scopo
            Media::addJsDef(['matomoLgPurpose' => $purpose]);

            // JS del bridge (può stare in head con defer)
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-lg-bridge',
                $this->_path.'views/js/matomo-lg-bridge.js',
                [
                    'position'   => 'head',
                    'priority'   => 40,
                    'attributes' => 'defer', // ok tenere defer qui
                ]
            );
        }

        if ($consentManager === 'artcookie') {
            $this->context->controller->registerJavascript(
                'module-' . $this->name . '-artcookie-bridge',
                $this->_path . 'views/js/matomo-artcookie-bridge.js',
                [
                    'position' => 'head',
                    'priority' => 41,
                    'attributes' => 'defer',
                ]
            );
        }

        if ($consentManager === 'iubenda') {
            $purpose = (int) Configuration::get('TEC_MATOMO_IUBENDA_PURPOSE');
            if ($purpose <= 0) { $purpose = 4; }

            Media::addJsDef(['matomoIubendaPurpose' => $purpose]);

            $this->context->controller->registerJavascript(
                'module-' . $this->name . '-iubenda-bridge',
                $this->_path . 'views/js/matomo-iubenda-bridge.js',
                [
                    'position' => 'head',
                    'priority' => 42,
                    'attributes' => 'defer',
                ]
            );
        }

        if ($consentManager === 'cookiebot') {
            $this->context->controller->registerJavascript(
                'module-' . $this->name . '-cookiebot-bridge',
                $this->_path . 'views/js/matomo-cookiebot-bridge.js',
                [
                    'position' => 'head',
                    'priority' => 43,
                    'attributes' => 'defer',
                ]
            );
        }

        if ($consentManager === 'cookieyes') {
            $this->context->controller->registerJavascript(
                'module-' . $this->name . '-cookieyes-bridge',
                $this->_path . 'views/js/matomo-cookieyes-bridge.js',
                [
                    'position' => 'head',
                    'priority' => 44,
                    'attributes' => 'defer',
                ]
            );
        }

    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $controllerName = isset($this->context->controller->controller_name)
            ? (string) $this->context->controller->controller_name
            : (string) Tools::getValue('controller');

        if (!in_array($controllerName, ['AdminTecMatomoStats', 'AdminDashboard', 'AdminModules', 'AdminProducts', 'AdminProduct', 'AdminCatalog'], true)) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : (int) Tools::getValue('id_product');
        if ($idProduct <= 0) {
            return '';
        }

        $dateRange = [
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d', strtotime('-1 day')),
            'label' => $this->l('Last 30 days'),
        ];
        $productData = $this->getProductMatomoData($idProduct, $dateRange);
        if ($productData['rows'] === []) {
            $yearDateTo = date('Y-m-d', strtotime('-1 day'));
            $yearDateRange = [
                'date_from' => date('Y-01-01', strtotime($yearDateTo)),
                'date_to' => $yearDateTo,
                'label' => date('Y-01-01', strtotime($yearDateTo)) . ' - ' . $yearDateTo,
            ];
            $yearProductData = $this->getProductMatomoData($idProduct, $yearDateRange);
            if ($yearProductData['rows'] !== []) {
                $dateRange = $yearDateRange;
                $productData = $yearProductData;
            }
        }

        $this->context->smarty->assign([
            'mtm_product_is_connected' => $this->isMatomoApiConfigured(),
            'mtm_product_api_error' => $productData['error'],
            'mtm_product_metrics' => $productData['metrics'],
            'mtm_product_rows' => $productData['rows'],
            'mtm_product_referrer_rows' => isset($productData['referrer_rows']) ? $productData['referrer_rows'] : [],
            'mtm_product_aliases' => $productData['aliases'],
            'mtm_product_period_label' => $dateRange['label'],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_metrics.tpl');
    }

    public function hookActionDispatcherBefore($params)
    {
        unset($params);

        if (!defined('_PS_ADMIN_DIR_') || (string) Tools::getValue('controller') !== 'AdminDashboard') {
            return;
        }

        $this->ensureDashboardEmployeeDateRange();
        $this->sanitizeDashboardRequestDate('date_from', (string) $this->context->employee->stats_date_from);
        $this->sanitizeDashboardRequestDate('date_to', (string) $this->context->employee->stats_date_to);
    }

    public function hookDashboardZoneTwo($params)
    {
        $dateRange = $this->getDashboardDateRange((array) $params);
        $matomoData = $this->getDashboardMatomoData($dateRange);

        $this->context->smarty->assign([
            'mtm_dashboard_url' => $this->context->link->getAdminLink('AdminModules')
                . '&configure=' . $this->name
                . '&tab_module=' . $this->tab
                . '&module_name=' . $this->name,
            'mtm_is_connected' => $this->isMatomoApiConfigured(),
            'mtm_api_error' => $matomoData['error'],
            'mtm_site_metrics' => $matomoData['site_metrics'],
            'mtm_channel_rows' => $matomoData['channel_rows'],
            'mtm_period_label' => $dateRange['label'],
            'mtm_period_from' => $dateRange['date_from'],
            'mtm_period_to' => $dateRange['date_to'],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/dashboard_widget.tpl');
    }

    public function isMatomoApiConfigured()
    {
        return (int) Configuration::get('TEC_MATOMO_SITEID') > 0
            && trim((string) Configuration::get('TEC_MATOMO_URL')) !== ''
            && trim((string) Configuration::get('TEC_MATOMO_TOKEN')) !== '';
    }

    public function getDashboardMatomoData($dateRange)
    {
        $siteMetrics = [
            'revenue' => 0.0,
            'revenue_formatted' => $this->formatDashboardAmount(0),
            'orders' => 0,
            'conversion_rate' => '0.00%',
            'average_order_value' => $this->formatDashboardAmount(0),
            'visits' => 0,
            'unique_visitors' => 0,
            'actions' => 0,
            'bounce_rate' => '0%',
        ];
        $emptyExtraRows = [
            'country_rows' => [],
            'product_rows' => [],
            'category_rows' => [],
        ];

        if (!$this->isMatomoApiConfigured()) {
            return [
                'site_metrics' => $siteMetrics,
                'channel_rows' => [],
                'country_rows' => $emptyExtraRows['country_rows'],
                'product_rows' => $emptyExtraRows['product_rows'],
                'category_rows' => $emptyExtraRows['category_rows'],
                'error' => '',
            ];
        }

        $visits = $this->callMatomoApi('VisitsSummary.get', $dateRange);
        if (isset($visits['error'])) {
            return [
                'site_metrics' => $siteMetrics,
                'channel_rows' => [],
                'country_rows' => $emptyExtraRows['country_rows'],
                'product_rows' => $emptyExtraRows['product_rows'],
                'category_rows' => $emptyExtraRows['category_rows'],
                'error' => (string) $visits['error'],
            ];
        }

        $goals = $this->callMatomoApi('Goals.get', $dateRange, ['idGoal' => 'ecommerceOrder']);
        if (isset($goals['error'])) {
            $goals = [];
        }

        $channels = $this->callMatomoApi('Referrers.getReferrerType', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($channels['error']) || !is_array($channels)) {
            $channels = [];
        }

        $countries = $this->callMatomoApi('UserCountry.getCountry', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($countries['error']) || !is_array($countries)) {
            $countries = [];
        }

        $products = $this->callMatomoApi('Goals.getItemsName', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($products['error']) || !is_array($products)) {
            $products = [];
        }

        $categories = $this->callMatomoApi('Goals.getItemsCategory', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($categories['error']) || !is_array($categories)) {
            $categories = [];
        }

        $revenue = $this->firstNumericValue($goals, ['revenue', 'revenue_subtotal']);
        $orders = (int) $this->firstNumericValue($goals, ['orders', 'nb_conversions', 'conversions']);
        $conversionRate = $this->firstStringValue($goals, ['conversion_rate']);
        if ($conversionRate === '') {
            $conversionRate = $this->calculateRate((int) $orders, (int) $this->firstNumericValue($visits, ['nb_visits']));
        }

        $siteMetrics = [
            'revenue' => $revenue,
            'revenue_formatted' => $this->formatDashboardAmount($revenue),
            'orders' => $orders,
            'conversion_rate' => $conversionRate,
            'average_order_value' => $orders > 0 ? $this->formatDashboardAmount($revenue / $orders) : $this->formatDashboardAmount(0),
            'visits' => (int) $this->firstNumericValue($visits, ['nb_visits']),
            'unique_visitors' => (int) $this->firstNumericValue($visits, ['nb_uniq_visitors', 'nb_users']),
            'actions' => (int) $this->firstNumericValue($visits, ['nb_actions']),
            'bounce_rate' => $this->firstStringValue($visits, ['bounce_rate']),
        ];

        if ($siteMetrics['bounce_rate'] === '') {
            $siteMetrics['bounce_rate'] = '0%';
        }

        return [
            'site_metrics' => $siteMetrics,
            'channel_rows' => $this->normalizeDashboardChannelRows($channels),
            'country_rows' => $this->normalizeTopRevenueRows($countries, 10),
            'product_rows' => $this->normalizeTopRevenueRows($products, 10),
            'category_rows' => $this->normalizeTopRevenueRows($categories, 10),
            'error' => '',
        ];
    }

    protected function getProductMatomoData($idProduct, $dateRange)
    {
        $aliases = $this->getProductMatomoAliases($idProduct);
        $nameAliases = $this->getProductMatomoNameAliases($idProduct);
        $aggregateSkuAliases = !$this->productHasCombinations($idProduct);
        $emptyMetrics = [
            'visits' => 0,
            'actions' => 0,
            'unique_visitors' => 0,
            'orders' => 0,
            'items_purchased' => 0,
            'revenue' => 0.0,
            'revenue_formatted' => $this->formatDashboardAmount(0),
            'average_price' => $this->formatDashboardAmount(0),
            'conversion_rate' => '0.00%',
            'matched_rows' => 0,
        ];

        if (!$this->isMatomoApiConfigured()) {
            return [
                'metrics' => $emptyMetrics,
                'rows' => [],
                'referrer_rows' => [],
                'aliases' => $aliases,
                'error' => '',
            ];
        }

        $items = $this->callMatomoApi('Goals.getItemsSku', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($items['error'])) {
            return [
                'metrics' => $emptyMetrics,
                'rows' => [],
                'referrer_rows' => [],
                'aliases' => $aliases,
                'error' => (string) $items['error'],
            ];
        }

        if (!is_array($items)) {
            $items = [];
        }

        $productData = $this->buildProductMetricsFromRows($items, $aliases, $emptyMetrics, 'sku', $aggregateSkuAliases);
        if ($productData['rows'] !== [] || $nameAliases === []) {
            return $this->addProductReferrerRows($productData, $idProduct, $dateRange);
        }

        $itemsByName = $this->callMatomoApi('Goals.getItemsName', $dateRange, [
            'idGoal' => 'ecommerceOrder',
            'filter_limit' => -1,
        ]);
        if (isset($itemsByName['error'])) {
            return $this->addProductReferrerRows($productData, $idProduct, $dateRange);
        }

        if (!is_array($itemsByName)) {
            $itemsByName = [];
        }

        $productDataByName = $this->buildProductMetricsFromRows($itemsByName, $nameAliases, $emptyMetrics, 'name', false);
        $productDataByName['aliases'] = $aliases;

        return $this->addProductReferrerRows($productDataByName, $idProduct, $dateRange);
    }

    protected function getProductMatomoAliases($idProduct)
    {
        $aliases = [
            (string) (int) $idProduct,
            (string) (int) $idProduct . 'v0',
        ];
        $rows = Db::getInstance()->executeS(
            'SELECT `id_product_attribute`
            FROM `' . _DB_PREFIX_ . 'product_attribute`
            WHERE `id_product` = ' . (int) $idProduct
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $idProductAttribute = isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : 0;
                if ($idProductAttribute > 0) {
                    $aliases[] = (string) (int) $idProduct . 'v' . (int) $idProductAttribute;
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    protected function productHasCombinations($idProduct)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'product_attribute`
            WHERE `id_product` = ' . (int) $idProduct
        ) > 0;
    }

    protected function getProductMatomoNameAliases($idProduct)
    {
        $aliases = [];
        $rows = Db::getInstance()->executeS(
            'SELECT `name`
            FROM `' . _DB_PREFIX_ . 'product_lang`
            WHERE `id_product` = ' . (int) $idProduct
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $name = isset($row['name']) ? trim((string) $row['name']) : '';
                if ($name !== '') {
                    $aliases[] = $name;
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    protected function buildProductMetricsFromRows($rows, $aliases, $emptyMetrics, $labelType, $aggregateAliases)
    {
        $aliasMap = [];
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeMatomoProductLabel($alias);
            if ($normalizedAlias !== '') {
                $aliasMap[$normalizedAlias] = true;
            }
        }

        $matchedRows = [];
        $visits = 0;
        $actions = 0;
        $uniqueVisitors = 0;
        $orders = 0;
        $itemsPurchased = 0;
        $revenue = 0.0;
        $weightedAveragePrice = 0.0;
        $averagePriceWeight = 0;
        $purchasedAveragePriceTotal = 0.0;
        $purchasedAveragePriceWeight = 0;
        $weightedConversionRate = 0.0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = isset($row['label']) ? (string) $row['label'] : '';
            if ($label === '' || !isset($aliasMap[$this->normalizeMatomoProductLabel($label)])) {
                continue;
            }

            $rowVisits = (int) $this->firstNumericValue($row, ['nb_visits', 'visits']);
            $rowActions = (int) $this->firstNumericValue($row, ['nb_actions', 'actions']);
            $rowUniqueVisitors = (int) $this->firstNumericValue($row, ['sum_daily_nb_uniq_visitors', 'nb_uniq_visitors', 'nb_users', 'unique_visitors']);
            $rowRevenue = $this->firstRevenueValue($row);
            $rowOrders = $this->firstOrderValue($row);
            $rowQuantity = $this->firstItemsValue($row);
            $rowAveragePrice = $this->firstNumericValue($row, ['avg_price', 'average_price', 'price']);
            $rowConversionRate = $this->normalizePercentValue($this->firstStringValue($row, ['conversion_rate']));

            $visits += $rowVisits;
            $actions += $rowActions;
            $uniqueVisitors += $rowUniqueVisitors;
            $orders += $rowOrders;
            $itemsPurchased += $rowQuantity;
            $revenue += $rowRevenue;
            $weightedConversionRate += $rowConversionRate * max(1, $rowOrders);
            if ($rowAveragePrice > 0) {
                $rowAveragePriceWeight = max(1, $rowVisits);
                $weightedAveragePrice += $rowAveragePrice * $rowAveragePriceWeight;
                $averagePriceWeight += $rowAveragePriceWeight;
                if ($rowQuantity > 0 || $rowOrders > 0) {
                    $rowPurchasedAveragePriceWeight = max(1, $rowQuantity);
                    $purchasedAveragePriceTotal += $rowAveragePrice * $rowPurchasedAveragePriceWeight;
                    $purchasedAveragePriceWeight += $rowPurchasedAveragePriceWeight;
                }
            }

            $displayLabel = $this->getMatomoProductDisplayLabel($label, $aliases, $labelType, $aggregateAliases);
            if (!isset($matchedRows[$displayLabel])) {
                $matchedRows[$displayLabel] = [
                    'sku' => $displayLabel,
                    'visits' => 0,
                    'actions' => 0,
                    'unique_visitors' => 0,
                    'orders' => 0,
                    'items_purchased' => 0,
                    'revenue' => 0.0,
                    'revenue_formatted' => $this->formatDashboardAmount(0),
                    'conversion_rate' => '0.00%',
                    'conversion_rate_value' => 0.0,
                    'conversion_rate_weight' => 0,
                ];
            }

            $matchedRows[$displayLabel]['visits'] += $rowVisits;
            $matchedRows[$displayLabel]['actions'] += $rowActions;
            $matchedRows[$displayLabel]['unique_visitors'] += $rowUniqueVisitors;
            $matchedRows[$displayLabel]['orders'] += $rowOrders;
            $matchedRows[$displayLabel]['items_purchased'] += $rowQuantity;
            $matchedRows[$displayLabel]['revenue'] += $rowRevenue;
            $matchedRows[$displayLabel]['conversion_rate_value'] += $rowConversionRate * max(1, $rowOrders);
            $matchedRows[$displayLabel]['conversion_rate_weight'] += max(1, $rowOrders);
        }

        if (!$matchedRows) {
            return [
                'metrics' => $emptyMetrics,
                'rows' => [],
                'aliases' => $aliases,
                'error' => '',
            ];
        }

        foreach ($matchedRows as &$matchedRow) {
            $matchedRow['revenue_formatted'] = $this->formatDashboardAmount($matchedRow['revenue']);
            if ($aggregateAliases && $matchedRow['visits'] > 0) {
                $matchedRow['conversion_rate'] = $this->calculateRate($matchedRow['orders'], $matchedRow['visits']);
            } else {
                $matchedRow['conversion_rate'] = $matchedRow['conversion_rate_weight'] > 0
                    ? number_format($matchedRow['conversion_rate_value'] / $matchedRow['conversion_rate_weight'], 2, '.', '') . '%'
                    : '0.00%';
            }
            unset($matchedRow['conversion_rate_value'], $matchedRow['conversion_rate_weight']);
        }
        unset($matchedRow);

        $matchedRows = array_values($matchedRows);

        usort($matchedRows, function ($left, $right) {
            if ($left['revenue'] === $right['revenue']) {
                return 0;
            }

            return $left['revenue'] < $right['revenue'] ? 1 : -1;
        });

        $averagePrice = 0.0;
        if ($purchasedAveragePriceWeight > 0) {
            $averagePrice = $purchasedAveragePriceTotal / $purchasedAveragePriceWeight;
        } elseif ($averagePriceWeight > 0) {
            $averagePrice = $weightedAveragePrice / $averagePriceWeight;
        } elseif ($itemsPurchased > 0 && $revenue > 0) {
            $averagePrice = $revenue / $itemsPurchased;
        }

        $metrics = [
            'visits' => $visits,
            'actions' => $actions,
            'unique_visitors' => $uniqueVisitors,
            'orders' => $orders,
            'items_purchased' => $itemsPurchased,
            'revenue' => $revenue,
            'revenue_formatted' => $this->formatDashboardAmount($revenue),
            'average_price' => $this->formatDashboardAmount($averagePrice),
            'conversion_rate' => $aggregateAliases && $visits > 0
                ? $this->calculateRate($orders, $visits)
                : ($orders > 0 ? number_format($weightedConversionRate / $orders, 2, '.', '') . '%' : '0.00%'),
            'matched_rows' => count($matchedRows),
        ];

        return [
            'metrics' => $metrics,
            'rows' => $matchedRows,
            'aliases' => $aliases,
            'error' => '',
        ];
    }

    protected function getMatomoProductDisplayLabel($label, $aliases, $labelType, $aggregateAliases)
    {
        if ($labelType === 'name') {
            return $this->l('Product name') . ': ' . $label;
        }

        if (!$aggregateAliases || !isset($aliases[0])) {
            return $label;
        }

        return (string) $aliases[0];
    }

    protected function addProductReferrerRows($productData, $idProduct, $dateRange)
    {
        $productData['referrer_rows'] = $this->getProductReferrerRows($idProduct, $dateRange);

        return $productData;
    }

    protected function getProductReferrerRows($idProduct, $dateRange)
    {
        $productUrls = $this->getProductMatomoUrls($idProduct);
        if ($productUrls === []) {
            return [];
        }

        $referrerRows = [];
        foreach ($productUrls as $productUrl) {
            $rows = $this->callMatomoApi('Referrers.getReferrerType', $dateRange, [
                'idGoal' => 'ecommerceOrder',
                'segment' => 'pageUrl=@' . $productUrl,
                'filter_limit' => -1,
            ]);
            if (isset($rows['error']) || !is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $label = isset($row['label']) ? trim((string) $row['label']) : '';
                if ($label === '') {
                    continue;
                }

                if (!isset($referrerRows[$label])) {
                    $referrerRows[$label] = [
                        'label' => $label,
                        'visits' => 0,
                        'actions' => 0,
                        'orders' => 0,
                        'revenue' => 0.0,
                        'revenue_formatted' => $this->formatDashboardAmount(0),
                    ];
                }

                $referrerRows[$label]['visits'] += (int) $this->firstNumericValue($row, ['nb_visits', 'visits']);
                $referrerRows[$label]['actions'] += (int) $this->firstNumericValue($row, ['nb_actions', 'actions']);
                $referrerRows[$label]['orders'] += $this->firstOrderValue($row);
                $referrerRows[$label]['revenue'] += $this->firstRevenueValue($row);
            }
        }

        foreach ($referrerRows as &$referrerRow) {
            $referrerRow['revenue_formatted'] = $this->formatDashboardAmount($referrerRow['revenue']);
        }
        unset($referrerRow);

        $referrerRows = array_values($referrerRows);
        usort($referrerRows, function ($left, $right) {
            if ($left['visits'] === $right['visits']) {
                return 0;
            }

            return $left['visits'] < $right['visits'] ? 1 : -1;
        });

        return array_slice($referrerRows, 0, 8);
    }

    protected function getProductMatomoUrls($idProduct)
    {
        $urls = [];
        foreach (Language::getLanguages(false) as $language) {
            $idLang = isset($language['id_lang']) ? (int) $language['id_lang'] : 0;
            if ($idLang <= 0) {
                continue;
            }

            $product = new Product((int) $idProduct, false, $idLang);
            if (!Validate::isLoadedObject($product)) {
                continue;
            }

            $url = (string) $this->context->link->getProductLink($product, null, null, null, $idLang);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    protected function normalizeMatomoProductLabel($label)
    {
        $label = html_entity_decode((string) $label, ENT_QUOTES, 'UTF-8');
        $label = preg_replace('/\s+/', ' ', trim($label));

        return $label === null ? '' : Tools::strtolower($label);
    }

    protected function callMatomoApi($method, $dateRange, $extraParams = [])
    {
        $baseUrl = rtrim((string) Configuration::get('TEC_MATOMO_URL'), '/') . '/';
        $params = array_merge([
            'module' => 'API',
            'method' => $method,
            'idSite' => (int) Configuration::get('TEC_MATOMO_SITEID'),
            'period' => 'range',
            'date' => $dateRange['date_from'] . ',' . $dateRange['date_to'],
            'format' => 'JSON',
            'token_auth' => (string) Configuration::get('TEC_MATOMO_TOKEN'),
        ], $extraParams);
        $url = $baseUrl . 'index.php?' . http_build_query($params, '', '&');
        $response = Tools::file_get_contents($url);

        if ($response === false || $response === '') {
            return ['error' => $this->l('Matomo API did not return data.')];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['error' => $this->l('Matomo API returned an invalid response.')];
        }

        if (isset($data['result']) && $data['result'] === 'error') {
            return ['error' => isset($data['message']) ? (string) $data['message'] : $this->l('Matomo API returned an error.')];
        }

        return $data;
    }

    protected function normalizeDashboardChannelRows($rows)
    {
        return $this->normalizeTopRevenueRows($rows, 8);
    }

    protected function normalizeTopRevenueRows($rows, $limit)
    {
        $normalizedRows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = isset($row['label']) ? (string) $row['label'] : '';
            if ($label === '') {
                continue;
            }

            $revenue = $this->firstRevenueValue($row);
            $orders = $this->firstOrderValue($row);
            $items = $this->firstItemsValue($row);

            $normalizedRows[] = [
                'label' => $label,
                'visits' => (int) $this->firstNumericValue($row, ['nb_visits', 'visits']),
                'orders' => $orders,
                'items' => $items,
                'revenue' => $revenue,
                'revenue_formatted' => $this->formatDashboardAmount($revenue),
            ];
        }

        usort($normalizedRows, function ($left, $right) {
            if ($left['revenue'] === $right['revenue']) {
                return 0;
            }

            return $left['revenue'] < $right['revenue'] ? 1 : -1;
        });

        return array_slice($normalizedRows, 0, (int) $limit);
    }

    protected function firstRevenueValue($row)
    {
        $revenue = $this->firstNumericValue($row, ['revenue', 'revenue_subtotal']);
        if ($revenue > 0) {
            return $revenue;
        }

        return $this->firstNumericValueBySuffix($row, '_revenue');
    }

    protected function firstOrderValue($row)
    {
        $orders = (int) $this->firstNumericValue($row, ['orders', 'nb_conversions', 'conversions']);
        if ($orders > 0) {
            return $orders;
        }

        return (int) $this->firstNumericValueBySuffix($row, '_nb_conversions');
    }

    protected function firstItemsValue($row)
    {
        $items = (int) $this->firstNumericValue($row, ['items', 'quantity', 'product_quantity', 'nb_items']);
        if ($items > 0) {
            return $items;
        }

        return (int) $this->firstNumericValueBySuffix($row, '_items');
    }

    protected function firstNumericValueBySuffix($row, $suffix)
    {
        if (!is_array($row)) {
            return 0.0;
        }

        foreach ($row as $key => $value) {
            if (!is_string($key) || substr($key, -strlen($suffix)) !== $suffix) {
                continue;
            }

            $normalizedValue = is_string($value) ? str_replace(['%', ','], ['', '.'], $value) : $value;
            if (is_numeric($normalizedValue)) {
                return (float) $normalizedValue;
            }
        }

        return 0.0;
    }

    protected function firstNumericValue($row, $keys)
    {
        foreach ($keys as $key) {
            if (!isset($row[$key])) {
                continue;
            }

            $value = is_string($row[$key]) ? str_replace(['%', ','], ['', '.'], $row[$key]) : $row[$key];
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    protected function firstStringValue($row, $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && is_scalar($row[$key])) {
                return (string) $row[$key];
            }
        }

        return '';
    }

    protected function calculateRate($orders, $visits)
    {
        if ($visits <= 0) {
            return '0.00%';
        }

        return number_format(($orders / $visits) * 100, 2, '.', '') . '%';
    }

    protected function normalizePercentValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace(['%', ','], ['', '.'], $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function formatDashboardAmount($amount)
    {
        $currency = $this->context->currency;
        $formattedAmount = number_format((float) $amount, 2, '.', '');

        if (Validate::isLoadedObject($currency) && isset($currency->sign)) {
            return $formattedAmount . ' ' . $currency->sign;
        }

        return $formattedAmount;
    }

    protected function ensureDashboardEmployeeDateRange()
    {
        if (!isset($this->context->employee) || !Validate::isLoadedObject($this->context->employee)) {
            return;
        }

        $dateFrom = $this->normalizeDashboardDate((string) $this->context->employee->stats_date_from);
        $dateTo = $this->normalizeDashboardDate((string) $this->context->employee->stats_date_to);
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom <= $dateTo) {
            return;
        }

        $this->context->employee->stats_date_from = date('Y-m-d', strtotime('-30 days'));
        $this->context->employee->stats_date_to = date('Y-m-d');
        $this->context->employee->update();
    }

    protected function sanitizeDashboardRequestDate($key, $fallbackDate)
    {
        $fallbackDate = $this->normalizeDashboardDate($fallbackDate);
        if ($fallbackDate === '') {
            $fallbackDate = date('Y-m-d');
        }

        foreach (['_GET', '_POST'] as $source) {
            if (!isset($GLOBALS[$source][$key])) {
                continue;
            }

            $value = $GLOBALS[$source][$key];
            $normalizedDate = is_scalar($value) ? $this->normalizeDashboardDate((string) $value) : '';
            $GLOBALS[$source][$key] = $normalizedDate !== '' ? $normalizedDate : $fallbackDate;
        }
    }

    protected function getDashboardDateRange($params)
    {
        $dateFrom = $this->normalizeDashboardDate($this->getDashboardParamValue($params, 'date_from'));
        $dateTo = $this->normalizeDashboardDate($this->getDashboardParamValue($params, 'date_to'));
        if ($dateFrom === '' || $dateTo === '' || $dateFrom > $dateTo) {
            $dateTo = date('Y-m-d', strtotime('-1 day'));
            $dateFrom = date('Y-m-d', strtotime('-30 days'));

            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'period_days' => 30,
                'label' => $this->l('Last 30 days'),
                'has_selected_range' => false,
            ];
        }

        $from = new DateTime($dateFrom);
        $to = new DateTime($dateTo);
        $periodDays = max(1, min(365, (int) $from->diff($to)->days + 1));

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period_days' => $periodDays,
            'label' => $dateFrom . ' - ' . $dateTo,
            'has_selected_range' => true,
        ];
    }

    protected function normalizeDashboardDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return '';
        }

        $formats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);
            if ($dateTime instanceof DateTime && $dateTime->format($format) === $date) {
                return $dateTime->format('Y-m-d');
            }
        }

        return '';
    }

    protected function getDashboardParamValue($params, $key)
    {
        if (isset($params[$key]) && is_scalar($params[$key])) {
            return (string) $params[$key];
        }

        return (string) Tools::getValue($key);
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayAfterBodyOpeningTag()
    {
        // base
        $active      = (int)Configuration::get('TEC_MATOMO_ACTIVE');
        $matomoId    = (int)Configuration::get('TEC_MATOMO_SITEID');
        $matomoUrl   = (string)Configuration::get('TEC_MATOMO_URL');

        // feature flags
        $trackEcom   = (int)Configuration::get('TEC_MATOMO_ECOMMERCE');
        $useUserId   = (int)Configuration::get('TEC_MATOMO_USERID');
        $addNoscript = (int)Configuration::get('TEC_MATOMO_JS');

        // privacy
        $privacyMode   = (string)Configuration::get('TEC_MATOMO_PRIVACY_MODE'); // none|cookieless|consent
        $secureCookie  = (int)Configuration::get('TEC_MATOMO_SECURECOOKIE');

        // subdomain / cross-domain
        $useSubdomains   = (int)Configuration::get('TEC_MATOMO_SUBDOMAINS');
        $baseDomain      = (string)Configuration::get('TEC_MATOMO_BASEDOMAIN'); // es. .example.it
        $useCrossDomain  = (int)Configuration::get('TEC_MATOMO_CROSSDOMAIN');
        $crossCsv        = (string)Configuration::get('TEC_MATOMO_CROSSDOMAIN_DOMAINS');
        $prependTitleDom = (int)Configuration::get('TEC_MATOMO_TITLE_DOMAIN');

        // campaigns
        $campNameKey = (string)Configuration::get('TEC_MATOMO_CAMPAIGN_NAMEKEY');
        $campTermKey = (string)Configuration::get('TEC_MATOMO_CAMPAIGN_TERMKEY');

        // engagement
        $hbEnable   = (int)Configuration::get('TEC_MATOMO_HEARTBEAT_ENABLE');
        $hbSeconds  = (int)Configuration::get('TEC_MATOMO_HEARTBEAT_SEC');

        $consentManager = $this->getConsentManagerMode();
        $lgEnable = $consentManager === 'lg' ? 1 : 0;
        $artCookieEnable = $consentManager === 'artcookie' ? 1 : 0;
        $iubendaEnable = $consentManager === 'iubenda' ? 1 : 0;
        $cookiebotEnable = $consentManager === 'cookiebot' ? 1 : 0;
        $cookieyesEnable = $consentManager === 'cookieyes' ? 1 : 0;

        // userid dal cliente loggato
        $idCustomer = null;
        if (isset($this->context->customer) && $this->context->customer->isLogged()) {
            $idCustomer = (int)$this->context->customer->id;
        }

        // normalizzo la lista domini cross-domain per il template (array)
        $crossDomains = array();
        if ($crossCsv !== '') {
            $parts = array_map('trim', explode(',', $crossCsv));
            $crossDomains = array_values(array_filter($parts, function ($v) { return $v !== ''; }));
        }

        $controller = $this->context->controller;
        $ctrlName   = isset($controller->php_self) ? $controller->php_self : '';
        $ctrlQuery  = Tools::getValue('controller', '');
        $isProduct  = (($ctrlName === 'product') || ($ctrlQuery === 'product')) ? 1 : 0;

        $mtmProduct = null; // struttura dati da passare a Smarty
        if ($isProduct && $trackEcom === 1) {
            $idProduct = (int)Tools::getValue('id_product');
            if ($idProduct > 0) {
                $idAttr  = (int)Tools::getValue('id_product_attribute');
                $idLang  = (int)$this->context->language->id;
                $product = new Product($idProduct, false, $idLang);
                if (Validate::isLoadedObject($product)) {
                    $sku  = (string)$idProduct . ($idAttr > 0 ? ('v'.$idAttr) : '');
                    $name = (string)$product->name;

                    // categorie (max 5 livelli; es: padre + default)
                    $cats = [];
                    if ((int)$product->id_category_default > 0) {
                        $cat = new Category((int)$product->id_category_default, $idLang);
                        if (Validate::isLoadedObject($cat)) {
                            if ((int)$cat->id_parent > 1) {
                                $parent = new Category((int)$cat->id_parent, $idLang);
                                if (Validate::isLoadedObject($parent)) {
                                    $cats[] = (string)$parent->name;
                                }
                            }
                            $cats[] = (string)$cat->name;
                        }
                    }
                    if (empty($cats)) { $cats = [$this->l('Not detected')]; }
                    $cats = array_slice($cats, 0, 5);

                    // prezzo con IVA, 2 decimali
                    $price = (float)Product::getPriceStatic($idProduct, true, $idAttr);
                    $price = (float)Tools::ps_round($price, 2);

                    $mtmProduct = [
                        'sku'   => $sku,
                        'name'  => $name,
                        'cats'  => $cats,
                        'price' => $price,
                    ];
                }
            }
        }

        // assegno al template
        $this->smarty->assign(array(
            // base
            'matomo_id'  => $matomoId,
            'matomo_url' => $matomoUrl,

            // feature flags
            'matomo_ecommerce' => $trackEcom,
            'matomo_userid'    => $useUserId,
            'matomo_js'        => $addNoscript,

            // privacy
            'matomo_privacy_mode' => $privacyMode,     // none|cookieless|consent
            'matomo_securecookie' => $secureCookie,    // 0|1
            'matomo_lg_enable'   => $lgEnable,        // 0|1
            'matomo_artcookie_enable' => $artCookieEnable,
            'matomo_iubenda_enable' => $iubendaEnable,
            'matomo_cookiebot_enable' => $cookiebotEnable,
            'matomo_cookieyes_enable' => $cookieyesEnable,

            // sub/cross domain
            'matomo_subdomains'        => $useSubdomains,
            'matomo_basedomain'        => $baseDomain,
            'matomo_crossdomain'       => $useCrossDomain,
            'matomo_crossdomain_list'  => $crossDomains, // ARRAY
            'matomo_title_domain'      => $prependTitleDom,

            // campaign keys
            'matomo_campaign_namekey'  => $campNameKey,
            'matomo_campaign_termkey'  => $campTermKey,

            // engagement
            'matomo_heartbeat_enable'  => $hbEnable,
            'matomo_heartbeat_sec'     => $hbSeconds,

            // prodotto
            'matomo_is_product' => (int)$isProduct,
            'mtm_product'       => $mtmProduct,

            // userid effettivo
            'motomo_customer' => $idCustomer,

        ));

        if ($active === 1 && $matomoUrl !== '' && $matomoId > 0) {
            return $this->display(__FILE__, 'jstracking.tpl');
        }

        return '';

    }

    public function hookOrderConfirmation($params)
    {
        $active = Configuration::get('TEC_MATOMO_ACTIVE');
        $ecommerce = Configuration::get('TEC_MATOMO_ECOMMERCE');

        $order = $params['order'];
        $order_id = $order->reference;
        $total_paid = $order->total_paid;
        $total_paid_tax_excl = $order->total_paid_tax_excl;
        $total_tax = $total_paid - $total_paid_tax_excl;
        $total_shipping = $order->total_shipping;
        $total_discounts = (float)$order->total_discounts; //discount coupon

        if ($total_discounts == 0) {
            $total_discounts = 'false';
        }
        $order_list_details = array();

        $ord_details = $order->getOrderDetailList();
        foreach ($ord_details as $detail) {
            $product_id = $detail['product_id'];
            $product_attribute_id = $detail['product_attribute_id'];
            $product_sku = $product_id.'v'.$product_attribute_id;
            $lang_id = (int)Configuration::get('PS_LANG_DEFAULT');
            $product = new Product($product_id, false, $lang_id);
            $product_name = $product->name;
            $product_category = $product->category;
            if ($product_category == '') {
                $product_category = $this->l('Not detected');
            }
            $product_price = $detail['product_price'];
            $product_quantity = $detail['product_quantity'];
            $order_list_details[] = array(
                'product_sku' => $product_sku,
                'product_name' => $product_name,
                'product_category' => $product_category,
                'product_price' => $product_price,
                'product_quantity' => $product_quantity
            );
        }

        $this->smarty->assign(array(
            'ecommerce' => $ecommerce,
            'order_id' => $order_id,
            'total_paid' => (float)$total_paid,
            'total_paid_tax_excl' => (float)$total_paid_tax_excl,
            'total_tax' => (float)$total_tax,
            'total_shipping' => (float)$total_shipping,
            'total_discounts' => $total_discounts,
            'ord_details' => $order_list_details,
        ));
        if ($active == 1 && $ecommerce ==1) {
            return $this->display(__FILE__, 'orderconfirmation.tpl');
        }
    }


    public function hookDisplayFooterProduct()
    {
        return '';
    }


    public function hookDisplayFooter()
    {
        $active = Configuration::get('TEC_MATOMO_ACTIVE');
        $ecommerce = Configuration::get('TEC_MATOMO_ECOMMERCE');
        if ($active == 1 && $ecommerce ==1) {
            return $this->display(__FILE__, 'matomo_jscart.tpl');
        }

        return '';
    }

}
