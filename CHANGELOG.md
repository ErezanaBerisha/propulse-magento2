# Changelog

All notable changes to this module are documented here. The project follows [Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-11-20

### Changed
- Renamed from StatHubs to proPulse: Composer package `propulse/magento2`, module `Solu1_ProPulse`, namespace `Solu1\ProPulse`, admin page **System → proPulse**, command `bin/magento propulse:credentials`.
- New installs create the admin user `propulse` with the role `proPulse`.
- README shows the proPulse logo.

## [1.0.1] - 2026-11-15

### Changed
- Composer package renamed from `solu1/module-stathubs` to `stathubs/magento2`. Install with `composer require stathubs/magento2`.
- README: one-command Composer installation.

### Removed
- `.sha256` checksum file from release downloads.

## [1.0.0] - 2026-11-14

### Added
- Read-only StatHubs API user and role, created on `setup:upgrade`.
- System → StatHubs admin page with shop URL, username, password and QR code.
- Login block for the StatHubs user in the Magento admin.
- REST token access for the StatHubs user when two-factor authentication is enabled.
- `bin/magento stathubs:credentials` command.
- Removal of the user, role and credentials on `bin/magento module:uninstall`.
- German translation.
