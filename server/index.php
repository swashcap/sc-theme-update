<?php

define('WP_THEME_UPDATER_VERSIONS_FILE', __DIR__ . '/versions.json');

require_once 'inc/class-wp-theme-updater-package.php';

require_once 'inc/class-wp-theme-updater-download.php';

require_once 'inc/class-wp-theme-updater.php';

$test = new WP_Theme_Updater();
