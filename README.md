# StatHubs for Magento 2

Connect your Magento 2 shop to [StatHubs](https://stathubs.com/) and see your sales in the StatHubs mobile app.

## What the module does

- Creates a dedicated, read-only Magento admin user for StatHubs, with its own role.
- Adds **System → StatHubs** to the Magento admin. That page shows the shop URL, username, password and a QR code. Scan the QR code with the StatHubs app to connect the shop.
- Blocks the StatHubs user from signing in to the Magento admin. It can only fetch a REST API token.
- If Magento two-factor authentication is enabled, allows the StatHubs user, and only that user, to get a REST token without an authenticator app. All other admins still use two-factor authentication.

StatHubs reads orders and store configuration through the standard Magento REST API. The module adds no new API endpoints, no cron jobs, no frontend code and no outgoing network calls.

## Compatibility

| | |
|---|---|
| Tested on | Mage-OS 3.6.0 (`magento/framework` 103.0.9), PHP 8.3, with two-factor authentication enabled, disabled and removed |
| Declared requirement | Magento Open Source / Adobe Commerce 2.4.4 or later (`magento/framework` ^103.0.4), PHP 8.1 or later |
| Not supported | Magento 1, Magento 2.3 and earlier, Magento 2.4.0–2.4.3 |

Versions between 2.4.4 and the tested version should work, based on the module's requirements, but they have not been tested yet. Please report any problems.

## Requirements

- Shell (SSH) access to the server. If you don't have it, send this page to your developer or hosting provider.
- The shop must be reachable over **HTTPS** from the internet. The StatHubs servers call the shop's REST API at `https://<your-shop>/rest/...`.
- The Magento REST API must not be blocked by a firewall, WAF or basic-auth protection for the StatHubs servers.

## Installation

Run these commands in the Magento root directory, as the user that owns the Magento files (not as `root`):

```sh
composer require stathubs/magento2
bin/magento module:enable Solu1_StatHubs
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

That's it. Continue with [Connect the shop to StatHubs](#connect-the-shop-to-stathubs).

- `setup:di:compile` and `setup:static-content:deploy` are only needed in production mode (check with `bin/magento deploy:mode:show`). If your shop uses more locales, list them, for example `setup:static-content:deploy -f en_US de_DE`.
- On a live shop, you can wrap the commands in `bin/magento maintenance:enable` and `bin/magento maintenance:disable`.
- Magento 2.4 cannot install extensions from a ZIP uploaded in the admin panel. Adobe removed the Web Setup Wizard in 2.4.0.

### Without Composer (ZIP)

1. Download `stathubs-magento2.zip` from the [Releases](../../releases) page.
2. Extract it into `app/code/Solu1/StatHubs`:

   ```sh
   mkdir -p app/code/Solu1/StatHubs
   unzip stathubs-magento2.zip -d app/code/Solu1/StatHubs
   ```

   `app/code/Solu1/StatHubs/registration.php` must exist afterwards.
3. Run the commands above, starting with `bin/magento module:enable`. Skip `composer require`.

## Connect the shop to StatHubs

1. In the Magento admin, open **System → StatHubs**.
2. In the StatHubs app, add a new shop and scan the QR code on that page.
   Or enter the shop URL, username and password shown on the page by hand.
3. StatHubs imports your orders. The first sync can take a few minutes on large shops.

Admins need the **StatHubs** permission (System → Permissions → User Roles → Role Resources → System → StatHubs) to see the page. Only give it to people who should see the connection password.

If the page shows a warning that the shop URL is not HTTPS, fix the base URL (Stores → Configuration → General → Web) before you connect.

## Permissions and data access

The module creates a Magento admin user (default username `stathubs`) with a role (default name `StatHubs`). The role allows these ACL resources only:

| Resource | Why |
|---|---|
| `Magento_Sales::sales`, `Magento_Sales::sales_operation`, `Magento_Sales::sales_order`, `Magento_Sales::actions` | Parent resources needed to reach the order view resource |
| `Magento_Sales::actions_view` | Read orders (`GET /V1/orders`) |
| `Magento_Backend::store` | Read store configuration and currency (`GET /V1/store/storeConfigs`) |

Every other resource is explicitly denied, including creating, editing, cancelling, invoicing, shipping and refunding orders, and access to customers, products and configuration. On the tested version, every REST route these resources allow is a `GET` route.

The Magento order API returns complete order records. Depending on your orders, these can include customer names, email addresses and billing and shipping addresses. StatHubs requests orders through this API. Take this into account in your privacy documentation.

## Credentials and security

- The password is randomly generated, at 27 characters. It is stored **encrypted** with your Magento encryption key in `core_config_data` (`solu1_stathubs/credentials/password`).
- The package contains no credentials or API keys. Each shop creates its own on installation.
- The password is shown in plain text on **System → StatHubs**, so that it can be scanned or copied. Restrict that page to trusted admins.
- The StatHubs user cannot sign in to the Magento admin while the module is enabled.
- Opening **System → StatHubs** re-checks the StatHubs user. If the user was deactivated or deleted, opening the page re-activates or recreates it with the stored password. To revoke access, see [Uninstall](#uninstall).

### Rotate the password

The module has no button to create a new password yet. To create one, delete the stored password and then open the StatHubs page:

```sh
bin/magento maintenance:enable
mysql -e "DELETE FROM core_config_data WHERE path = 'solu1_stathubs/credentials/password';" <your_database>
bin/magento cache:clean config
bin/magento maintenance:disable
```

If your tables use a prefix, add it to `core_config_data`. Then open **System → StatHubs**. A new password is generated and set on the StatHubs user. Reconnect the shop in the StatHubs app with the new QR code.

## Update

**Installed with Composer:**

```sh
composer update stathubs/magento2
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

**Installed from the ZIP:** download the new ZIP, replace the contents of `app/code/Solu1/StatHubs` with it, then run the same `bin/magento` commands.

Your existing StatHubs credentials are kept.

## Uninstall

**Installed with Composer:**

```sh
bin/magento module:uninstall Solu1_StatHubs
bin/magento cache:flush
```

This removes the code, the StatHubs user, its role and the stored credentials.

**Installed from the ZIP:** `bin/magento module:uninstall` only works for Composer installs. Instead run:

```sh
bin/magento module:disable Solu1_StatHubs
rm -rf app/code/Solu1/StatHubs
bin/magento setup:upgrade
bin/magento cache:flush
```

Then remove the StatHubs user and role by hand. **This is important.** Once the module is disabled, nothing stops that user from signing in:

1. **System → Permissions → All Users**: delete the user `stathubs` (or the username shown on the StatHubs page).
2. **System → Permissions → User Roles**: delete the role `StatHubs`.
3. Optional: delete the stored settings with `DELETE FROM core_config_data WHERE path LIKE 'solu1_stathubs/%';`

Finally, remove the shop in the StatHubs app.

## Troubleshooting

**System → StatHubs is missing in the admin.**
Run `bin/magento module:status Solu1_StatHubs` and check that the module is enabled, then run `bin/magento cache:flush`. Check that your admin role includes the StatHubs resource.

**The page says "Could not create the StatHubs API user".**
Run `bin/magento setup:upgrade` and reload the page. If the problem continues, check `var/log/exception.log` and `var/log/system.log`.

**The StatHubs app cannot connect.**
- Open `https://<your-shop>/rest/V1/store/storeConfigs` in a browser. A JSON error such as "The consumer isn't authorized" means the REST API is reachable. An HTML page, a 403 or a timeout means a firewall, WAF, maintenance mode or basic-auth protection is blocking it.
- Check that the shop URL on the StatHubs page is the public HTTPS address of the shop.
- If you changed the StatHubs user's password in System → Permissions, the page no longer shows the right password. [Rotate the password](#rotate-the-password) to sync it.

**Orders are missing or old.**
StatHubs reads orders of all store views. Check in the StatHubs app that the shop's last sync succeeded. If it did not, the API is probably blocked (see above).

**Errors after installing in production mode.**
Run `setup:di:compile` and `setup:static-content:deploy -f` again, then `cache:flush`. Check file ownership and permissions under `generated/`, `pub/static/` and `var/`.

## Support

- Website: <https://stathubs.com/>
- Email: <info@profihost.com>

When you report a problem, include your Magento version, PHP version, module version and the relevant lines from `var/log/`. **Never send the StatHubs password.**

## License

This module is licensed under the GPL-3.0-or-later license (see `composer.json`).

It includes [QR Code Generator for JavaScript](https://github.com/kazuhikoarase/qrcode-generator) by Kazuhiko Arase, licensed under the MIT license (see the header of `view/adminhtml/web/js/qrcode-generator.js`). "QR Code" is a registered trademark of DENSO WAVE INCORPORATED.
