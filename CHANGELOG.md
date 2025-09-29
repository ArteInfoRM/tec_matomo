# CHANGELOG

All notable changes to this project will be documented in this file.  
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.5] – 2025-09-29
### Added
- Product view tracking: `setEcommerceView` is now pushed **before** `trackPageView` to correctly record product impressions in Matomo.
- Integration with **LG Cookies Law (Línea Gráfica)**: when enabled, the module listens to the consent status for the *Analytics* purpose and enables/disables Matomo cookies accordingly. This setting **overrides Privacy Mode**.
- **Heartbeat timer** option to improve the accuracy of “time on page”.

### Changed
- Prices sent to Matomo are now rounded to **two decimals** for cleaner reporting.
- Product data: categories are passed as an **array** (up to 5 levels); SKU now includes the combination suffix (`idv{id_attribute}`).
- Clearer handling of Privacy Mode options (None, Cookieless, Require cookie consent) and their interaction with LG integration.

### Fixed
- Compatibility with **PrestaShop 9** asset management (loading with `defer` where required).
- Prevented duplicate pageviews on product pages by controlling the order of `trackPageView`.
- Improved robustness of controller detection on product pages.

### Translations / UX
- Full Italian translations for configuration and information pages.
- Clearer field descriptions (Matomo URL and SiteID, API Token, ecommerce tracking, UserID, noscript, secure cookies, subdomains, cross-domain, campaign parameters, Heartbeat).
