# Security policy

## Reporting a vulnerability

Please report suspected vulnerabilities in Matomo Analytics for PrestaShop
(`tec_matomo`) privately to [helpdesk@tecnoacquisti.com](mailto:helpdesk@tecnoacquisti.com).
Include the module, PrestaShop and PHP versions, reproduction steps, and the
potential impact. Do not publish unpatched security issues, credentials,
Matomo API tokens, or customer data in public GitHub issues.

If credentials are necessary to reproduce the issue, follow the instructions at
https://www.tecnoacquisti.com/it/content/19-come-inviare-le-tue-password.

## Supported releases

| Release line | PrestaShop and PHP requirements | Security support |
|---|---|---|
| 1.2.x / 1.3.x | PrestaShop 1.7.5+ and the PHP version supported by the installed PrestaShop release | Current maintained line; contact us for the applicable support terms. |

## Security updates

Security fixes and update instructions are distributed through the module
repository and the applicable customer delivery channel. Commercial assistance
and security update availability are managed separately. Read each update note
for affected versions, compatibility requirements, and any required action.

## Purchase tracking credentials and storage

The optional purchase tracking token is distinct from the reporting token.
It is masked in the back office and sent only in HTTPS POST bodies. The CLI
worker accepts no HTTP requests or URL secrets. Queue diagnostics contain fixed
error codes rather than payloads or upstream responses. The outbox stores a
minimal shop/order delivery ledger, which survives uninstall to prevent replay.
Server purchases are restricted to the module's cookieless configuration without
a consent manager; they do not forward customer cookies, IP, user agent or email.
