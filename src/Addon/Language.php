<?php

namespace GivePaystack\Addon;

/**
 * Helper class responsible for loading add-on translations.
 *
 * @package     GivePaystack\Addon\Helpers
 * @copyright   Copyright (c) 2020, GiveWP
 */
class Language
{
    /**
     * Load language.
     *
     * @since 3.0.0
     * @return void
     */
    public static function load()
    {
        // Set filter for plugin's languages directory.
        $langDir = apply_filters(
            sprintf('%s_languages_directory', 'give-paystack'),
            // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
            dirname(GIVE_PAYSTACK_BASENAME) . '/languages/'
        );

        // Traditional WordPress plugin locale filter.
        $locale = apply_filters('plugin_locale', get_locale(), 'give-paystack');
        $moFile = sprintf('%1$s-%2$s.mo', 'give-paystack', $locale);

        // Setup paths to current locale file.
        $moFileLocal = $langDir . $moFile;
        $moFileGlobal = WP_LANG_DIR . 'give-paystack' . $moFile;

        if (file_exists($moFileGlobal)) {
            // Look in global /wp-content/languages/TEXTDOMAIN folder.
            load_textdomain('give-paystack', $moFileGlobal);
        } elseif (file_exists($moFileLocal)) {
            // Look in local /wp-content/plugins/TEXTDOMAIN/languages/ folder.
            load_textdomain('give-paystack', $moFileLocal);
        } else {
            // Load the default language files.
            load_plugin_textdomain('give-paystack', false, $langDir);
        }
    }
}
