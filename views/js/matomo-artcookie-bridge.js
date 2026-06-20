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
  var consentCookieName = 'displayCookieConsent';
  var preferencesCookieName = 'displayCookieConsentPreferences';
  var lastGranted = null;

  function getCookieValue(name) {
    var parts = document.cookie ? document.cookie.split(';') : [];

    for (var i = 0; i < parts.length; i++) {
      var part = parts[i].replace(/^\s+|\s+$/g, '');
      var separator = part.indexOf('=');

      if (separator < 0) {
        continue;
      }

      if (part.substring(0, separator) === name) {
        return decodeURIComponent(part.substring(separator + 1));
      }
    }

    return null;
  }

  function getStoredPreferences() {
    var raw = getCookieValue(preferencesCookieName);

    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function hasAnalyticsConsent() {
    var preferences = getStoredPreferences();
    var legacyConsent;

    if (preferences) {
      return !!preferences.analytics || !!preferences.performance;
    }

    legacyConsent = getCookieValue(consentCookieName);

    if (legacyConsent === 'y') {
      return true;
    }

    return false;
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
  }

  delayedSync();

  document.addEventListener('click', function (event) {
    var target = event.target;

    if (!target) {
      return;
    }

    if (
      target.id === 'InformativaAccetto' ||
      target.id === 'InformativaReject' ||
      target.id === 'InformativaSavePreferences' ||
      target.id === 'close_cookie_block' ||
      (
        typeof target.closest === 'function' &&
        target.closest('#InformativaAccetto, #InformativaReject, #InformativaSavePreferences, #close_cookie_block')
      )
    ) {
      delayedSync();
    }
  }, false);
})();
