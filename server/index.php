<?php

define('WP_THEME_UPDATER_VERSIONS_FILE', __DIR__ . '/versions.json');
define('WP_THEME_UPDATER_DOWNLOADS_URL', 'http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']) . 'downloads/');

require_once './inc/class-wp-theme-updater.php';
require_once './inc/class-wp-theme-updater-package.php';

$server = new WP_Theme_Updater();
$server::init();
