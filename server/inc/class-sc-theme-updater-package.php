<?php
/**
 * WordPress Theme Updater.
 *
 * @author   Cory Reed <swashcap@gmail.com>
 * @package  WP_Theme_Updater
 */

class SC_Theme_Updater_Package
{
    private static $package_required_keys = array('version', 'file_name');

    public $packages = array();

    public function __construct($args = array())
    {
        if (file_exists(WP_THEME_UPDATER_VERSIONS_FILE)) {
            $versions = json_decode(file_get_contents(WP_THEME_UPDATER_VERSIONS_FILE), true);

            if (isset($versions['packages']) && ! empty($versions['packages'])) {
                $packages = $versions['packages'];

                if (isset($versions['defaults']) && ! empty($versions['defaults'])) {
                    foreach ($packages as $key => &$package) {
                        $package = array_merge($versions['defaults'], $package);

                        if (self::is_valid_package($package)) {
                            $package['package'] = WP_THEME_UPDATER_DOWNLOADS_URL . $package['file_name'];
                        } else {
                            unset($packages[$key]);
                        }
                    }
                }

                usort($packages, array('self', 'package_compare'));

                $this->packages = $packages;
            } else {
                error_log('versions.json has no packages.');
                exit;
            }
        } else {
            error_log('versions.json file required for WP_Theme_Updater_Package');
            exit;
        }
    }

    public function get_latest()
    {
        return array_shift($this->packages);
    }

    private static function is_valid_package($package)
    {
        foreach (self::$package_required_keys as $required_key) {
            if (
                ! isset($package[$required_key]) ||
                 empty($package[$required_key])
            ) {
                return false;
            }
        }

        return true;
    }

    private static function package_compare($a, $b)
    {
        return version_compare($b['version'], $a['version']);
    }
}
