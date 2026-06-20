{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
*}

<div class="panel tec-matomo-stats-page">
  <div class="panel-heading">
    <i class="icon icon-bar-chart"></i>
    {l s='Matomo Analytics' mod='tec_matomo'}
    <span class="panel-heading-action">
      <a class="btn btn-xs btn-default tec-matomo-stats-config-link" href="{$mtm_stats_config_url|escape:'html':'UTF-8'}">
        {l s='Configure' mod='tec_matomo'}
      </a>
    </span>
  </div>

  <div class="tec-matomo-stats-toolbar">
    {if $mtm_stats_period_presets|count}
      <div class="btn-group tec-matomo-stats-presets">
        {foreach from=$mtm_stats_period_presets item=preset}
          <a class="btn btn-default{if $preset.active} active{/if}" href="{$preset.url|escape:'html':'UTF-8'}">
            {$preset.label|escape:'html':'UTF-8'}
          </a>
        {/foreach}
      </div>
    {/if}

    <form method="get" action="{$mtm_stats_form_action|escape:'html':'UTF-8'}" class="form-inline tec-matomo-stats-calendar">
      <input type="hidden" name="controller" value="AdminTecMatomoStats">
      <input type="hidden" name="token" value="{$mtm_stats_token|escape:'html':'UTF-8'}">
      <input type="hidden" name="period_preset" value="">
      <div class="form-group">
        <label for="mtm_stats_date_from">{l s='From' mod='tec_matomo'}</label>
        <input type="date" id="mtm_stats_date_from" name="date_from" class="form-control" value="{$mtm_stats_date_from|escape:'html':'UTF-8'}">
      </div>
      <div class="form-group">
        <label for="mtm_stats_date_to">{l s='To' mod='tec_matomo'}</label>
        <input type="date" id="mtm_stats_date_to" name="date_to" class="form-control" value="{$mtm_stats_date_to|escape:'html':'UTF-8'}">
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="icon-search"></i> {l s='Apply' mod='tec_matomo'}
      </button>
    </form>
  </div>

  <p class="text-muted tec-matomo-stats-period">
    {l s='Selected period:' mod='tec_matomo'} {$mtm_stats_period_label|escape:'html':'UTF-8'}
  </p>

  <form method="get" action="{$mtm_stats_form_action|escape:'html':'UTF-8'}" class="form-inline tec-matomo-stats-export">
    <input type="hidden" name="controller" value="AdminTecMatomoStats">
    <input type="hidden" name="token" value="{$mtm_stats_token|escape:'html':'UTF-8'}">
    <input type="hidden" name="date_from" value="{$mtm_stats_date_from|escape:'html':'UTF-8'}">
    <input type="hidden" name="date_to" value="{$mtm_stats_date_to|escape:'html':'UTF-8'}">
    <input type="hidden" name="period_preset" value="{$mtm_stats_period_preset|escape:'html':'UTF-8'}">
    <div class="form-group">
      <label for="mtm_stats_export_format">{l s='Export format' mod='tec_matomo'}</label>
      <select name="export_format" id="mtm_stats_export_format" class="form-control">
        {foreach from=$mtm_stats_export_formats key=format item=label}
          <option value="{$format|escape:'html':'UTF-8'}"{if $mtm_stats_export_format == $format} selected{/if}>{$label|escape:'html':'UTF-8'}</option>
        {/foreach}
      </select>
    </div>
    <button type="submit" name="export_matomo_data" class="btn btn-default tec-matomo-stats-export-button">
      <i class="icon-download"></i> {l s='Export data' mod='tec_matomo'}
    </button>
  </form>
</div>

