/**
 *  2009-2025 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2025 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.0
 */

/* Matomo <-> LG Cookies Law bridge */
(function () {
  // ID scopo Analitici passato da PHP (fallback 3)
  var PURPOSE_KEY = 'lgcookieslaw_purpose_' + (window.matomoLgPurpose || 3);
  var lastGranted = null;

  function hasAnalyticsConsent() {
    var v = window.lgcookieslaw_cookie_values;
    return (typeof v === 'object' && v && v[PURPOSE_KEY] === true);
  }

  function syncMatomo() {
    var granted = !!hasAnalyticsConsent();
    if (lastGranted === granted) return;
    lastGranted = granted;
    if (typeof window._paq !== 'undefined') {
      window._paq.push([ granted ? 'rememberCookieConsentGiven' : 'forgetCookieConsentGiven' ]);
      // console.debug('[Matomo LG bridge] analytics =', granted ? 'GRANTED' : 'DENIED');
    }
  }

  // 1) Sync iniziale: aspetta che LG popoli lgcookieslaw_cookie_values
  var tries = 0, maxTries = 40; // ~20s
  var poll = setInterval(function () {
    tries++;
    if (typeof window.lgcookieslaw_cookie_values === 'object' || tries >= maxTries) {
      clearInterval(poll);
      syncMatomo();
    }
  }, 500);

  // 2) Sync a ogni cambio consenso (copriamo i principali eventi)
  ['lgcookieslaw_onaccept','lgcookieslaw_onchange','lgcookieslaw_onrevoke',
    'lgConsentGiven','lgConsentUpdated','lgConsentRevoked'
  ].forEach(function (evt) {
    window.addEventListener(evt, syncMatomo, false);
  });
})();
