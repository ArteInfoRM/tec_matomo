<?php
/**
 *  2009-2025 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2023 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.0
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
        $this->version = '1.1.4';
        $this->author = 'Tecnoacquisti.com';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Matomo Analytics for PrestaShop, with ecommerce tracking');
        $this->description = $this->l('Matomo is Google Analytics alternative that protects your data and your customers\' privacy');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
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
        Configuration::updateValue('TEC_MATOMO_LG_ENABLE', false);
        Configuration::updateValue('TEC_MATOMO_LG_PURPOSE', 3); // default purpose ID for Analytics in LG Cookies Law

        return parent::install() &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayAfterBodyOpeningTag');
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
        Configuration::deleteByName('TEC_MATOMO_LG_ENABLE');
        Configuration::deleteByName('TEC_MATOMO_LG_PURPOSE');

        return parent::uninstall();
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
        $token = Configuration::get('TEC_MATOMO_TOKEN');
        $matomo_url = Configuration::get('TEC_MATOMO_URL');
        $matomo_id = Configuration::get('TEC_MATOMO_SITEID');
        $useSsl = (bool)Configuration::get('PS_SSL_ENABLED_EVERYWHERE') || (bool)Configuration::get('PS_SSL_ENABLED');
        $shop_base_url = $this->context->link->getBaseLink((int)$this->context->shop->id, $useSsl);

        if (((bool)Tools::isSubmit('submitTec_matomoModule')) == true) {
            $output .= $this->postProcess();
        }

        $graph_visits = null;
        if ($token != '' && $matomo_url != '' && $matomo_id > 0) {
            $url = $matomo_url;
            $url .= "?module=API&method=ImageGraph.get";
            $url .= "&idSite=".(int)$matomo_id."&apiModule=VisitsSummary&apiAction=get";
            $url .= "&graphType=verticalBar&period=day&date=previous30&width=1680&height=500";
            $url .= "&token_auth=$token";
            $graph_visits = $url;

        }

        $this->smarty->assign(array(
            'graph_visits' => $graph_visits,
            'shop_base_url' => $shop_base_url,
        ));

        $this->context->smarty->assign('module_dir', $this->_path);

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
                    // === INTEGRAZIONE LG COOKIES LAW ===
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable LG Cookies Law integration'),
                        'name' => 'TEC_MATOMO_LG_ENABLE',
                        'is_bool' => true,
                        'desc' => $this->l('If enabled, the module listens to the LG Cookies Law banner (Analitici purpose) to enable/disable Matomo cookies.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('LG Cookies Law purpose ID'),
                        'name' => 'TEC_MATOMO_LG_PURPOSE',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->l('Numeric ID of the LG purpose for Analytics (default is 3, corresponding to lgcookieslaw_purpose_3).'),
                    ),
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
            'TEC_MATOMO_LG_ENABLE'  => (int) Tools::getValue('TEC_MATOMO_LG_ENABLE',  (int)Configuration::get('TEC_MATOMO_LG_ENABLE')),
            'TEC_MATOMO_LG_PURPOSE' => (int) Tools::getValue('TEC_MATOMO_LG_PURPOSE', (int)Configuration::get('TEC_MATOMO_LG_PURPOSE'))
        );
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
        if ((int) Configuration::get('TEC_MATOMO_LG_ENABLE') === 1) {
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

        $lgEnable = (int) Configuration::get('TEC_MATOMO_LG_ENABLE');

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
