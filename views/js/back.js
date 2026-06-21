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
  function getFormGroup(element) {
    if (!element) {
      return null;
    }

    if (typeof element.closest === 'function') {
      return element.closest('.form-group');
    }

    while (element && element.parentNode) {
      if ((' ' + element.className + ' ').indexOf(' form-group ') !== -1) {
        return element;
      }

      element = element.parentNode;
    }

    return null;
  }

  function updateLgPurposeVisibility() {
    var managerSelect = document.querySelector('[name="TEC_MATOMO_CONSENT_MANAGER"]');
    var purposeInput = document.querySelector('[name="TEC_MATOMO_LG_PURPOSE"]');
    var purposeRow = document.querySelector('.tec-matomo-lg-purpose-row') || getFormGroup(purposeInput);
    var iubendaPurposeInput = document.querySelector('[name="TEC_MATOMO_IUBENDA_PURPOSE"]');
    var iubendaPurposeRow = document.querySelector('.tec-matomo-iubenda-purpose-row') || getFormGroup(iubendaPurposeInput);

    if (!managerSelect) {
      return;
    }

    if (purposeRow) {
      purposeRow.style.display = managerSelect.value === 'lg' ? '' : 'none';
    }

    if (iubendaPurposeRow) {
      iubendaPurposeRow.style.display = managerSelect.value === 'iubenda' ? '' : 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var managerSelect = document.querySelector('[name="TEC_MATOMO_CONSENT_MANAGER"]');

    updateLgPurposeVisibility();

    if (managerSelect) {
      managerSelect.addEventListener('change', updateLgPurposeVisibility, false);
    }
  }, false);
})();