{if $mtm_stats_is_connected}
  {if $mtm_stats_api_error}
    <div class="alert alert-warning">
      {$mtm_stats_api_error|escape:'html':'UTF-8'}
    </div>
  {else}
    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='Ecommerce performance' mod='tec_matomo'}</div>
      <div class="row tec-matomo-stats-kpis">
        <div class="col-md-3"><strong>{l s='Revenue' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</span></div>
        <div class="col-md-3"><strong>{l s='Orders' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.orders|default:0|intval}</span></div>
        <div class="col-md-3"><strong>{l s='Conversion rate' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.conversion_rate|default:'0.00%'|escape:'html':'UTF-8'}</span></div>
        <div class="col-md-3"><strong>{l s='Average order value' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.average_order_value|default:'0.00'|escape:'html':'UTF-8'}</span></div>
      </div>
      <div class="row tec-matomo-stats-kpis">
        <div class="col-md-3"><strong>{l s='Visits' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.visits|default:0|intval}</span></div>
        <div class="col-md-3"><strong>{l s='Unique visitors' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.unique_visitors|default:0|intval}</span></div>
        <div class="col-md-3"><strong>{l s='Actions' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.actions|default:0|intval}</span></div>
        <div class="col-md-3"><strong>{l s='Bounce rate' mod='tec_matomo'}</strong><span>{$mtm_stats_site_metrics.bounce_rate|default:'0%'|escape:'html':'UTF-8'}</span></div>
      </div>
    </div>

    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='Revenue by channel' mod='tec_matomo'}</div>
      {if $mtm_stats_channel_rows|count}
        <div class="table-responsive">
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
              {foreach from=$mtm_stats_channel_rows item=row}
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
        <p class="text-muted">{l s='No channel revenue data is available for the selected period.' mod='tec_matomo'}</p>
      {/if}
    </div>

    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='AI Assistant traffic' mod='tec_matomo'}</div>
      {if $mtm_stats_ai_assistant_metrics.available}
        <div class="row tec-matomo-stats-kpis">
          <div class="col-md-3"><strong>{l s='Channel' mod='tec_matomo'}</strong><span>{$mtm_stats_ai_assistant_metrics.label|escape:'html':'UTF-8'}</span></div>
          <div class="col-md-3"><strong>{l s='Visits' mod='tec_matomo'}</strong><span>{$mtm_stats_ai_assistant_metrics.visits|default:0|intval}</span></div>
          <div class="col-md-3"><strong>{l s='Orders' mod='tec_matomo'}</strong><span>{$mtm_stats_ai_assistant_metrics.orders|default:0|intval}</span></div>
          <div class="col-md-3"><strong>{l s='Revenue' mod='tec_matomo'}</strong><span>{$mtm_stats_ai_assistant_metrics.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</span></div>
        </div>
      {else}
        <p class="text-muted">{l s='No AI Assistant traffic is available for the selected period.' mod='tec_matomo'}</p>
      {/if}
    </div>

    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='Top countries by revenue' mod='tec_matomo'}</div>
      {if $mtm_stats_country_rows|count}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{l s='Country' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Visits' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Orders' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Revenue' mod='tec_matomo'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$mtm_stats_country_rows item=row}
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
        <p class="text-muted">{l s='No country revenue data is available for the selected period.' mod='tec_matomo'}</p>
      {/if}
    </div>

    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='Top products by revenue' mod='tec_matomo'}</div>
      {if $mtm_stats_product_rows|count}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{l s='Product' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Items' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Orders' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Revenue' mod='tec_matomo'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$mtm_stats_product_rows item=row}
                <tr>
                  <td>{$row.label|escape:'html':'UTF-8'}</td>
                  <td class="text-right">{$row.items|default:0|intval}</td>
                  <td class="text-right">{$row.orders|default:0|intval}</td>
                  <td class="text-right">{$row.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {else}
        <p class="text-muted">{l s='No product revenue data is available for the selected period.' mod='tec_matomo'}</p>
      {/if}
    </div>

    <div class="panel tec-matomo-stats-page">
      <div class="panel-heading">{l s='Top categories by revenue' mod='tec_matomo'}</div>
      {if $mtm_stats_category_rows|count}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{l s='Category' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Items' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Orders' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Revenue' mod='tec_matomo'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$mtm_stats_category_rows item=row}
                <tr>
                  <td>{$row.label|escape:'html':'UTF-8'}</td>
                  <td class="text-right">{$row.items|default:0|intval}</td>
                  <td class="text-right">{$row.orders|default:0|intval}</td>
                  <td class="text-right">{$row.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {else}
        <p class="text-muted">{l s='No category revenue data is available for the selected period.' mod='tec_matomo'}</p>
      {/if}
    </div>
  {/if}
{else}
  <div class="alert alert-info">
    {l s='Matomo API is not configured. Enter Matomo URL, SiteID and API token in the module configuration.' mod='tec_matomo'}
  </div>
{/if}
