{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
*}

<section class="panel tec-matomo-dashboard-widget">
  <header class="panel-heading">
    <i class="icon icon-bar-chart"></i>
    {l s='Matomo Analytics' mod='tec_matomo'}
    {if $mtm_period_label}
      <small class="text-muted">{$mtm_period_label|escape:'html':'UTF-8'}</small>
    {/if}
    <span class="panel-heading-action">
      <a class="btn btn-xs btn-default" href="{$mtm_dashboard_url|escape:'html':'UTF-8'}" style="white-space:nowrap;">
        {l s='Configure' mod='tec_matomo'}
      </a>
    </span>
  </header>

  {if $mtm_is_connected}
    {if $mtm_api_error}
      <p class="alert alert-warning tec-matomo-dashboard-notice">
        {$mtm_api_error|escape:'html':'UTF-8'}
      </p>
    {else}
      <h4 class="tec-matomo-widget-title">{l s='Ecommerce performance' mod='tec_matomo'}</h4>
      <div class="row tec-matomo-widget-kpis">
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</span>
            <span class="tec-matomo-widget-label">{l s='Revenue' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.orders|default:0|intval}</span>
            <span class="tec-matomo-widget-label">{l s='Orders' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.conversion_rate|default:'0.00%'|escape:'html':'UTF-8'}</span>
            <span class="tec-matomo-widget-label">{l s='Conversion rate' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.average_order_value|default:'0.00'|escape:'html':'UTF-8'}</span>
            <span class="tec-matomo-widget-label">{l s='Average order value' mod='tec_matomo'}</span>
          </div>
        </div>
      </div>

      <h4 class="tec-matomo-widget-title">{l s='Visits' mod='tec_matomo'}</h4>
      <div class="row tec-matomo-widget-kpis">
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.visits|default:0|intval}</span>
            <span class="tec-matomo-widget-label">{l s='Visits' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.unique_visitors|default:0|intval}</span>
            <span class="tec-matomo-widget-label">{l s='Unique visitors' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.actions|default:0|intval}</span>
            <span class="tec-matomo-widget-label">{l s='Actions' mod='tec_matomo'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-matomo-widget-kpi">
            <span class="tec-matomo-widget-number">{$mtm_site_metrics.bounce_rate|default:'0%'|escape:'html':'UTF-8'}</span>
            <span class="tec-matomo-widget-label">{l s='Bounce rate' mod='tec_matomo'}</span>
          </div>
        </div>
      </div>

      <h4 class="tec-matomo-widget-title">{l s='Revenue by channel' mod='tec_matomo'}</h4>
      {if $mtm_channel_rows|count}
        <div class="table-responsive tec-matomo-channel-table">
          <table class="table">
            <thead>
              <tr>
                <th>{l s='Channel' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Visits' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Orders' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Revenue' mod='tec_matomo'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$mtm_channel_rows item=row}
                <tr>
                  <td>{$row.label|escape:'html':'UTF-8'}</td>
                  <td class="text-right">{$row.visits|default:0|intval}</td>
                  <td class="text-right">{$row.orders|default:0|intval}</td>
                  <td class="text-right">{$row.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {else}
        <p class="text-muted tec-matomo-empty-channel-data">
          {l s='No channel revenue data is available for the selected period.' mod='tec_matomo'}
        </p>
      {/if}
    {/if}
  {else}
    <p class="text-muted text-center">
      {l s='Matomo API is not configured. Enter Matomo URL, SiteID and API token in the module configuration.' mod='tec_matomo'}
    </p>
  {/if}
</section>
