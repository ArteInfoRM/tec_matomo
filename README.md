# Matomo Analytics for PrestaShop

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
![Built for PrestaShop](https://img.shields.io/badge/Built%20for-PrestaShop-DF0067?logo=prestashop&logoColor=white)  

Matomo Analytics integration module for PrestaShop with advanced ecommerce tracking and privacy features.  
This module allows merchants to replace Google Analytics with Matomo, keeping **100% data ownership** and ensuring full **GDPR compliance**.

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
