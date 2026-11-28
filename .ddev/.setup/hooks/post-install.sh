# Repo-owned install hook (sourced by the add-on in each instance after its setup).
# The instance pushes to the local E2E Observer, whose project for this WordPress
# version has a fixed token: `ddev e2e-reset` in the cms-observer repository
# creates them (its CONTRIBUTING.md lists them). Constants, so the environment
# variables still win.
wp config set CMS_OBSERVER_API_URL 'https://e2e.cms-observer.ddev.site' --anchor="define('WP_ENVIRONMENT_TYPE'" --placement=before --quiet
wp config set CMS_OBSERVER_TOKEN "e2e-wordpress-${label}" --anchor="define('WP_ENVIRONMENT_TYPE'" --placement=before --quiet
