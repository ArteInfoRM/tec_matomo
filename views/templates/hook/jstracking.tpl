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
<script>
    var _paq = window._paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    {/literal}{if $matomo_userid == 1 && $motomo_customer > 0}{literal}_paq.push(['setUserId', '{/literal}{$motomo_customer|escape:"javascript":'UTF-8'}{literal}}{literal}']);{/literal}{/if}
    {literal}
    {/literal}{if $matomo_dntrack == 1}{literal}_paq.push(["setDoNotTrack", true]);{/literal}{/if}
    {literal}
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
        var u="{/literal}{$matomo_url|escape:"javascript":'UTF-8'}{literal}";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['setSiteId', '{/literal}{$matomo_id|escape:"javascript":'UTF-8'}{literal}']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
    })();
</script>
{/literal}
{if $matomo_js == 1}
<noscript><p><img src="{$matomo_url|escape:'html':'UTF-8'}/matomo.php?idsite={$matomo_id|escape:'html':'UTF-8'}&amp;rec=1" style="border:0;" alt="" /></p></noscript>
{/if}
<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->

