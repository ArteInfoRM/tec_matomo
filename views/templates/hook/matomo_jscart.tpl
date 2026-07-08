{*
**
*  2009-2026 Tecnoacquisti.com
*
*  For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
*  @author    Tecnoacquisti.com <admin@arteinformatica.eu>
*  @copyright 2009-2026 Tecnoacquisti.com
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
      // Remove currency symbols, spaces and thousands separators.
      var n = v.replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.');
      var f = parseFloat(n);
      return isNaN(f) ? 0 : f;
    }
    return 0;
  }

  function makeSku(p) {
    var a = p.id_product_attribute || p.id_product_attribute === 0 ? p.id_product_attribute : 0;
    return String(p.id_product) + 'v' + String(a || 0);
  }

  function firstNumber(values) {
    for (var i = 0; i < values.length; i++) {
      if (values[i] == null) continue;
      var n = toNumber(values[i]);
      if (n > 0) return n;
    }

    return 0;
  }

  function unitPrice(p, qty) {
    var unit = firstNumber([
      p.unit_price_tax_incl,
      p.unit_price_tax_excl,
      p.price_with_reduction,
      p.price_with_reduction_without_tax,
      p.price_without_reduction,
      p.price_amount
    ]);
    if (unit > 0) return unit;

    var lineTotal = firstNumber([
      p.total_wt,
      p.total,
      p.total_price_tax_incl,
      p.total_price_tax_excl
    ]);
    if (lineTotal > 0 && qty > 0) {
      return lineTotal / qty;
    }

    return toNumber(p.price);
  }

  function categoryArray(p) {
    // Try common cart product category fields in order.
    if (p.category) return [String(p.category)];
    if (p.category_name) return [String(p.category_name)];
    if (Array.isArray(p.categories) && p.categories.length) return [String(p.categories[0])];
    return [];
  }

  function pushCartState(cart) {
    if (!cart || !Array.isArray(cart.products)) return;

    _paq.push(['clearEcommerceCart']);

    for (var i = 0; i < cart.products.length; i++) {
      var pr = cart.products[i];
      var qty = pr.quantity != null ? pr.quantity :
                (pr.cart_quantity != null ? pr.cart_quantity : 1);
      qty = parseInt(qty, 10) || 1;
      var price = unitPrice(pr, qty);

      _paq.push([
        'addEcommerceItem',
        makeSku(pr),
        pr.name || null,
        categoryArray(pr),
        Number(price) || 0,
        qty
      ]);
    }

    // Cart total.
    var total =
      cart?.totals?.total?.amount != null ? cart.totals.total.amount :
      (cart?.total != null ? toNumber(cart.total) : 0);

    _paq.push(['trackEcommerceCartUpdate', Number(total) || 0]);
  }

  function onUpdateCart(event) {
    try {
      if (!event) return;
      // PrestaShop emits updateCart with reason and/or resp.
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
	      // A single handler tracks add, remove and quantity changes.
	      prestashop.on('updateCart', onUpdateCart);
	    }
	  });
})();
</script>

{/literal}

<!-- End Matomo PrestaShop Module by https://www.tecnoacquisti.com -->
