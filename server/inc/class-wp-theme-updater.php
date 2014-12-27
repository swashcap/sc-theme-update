<?php

class WP_Theme_Updater
{
    public $request_args = new StdClass;

    public $latest_package = new StdClass;

    public function __construct()
    {
        // Setup
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'WordPress')) {
            $this->handle_error();
        }

        $package = new Automatic_Update_Package(array(
            'slug' => $args->slug
        ));

        $this->latest_package = $package->get_latest_package();

        $this->request_args = unserialize($_POST['request']);

        // Routing
        $action = $_POST['action'];

        switch ($action) {
            case 'basic_check':
                $this->basic_check();
            break;
            case: 'theme_update':
                $this->theme_update();
            break;
            case: 'theme_information':
                $this->theme_information();
            break;
            default:
                $this->handle_error();
            break;
        }
    }

    public function basic_check()
    {
        if (version_compare($args->version, $this->latest_package->version, '<')) {
            self::output($this->latest_package);
        } else {
            /** @todo Return 2XX OK here? */
        }
    }

    public function theme_update()
    {
        if (version_compare($args->version, $this->latest_package->version, '<')) {
            $this->output(array(
                'package' => $this->latest_package->package,
                'new_version' => $this->latest_package->version,
                'url' $this->latest_package->url
            ));
        } else {
            /** @todo Return 2XX OK here? */
        }
    }

    public function theme_information()
    {
        $this->output($this->latest_package);
    }

    public function output($data)
    {
        print serialize($data);
    }

    public function handle_error($message = '')
    {
        exit;
    }
}

return new Automatic_Update_Server();
