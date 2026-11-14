# CHANGELOG

All notable changes to this project will be documented in this file.  
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.3.1] - 2026-11-14
### Fixed
- Add complete, correctly ordered author, copyright and MIT license metadata to the new purchase tracking files and directory guards.
- Bind the CLI worker shop through the module context and verify the loaded module type before processing the queue.
- Remove redundant product-property checks and normalize the global replay checkpoint before checking for an absent value.
- Use the associative-fetch and statement APIs shared by DBAL 2.13 and 3.x, without calling the removed fetchAll method.
- Protect template and generated configuration files with the standard root Apache rules, preserving existing PHP access restrictions.

## [1.3.0] - 2026-11-14
### Fixed
- Require qualifying order states before generating purchase tracking. Payment errors and unpaid backorders no longer produce purchases merely because the customer reaches order confirmation (issue #1).
- Validate order-line IDs, quantities, names and monetary values before browser or server serialization, and use the product subtotal excluding shipping.

### Added
- Shop-specific purchase state selection with paid-state defaults and hard exclusions for error, cancellation, refund and unpaid backorder states.
- Optional cookieless server purchase delivery for cookieless shops without a consent manager, using an outbox, atomic leases, bounded retries and a CLI worker for asynchronous payment confirmations and missed-hook reconciliation.
- A separate masked HTTPS tracking token, stable shop/order purchase IDs and an upgrade cutoff preventing automatic historical replay.
- Separate Doctrine DBAL (PS 8+) and legacy database paths for the outbox.


## [1.2.1] - 2026-09-08
### Fixed
- Replaced the legacy unbounded Matomo API helper with cURL requests using a five-second connection timeout, a twenty-second total timeout, TLS certificate verification, and explicit HTTP-status validation.
- Limited per-language product referrer API queries to 20 rows, preventing an unbounded product-report request from delaying the back office.

## [1.2.0] - 2026-07-08
### Fixed
- Cart update tracking now clears the Matomo ecommerce cart before rebuilding it, preventing stale cart items from polluting product average price reports.
- Cart update tracking now uses the same no-combination SKU suffix convention as order tracking (`idv0`) and prefers unit prices over line totals.
- Order confirmation tracking now uses the canonical `displayOrderConfirmation` hook used by PS 1.7+ confirmation and free-order flows, and clears the Matomo ecommerce cart before sending order items.
- Order item prices now prefer the order line unit price including tax, matching product view tracking more closely.

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
