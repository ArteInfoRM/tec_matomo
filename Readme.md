# Matomo Analytics for PrestaShop

Matomo Analytics integration module for PrestaShop with advanced ecommerce tracking and privacy features.  
This module allows merchants to replace Google Analytics with Matomo, keeping **100% data ownership** and ensuring full **GDPR compliance**.

## Features

- Easy integration of **Matomo Analytics** into PrestaShop.
- **Ecommerce tracking**: product views, cart updates, orders, and revenue.
- **User ID tracking** for cross-device recognition (optional, may require user consent under GDPR).
- **Privacy modes**:
  - None (default)
  - Cookieless (disableCookies)
  - Require cookie consent
- Integration with **LG Cookies Law (Línea Gráfica)** CMP:
  - Automatically enables/disables Matomo cookies based on user consent.
  - Overrides standard Privacy Mode.
- Support for **noscript tracking pixel** (users with JavaScript disabled).
- **Heartbeat timer** to improve accuracy of "time on page".
- Advanced options:
  - Secure cookies (HTTPS only).
  - Track visitors across subdomains.
  - Cross-domain linking between multiple shops/domains.
  - Custom campaign parameters (extra name and keyword keys).

## Requirements

- PrestaShop 8.x or 9.x  
- Matomo On-Premise or Matomo Cloud instance (URL and Site ID required)

## Installation

1. Download the module as a `.zip` file from the [Releases](../../releases) page.
2. Upload it into your PrestaShop back office (**Modules > Module Manager > Upload a module**).
3. Configure your Matomo instance URL and Site ID.
4. (Optional) Add your API Token to display statistics directly in the PrestaShop back office.

## Development

- Follows **Semantic Versioning**.
- See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License

This project is released under the MIT License.  
See the [LICENSE](LICENSE) file for details.
