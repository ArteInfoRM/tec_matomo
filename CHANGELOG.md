# CHANGELOG

All notable changes to this project will be documented in this file.  
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.9] - 2026-07-08
### Fixed
- Added the missing `documentation/index.php` directory guard required by the PrestaShop validator.

## [1.1.8] - 2026-07-08
### Added
- Product back-office widget now shows product visits, actions, unique visitors, and Matomo report rows in addition to ecommerce revenue and orders.
- Product back-office widget now shows visit-source data for the product page using Matomo referrer reports segmented by product URL.
- English legacy translation file for the new product widget labels.

### Changed
- Product statistics now include a year-to-date fallback when the last 30 days do not contain matching Matomo rows.
- Products without combinations now aggregate the plain product ID and `idv0` Matomo SKU rows, while products with real combinations keep variant SKU rows separated.
- Product statistics can fall back from SKU reports to product-name reports when SKU matching is not enough.

### Fixed
- Product back-office statistics now match Matomo product-view rows and order rows recorded under different no-combination SKU labels.
- Average price, quantity, visits, and conversion metrics now read additional Matomo API field variants for more robust product reporting.

## [1.1.7] - 2026-06-21
### Added
- Consent manager integrations for iubenda, Cookiebot, and CookieYes.
- Configurable iubenda Analytics purpose ID for Matomo cookie consent.
- Dutch, Portuguese, Polish, and Romanian translations.
- README notes about LegalBlink evaluation and the requirement for a stable JavaScript consent API or native script blocking.

## [1.1.6] - 2026-06-20
### Added
- AI Assistant traffic block in the dedicated statistics controller when Matomo exposes the AI Assistant referrer channel.
- README notes for Matomo AI Assistant channel and AI Chatbot server-side tracking setup.
- Dedicated Matomo Analytics admin controller with menu entry and custom date range selector.
- Spanish, German, and French translations.
- Top 10 countries, products, and categories by revenue in the dedicated statistics controller.
- CSV, JSON, and XML export for the dedicated statistics controller dataset.
- Product back-office widget with Matomo ecommerce metrics by tracked SKU.
- Back-office dashboard widget with Matomo ecommerce KPIs, visits, and revenue by channel.
- Optional Art Cookie Choices Pro integration for Matomo cookie consent.

### Changed
- Replaced deprecated tab lookup calls and improved module method resolution in the admin statistics controller.
- Dashboard and statistics channel revenue now use Matomo referrer types instead of detailed referrer rows.
- The dedicated statistics controller calendar now includes quick period presets for month, year, day -1, month -1, and year -1.
- Removed the legacy Matomo ImageGraph preview from the module configuration page.
- Consent manager settings now use a single selector with Disabled, LG Cookies Law, and Art Cookie Choices Pro options.
- Minimum PrestaShop compatibility is now 1.7.5.

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
