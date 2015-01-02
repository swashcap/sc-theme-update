<?php
/**
 * WordPress Theme Updater.
 *
 * @author   Cory Reed <swashcap@gmail.com>
 * @package  SC_Theme_Updater
 */

require_once 'class-sc-theme-updater-package.php';

class SC_Theme_Updater
{
    /**
     * Initialize.
     *
     * @return void
     */
    public static function init()
    {
        /**
         * WordPress uses an internal function to request theme updates. Ensure
         * traffic comes exclusively from WordPress by checking the `USER_AGENT`
         * header.
         */
        if (! stristr($_SERVER['HTTP_USER_AGENT'], 'WordPress')) {
            self::respond(array(
                'http_status' => 406,
                'message'     => 'Requests must be made from WordPress.'
            ));
        }

        $args = self::get_request_args();
        $latest_package = self::get_latest_package();

        /**
         * @todo Validate request's `version` parameter?
         */
        if (isset($args['version']) && ! empty($args['version'])) {
            if (version_compare($args['version'], $latest_package['new_version'], '<')) {
                self::respond(array(
                    'message'      => json_encode($latest_package),
                    'content_type' => 'application/json'
                ));
            } else {
                /**
                 * The client requires all successful returns to have a `200`
                 * status code.
                 */
                self::respond(array(
                    'http_status' => 200,
                    'message'     => 'Theme is up to date.'
                ));
            }
        } else {
            self::respond(array(
                'http_status' => 400,
                'message'     => 'Request missing valid arguments.'
            ));
        }
    }

    /**
     * Get posted request arguments.
     *
     * @return void
     */
    public static function get_request_args() {
        if (isset($_POST['request']) && ! empty($_POST['request'])) {
            $request = json_decode($_POST['request']);

            if (gettype($request) == 'object' || gettype($request) == 'array') {
                return (array) $request;
            }
        }
    }

    /**
     * Get the latest package.
     *
     * @return array|void
     */
    public static function get_latest_package()
    {
        $package = new SC_Theme_Updater_Package();

        $package = $package->get_latest();

        if (! empty($package)) {
            return array(
                'new_version' => $package['version'],
                'package'     => $package['package'],
                'url'         => $package['url']
            );
        }
    }

    /**
     * Return a response for the UA.
     *
     * @param  array $args Parameters for output
     * @return void
     */
    public static function respond($args = array())
    {
        $defaults = array(
            'http_status' => 200,
            'message'     => '',
            'content_type' => 'text/plain'
        );

        $args = array_merge($defaults, $args);

        http_response_code($args['http_status']);

        if (! empty($args['content_type'])) {
            header('Content-type: ' . $args ['content_type']);
        }

        echo $args['message'] . "\n";
        exit;
    }
}
