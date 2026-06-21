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
  var purposeId = String(window.matomoIubendaPurpose || 4);
  var lastGranted = null;

  function getPreferences() {
    if (
      !window._iub ||
      !window._iub.cs ||
      !window._iub.cs.api ||
      typeof window._iub.cs.api.getPreferences !== 'function'
    ) {
      return null;
    }

    try {
      return window._iub.cs.api.getPreferences();
    } catch (e) {
      return null;
    }
  }

  function hasAnalyticsConsent() {
    var preferences = getPreferences();

    if (!preferences || typeof preferences !== 'object' || !preferences.purposes) {
      return false;
    }

    return preferences.purposes[purposeId] === true || preferences.purposes[purposeId] === 1;
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
    'iubenda_consent_given',
    'iubenda_consent_rejected',
    'iubenda_preference_update',
    'iubenda_preferences_updated'
  ].forEach(function (eventName) {
    window.addEventListener(eventName, delayedSync, false);
  });

  window.setTimeout(delayedSync, 2000);
})();
