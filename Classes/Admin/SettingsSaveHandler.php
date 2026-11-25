<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Admin;

use Portrino\CmsObserverClient\Configuration;

/**
 * Saves the settings form of SettingsPage as network options (regular options on a single site).
 */
final class SettingsSaveHandler
{
    public function save(): void
    {
        if (!current_user_can(SettingsPage::getCapability())) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'cms-observer-client'), '', ['response' => 403]);
        }
        check_admin_referer(SettingsPage::ACTION);

        // Overridden fields are disabled in the form, so the browser doesn't submit them
        $result = isset($_POST['api_url']) ? $this->saveApiUrl(sanitize_text_field(wp_unslash($_POST['api_url']))) : 'updated';
        if (isset($_POST['remove_token'])) {
            delete_site_option(Configuration::OPTION_TOKEN);
        } elseif (isset($_POST['token'])) {
            $this->saveToken(sanitize_text_field(wp_unslash($_POST['token'])));
        }

        wp_safe_redirect(add_query_arg(['page' => SettingsPage::PAGE, 'result' => $result], SettingsPage::getPageUrl()));
        exit;
    }

    /**
     * @return 'updated'|'https-required'
     */
    private function saveApiUrl(string $apiUrl): string
    {
        $apiUrl = trim($apiUrl);
        if ($apiUrl !== '' && strtolower((string)wp_parse_url($apiUrl, PHP_URL_SCHEME)) !== 'https') {
            return 'https-required';
        }
        update_site_option(Configuration::OPTION_API_URL, esc_url_raw($apiUrl, ['https']));

        return 'updated';
    }

    private function saveToken(string $token): void
    {
        $token = trim($token);
        if ($token === '') {
            return;
        }
        // A new network option is added without autoload, also on a single site,
        // so the token isn't loaded into memory on every request
        update_site_option(Configuration::OPTION_TOKEN, $token);
    }

    /**
     * The notice for the result the redirect after saving carries.
     */
    public static function renderResultNotice(): void
    {
        $notices = [
            'updated' => ['success', __('Settings saved.', 'cms-observer-client')],
            'https-required' => ['error', __('The API URL must use https. It was not saved.', 'cms-observer-client')],
        ];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only selects one of the fixed notices, changes nothing
        $result = isset($_GET['result']) ? sanitize_key(wp_unslash($_GET['result'])) : '';
        if (isset($notices[$result])) {
            [$type, $message] = $notices[$result];
            printf('<div class="notice notice-%s"><p>%s</p></div>', esc_attr($type), esc_html($message));
        }
    }
}
