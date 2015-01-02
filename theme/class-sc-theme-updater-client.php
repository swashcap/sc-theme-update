<?php
/**
 * WordPress Theme Update Client.
 *
 * This class relies on one PHP constants, `SC_THEME_UPDATER_API_URL`. This
 * tells the class what URL to query for theme updates. Add it in your
 * _functions.php_ or another theme configuration file like so:
 *
 * ```
 * define('SC_THEME_UPDATER_API_URL', 'http://yoursite.com/path/to/update/api');
 * ```
 *
 * You may want to test your theme updates during theme development. To force
 * update server queries for every visit to _Updates_ page visit, set
 * `SC_THEME_UPDATER_ALWAYS_UPDATE`:
 *
 * ```
 * define('SC_THEME_UPDATER_ALWAYS_UPDATE', true);
 * ```
 *
 * @author   Cory Reed <swashcap@gmail.com>
 * @package  SC_Theme_Updater
 */

class SC_Theme_Updater_Client
{
    /**
     * Theme's update API url.
     *
     * @var string
     */
    public static $api_url = '';

    /**
     * Theme's slug.
     *
     * @var string
     */
    public static $theme_slug = '';

    /**
     * Theme's version.
     *
     * @var string
     */
    public static $theme_version = '';

    /**
     * Initialize.
     *
     * @return void
     */
    public static function init()
    {
        add_action('init', __CLASS__ . '::maybe_set_update_transient', 50);
        add_filter('pre_set_site_transient_update_themes', __CLASS__ . '::check_for_update', 100, 1);
    }

    /**
     * Clear the `update_themes` transient.
     *
     * @return void
     */
    public static function maybe_set_update_transient()
    {
        if (defined('SC_THEME_UPDATER_ALWAYS_UPDATE') && SC_THEME_UPDATER_ALWAYS_UPDATE) {
            set_site_transient('update_themes', null);
        }
    }

    /**
     * Check the server for theme updates.
     *
     * @param  object $data `update_themes` transient
     * @return object       Transformed `update_themes` transient
     */
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
                'request' => wp_json_encode($request)
            )
        );

        $raw_response = wp_remote_post(self::$api_url, $options);

        if (is_wp_error($raw_response)) {
            return $raw_response;
        } else if (wp_remote_retrieve_response_code($raw_response) !== 200) {
            return new WP_Error('broke', __('Theme update failed:'), $raw_response);
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
     * Set up the class for use.
     *
     * `wp_get_theme` has support since WordPress 3.4.0.
     *
     * @return void
     */
    public static function set_theme_data()
    {
        if (defined('SC_THEME_UPDATER_API_URL') && SC_THEME_UPDATER_API_URL) {
            self::$api_url = SC_THEME_UPDATER_API_URL;
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

SC_Theme_Updater_Client::init();
