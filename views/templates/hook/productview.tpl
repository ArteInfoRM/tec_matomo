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
{if $product.id > 0}
{literal}
<script type="text/javascript">
    // Push Product View Data to Matomo - Populate parameters dynamically
    _paq.push(['setEcommerceView',
    "{/literal}{$product_sku|escape:"javascript":'UTF-8'}{literal}", // (Required) productSKU
    "{/literal}{$product_name|escape:"javascript":'UTF-8'}{literal}", // (Optional) productName
    "{/literal}{$product_category|escape:"javascript":'UTF-8'}{literal}", // (Optional) categoryName
     {/literal}{$product.price_amount|escape:"javascript":'UTF-8'}{literal} // (Optional) price
    ]);
    // You must also call trackPageView when tracking a product view
    _paq.push(['trackPageView']);
</script>
{/literal}
{/if}
<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->
