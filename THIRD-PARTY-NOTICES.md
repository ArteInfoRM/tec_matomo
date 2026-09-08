# Third-party notices

## Included components

No third-party library, SDK, or vendored dependency is included in this module
release. The JavaScript consent bridges are module source code and do not embed
the consent-manager scripts they integrate with.

## Runtime dependencies and services

PrestaShop, Smarty, the PHP runtime, and the PHP cURL extension are supplied by
the merchant's PrestaShop installation and are not redistributed by this
package.

The module sends tracking requests and optional back-office reporting requests
to the Matomo instance configured by the merchant. Matomo is an external
analytics service, is not bundled in this package, and its version, hosting,
license, and retention settings are selected by the merchant.

Optional integrations with LG Cookies Law, Art Cookie Choices Pro, iubenda,
Cookiebot, and CookieYes rely on the merchant-installed consent manager. No
consent-manager SDK or script is redistributed by this module.
