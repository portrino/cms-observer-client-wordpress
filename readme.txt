=== CMS Observer Client ===
Tags: security, monitoring, updates, vulnerabilities, multisite
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Reports WordPress core, PHP and database versions plus all installed plugins and themes to a CMS Observer instance.

== Description ==

This plugin reports a site's WordPress core version, PHP and database versions, and the installed plugin and theme
versions to a central [CMS Observer](https://github.com/portrino/cms-observer) instance, which flags insecure and
outdated versions.

Without a central CMS Observer instance this plugin is of no use. Set up your CMS Observer instance first, then
connect any number of WordPress sites with this plugin. It needs CMS Observer 0.7.0 or newer.

Works on single sites and multisite networks. A network is reported as one site: its configuration is network-wide,
and a plugin or theme counts as active when it's active on any site of the network (a plugin also when
network-activated). The parent of an active child theme counts as active too.

The report is sent once a day via WP-Cron. It can also be sent with WP-CLI:

`wp cms-observer push`

To check what would be sent without sending it:

`wp cms-observer push --dry-run`

== External services ==

This plugin connects to a CMS Observer instance, a self-hosted application (https://github.com/portrino/cms-observer)
whose URL you configure. It doesn't connect to any service before you have set the API URL and token. No data is
sent to the plugin authors or any other third party.

Once a day via WP-Cron, and whenever `wp cms-observer push` is run, the plugin sends a report to
`<API URL>/api/v1/sites/report` over https, authenticated with the configured token. The report contains:

* the WordPress core, PHP and database versions and the database platform (MySQL or MariaDB)
* the plugin version and the environment type (`wp_get_environment_type()`)
* the domains of the site (on multisite: of all sites of the network)
* the name, title, version and active state of every installed plugin and theme

It contains no content, users or other personal data. What happens to the data on the Observer is up to whoever
operates the instance you configure.

== Installation ==

1. Install and activate the plugin, on multisite network-wide.
2. On the CMS Observer, register a new site with the type `wordpress` and copy the printed token:
   `php bin/console app:site:create "<name>" wordpress "<site-url>"`
3. In WordPress, go to Settings > CMS Observer (on multisite: Network Admin > Settings > CMS Observer) and set the
   API URL (the Observer's base URL, must use https) and the token.

Alternatively define `CMS_OBSERVER_API_URL` and `CMS_OBSERVER_TOKEN` as constants in `wp-config.php` or as
environment variables. They take precedence over the settings page (environment variable first), so a shared
`wp-config.php` can serve several environments.

For Composer based setups the plugin is also available as `portrino/cms-observer-client-wordpress`.

== Frequently Asked Questions ==

= How do I run several environments (production, staging, …)? =

Set the token for each environment as environment variable or constant in `wp-config.php`, not on the settings
page. A token saved on the settings page is stored in the database and copied along with it, e.g. when staging gets
the production database. The Observer rejects a report with HTTP 409 when none of its domains matches the
registered site URL; WP-CLI shows the Observer's message, the settings page shows the last failed push.

Set `WP_ENVIRONMENT_TYPE` in each environment's `wp-config.php`, the report sends it as the application context.
Without it WordPress reports `production`.

= The report isn't sent every day. Why? =

WP-Cron only runs when the site gets visits. On sites with low traffic, or with `DISABLE_WP_CRON`, run WP-Cron from
a system cron job, or run `wp cms-observer push` from one.

== Changelog ==

= 2.0.2 =
* Maintenance release without functional changes.

= 2.0.0 =
* Report the installed themes along with the plugins, as `packages`. Needs CMS Observer 0.7.0 or newer.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 2.0.0 =
Needs CMS Observer 0.7.0 or newer.
