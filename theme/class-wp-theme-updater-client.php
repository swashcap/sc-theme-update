<?php

class WP_Theme_Updater_Client
{
    public static $theme_slug = '';
    public static $theme_version = '';
    public static $api_url = '';

    public static function init()
    {
        add_action('init', __CLASS__ . '::maybe_set_update_transient', 50);
        add_filter('pre_set_site_transient_update_themes', __CLASS__ . '::check_for_update', 100, 1);
    }

    public static function maybe_set_update_transient()
    {
        if (defined('WP_THEME_UPDATER_ALWAYS_UPDATE') && WP_THEME_UPDATER_ALWAYS_UPDATE) {
            set_site_transient('update_themes', null);
        }
    }

    public static function check_for_update($data)
    {
        // Setup
        self::set_theme_data();

        $request = array(
            'slug'    => self::$theme_slug,
            'version' => self::$theme_version
        );

        if (defined('DOING_CRON') && DOING_CRON) {
            $timeout = 30;
        } else {
            $timeout = 3;
        }

        $options = array(
            'timeout' => $timeout,
            'body' => array(
                'themes' => wp_json_encode($request)
            )
        );

        $raw_response = wp_remote_post(self::$api_url, $options);

        if (is_wp_error($raw_response)) {
            return $raw_response;
        } else if (wp_remote_retrieve_response_code($raw_response) !== 200) {
            return new WP_Error('broke', __('Theme update failed:') . ' ' . print_r($raw_response));
        }

        $response = json_decode(wp_remote_retrieve_body($raw_response), true);

        if (
            gettype($response) === 'array' &&
            isset($response['new_version']) &&
            isset($response['url']) &&
            isset($response['package'])
        ) {
            $data->response[self::$theme_slug] = array(
                'theme'       => self::$theme_slug,
                'new_version' => $response['new_version'],
                'url'         => $response['url'],
                'package'     => $response['package']
            );
        }

        return $data;
    }

    /**
     * `wp_get_theme` since WordPress 3.4.0
     */
    public static function set_theme_data()
    {
        if (defined('WP_THEME_UPDATER_API_URL') && WP_THEME_UPDATER_API_URL) {
            self::$api_url = WP_THEME_UPDATER_API_URL;
        } else {
            return new WP_Error(
                'themes_api_failed',
                __('Theme’s updater not properly configured.')
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
