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
        $this->version = '1.1.2';
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

        return parent::install() &&
            //$this->registerHook('header') &&
            //$this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('displayAfterBodyOpeningTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TEC_MATOMO_URL');
        Configuration::deleteByName('TEC_MATOMO_SITEID');
        Configuration::deleteByName('TEC_MATOMO_TOKEN');
        Configuration::deleteByName('TEC_MATOMO_ACTIVE');
        Configuration::deleteByName('TEC_MATOMO_ECOMMERCE');
        Configuration::deleteByName('TEC_MATOMO_USERID');
        Configuration::deleteByName('TEC_MATOMO_JS');
        Configuration::deleteByName('TEC_MATOMO_DNTRACK');

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
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable monitoring'),
                        'name' => 'TEC_MATOMO_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Active monitoring of statistics with matomo'),
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
                        'label' => $this->l('Matomo URL'),
                        'desc' => $this->l('Enter Matomo URL es. https://stat.matomo.net/ be careful to enter complete URL of final /'),
                        'name' => 'TEC_MATOMO_URL',
                        'lang' => false,
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Matomo SiteID'),
                        'name' => 'TEC_MATOMO_SITEID',
                        'lang' => false,
                        'autoload_rte' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Matomo API Token'),
                        'desc' => $this->l('Optional to import and / or view data in the PrestaShop backend'),
                        'name' => 'TEC_MATOMO_TOKEN',
                        'lang' => false,
                        'autoload_rte' => true,
                    ),
                    /*array(
                        'type' => 'textarea',
                        'label' => $this->l('Tracking code'),
                        'name' => 'TEC_MATOMO_CODE',
                        'lang' => false,
                        'desc' => $this->l('Enter the tracking code generated in Matomo'),
                        'class' => 'code_area',
                    ), */
                    array(
                            'type' => 'switch',
                            'label' => $this->l('Ecommerce tracking'),
                            'name' => 'TEC_MATOMO_ECOMMERCE',
                            'is_bool' => true,
                            'desc' => $this->l('Activate the tracking of ecommerce activities, orders, carts and product display.'),
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
                                'type' => 'switch',
                                'label' => $this->l('UserID tracking'),
                                'name' => 'TEC_MATOMO_USERID',
                                'is_bool' => true,
                                'desc' => $this->l('Enable tracking of the User ID. When you enable User ID tracking, you associate a persistent identifier with a user so you can recognise their visits across different devices and visits. As User IDs precisely track specific users on your site, this feature has significant implications for user privacy. While using this feature, you will likely require consent from your users under regulations such as the GDPR.'),
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
                        'type' => 'switch',
                        'label' => $this->l('Track Javascript disabled'),
                        'name' => 'TEC_MATOMO_JS',
                        'is_bool' => true,
                        'desc' => $this->l('Track users with JavaScript disabled'),
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
                        'type' => 'switch',
                        'label' => $this->l('Respect DoNotTrack'),
                        'name' => 'TEC_MATOMO_DNTRACK',
                        'is_bool' => true,
                        'desc' => $this->l('In this way, tracking requests will not be sent if the visitor does not want to be tracked.'),
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
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'TEC_MATOMO_ACTIVE' => Tools::getValue('TEC_MATOMO_ACTIVE', Configuration::get('TEC_MATOMO_ACTIVE')),
            //'TEC_MATOMO_CODE' => Tools::getValue('TEC_MATOMO_CODE', Configuration::get('TEC_MATOMO_CODE')),
            'TEC_MATOMO_ECOMMERCE' => Tools::getValue('TEC_MATOMO_ECOMMERCE', Configuration::get('TEC_MATOMO_ECOMMERCE')),
            'TEC_MATOMO_USERID' => Tools::getValue('TEC_MATOMO_USERID', Configuration::get('TEC_MATOMO_USERID')),
            'TEC_MATOMO_URL' => Tools::getValue('TEC_MATOMO_URL', Configuration::get('TEC_MATOMO_URL')),
            'TEC_MATOMO_SITEID' => Tools::getValue('TEC_MATOMO_SITEID', Configuration::get('TEC_MATOMO_SITEID')),
            'TEC_MATOMO_TOKEN' => Tools::getValue('TEC_MATOMO_TOKEN', Configuration::get('TEC_MATOMO_TOKEN')),
            'TEC_MATOMO_DNTRACK' => Tools::getValue('TEC_MATOMO_DNTRACK', Configuration::get('TEC_MATOMO_DNTRACK')),
            'TEC_MATOMO_JS' => Tools::getValue('TEC_MATOMO_JS', Configuration::get('TEC_MATOMO_JS')),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';
        $this->_errors = array();
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            switch ($key) {
                case 'TEC_MATOMO_CODE':
                    Configuration::updateValue($key, Tools::getValue($key), true);
                    break;
                case 'TEC_MATOMO_URL':
                    $url = trim(Tools::getValue($key));
                    if (substr($url, -1) !=  '/') {
                       $url = $url.'/';
                    } if (!Validate::isAbsoluteUrl($url)) {
                    $this->_errors[] = $url.' '.$this->l('it is not a valid url');
                    break;
                    } else {
                        Configuration::updateValue($key, $url);
                        break;
                    }
                case 'TEC_MATOMO_SITEID':
                    $id_site = (int)Tools::getValue($key);
                    if ($id_site < 1) {
                        $this->_errors[] = $id_site.' '.$this->l('it is not a valid ID');
                        break;
                    } else {
                        Configuration::updateValue($key, $id_site);
                        break;
                    }
                case 'TEC_MATOMO_TOKEN':
                    $token = Tools::getValue($key);
                    Configuration::updateValue($key, $token);
                    break;
                default:
                    Configuration::updateValue($key, Tools::getValue($key));
                    break;

            }
        }
        if (!count($this->_errors)){
        $output .= $this->displayConfirmation($this->l('Settings updated'));
        } else {
            foreach ($this->_errors as $error)
                $errors = $error.' '.$this->l('Settings failed');

            $output .= $this->displayError($errors);
        }
        return $output;
    }


    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayAfterBodyOpeningTag()
    {
        $active = Configuration::get('TEC_MATOMO_ACTIVE');
        //$matomo_code = Configuration::get('TEC_MATOMO_CODE');
        $matomo_id = Configuration::get('TEC_MATOMO_SITEID');
        $matomo_url = Configuration::get('TEC_MATOMO_URL');
        $matomo_userid = Configuration::get('TEC_MATOMO_USERID');
        $matomo_js = Configuration::get('TEC_MATOMO_JS');
        $matomo_dntrack = Configuration::get('TEC_MATOMO_DNTRACK');

        $id_customer = null;
        if ($this->context->customer->isLogged()) {
            $id_customer = $this->context->customer->id;
        }

        $this->smarty->assign(array(
            'matomo_userid' => $matomo_userid,
            'matomo_id' => $matomo_id,
            'matomo_url' => $matomo_url,
            'matomo_js' => $matomo_js,
            'matomo_dntrack' => $matomo_dntrack,
            'motomo_customer' => $id_customer,

        ));
        if ($active == 1 && $matomo_url != '' && $matomo_id > 0) {
            return $this->display(__FILE__, 'jstracking.tpl');
        }
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
        $id_product = (int)Tools::getValue('id_product');
        $lang_id = (int)Configuration::get('PS_LANG_DEFAULT');
        $product = new Product($id_product, false, $lang_id);
        $attribute_id = (int)Tools::getValue('id_product_attribute');;
        $product_sku = $id_product.'v'.$attribute_id;
        $product_category = $product->category;
        if ($product_category == '') {
            $product_category = $this->l('Not detected');
        }

        $active = Configuration::get('TEC_MATOMO_ACTIVE');
        $ecommerce = Configuration::get('TEC_MATOMO_ECOMMERCE');
        $this->smarty->assign(array(
            'product_sku' => $product_sku,
            'product_name' => $product->name,
            'product_category' => $product_category,
        ));

        if ($active == 1 && $ecommerce == 1) {
            return $this->display(__FILE__, 'productview.tpl');
        }
    }


    public function hookDisplayFooter()
    {
        $active = Configuration::get('TEC_MATOMO_ACTIVE');
        $ecommerce = Configuration::get('TEC_MATOMO_ECOMMERCE');
        if ($active == 1 && $ecommerce ==1) {
            return $this->display(__FILE__, 'matomo_jscart.tpl');
        }

    }

}
