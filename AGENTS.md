# AGENTS.md

WordPress plugin `cms-observer-client` (Composer: `portrino/cms-observer-client-wordpress`).
It collects the site's WordPress core, PHP and DB versions plus the installed
plugins and pushes them to a CMS Observer instance (a separate Symfony
application, its own repository). The TYPO3 counterpart is
`portrino/cms-observer-client-typo3`; keep both clients aligned where WordPress
allows it.

## Support matrix

WordPress 6.4+ on PHP 8.1+. Every change must work on all of them:

- No syntax newer than PHP 8.1: e.g. no `readonly` classes (8.2), no typed
  class constants (8.3), no `new Foo()->bar()` without parentheses (8.4).
  Rector pins `PHP_81`; `composer.json` pins `config.platform.php` to `8.1.0`,
  so the dev dependencies resolve to versions that run on PHP 8.1 (that's why
  PHPUnit is 10.x and `armin/editorconfig-cli` is missing).
- No WordPress API newer than 6.4 without a fallback. The DDEV and CI matrix
  test the oldest and the latest supported version.
- Single site and multisite. A network is one Observer site: the configuration
  is network-wide (network options), only the main site schedules the cron
  event, and a plugin counts as active when it's network-activated or active on
  any site of the network (`ActivePluginFiles`).

## How it works

