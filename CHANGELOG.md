# Changelog

All notable changes to this module are documented here. The project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-14

### Added
- Read-only StatHubs API user and role, created on `setup:upgrade`.
- System → StatHubs admin page with shop URL, username, password and QR code.
- Login block for the StatHubs user in the Magento admin.
- REST token access for the StatHubs user when two-factor authentication is enabled.
- `bin/magento stathubs:credentials` command.
- Removal of the user, role and credentials on `bin/magento module:uninstall`.
- German translation.
