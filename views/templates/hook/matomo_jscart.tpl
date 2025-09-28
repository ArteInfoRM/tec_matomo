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
(function () {
  function toNumber(v) {
    if (typeof v === 'number') return v;
    if (typeof v === 'string') {
      // rimuove simboli valuta, spazi, migliaia, usa il punto come decimale
      var n = v.replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.');
      var f = parseFloat(n);
      return isNaN(f) ? 0 : f;
    }
    return 0;
  }

  function makeSku(p) {
    var a = p.id_product_attribute || p.id_product_attribute === 0 ? p.id_product_attribute : 0;
    return String(p.id_product) + (a ? ('v' + a) : '');
  }

  function categoryArray(p) {
    // prova in ordine: category (string), category_name, categories[0]
    if (p.category) return [String(p.category)];
    if (p.category_name) return [String(p.category_name)];
    if (Array.isArray(p.categories) && p.categories.length) return [String(p.categories[0])];
    return []; // opzionale: puoi tornare null se preferisci
  }

  function pushCartState(cart) {
    if (!cart || !Array.isArray(cart.products)) return;

    for (var i = 0; i < cart.products.length; i++) {
      var pr = cart.products[i];
      var price =
        pr.price_amount != null ? pr.price_amount :
        (pr.price_with_reduction_without_tax != null ? pr.price_with_reduction_without_tax :
        toNumber(pr.price));

      var qty = pr.quantity != null ? pr.quantity :
                (pr.cart_quantity != null ? pr.cart_quantity : 1);

      _paq.push([
        'addEcommerceItem',
        makeSku(pr),                 // SKU (obbligatorio)
        pr.name || null,             // Nome
        categoryArray(pr),           // Categoria (array o [])
        Number(price) || 0,          // Prezzo (numero)
        parseInt(qty, 10) || 1       // Quantità
      ]);
    }

    // Totale carrello (numero)
    var total =
      cart?.totals?.total?.amount != null ? cart.totals.total.amount :
      (cart?.total != null ? toNumber(cart.total) : 0);

    _paq.push(['trackEcommerceCartUpdate', Number(total) || 0]);
  }

  function onUpdateCart(event) {
    try {
      if (!event) return;
      // PS emette updateCart con 'reason' e/o 'resp'
      var cart = (event.reason && event.reason.cart) ? event.reason.cart :
                 (event.resp && event.resp.cart) ? event.resp.cart : null;

      var hasError = !!(event.resp && event.resp.hasError);
      if (cart && !hasError) {
        pushCartState(cart);
      }
    } catch (e) {
      // console.warn('[Matomo] cart update error', e);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    window._paq = window._paq || [];
    if (window.prestashop && typeof prestashop.on === 'function') {
      // Un solo handler che gestisce add/remove/qty change
      prestashop.on('updateCart', onUpdateCart);
    }
  });
})();
</script>

{/literal}

<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->
