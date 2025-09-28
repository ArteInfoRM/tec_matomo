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
{foreach from=$ord_details item=product}
{literal}
<script type="text/javascript">
    // Product Array
    _paq.push(['addEcommerceItem',
    "{/literal}{$product['product_sku']|escape:"javascript":'UTF-8'}{literal}", // (required) SKU: Product unique identifier
    "{/literal}{$product['product_name']|escape:"javascript":'UTF-8'}{literal}", // (optional) Product name
    "{/literal}{$product['product_category']|escape:"javascript":'UTF-8'}{literal}", // (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
    {/literal}{$product['product_price']|escape:"javascript":'UTF-8'}{literal}, // (Recommended) Product Price
    {/literal}{$product['product_quantity']|escape:"javascript":'UTF-8'}{literal} // (Optional - Defaults to 1)
    ]);
</script>
{/literal}
{/foreach}

{literal}
<script type="text/javascript">
// Order Array - Parameters should be generated dynamically
_paq.push(['trackEcommerceOrder',
"{/literal}{$order_id}{literal}", // (Required) orderId
{/literal}{$total_paid}{literal}, // (Required) grandTotal (revenue)
{/literal}{$total_paid_tax_excl}{literal}, // (Optional) subTotal
{/literal}{$total_tax}{literal}, // (optional) tax
{/literal}{$total_shipping}{literal}, // (optional) shipping
{/literal}{$total_discounts}{literal} // (optional) discount
]);
</script>
{/literal}
<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->