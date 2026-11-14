<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Admin;

use Portrino\CmsObserverClient\Configuration;

/**
 * Settings page for the API URL and token: under Settings on a single site, under
 * Network Admin > Settings on multisite (the configuration is network-wide).
 * Not built on the Settings API, since options.php can't save network options;
 * one admin-post handler (SettingsSaveHandler) covers both cases.
 */
final class SettingsPage
{
    public const PAGE = 'cms-observer-client';
    public const ACTION = 'cms_observer_client_save';

    public function __construct(
        private readonly Configuration $configuration,
    ) {}

    public function register(): void
    {
        add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', $this->addPage(...));
        add_action('admin_post_' . self::ACTION, [new SettingsSaveHandler(), 'save']);
    }

    public function addPage(): void
    {
        add_submenu_page(
            is_multisite() ? 'settings.php' : 'options-general.php',
            __('CMS Observer Client', 'cms-observer-client'),
            __('CMS Observer', 'cms-observer-client'),
            self::getCapability(),
            self::PAGE,
            $this->render(...),
        );
    }

    public function render(): void
    {
        if (!current_user_can(self::getCapability())) {
            return;
        }
        $hasToken = $this->configuration->getToken() !== '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('CMS Observer Client', 'cms-observer-client'); ?></h1>
            <?php SettingsSaveHandler::renderResultNotice(); ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
                <?php wp_nonce_field(self::ACTION); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cms-observer-api-url"><?php echo esc_html__('API URL', 'cms-observer-client'); ?></label></th>
                        <td>
                            <input type="url" id="cms-observer-api-url" name="api_url" class="regular-text" placeholder="https://observer.example.com"
                                value="<?php echo esc_attr($this->configuration->getApiUrl()); ?>"<?php disabled($this->isOverridden(Configuration::API_URL)); ?>>
                            <?php $this->renderSourceHint(Configuration::API_URL); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cms-observer-token"><?php echo esc_html__('Token', 'cms-observer-client'); ?></label></th>
                        <td>
                            <?php // The saved token is never sent back to the browser?>
                            <input type="password" id="cms-observer-token" name="token" class="regular-text" autocomplete="new-password" value=""
                                placeholder="<?php echo esc_attr($hasToken ? __('Saved, leave empty to keep it', 'cms-observer-client') : ''); ?>"<?php disabled($this->isOverridden(Configuration::TOKEN)); ?>>
                            <?php $this->renderSourceHint(Configuration::TOKEN); ?>
                            <?php if ($hasToken && !$this->isOverridden(Configuration::TOKEN)) { ?>
                                <p><label><input type="checkbox" name="remove_token" value="1"> <?php echo esc_html__('Remove the saved token', 'cms-observer-client'); ?></label></p>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param Configuration::API_URL|Configuration::TOKEN $name
     */
    private function renderSourceHint(string $name): void
    {
        $hint = match ($this->configuration->getSource($name)) {
            /* translators: %s: environment variable name */
            Configuration::SOURCE_ENVIRONMENT => __('Set by the environment variable %s.', 'cms-observer-client'),
            /* translators: %s: constant name */
            Configuration::SOURCE_CONSTANT => __('Set by the constant %s in wp-config.php.', 'cms-observer-client'),
            /* translators: %s: environment variable and constant name */
            Configuration::SOURCE_SETTINGS => __('Can also be set as environment variable or constant %s, which take precedence.', 'cms-observer-client'),
        };
        echo '<p class="description">' . esc_html(sprintf($hint, $name)) . '</p>';
    }

    /**
     * @param Configuration::API_URL|Configuration::TOKEN $name
     */
    private function isOverridden(string $name): bool
    {
        return $this->configuration->getSource($name) !== Configuration::SOURCE_SETTINGS;
    }

    public static function getCapability(): string
    {
        return is_multisite() ? 'manage_network_options' : 'manage_options';
    }

    public static function getPageUrl(): string
    {
        return is_multisite() ? network_admin_url('settings.php') : admin_url('options-general.php');
    }
}