- `cms-observer-client.php` (plugin header, own PSR-4 autoloader, since ZIP
  installs ship without `vendor/`) → `Plugin::register()`: daily WP-Cron event
  `cms_observer_client_push` (scheduled on `init`, because the activation hook
  doesn't fire for DB or Composer-based activation), WP-CLI command
  `wp cms-observer push [--dry-run]`, settings page.
- `ReportBuilder` → `EnvironmentService` (versions, `wp_get_environment_type()`),
  `PluginInventoryService` (all installed plugins, active or not) and
  `ThemeInventoryService` (all installed themes, active or not) →
  `ObserverApiClient`.
- Theme names are their directories (`wp_get_themes()` keys), which are the
  wordpress.org slugs. A theme counts as active when it's the active theme or
  the parent of the active child theme (option `stylesheet`/`template`), on
  multisite on any site of the network.
- Plugin names are wordpress.org slugs (`PluginSlugs`), which the Observer
  matches against wordpress.org and Wordfence. The slug isn't always the
  directory (`hello.php` is `hello-dolly`); it comes from the `update_plugins`
  site transient, the directory or file name is the fallback.
- `dbVersion` is `wpdb::db_server_info()` (keeps the MariaDB marker that
  `db_version()` strips), `dbPlatform` is `mariadb`, `mysql` or `null` for
  non-MySQL databases (SQLite plugin).
- The Observer rejects a report with an empty `title`, `version` or
  `dbVersion` (422), so they fall back to the slug, `0.0.0` and `unknown`,
  for plugins and themes alike.
- Configuration (`Configuration`): API URL and token from the environment
  variables `CMS_OBSERVER_API_URL` / `CMS_OBSERVER_TOKEN`, then the constants
  of the same name (wp-config.php), then the settings page. The settings page
  is not built on the Settings API (options.php can't save network options);
  `SettingsSaveHandler` handles both cases via admin-post. The saved token is
  never rendered back, and it isn't autoloaded.
- HTTP: https only, 10 s timeout (WordPress' default is 5 s), no redirects
  (`redirection => 0`, so the bearer token can't follow one), success is 202.
- Header `Update URI` points to GitHub, so wordpress.org can't offer
  "updates" for this slug. Remove it only when the plugin is published on
  wordpress.org; until then the publish workflow strips it from the
  separate WordPress.org ZIP only.

## API contract with the Observer

`POST {apiUrl}/api/v1/sites/report`, `Authorization: Bearer <token>`, JSON body:

```json
{
  "coreVersion": "7.1", "phpVersion": "8.3.0", "dbVersion": "10.11.19-MariaDB",
  "dbPlatform": "mariadb", "clientVersion": "1.0.0", "applicationContext": "production",
  "domains": ["example.com"],
  "packages": [
    {"name": "akismet", "type": "plugin", "title": "Akismet", "version": "5.3", "active": true},
    {"name": "twentytwentyfour", "type": "theme", "title": "Twenty Twenty-Four", "version": "1.2", "active": true}
  ]
}
```

`packages` (since 2.0.0, needs CMS Observer 0.7.0 or newer; 1.x sent the
plugins as `extensions`): every installed plugin and theme, active or not, with
its `type`. The list is complete: the Observer removes what's missing from it.

`domains` (`SiteDomains`): all hosts of the instance,
lowercase, without scheme, port or path, unique and sorted; the hosts of
`home_url()` and `site_url()`, on multisite the domains of all sites. Empty
array when there is none, never `null`. The Observer answers 409 when no
domain matches the site URL (e.g. staging with the production database and
token); the push fails with the `message` of the JSON body, the token is
masked. Any other status fails with the `detail` of the JSON body if there
is one, e.g. the invalid field of a 422; the client sends `Accept:
application/json`, without it the Observer answers a 422 with an HTML page. The last failed push is kept in a network option (`LastPushError`)
and shown on the settings page.

The site must be created on the Observer with the type `wordpress`. Sites
update the client late or never, so the Observer must keep accepting older
payloads: add new fields as optional, never rename or remove one. Coordinate
payload changes with the Observer repository (its `SiteReportRequest` DTO).
Ask the user before any breaking change, even while nothing is in production.

## Workflow

DDEV with the add-on `portrino/ddev-wordpress-multi-version-plugin`: one
Composer-based WordPress instance per version (`WP_VERSIONS`, default `6.4 7.1`)
in `.Build/<label>` (`7.1` -> `71`), each on its own hostname
(`71.cms-observer-client-wordpress.ddev.site`), the plugin required from the
project root. Docs: https://github.com/portrino/ddev-wordpress-multi-version-plugin.
DDEV runs PHP 8.1, the lowest supported version. Dev tools live in
`.Build/vendor`.

- `ddev start`, `ddev install all` (or `ddev install 71 --multisite`),
  `ddev 71 wp cms-observer push --dry-run`, `ddev all wp <command>`,
  `ddev open` (intro page with every instance).
  Login: `https://71.cms-observer-client-wordpress.ddev.site/wp-admin/`,
  `admin` / `Password1!`.
- Run curl and other HTTP probes (the Observer, external APIs) inside the
  container (`ddev exec curl ...`), not on the host.
- `ddev composer test`: unit tests (`Build/phpunit.xml.dist`),
  `test:unit:coverage` adds coverage reports in `.Build/coverage`.
- `ddev composer ci`: composer checks (normalize, audit, dependency analyser,
  PSR-4) and static checks (JSON/YAML lint, PHP lint, PHP-CS-Fixer with the
  TYPO3 coding standard, Rector, PHPStan level 8 with strict rules, WordPress
  stubs and cognitive complexity limits). `ddev composer fix` runs the fixers.
- Code style is the TYPO3 coding standard (PSR-12 based, `typo3/coding-standards`),
  not WPCS, where it doesn't break WordPress: hook callbacks, WP-CLI command
  signatures and WordPress function names stay as WordPress needs them.
- PHPStan: fix the cause, don't suppress or baseline errors.
- The CI checks code style strictly (no auto-fix step) and doesn't run
  `ci:composer`; run `ddev composer ci` locally before committing.

## Testing

Unit tests in `Tests/Unit` (PHPUnit 10, Brain Monkey for WordPress functions;
`Tests/Unit/Fixtures/WordpressClasses.php` stands in for `wpdb`, `WP_Error`
and `WP_CLI`). Every change needs a test that calls the code the way a caller
would. The CI also installs every WordPress version of the matrix (single site
and multisite), activates the plugin and validates the `--dry-run` report.

## Releases

- SemVer. Keep `Version` in the plugin header of `cms-observer-client.php` and
  `Stable tag` in `readme.txt` in sync with the tag (plus a changelog entry
  there); the publish workflow refuses a tag that doesn't match.
  Don't put a top-level `version` into composer.json, Packagist takes it from
  the tag. Tag annotated (`git tag -a 1.0.0 -m "..."`), the message becomes
  the release notes.
- Release targets: Packagist (`portrino/cms-observer-client-wordpress`) and two
  ZIPs on the GitHub release, built with `git archive --prefix=cms-observer-client/`
  (honours `export-ignore`): `cms-observer-client-<version>.zip` and
  `cms-observer-client-<version>-wordpress-org.zip` without the `Update URI`
  header, for WordPress.org. GitHub's own source ZIP would unpack to a
  different directory and change the plugin slug.
- GitHub Actions are pinned to commit SHAs, Dependabot keeps them current.

## Git

Commit subject prefixes `[FEATURE]`, `[TASK]`, `[BUGFIX]`, `[CLEANUP]`
(changes without functional effect), following the TYPO3 commit message rules
(https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html):
imperative subject, capitalized after the prefix, under 52 chars if possible,
never over 72; body wrapped at 72 explaining what and why. TYPO3-Core-only
footers (`Resolves:`, `Releases:`, `Change-Id:`) don't apply.
