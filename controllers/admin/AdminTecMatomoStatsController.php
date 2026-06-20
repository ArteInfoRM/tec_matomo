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

class AdminTecMatomoStatsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->meta_title = 'Matomo Analytics';

        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('export_matomo_data')) {
            $this->exportData();
        }

        parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();

        $matomoModule = $this->getMatomoModule();
        $dateRange = $this->getDateRange();
        $matomoData = $matomoModule->getDashboardMatomoData($dateRange);

        $this->context->controller->addCSS($matomoModule->getPathUri() . 'views/css/admin.css');
        $this->context->smarty->assign([
            'mtm_stats_form_action' => $this->context->link->getAdminLink('AdminTecMatomoStats'),
            'mtm_stats_token' => Tools::getAdminTokenLite('AdminTecMatomoStats'),
            'mtm_stats_config_url' => $this->context->link->getAdminLink('AdminModules')
                . '&configure=' . $matomoModule->name
                . '&tab_module=' . $matomoModule->tab
                . '&module_name=' . $matomoModule->name,
            'mtm_stats_is_connected' => $matomoModule->isMatomoApiConfigured(),
            'mtm_stats_api_error' => $matomoData['error'],
            'mtm_stats_site_metrics' => $matomoData['site_metrics'],
            'mtm_stats_channel_rows' => $matomoData['channel_rows'],
            'mtm_stats_ai_assistant_metrics' => $this->getAiAssistantMetrics($matomoData['channel_rows']),
            'mtm_stats_country_rows' => $matomoData['country_rows'],
            'mtm_stats_product_rows' => $matomoData['product_rows'],
            'mtm_stats_category_rows' => $matomoData['category_rows'],
            'mtm_stats_date_from' => $dateRange['date_from'],
            'mtm_stats_date_to' => $dateRange['date_to'],
            'mtm_stats_period_label' => $dateRange['label'],
            'mtm_stats_period_preset' => $dateRange['preset'],
            'mtm_stats_period_presets' => $this->buildPeriodPresetLinks($dateRange['preset']),
            'mtm_stats_export_formats' => $this->getExportFormats(),
            'mtm_stats_export_format' => $this->normalizeExportFormat((string) Tools::getValue('export_format', 'csv')),
        ]);

        $this->content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'tec_matomo/views/templates/admin/stats_dashboard.tpl');
        $this->context->smarty->assign('content', $this->content);
    }

    protected function exportData()
    {
        $matomoModule = $this->getMatomoModule();
        $format = $this->normalizeExportFormat((string) Tools::getValue('export_format', 'csv'));
        if ($format === '') {
            $this->errors[] = $matomoModule->l('Invalid export request.');

            return;
        }

        $dateRange = $this->getDateRange();
        $matomoData = $matomoModule->getDashboardMatomoData($dateRange);
        $rows = $this->buildExportRows($matomoData);
        $content = $this->renderExport($rows, $format, $dateRange);

        header('Content-Type: ' . $this->getExportContentType($format));
        header('Content-Disposition: attachment; filename="' . $this->getExportFilename($format, $dateRange) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $content;
        exit;
    }

    /**
     * @return Tec_matomo
     */
    protected function getMatomoModule()
    {
        if ($this->module instanceof Tec_matomo) {
            return $this->module;
        }

        return Module::getInstanceByName('tec_matomo');
    }

    protected function getDateRange()
    {
        $dateRange = $this->getPresetDateRange((string) Tools::getValue('period_preset'));
        if ($dateRange !== []) {
            return $dateRange;
        }

        $dateFrom = $this->normalizeDate((string) Tools::getValue('date_from'));
        $dateTo = $this->normalizeDate((string) Tools::getValue('date_to'));

        if ($dateFrom === '' || $dateTo === '' || $dateFrom > $dateTo) {
            $dateTo = date('Y-m-d', strtotime('-1 day'));
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'label' => $dateFrom . ' - ' . $dateTo,
            'preset' => '',
        ];
    }

    protected function getExportFormats()
    {
        return [
            'csv' => 'CSV',
            'json' => 'JSON',
            'xml' => 'XML',
        ];
    }

    protected function normalizeExportFormat($format)
    {
        $format = strtolower(trim((string) $format));

        return isset($this->getExportFormats()[$format]) ? $format : '';
    }

    protected function getExportContentType($format)
    {
        if ($format === 'json') {
            return 'application/json; charset=utf-8';
        }

        if ($format === 'xml') {
            return 'application/xml; charset=utf-8';
        }

        return 'text/csv; charset=utf-8';
    }

    protected function getExportFilename($format, $dateRange)
    {
        $dateFrom = isset($dateRange['date_from']) ? preg_replace('/[^0-9-]/', '', (string) $dateRange['date_from']) : date('Y-m-d');
        $dateTo = isset($dateRange['date_to']) ? preg_replace('/[^0-9-]/', '', (string) $dateRange['date_to']) : date('Y-m-d');

        return 'tec_matomo_stats_' . $dateFrom . '_' . $dateTo . '.' . $format;
    }

    protected function buildExportRows($matomoData)
    {
        $rows = [];
        $siteMetrics = isset($matomoData['site_metrics']) && is_array($matomoData['site_metrics'])
            ? $matomoData['site_metrics']
            : [];

        $rows[] = [
            'section' => 'site_metrics',
            'label' => 'Site totals',
            'visits' => isset($siteMetrics['visits']) ? (int) $siteMetrics['visits'] : 0,
            'unique_visitors' => isset($siteMetrics['unique_visitors']) ? (int) $siteMetrics['unique_visitors'] : 0,
            'actions' => isset($siteMetrics['actions']) ? (int) $siteMetrics['actions'] : 0,
            'bounce_rate' => isset($siteMetrics['bounce_rate']) ? (string) $siteMetrics['bounce_rate'] : '',
            'orders' => isset($siteMetrics['orders']) ? (int) $siteMetrics['orders'] : 0,
            'items' => 0,
            'revenue' => isset($siteMetrics['revenue']) ? (float) $siteMetrics['revenue'] : 0.0,
            'conversion_rate' => isset($siteMetrics['conversion_rate']) ? (string) $siteMetrics['conversion_rate'] : '',
            'average_order_value' => isset($siteMetrics['average_order_value']) ? (string) $siteMetrics['average_order_value'] : '',
        ];

        foreach ([
            'channel' => 'channel_rows',
            'country' => 'country_rows',
            'product' => 'product_rows',
            'category' => 'category_rows',
        ] as $section => $key) {
            $sourceRows = isset($matomoData[$key]) && is_array($matomoData[$key]) ? $matomoData[$key] : [];
            foreach ($sourceRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rows[] = [
                    'section' => $section,
                    'label' => isset($row['label']) ? (string) $row['label'] : '',
                    'visits' => isset($row['visits']) ? (int) $row['visits'] : 0,
                    'unique_visitors' => 0,
                    'actions' => 0,
                    'bounce_rate' => '',
                    'orders' => isset($row['orders']) ? (int) $row['orders'] : 0,
                    'items' => isset($row['items']) ? (int) $row['items'] : 0,
                    'revenue' => isset($row['revenue']) ? (float) $row['revenue'] : 0.0,
                    'conversion_rate' => '',
                    'average_order_value' => '',
                ];
            }
        }

        return $rows;
    }

    protected function getAiAssistantMetrics($channelRows)
    {
        $emptyMetrics = [
            'available' => false,
            'label' => 'AI Assistant',
            'visits' => 0,
            'orders' => 0,
            'revenue' => 0.0,
            'revenue_formatted' => '0.00',
        ];

        if (!is_array($channelRows)) {
            return $emptyMetrics;
        }

        foreach ($channelRows as $row) {
            if (!is_array($row) || !isset($row['label'])) {
                continue;
            }

            $label = (string) $row['label'];
            $normalizedLabel = strtolower($label);
            if (strpos($normalizedLabel, 'ai assistant') === false) {
                continue;
            }

            return [
                'available' => true,
                'label' => $label,
                'visits' => isset($row['visits']) ? (int) $row['visits'] : 0,
                'orders' => isset($row['orders']) ? (int) $row['orders'] : 0,
                'revenue' => isset($row['revenue']) ? (float) $row['revenue'] : 0.0,
                'revenue_formatted' => isset($row['revenue_formatted']) ? (string) $row['revenue_formatted'] : '0.00',
            ];
        }

        return $emptyMetrics;
    }

    protected function renderExport($rows, $format, $dateRange)
    {
        if ($format === 'json') {
            return $this->renderJsonExport($rows, $dateRange);
        }

        if ($format === 'xml') {
            return $this->renderXmlExport($rows, $dateRange);
        }

        return $this->renderCsvExport($rows);
    }

    protected function renderJsonExport($rows, $dateRange)
    {
        $payload = [
            'meta' => [
                'module' => 'tec_matomo',
                'scope' => 'admin_stats',
                'date_from' => $dateRange['date_from'],
                'date_to' => $dateRange['date_to'],
                'generated_at' => date('c'),
                'row_count' => count($rows),
            ],
            'rows' => $rows,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? $json . "\n" : "{}\n";
    }

    protected function renderXmlExport($rows, $dateRange)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('matomo_stats_export');
        $root->setAttribute('module', 'tec_matomo');
        $root->setAttribute('scope', 'admin_stats');
        $root->setAttribute('date_from', (string) $dateRange['date_from']);
        $root->setAttribute('date_to', (string) $dateRange['date_to']);
        $root->setAttribute('generated_at', date('c'));
        $root->setAttribute('row_count', (string) count($rows));
        $document->appendChild($root);

        foreach ($rows as $row) {
            $rowNode = $document->createElement('row');
            foreach ($row as $field => $value) {
                $node = $document->createElement((string) $field);
                $node->appendChild($document->createTextNode((string) $value));
                $rowNode->appendChild($node);
            }
            $root->appendChild($rowNode);
        }

        $xml = $document->saveXML();

        return is_string($xml) ? $xml : '';
    }

    protected function renderCsvExport($rows)
    {
        $handle = fopen('php://temp', 'r+');
        if (!is_resource($handle)) {
            return '';
        }

        $headers = [
            'section',
            'label',
            'visits',
            'unique_visitors',
            'actions',
            'bounce_rate',
            'orders',
            'items',
            'revenue',
            'conversion_rate',
            'average_order_value',
        ];
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $this->sanitizeCsvValue(isset($row[$header]) ? $row[$header] : '');
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return is_string($content) ? $content : '';
    }

    protected function sanitizeCsvValue($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? '\'' . $value : $value;
    }

    protected function getPresetDateRange($preset)
    {
        $preset = trim((string) $preset);
        if (!isset($this->getPeriodPresetDefinitions()[$preset])) {
            return [];
        }

        $ranges = $this->buildPeriodPresetRanges();
        if (!isset($ranges[$preset])) {
            return [];
        }

        $dateFrom = $ranges[$preset]['date_from'];
        $dateTo = $ranges[$preset]['date_to'];

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'label' => $dateFrom . ' - ' . $dateTo,
            'preset' => $preset,
        ];
    }

    protected function buildPeriodPresetLinks($activePreset)
    {
        $links = [];
        $ranges = $this->buildPeriodPresetRanges();
        $baseUrl = $this->context->link->getAdminLink('AdminTecMatomoStats');

        foreach ($this->getPeriodPresetDefinitions() as $preset => $label) {
            if (!isset($ranges[$preset])) {
                continue;
            }

            $links[] = [
                'label' => $label,
                'active' => $preset === $activePreset,
                'url' => $baseUrl . '&' . http_build_query([
                    'period_preset' => $preset,
                    'date_from' => $ranges[$preset]['date_from'],
                    'date_to' => $ranges[$preset]['date_to'],
                ], '', '&'),
            ];
        }

        return $links;
    }

    protected function getPeriodPresetDefinitions()
    {
        return [
            'month' => $this->module->l('Month'),
            'year' => $this->module->l('Year'),
            'previous_day' => $this->module->l('Day -1'),
            'previous_month' => $this->module->l('Month -1'),
            'previous_year' => $this->module->l('Year -1'),
        ];
    }

    protected function buildPeriodPresetRanges()
    {
        $today = new DateTime('today');
        $yesterday = new DateTime('yesterday');
        $currentMonthStart = new DateTime('first day of this month');
        $currentYearStart = new DateTime('first day of january this year');
        $previousMonthStart = new DateTime('first day of previous month');
        $previousMonthEnd = new DateTime('last day of previous month');
        $previousYearStart = new DateTime('first day of january previous year');
        $previousYearEnd = new DateTime('last day of december previous year');

        return [
            'month' => [
                'date_from' => $currentMonthStart->format('Y-m-d'),
                'date_to' => $today->format('Y-m-d'),
            ],
            'year' => [
                'date_from' => $currentYearStart->format('Y-m-d'),
                'date_to' => $today->format('Y-m-d'),
            ],
            'previous_day' => [
                'date_from' => $yesterday->format('Y-m-d'),
                'date_to' => $yesterday->format('Y-m-d'),
            ],
            'previous_month' => [
                'date_from' => $previousMonthStart->format('Y-m-d'),
                'date_to' => $previousMonthEnd->format('Y-m-d'),
            ],
            'previous_year' => [
                'date_from' => $previousYearStart->format('Y-m-d'),
                'date_to' => $previousYearEnd->format('Y-m-d'),
            ],
        ];
    }

    protected function normalizeDate($date)
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
}
