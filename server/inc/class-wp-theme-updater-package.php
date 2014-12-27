<?php

class WP_Theme_Updater_Package
{
    public static $valid_package_properties = array('author', 'name', 'screenshot_url', 'url', 'version', 'date', 'file_name', 'requires', 'tested');

    public $versions = array();

    public function __construct($args = array())
    {
        $defaults = array(
            'slug' => ''
        );

        $args = array_merge($defaults, $args);

        if (! isset($args['slug']) || empty($args['slug'])) {
            error_log('Theme slug required for WP_Theme_Updater_Package');
            exit;
        }

        if (file_exists(WP_THEME_UPDATER_VERSIONS_FILE)) {
            $this->versions = json_decode(file_get_contents(WP_THEME_UPDATER_VERSIONS_FILE), true);
        } else {
            error_log('versions.json file required for WP_Theme_Updater_Package');
            exit;
        }
    }

    public function get_latest_package()
    {
        $defaults = array();
        $latest_package = array();
        $data = new stdClass;

        if (isset($this->versions['defaults'])) {
            $defaults = $this->versions['defaults'];
        }
        if (isset($this->versions['packages'])) {
            $latest_package = array_shift($this->versions['packages']);
        }

        $latest_package = array_merge($defaults, $latest_package);

        if (! count($latest_package)) {
            error_log('versions.json has no latest package for WP_Theme_Updater_Package');
            exit;
        }

        foreach (self::$valid_package_properties as $key) {
            if (isset($latest_package[$key]) && ! empty($latest_package[$key])) {
                $data->{$key} = $latest_package[$key];
            } else {
                /** @todo Handle errors */
            }
        }

        $data->package = $this->get_package($data->file_name);

        return $data;
    }

    public function get_package($file_name = '')
    {
        $package = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $package .= 'download.php?key=';
        $package .= md5($file_name . mktime(0, 0, 0, date('n'), date('j'), date('Y')));

        return $package;
    }
}
