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
        $(document).ready(function () {
            prestashop.on(
                'updateCart',
                function (event) {
                    if (event && event.reason && typeof event.resp !== 'undefined' && !event.resp.hasError) {
                        if (event.reason.linkAction =="add-to-cart" && event.reason.cart) {
                            for (var {id_product: i, id_product_attribute: a, name: n, quantity: q, category: c, price: p} of event.reason.cart.products) {
                                p = p.replace(',','.');
                                p = p.replace('€','');
                                _paq.push(['addEcommerceItem',
                                    "" + i + "v" + a + "", // (Required) productSKU
                                    "" + n + "", // (Optional) productName
                                    "" + c + "", // (Optional) productCategory
                                    parseFloat(p), // (Recommended) price
                                    q // (Optional, defaults to 1) quantity
                                ]);
                            }
                            // An addEcommerceItem push should be generated for each cart item, even the products not updated by the current "Add to cart" click.
                            // Pass the Cart's Total Value as a numeric parameter
                            _paq.push(['trackEcommerceCartUpdate', event.reason.cart.totals.total.amount]);
                            //console.log(event.reason.cart.products);
                        }
                    }
                }
            );
        });

    </script>
{/literal}
<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->
