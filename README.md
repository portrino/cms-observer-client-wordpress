# WordPress Plugin `cms-observer-client`

[![Latest Stable Version](https://poser.pugx.org/portrino/cms-observer-client-wordpress/v/stable)](https://packagist.org/packages/portrino/cms-observer-client-wordpress)
[![WordPress 6.4+](https://img.shields.io/badge/WordPress-6.4+-21759b.svg?logo=wordpress)](https://wordpress.org/download/releases/)
[![PHP 8.1 - 8.5](https://img.shields.io/badge/PHP-8.1_--_8.5-4f5b93.svg?logo=php)](https://www.php.net/releases/)
[![License](https://poser.pugx.org/portrino/cms-observer-client-wordpress/license)](https://packagist.org/packages/portrino/cms-observer-client-wordpress)
[![CI](https://github.com/portrino/cms-observer-client-wordpress/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/portrino/cms-observer-client-wordpress/actions/workflows/ci.yml)

This plugin reports a site's WordPress core version, PHP + DB versions, and installed plugin versions to a central
[CMS Observer](https://github.com/portrino/cms-oberserver) instance, which flags insecure and outdated versions.

> [!IMPORTANT]
> Without a central [CMS Observer](https://github.com/portrino/cms-oberserver) instance this plugin is somewhat
> useless. So set up your central CMS Observer instance first and then connect any number of WordPress sites by using
> this client.

Works on single sites and multisite networks. A network is reported as one site: its configuration is network-wide,
and a plugin counts as active when it's network-activated or active on any site of the network.

## Installation

### Composer

The **recommended** way for Composer based WordPress setups (e.g. [Bedrock](https://roots.io/bedrock/)):

```bash
composer require portrino/cms-observer-client-wordpress
```

### ZIP

Download `cms-observer-client-<version>.zip` from the
[GitHub releases](https://github.com/portrino/cms-observer-client-wordpress/releases) and upload it under
**Plugins > Add New > Upload Plugin**. (Don't use GitHub's "Source code" ZIP, it unpacks to a different directory.)

Activate the plugin, on multisite network-wide.

## Configuration

1. On the Observer, register a new site with the type `wordpress` (using command line or web interface) and copy
   the printed token:
   ```
   php bin/console app:site:create "<name>" wordpress "<site-url>"
   ```
2. In WordPress, go to **Settings > CMS Observer** (on multisite: **Network Admin > Settings > CMS Observer**) and set:
   - `API URL` – the Observer's base URL, must use `https://`
   - `Token` – the token from step 1

   Alternatively define `CMS_OBSERVER_API_URL` and `CMS_OBSERVER_TOKEN` as constants in `wp-config.php` or as
   environment variables. They take precedence over the settings page (environment variable first), so a shared
   `wp-config.php` can serve several environments.

### Several environments (production, staging, …)

- Set the token for each environment as environment variable or constant in `wp-config.php`, not on the settings
  page. A token saved on the settings page lives in `wp_options` and is copied along with the database, e.g. when
  staging gets the production database, and staging would then report as production. The Observer catches this
  as long as staging has a different domain: the report carries all domains of the site (`domains`), and the
  Observer rejects it with HTTP 409 when none matches the registered site URL. WP-CLI shows the Observer's
  message, the settings page shows the last failed push.
- Register the site on the Observer with exactly the host WordPress uses (`home_url()`): the comparison is exact,
  `www.example.com` doesn't match `example.com`.
- Set `WP_ENVIRONMENT_TYPE` in each environment's `wp-config.php` (e.g. `define('WP_ENVIRONMENT_TYPE', 'staging');`),
  the report sends it as `applicationContext`. Without it WordPress reports `production`.

## 🚀 Run

The plugin sends the report once a day via WP-Cron. On sites with low traffic, or with `DISABLE_WP_CRON`, run
WP-Cron from a system cron job, or push the report with WP-CLI:

```bash
wp cms-observer push
```

To check what would be sent without sending it:

```bash
wp cms-observer push --dry-run
```

## 💎 Credits

This project is the WordPress counterpart of the TYPO3 extension
[cms-observer-client-typo3](https://github.com/portrino/cms-observer-client-typo3).

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).
