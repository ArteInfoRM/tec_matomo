# Matomo Analytics for PrestaShop

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
![Built for PrestaShop](https://img.shields.io/badge/Built%20for-PrestaShop-DF0067?logo=prestashop&logoColor=white)  

Matomo Analytics integration module for PrestaShop with advanced ecommerce tracking and privacy features.  
Maintained by **Tecnoacquisti.com®**, the module connects the shop to the merchant’s Matomo installation and provides configurable ecommerce and privacy controls.

## Features

- Easy integration of **Matomo Analytics** into PrestaShop.
- **Ecommerce tracking**: product views, cart updates, orders, and revenue.
- **Back-office dashboard widget** with ecommerce KPIs, visits, and revenue by channel from Matomo API.
- Dedicated **Matomo Analytics** back-office statistics page with its own date range selector, quick period presets, top countries, top products, top categories, and CSV/JSON/XML export.
- **Product back-office widget** with Matomo ecommerce revenue, orders, purchased items, product visits, matched Matomo rows, and visit sources.
- **User ID tracking** for cross-device recognition (optional, may require user consent under GDPR).
- **Privacy modes**:
  - None (default)
  - Cookieless (disableCookies)
  - Require cookie consent
- Consent manager integration:
  - Supports **LG Cookies Law (Línea Gráfica)**, **Art Cookie Choices Pro**, **iubenda**, **Cookiebot**, and **CookieYes**.
  - Matomo cookie consent can follow the selected consent manager from module configuration.
- Support for **noscript tracking pixel** (users with JavaScript disabled).
- **Heartbeat timer** to improve accuracy of "time on page".
- Advanced options:
  - Secure cookies (HTTPS only).
  - Track visitors across subdomains.
  - Cross-domain linking between multiple shops/domains.
  - Custom campaign parameters (extra name and keyword keys).

## Requirements

- PrestaShop 1.7.5 or newer
- Matomo On-Premise or Matomo Cloud instance (URL and Site ID required)
- PHP cURL extension for back-office API statistics, product metrics, and exports

## Installation

1. Download the module as a `.zip` file from the [Releases](../../releases) page.
2. Upload it into your PrestaShop back office (**Modules > Module Manager > Upload a module**).
3. Configure your Matomo instance URL and Site ID.
4. (Optional) Add your API Token to display dashboard statistics, product metrics, and exports directly in the PrestaShop back office.

## Back-office statistics

The Matomo API token enables a dedicated statistics page in the PrestaShop back office. The page includes ecommerce KPIs, visits, revenue by channel, top countries by revenue, top products by revenue, and top categories by revenue.

The statistics page supports manual date ranges, quick presets for month, year, day -1, month -1, and year -1, plus CSV, JSON, and XML export for the currently selected dataset.

Matomo 5.5+ can classify referral traffic from AI tools such as ChatGPT, Claude, Gemini, Copilot, and Perplexity under the **AI Assistant** channel. When that channel is available in Matomo API data, the dedicated statistics page shows a separate AI Assistant traffic block.

Matomo 5.8+ also supports AI Chatbot tracking through server-side integrations such as Cloudflare, Amazon CloudFront, WordPress, or the Matomo HTTP Tracking API. This module does not configure server-side chatbot tracking automatically; use Matomo setup instructions when that report is required.

## Product back-office metrics

When the Matomo API token is configured, the product edit page shows product-level ecommerce statistics from Matomo. The widget matches product views and purchases by tracked SKU and falls back to product-name reports when SKU data is not enough.

For products without combinations, the widget treats the plain product ID and the `idv0` SKU used by order tracking as the same product. Products with real combinations keep their `idv<id_product_attribute>` rows separated, so variant-level reporting remains available.

The widget also shows visit-source data for the product page by querying Matomo referrer reports with a page URL segment generated from the product URLs in the active shop languages.
Back-office API calls use a five-second connection timeout and a twenty-second
total timeout. Product referrer lookups are limited to 20 rows per shop language
so an unavailable or slow Matomo instance does not indefinitely block the editor.

## Purchase eligibility and delayed payments

Since version 1.3.0, reaching the confirmation page is not enough to record a
purchase. **Qualifying purchase states** defaults to the states marked as paid
by PrestaShop. Merchants can explicitly include pending bank transfer or
cash-on-delivery states if their reporting policy counts those orders as
purchases. Payment error, cancellation, refund and unpaid backorder states are
always excluded, including when an error is followed by an automatic unpaid
backorder transition. The order total alone is never used as proof of payment.
A legitimate free order can qualify through its state.

Settings and Matomo targets are read for the order's shop. Configure each shop
when its purchase policy differs. An empty selection disables purchases.

### Browser delivery (default)

Eligible purchases are emitted on the confirmation page. Existing browser
privacy settings and consent integrations remain in control. A payment that
becomes eligible after the customer leaves the page requires the server mode
below or another confirmation-page visit.

### Optional server delivery

**Server purchase delivery** replaces the browser purchase event. Order creation
and order-state hooks enqueue eligible purchases without making network calls
during checkout. The worker also reconciles a bounded batch of eligible orders
created after the upgrade, recovering missed hooks without replaying orders
already present in the delivery ledger. A CLI worker sends them even if the customer does not revisit
the shop. Product and cart browsing events continue through the browser.

This mode is available only with **Cookieless** privacy mode and the consent
manager **disabled**. A server callback has no browser consent context; the
module refuses this mode with consent-controlled settings. If those settings
are changed outside the form, server delivery pauses rather than falling back
to a competing purchase channel.

The purchase reference and product data are still sent to Matomo. Server
purchases do not forward the customer's IP address, user agent, email,
browser cookies or User ID. They use a synthetic visitor ID specific to the
order. This is cookieless delivery, not a guarantee of anonymous data. It does not
preserve the original browser session or campaign
attribution and can appear as separate visits. Matomo records the delivery
time. The Matomo server still receives the tracking server's network address.
Choose this mode only when these reporting semantics suit the shop.

Run the worker every minute from the shop root, once for each shop ID:

```bash
php modules/tec_matomo/bin/process-purchases.php --shop=1 --limit=10
```

The worker is CLI-only and needs no secret in a URL. Each run processes at most
20 entries. PHP cURL and a secure random source (native `random_bytes` or
OpenSSL on PHP 5.6) are required. Failures use exponential backoff, bounded at one hour between
attempts, with at most ten attempts. Worker output includes aggregate queue
counts. After correcting a delivery problem, explicitly retry failed entries:

```bash
php modules/tec_matomo/bin/process-purchases.php --shop=1 --limit=10 --retry-failed
```

An optional **Purchase tracking token** can be supplied if the Matomo tracker
requires authentication. It is separate from the reporting API token, masked
in the form, preserved on empty or unchanged input, sent only in an HTTPS POST
body and never exposed in browser code or normal logs. The tracker must return
HTTP 204 with `send_image=0`; redirects are not followed. Matomo filters must
allow tracking from the worker. An HTTP acknowledgement does not independently
verify storage in a Matomo report.

The outbox has one entry per shop, order and target, atomic worker leases and
persistent acknowledgement. Retries reuse a stable purchase ID, including
when a response or acknowledgement is lost. This relies on Matomo retaining
order IDs for deduplication; its order-ID anonymisation setting can weaken
deduplication after ambiguous failures. Delivery rechecks eligibility and
the target before sending. A changed target or an order that no longer
qualifies is skipped. Purchases already acknowledged are never reset by the
retry command. Later refunds are not automatically reversed in Matomo.

### Upgrade and historical data

Run the module upgrade to create the outbox and register the order lifecycle
hooks. Version 1.3.0 records a cutoff: the highest existing order ID at the first upgrade
or installation defines the replay boundary. Earlier orders are not replayed
automatically, independently of shop timezones. New purchase IDs include shop
and order IDs, separating stores and split orders. Historical pending orders
need a separately reviewed backfill; previously recorded false purchases are
not removed from Matomo by this update.

The minimal outbox ledger and cutoff survive uninstall/reinstall to prevent
accidental replay. The ledger stores order/shop IDs, a target hash, status,
attempts and timestamps; it stores no product payload, email addresses or network data.
It must not be deleted as routine cache cleanup.

## Cookie consent integrations

Matomo does not use Google Consent Mode. When Matomo cookies require consent, this module can call Matomo's cookie consent API after the selected cookie banner grants analytics/statistics consent.

Supported integrations:

- **LG Cookies Law (Línea Gráfica)**: uses the configured LG Analytics purpose ID.
- **Art Cookie Choices Pro**: reads the analytics/performance consent preferences exposed by the banner cookies.
- **iubenda**: reads `_iub.cs.api.getPreferences()` and the configured Analytics purpose ID. The default purpose ID is `4`, but it can be changed in the module configuration.
- **Cookiebot**: reads `Cookiebot.consent.statistics` and listens to Cookiebot consent events.
- **CookieYes**: reads `getCkyConsent()` and the Analytics category exposed by the CookieYes banner.

LegalBlink was evaluated. Public documentation describes banner installation, script blocking, Google Consent Mode v2, and statistical cookie configuration, but no stable public JavaScript callback/preference API was found for a direct Matomo bridge. Use LegalBlink native script blocking for Matomo or provide the official callback/API details before enabling a dedicated module integration.

## Development

- Follows **Semantic Versioning**.
- See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License

This project is released under the MIT License.  
See the [LICENSE](LICENSE) file for details.
