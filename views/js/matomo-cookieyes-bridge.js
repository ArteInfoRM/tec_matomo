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

  function isGranted(value) {
    return value === true || value === 1 || value === 'yes' || value === 'accepted';
  }

  function hasCategory(categoryList, categoryName) {
    return (
      categoryList &&
      typeof categoryList.indexOf === 'function' &&
      categoryList.indexOf(categoryName) !== -1
    );
  }

  function hasAnalyticsInPayload(consentData) {
    if (!consentData || typeof consentData !== 'object') {
      return false;
    }

    if (hasCategory(consentData.accepted, 'analytics')) {
      return true;
    }

    if (hasCategory(consentData.accepted, 'performance')) {
      return true;
    }

    if (Object.prototype.hasOwnProperty.call(consentData, 'analytics')) {
      return isGranted(consentData.analytics);
    }

    if (
      consentData.categories &&
      Object.prototype.hasOwnProperty.call(consentData.categories, 'analytics')
    ) {
      return isGranted(consentData.categories.analytics);
    }

    if (
      consentData.categories &&
      Object.prototype.hasOwnProperty.call(consentData.categories, 'performance')
    ) {
      return isGranted(consentData.categories.performance);
    }

    if (
      consentData.consent &&
      Object.prototype.hasOwnProperty.call(consentData.consent, 'analytics')
    ) {
      return isGranted(consentData.consent.analytics);
    }

    return false;
  }

  function readConsentData() {
    if (typeof window.getCkyConsent !== 'function') {
      return null;
    }

    try {
      return window.getCkyConsent();
    } catch (error) {
      return null;
    }
  }

  function hasAnalyticsConsent() {
    return hasAnalyticsInPayload(readConsentData());
  }

  function syncMatomo(consentData) {
    var granted = consentData ? hasAnalyticsInPayload(consentData) : hasAnalyticsConsent();

    if (lastGranted === granted) {
      return;
    }

    lastGranted = granted;

    if (typeof window._paq !== 'undefined') {
      window._paq.push([granted ? 'rememberCookieConsentGiven' : 'forgetCookieConsentGiven']);
    }
  }

  function delayedSync(event) {
    var consentData = event && event.detail ? event.detail : null;

    window.setTimeout(function () { syncMatomo(consentData); }, 0);
    window.setTimeout(syncMatomo, 250);
    window.setTimeout(syncMatomo, 1000);
  }

  delayedSync();

  [
    'cookieyes_banner_load',
    'cookieyes_consent_update',
    'cookieyes_consent_accept',
    'cookieyes_consent_reject',
    'cky-consent-update',
    'cky-consent-accepted',
    'cky-consent-rejected'
  ].forEach(function (eventName) {
    window.addEventListener(eventName, delayedSync, false);
    document.addEventListener(eventName, delayedSync, false);
  });
})();
