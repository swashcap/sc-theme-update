<?php

class WP_Theme_Updater_Package
{
    public static $versions_file = 'versions.json';


    public static $valid_package_properties = array('author', 'name', 'screenshot_url', 'url', 'version', 'date', 'file_name', 'requires', 'tested');

    public $versions = array();

    public function __construct($args = array())
    {
        $defaults = array(
            'slug' => ''
        );

        $args = array_merge($defaults, $args);

        if (! isset($args['slug']) || empty($args['slug'])) {
            error_log('Theme slug required for Automatic_Update_Package');
            exit;
        }

        if (file_exists(self::$versions_file)) {
            $this->versions = json_decode(self::$versions_file, TRUE);
        } else {
            error_log('versions.json file required for Automatic_Update_Package');
            exit;
        }
    }

    public function get_latest_package()
    {
        $defaults = array();
        $latest_version = array();
        $package = new StdClass;

        if (isset($this->versions['defaults'])) {
            $defaults = $this->versions['defaults'];
        }
        if (isset($this->versions['versions'])) {
            $latest_version = array_shift($this->versions['versions']);
        }

        $latest_version = array_merge($defaults, $latest_version);

        if (! count($latest_version)) {
            error_log('versions.json has no latest version for Automatic_Update_Package');
            exit;
        }

        foreach (self::$valid_package_properties as $key) {
            if (isset($latest_version[$key]) && ! empty($latest_version[$key]) {
                $package->{$key} = $latest_version_key;
            }
        }

        $package->package = $this->get_package($package->file_name);

        return $package;
    }

    public function get_package($file_name = '')
    {
        $package = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $package .= 'download.php?key=';
        $package .= md5($file_name . mktime(0, 0, 0, date('n'), date('j'), date('Y')));

        return $package;
    }
}
