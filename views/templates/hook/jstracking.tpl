{*
**
*  2009-2025 Tecnoacquisti.com
*
*  For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
*  @author    Tecnoacquisti.com <admin@arteinformatica.eu>
*  @copyright 2009-2025 Tecnoacquisti.com
*  @version   1.0
*  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
*
*}

<!-- Start Matomo PrestaShop Module by https://www.tecnoacquisti.com -->
{literal}
<script type="text/javascript">
    var _paq = window._paq = window._paq || [];
    {/literal}

    {* ===== PRIVACY ===== *}
    {if $matomo_lg_enable == 1}
    // LG Integration: We always start with requireCookieConsent
    _paq.push(['requireCookieConsent']);
    {else}
    {if $matomo_privacy_mode == 'cookieless'}
    _paq.push(['disableCookies']);
    {elseif $matomo_privacy_mode == 'consent'}
    _paq.push(['requireCookieConsent']);
    {/if}
    {/if}



    {if $matomo_securecookie == 1}
    _paq.push(['setSecureCookie', true]);
    {/if}

    {* ===== SUBDOMAINS ===== *}
    {if $matomo_subdomains == 1 && $matomo_basedomain|strlen > 1}
    {* Imposta il cookie a livello di base domain, es. ".example.it" *}
    _paq.push(['setCookieDomain', '{$matomo_basedomain|escape:"javascript":"UTF-8"}']);
    {/if}

    {* ===== CROSS-DOMAIN LINKING ===== *}
    {if $matomo_crossdomain == 1}
    {literal}
    (function() {
        var domains = [];
        {/literal}
        {if $matomo_crossdomain_list|@count > 0}
        {foreach from=$matomo_crossdomain_list item=__d}
        {if $__d|strlen}
        {literal}  domains.push('{/literal}{$__d|escape:"javascript":"UTF-8"}{literal}');{/literal}
        {/if}
        {/foreach}
        {/if}
        {*
          Se vuoi includere anche il wildcard del basedomain dentro setDomains quando cross-domain è ON:
          (utile quando vuoi trattare i sottodomini come interni e avere linking anche lì)
        *}
        {if $matomo_subdomains == 1 && $matomo_basedomain|strlen > 1}
        {capture assign=__base_no_dot}{$matomo_basedomain|substr:1}{/capture}
        {literal}  domains.push('*.{/literal}{$__base_no_dot|escape:"javascript":"UTF-8"}{literal}');{/literal}
        {/if}
        {literal}
        if (domains.length > 0) {
            _paq.push(['setDomains', domains]);
        }
        _paq.push(['enableCrossDomainLinking']);
    })();
    {/literal}
    {/if}

    {* ===== DOCUMENT TITLE ===== *}
    {if $matomo_title_domain == 1}
    _paq.push(['setDocumentTitle', document.domain + '/' + document.title]);
    {/if}

    {* ===== CAMPAIGN KEYS ===== *}
    {if $matomo_campaign_namekey|strlen}
    _paq.push(['setCampaignNameKey', '{$matomo_campaign_namekey|escape:"javascript":"UTF-8"}']);
    {/if}
    {if $matomo_campaign_termkey|strlen}
    _paq.push(['setCampaignKeywordKey', '{$matomo_campaign_termkey|escape:"javascript":"UTF-8"}']);
    {/if}

    {* ===== HEARTBEAT ===== *}
    {if $matomo_heartbeat_enable == 1}
    _paq.push(['enableHeartBeatTimer', {$matomo_heartbeat_sec|intval}]);
    {/if}

    {* ===== USER ID ===== *}
    {if $matomo_userid == 1 && $motomo_customer > 0}
    _paq.push(['setUserId', '{$motomo_customer|escape:"javascript":"UTF-8"}']);
    {/if}

    {* ===== PRODUCT VIEW (prima del trackPageView) ===== *}
    {if $matomo_is_product == 1 && $matomo_ecommerce == 1 && $mtm_product}
    _paq.push(['setEcommerceView',
      '{$mtm_product.sku|escape:"javascript":"UTF-8"}',
      '{$mtm_product.name|escape:"javascript":"UTF-8"}',
      [{foreach $mtm_product.cats as $c}'{$c|escape:"javascript":"UTF-8"}'{if !$c@last},{/if}{/foreach}],
      {$mtm_product.price|floatval}
    ]);
    {/if}

    {literal}

    /* ===== BASE TRACKING ===== */
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);

    (function() {
        var u = "{/literal}{$matomo_url|escape:"javascript":"UTF-8"}{literal}";
        _paq.push(['setTrackerUrl', u + 'matomo.php']);
        _paq.push(['setSiteId', '{/literal}{$matomo_id|escape:"javascript":"UTF-8"}{literal}']);
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
    })();
</script>
{/literal}

{* ===== NOSCRIPT ===== *}
{if $matomo_js == 1}
    <noscript>
        <p><img src="{$matomo_url|escape:'html':'UTF-8'}matomo.php?idsite={$matomo_id|escape:'html':'UTF-8'}&amp;rec=1" style="border:0;" alt="" /></p>
    </noscript>
{/if}
<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->


