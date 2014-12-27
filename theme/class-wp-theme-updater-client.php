<?php

class WP_Theme_Updater_Client
{
    public static $config_file = 'wp-theme-updater-config.json';

    public static $theme_slug = '';
    public static $theme_version = '';
    public static $api_url = '';

    public static function init()
    {
        add_action('init', 'self::maybe_set_update_transient', 50);
        add_filter('pre_set_site_transient_update_themes', 'self::check_for_update', 1, 20);
    }

    public static function maybe_set_update_transient()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            set_site_transient('update_themes', null);
        }
    }

    public static function check_for_update($data)
    {
        global $wp_version;

        // Setup
        self::set_theme_data();

        $request = array(
            'slug'    => self::$theme_slug,
            'version' => self::$theme_version
        );

        $response = wp_remote_post(
            self::get_api_url(),
            array(
                'body' => array(
                    'action' => 'theme_update',
                    'request' => serialize($request),
                    'api-key' => md5(get_bloginfo('url'))
                )
            )
        );

        if (! is_wp_error($response) && $response['response']['code'] === 200) {
            $response = unserialize($response['body']);
        } else {
            throw new WP_Error(
                'themes_api_failed',
                __('Theme’s automatic updater couldn’t process request', $response);
            );
        }

        if (! empty($response)) {
            $data->response[self::$theme_slug] = $response;
        }

        return $data;
    }

    /**
     * `wp_get_theme` since WordPress 3.4.0
     */
    public static function set_theme_data()
    {
        if (file_exists(self::$config_file)) {
            $config = json_decode(self::$config_file, TRUE);

            if (isset($config['apiUrl'])) {
                self::$api_url = $config['apiUrl'];
            } else {
                throw new WP_Error(
                    'themes_api_failed',
                    __('Theme’s automatic updater configuration doesn’t contain an API URL.')
                );
            }
        } else {
            throw new WP_Error(
                'themes_api_failed',
                __('Theme’s automatic updater configuration file doesn’t exist.')
            );
        }

        if (is_child_theme()) {
            $theme_data = wp_get_theme(get_option('stylesheet'));
            self::$theme_slug = get_option('template');
        } else {
            $theme_data = wp_get_theme(get_option('stylesheet'));
            self::$theme_slug = get_option('stylesheet');
        }
        self::$theme_version = $theme_data->Version;
    }
}

WP_Theme_Updater_Client::init();
