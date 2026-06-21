/**
 *  2009-2026 Matomo Analytics PrestaShop Module
 *
 *  For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 *  @author    Tecnoacquisti.com <shop@tecnoacquisti.com>
 *  @copyright 2009-2026 Tecnoacquisti.com
 *  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
 *  @version   1.0
 */

(function () {
  var lastGranted = null;

  function hasAnalyticsConsent() {
    return !!(
      window.Cookiebot &&
      window.Cookiebot.consent &&
      window.Cookiebot.consent.statistics
    );
  }

  function syncMatomo() {
    var granted = hasAnalyticsConsent();

    if (lastGranted === granted) {
      return;
    }

    lastGranted = granted;

    if (typeof window._paq !== 'undefined') {
      window._paq.push([granted ? 'rememberCookieConsentGiven' : 'forgetCookieConsentGiven']);
    }
  }

  function delayedSync() {
    window.setTimeout(syncMatomo, 0);
    window.setTimeout(syncMatomo, 250);
    window.setTimeout(syncMatomo, 1000);
  }

  delayedSync();

  [
    'CookiebotOnAccept',
    'CookiebotOnDecline',
    'CookiebotOnLoad',
    'CookiebotOnConsentReady'
  ].forEach(function (eventName) {
    window.addEventListener(eventName, delayedSync, false);
  });
})();
