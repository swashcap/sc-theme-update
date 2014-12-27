<?php

require_once 'class-wp-theme-updater-package.php';

class WP_Theme_Updater
{
    public $request_args = array();

    public $latest_package;

    public function __construct()
    {
        $action = '';
        $request_slug = '';
        $request_version = '';

        /**
         * WordPress uses an internal function to request theme updates. Ensure
         * traffic comes exclusively from WordPress by checking the `USER_AGENT`
         * header.
         */
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'WordPress')) {
            $this->respond(array(
                'http_status' => 406,
                'message'     => 'Requests must be made from WordPress.'
            ));
        }

        /** Process the posted `request` */
        if (isset($_POST['request']) && ! empty($_POST['request'])) {
            $request_slug = $this->get_request_slug($_POST['request']);
            $request_version = $this->get_request_version($_POST['request']);

            if (! empty($request_slug) && ! empty($request_version)) {
                $this->request_args['slug'] = $request_slug;
                $this->request_args['version'] = $request_version;
            } else {
                $this->respond(array(
                    'http_status' => 400,
                    'message'     => 'Improper posted request.'
                ));
            }
        } else {
            $this->respond(array(
                'http_status' => 400,
                'message'     => 'Posted request required.'
            ));
        }

        /** Retrieve the latest package and keep it on this class. */
        $package = new WP_Theme_Updater_Package(array(
            'slug' => $this->request_args['slug']
        ));
        $this->latest_package = $package->get_latest_package();

        /** Process the incoming request based on its `action` parameter */
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
        }

        switch ($action) {
            case 'basic_check':
                $this->basic_check();
            break;
            case 'theme_update':
                $this->theme_update();
            break;
            case 'theme_information':
                $this->theme_information();
            break;
            default:
                $this->respond(array(
                    'http_status' => 400,
                    'message'     => 'Specify a valid action.'
                ));
            break;
        }
    }

    public function basic_check()
    {
        if (version_compare($this->request_args['version'], $this->latest_package->version, '<')) {
            $this->respond(array(
                'http_status' => 200,
                'message'     => serialize($this->latest_package)
            ));
        } else {
            $this->respond(array(
                'http_status' => 304,
                'message'     => 'Your theme is up to date.'
            ));
        }
    }

    public function theme_update()
    {
        if (version_compare($this->request_args['version'], $this->latest_package->version, '<')) {
            $data = array(
                'package'     => $this->latest_package->package,
                'new_version' => $this->latest_package->version,
                'url'         => $this->latest_package->url
            );

            $this->respond(array(
                'http_status' => 200,
                'message'     => serialize($data)
            ));
        } else {
            $this->respond(array(
                'http_status' => 304,
                'message'     => 'Your theme is up to date.'
            ));
        }
    }

    public function theme_information()
    {
        $this->respond(array(
            'http_status' => 200,
            'message'     => serialize($this->latest_package)
        ));
    }

    public function get_request_slug($post_request)
    {
        $matches;
        $slug_pattern = '/s:4:"slug";s:\d+:"(\w+)";/';

        preg_match($slug_pattern, $post_request, $matches);

        if (count($matches) >= 2) {
            return $matches[1];
        }
    }

    public function get_request_version($post_request)
    {
        $matches;
        $version_pattern = '/s:7:"version";s:\d+:"([\d\.]+)";/';

        preg_match($version_pattern, $post_request, $matches);

        if (count($matches) >= 2) {
            return $matches[1];
        }
    }

    public function respond($args = array())
    {
        $defaults = array(
            'http_status' => 404,
            'message'     => 'Not found.'
        );

        $args = array_merge($defaults, $args);

        http_response_code($args['http_status']);
        echo $args['message'];
        exit;
    }
}
