{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
*}

<div class="panel tec-matomo-product-widget">
  <div class="panel-heading">
    <i class="icon icon-bar-chart"></i>
    {l s='Matomo Analytics' mod='tec_matomo'}
    {if $mtm_product_period_label}
      <small class="text-muted">{$mtm_product_period_label|escape:'html':'UTF-8'}</small>
    {/if}
  </div>

  {if $mtm_product_is_connected}
    {if $mtm_product_api_error}
      <p class="alert alert-warning tec-matomo-dashboard-notice">
        {$mtm_product_api_error|escape:'html':'UTF-8'}
      </p>
    {else}
      <div class="row tec-matomo-product-kpis">
        <div class="col-md-3">
          <strong>{l s='Revenue' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</span>
        </div>
        <div class="col-md-3">
          <strong>{l s='Orders' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.orders|default:0|intval}</span>
        </div>
        <div class="col-md-3">
          <strong>{l s='Items purchased' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.items_purchased|default:0|intval}</span>
        </div>
        <div class="col-md-3">
          <strong>{l s='Average item price' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.average_price|default:'0.00'|escape:'html':'UTF-8'}</span>
        </div>
      </div>
      <div class="row tec-matomo-product-kpis">
        <div class="col-md-3">
          <strong>{l s='Conversion rate' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.conversion_rate|default:'0.00%'|escape:'html':'UTF-8'}</span>
        </div>
        <div class="col-md-3">
          <strong>{l s='Matched SKU rows' mod='tec_matomo'}</strong>
          <span>{$mtm_product_metrics.matched_rows|default:0|intval}</span>
        </div>
      </div>

      <h4>{l s='Matched SKUs' mod='tec_matomo'}</h4>
      {if $mtm_product_rows|count}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{l s='SKU' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Orders' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Items purchased' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Conversion rate' mod='tec_matomo'}</th>
                <th class="text-right">{l s='Revenue' mod='tec_matomo'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$mtm_product_rows item=row}
                <tr>
                  <td>{$row.sku|escape:'html':'UTF-8'}</td>
                  <td class="text-right">{$row.orders|default:0|intval}</td>
                  <td class="text-right">{$row.items_purchased|default:0|intval}</td>
                  <td class="text-right">{$row.conversion_rate|default:'0.00%'|escape:'html':'UTF-8'}</td>
                  <td class="text-right">{$row.revenue_formatted|default:'0.00'|escape:'html':'UTF-8'}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {else}
        <p class="text-muted">
          {l s='No Matomo ecommerce data is available for this product in the selected period.' mod='tec_matomo'}
        </p>
      {/if}

      <h4>{l s='Tracked SKU aliases' mod='tec_matomo'}</h4>
      {if $mtm_product_aliases|count}
        <p class="text-muted tec-matomo-product-aliases">
          {foreach from=$mtm_product_aliases item=alias name=aliases}
            <code>{$alias|escape:'html':'UTF-8'}</code>{if !$smarty.foreach.aliases.last} {/if}
          {/foreach}
        </p>
      {/if}
    {/if}
  {else}
    <p class="text-muted text-center">
      {l s='Matomo API is not configured. Enter Matomo URL, SiteID and API token in the module configuration.' mod='tec_matomo'}
    </p>
  {/if}
</div>
