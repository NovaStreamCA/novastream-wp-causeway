<?php

namespace NovaStream\CausewayImporter\Admin;

use NovaStream\CausewayImporter\CausewayImporter;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

class Settings
{
    private $plugin;

    public function __construct(CausewayImporter $plugin)
    {
        $this->plugin = $plugin;

        add_action('admin_menu', array($this, 'addMenu'));
    }

    /**
     * Add the menu item to the admin bar
     *
     * @return void
     */
    public function addMenu()
    {
        add_menu_page(
            __($this->plugin->getShortName(), 'novastream-wp-causeway'),
            $this->plugin->getShortName(),
            'edit_posts',
            $this->plugin->getSlug(),
            array($this, 'displaySettings'),
            plugin_dir_url(__FILE__) . 'public/images/logo.png',
            6
        );
    }

    /**
     * Display the settings page
     *
     * @return void
     */
    public function displaySettings()
    {
        global $wpdb;

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'novastream-wp-causeway'));
        }

        $options = $this->plugin->getOptions();

        include(sprintf('%s/html/settings.php', dirname(__FILE__)));
    }
}
