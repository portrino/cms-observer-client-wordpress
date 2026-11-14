# Contributing

Thank you for considering contributing to this project! Every contribution is welcome and helps improve the quality of
the project. To ensure a smooth process and maintain high code quality, please follow the steps below.

## Requirements

- [DDEV](https://ddev.readthedocs.io/en/stable/)

## Preparation

```bash
# Clone repository
git clone https://github.com/portrino/cms-observer-client-wordpress.git
cd cms-observer-client-wordpress

# Start the project with DDEV (installs the Composer dependencies)
ddev start
```

## Run composer scripts

```bash
# Run all composer checks + static code analyzers
ddev composer ci

# Specific CGL/ static code analyzers
ddev composer ci:json:lint
ddev composer ci:php:cs-fixer
ddev composer ci:php:lint
ddev composer ci:php:rector
ddev composer ci:php:stan
ddev composer ci:yaml:lint

# Fix all CGL/ SCA issues
ddev composer fix

# Fix specific CGL/ SCA issues
ddev composer fix:php:cs
ddev composer fix:php:rector
```

## Run tests

```bash
# Run all tests
ddev composer test

# Run unit tests (with code coverage)
ddev composer test:unit
ddev composer test:unit:coverage
```

## WordPress Setup

For testing the plugin, set up the WordPress instances (one per supported version, the plugin is symlinked in and
activated):

```bash
# Install all WordPress versions
ddev install all

# Or install a specific version, optionally as multisite network
ddev install 6.4
ddev install 7.1 --multisite

# Run WP-CLI commands in one or all instances
ddev wpcli 7.1 cms-observer push --dry-run
ddev wpcli all plugin list
```

The instances are reachable at `https://wp64.cms-observer-client-wordpress.ddev.site/wp-admin/` and
`https://wp71.cms-observer-client-wordpress.ddev.site/wp-admin/` (`admin` / `password`).

## Submit a pull request

After completing your work, **open a pull request** and provide a description of your changes. Ideally, your PR
should reference an issue that explains the problem you are addressing.

All mentioned code quality tools will run automatically on every pull request. For more details, see the relevant
[workflows][1].

[1]: .github/workflows
