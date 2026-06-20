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
- **Product back-office widget** with Matomo ecommerce revenue, orders, purchased items, and matched SKUs.
- **User ID tracking** for cross-device recognition (optional, may require user consent under GDPR).
- **Privacy modes**:
  - None (default)
  - Cookieless (disableCookies)
  - Require cookie consent
- Consent manager integration:
  - Supports **LG Cookies Law (Línea Gráfica)** and **Art Cookie Choices Pro**.
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

## Development

- Follows **Semantic Versioning**.
- See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License

This project is released under the MIT License.  
See the [LICENSE](LICENSE) file for details.
