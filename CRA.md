# CRA readiness note

This document records release-facing security information for Matomo Analytics
for PrestaShop (`tec_matomo`) version 1.2.1. It is not an EU Cyber Resilience
Act declaration of conformity and does not replace the technical documentation,
risk assessment, vulnerability-handling process, or retention records that may
be required for a specific distribution.

The declared module license is the MIT License in [LICENSE](LICENSE). Legacy
source-file headers have been preserved for historical provenance and require a
separate legal-header review; they are not a replacement for the repository
license text. Third-party notices remain separate from the module license.

## Product scope

The module integrates a merchant-configured Matomo instance with PrestaShop
1.7.5 and later. It sends front-office analytics and ecommerce events to that
instance and, when the merchant configures an API token, retrieves aggregated
analytics reports for back-office dashboards, product metrics, and exports.

The merchant determines the Matomo endpoint, hosting, retention, consent
configuration, and API-token permissions. The module does not bundle Matomo or
a consent-manager SDK.

## Security controls in this release

- The Matomo API token is used only for server-side Matomo API requests and is
  not rendered in front-office templates.
- Back-office report requests verify TLS certificates for HTTPS connections,
  apply a five-second connection timeout and a twenty-second total timeout,
  and reject non-success HTTP responses.
- Product referrer report requests are bounded to 20 rows per active shop
  language to limit the work performed while editing a product.
- The module relies on the merchant's PrestaShop permissions and consent
  configuration; privacy and lawful-basis choices remain merchant decisions.

## Dependencies and vulnerability reporting

The release inventory is recorded in [sbom.cdx.json](sbom.cdx.json) and
[THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md). Report suspected
vulnerabilities according to [SECURITY.md](SECURITY.md).

## Release evidence

The source repository records the release code, changelog, upgrade scripts, and
generated documentation. ZIP installation or upgrade testing on a clean
environment and marketplace-validator execution remain manual release controls
and are not asserted by this document.
