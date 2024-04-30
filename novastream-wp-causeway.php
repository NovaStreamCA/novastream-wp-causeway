<?php

/**
 * Causeway 5.0 WordPress Importer
 *
 * @package           PluginPackage
 * @author            Matt Lewis
 * @copyright         2024 NovaStream Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       Causeway 5.0 WordPress Importer
 * Plugin URI:        https://causewayapp.ca
 * Description:       Import approved listings from causewayapp.ca backend using REST API to display on your website.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.1
 * Author:            NovaStream Inc.
 * Author URI:        https://novastream.ca
 * Text Domain:       novastream-wp-causeway
 */

namespace NovaStream\CausewayImporter;

use NovaStream\CausewayImporter\Admin\Settings;
use NovaStream\CausewayImporter\Admin\PostTypes;
use NovaStream\CausewayImporter\Admin\ImportFeed;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require __DIR__ . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

class CausewayImporter
{
    private $version = '1.0.5';
    private $slug = 'novastream-wp-causeway';
    private $longName = 'Causeway 5.0 WordPress Importer';
    private $shortName = 'Causeway';
    private $defaultEndpointUrl = 'https://causewayapp.ca/export';
    private $githubRepository = 'novastream-wp-causeway';
    private $githubOrganization = 'NovaStreamCA';
    private $optionKey = 'novastream-causeway-options';
    private $postTypes;
    private $importFeed;
    protected $options = [];
    private $baseMemory = 0;

    public function __construct()
    {
        $this->baseMemory = memory_get_usage();
        register_activation_hook(__FILE__, array($this, 'onActivate'));
        register_deactivation_hook(__FILE__, array($this, 'onDeactivate'));

        add_action('init', array($this, 'onInit'));


        if (is_admin()) {
            new Settings($this);
        }
        return;
    }

    /**
     * Get the long-form name of the plugin
     *
     * @return String
     */
    public function getName()
    {
        return $this->longName;
    }

    /**
     * Get the short-form name of the plugin
     *
     * @return String
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Get the slug of the plugin
     *
     * @return String
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get the default endpoint URL from Causewayapp.ca
     *
     * @return String
     */
    public function getDefaultEndpointUrl()
    {
        return $this->defaultEndpointUrl;
    }

    /**
     * Get the saved endpoint URL
     *
     * @return String
     */
    public function getEndpointUrl()
    {
        return rtrim($this->getOption('url') ?? $this->getDefaultEndpointUrl(), '/');
    }

    /**
     * Get current version of the plugin
     *
     * @return String
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get all option configured by our settings
     *
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get a single option
     *
     * @return mixed
     */
    public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }

        if (empty($this->options[$key])) {
            return null;
        }

        return $this->options[$key];
    }

    /**
     * Set the options
     *
     * @param Array $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Return the value of the key used for get_options()
     *
     * @return String
     */
    public function getOptionKey()
    {
        return $this->optionKey;
    }

    /**
     * Load the current settings
     *
     * @return void
     */
    public function loadOptions()
    {
        $options = get_option($this->getOptionKey(), []);
        $this->setOptions($options);

        return;
    }

    /**
     * Save the new settings
     *
     * @return void
     */
    public function save()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'novastream-wp-causeway'));
        }

        $options = [
            'url' => $_POST['causeway_url'],
            'key' => $_POST['causeway_key'],
        ];
        update_option($this->getOptionKey(), $options);

        $this->loadOptions();
        return;
    }

    /**
     * Retrieve the importer class
     *
     * @return object
     */
    public function getImporter()
    {
        return $this->importFeed;
    }

    /**
     * Retrieve the post type class
     *
     * @return object
     */
    public function getPostTypes()
    {
        return $this->postTypes;
    }

    /**
     * Get a list of Causeway listing keys that we do not have to store as meta
     *
     * @return Array
     */
    public function getIgnoreMeta()
    {
        return [
            'name',
            'status',
            'slug',
            'description',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get a list of Causeway listing keys that we use ACF instead of meta instead for
     *
     * @return Array
     */
    public function getIgnoreMetaAcf()
    {
        return [
            'highlights',
            'phone_primary',
            'phone_secondary',
            'phone_offseason',
            'phone_tollfree',
            'email',
            'activated_at',
            //'expired_at',
            'price',
            'tripadvisor_id',
            // Still use "id" to locate posts
            //'id',
            'is_featured',
            'is_acadian_cuisine',
            'is_clean_it_right'
        ];
    }

    /**
     * Configure anything that needs to be done on plugin activation
     *
     * @return void
     */
    public function onActivate()
    {
        flush_rewrite_rules();

        return;
    }

    /**
     * Configure anything that needs to be done on plugin deactivation
     *
     * @return void
     */
    public function onDeactivate()
    {
        $this->importFeed->deactivate();
        $this->postTypes->deactivate();

        flush_rewrite_rules();

        return;
    }

    /**
     * Executes on WordPress init action
     *
     * @return void
     */
    public function onInit()
    {
        $this->postTypes = new PostTypes($this);
        $this->importFeed = new ImportFeed($this);
        $this->loadOptions();


        add_action('send_headers', array($this, 'addHeaderForExpiringListings'));
        add_action('pre_get_posts', array($this, 'hideExpiredListings'));

        $updater = new GitHubUpdater(__FILE__);
        $updater->set_username($this->githubOrganization)->set_repository($this->githubRepository);
        $updater->initialize();

        return;
    }

    /**
     * Get the expiry date of a listing based on their expired_at meta or their repeater fields
     *
     * @param Int $id
     * @return Bool|\DateTime
     */
    private function getExpiryDate($id)
    {
        $dtExpiry = null;
        $timezone = new \DateTimeZone('America/Glace_Bay');
        $expiryDate = get_post_meta($id, 'expired_at', true);

        if (!empty($expiryDate)) {
            $dtExpiry = (new \DateTime($expiryDate))->setTimezone($timezone);
            return $dtExpiry;
        }

        $repeater = (array)get_field('event_schedule', $id);

        // We already store the dates in local timezone, so no need to adjust it again.
        $endDates = array_filter(array_map(function ($value) use ($timezone) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $value['end_date'], $timezone);
        }, $repeater), 'is_object');

        if (empty($repeater) || empty($endDates)) {
            return false;
        }

        // Sort it again just in case it was modified.
        usort($endDates, function ($a, $b) {
            return $a <=> $b;
        });

        $dtExpiry = max($endDates);

        if ($dtExpiry) {
            return $dtExpiry->setTimezone($timezone);
        }

        return false;
    }

    /**
     * Check if a listing is considered expired
     *
     * @param Int $id
     * @param \DateTime $time
     * @return boolean
     */
    private function isExpired($id, $time = null)
    {
        $timezone = new \DateTimeZone('America/Glace_Bay');

        if (!$time) {
            $time = new \DateTime('now', $timezone);
        }

        $expiry = $this->getExpiryDate($id);

        if ($expiry && $expiry < $time) {
            return true;
        }

        return false;
    }


    /**
     * Remove expired listings from the query results
     *
     * @param \WP_Query $query
     * @return void
     */
    public function hideExpiredListings($query)
    {
        return $query;
    }

    /**
     * Let web crawlers know when an event will be considered over/expired
     *
     * @param WP $wp
     * @return WP
     */
    public function addHeaderForExpiringListings($wp)
    {
        // if (!is_singular('events')) {
        //     return $wp;
        // }

        $format = 'd M Y H:i:s T';
        $expiryDate = $this->getExpiryDate(get_the_ID());

        if ($expiryDate) {
            $formattedDate = $expiryDate->format($format);
            header('X-Robots-Tag: unavailable_after: ' . $formattedDate);
        }

        return $wp;
    }

    /**
     * Display debug message to WP_CLI and to Query Monitor
     *
     * @param String $message
     * @param array $context
     * @return void
     */
    public function debug($message, $context = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            do_action('qm/debug', $message, $context);
        }

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::debug($message);
        }

        return;
    }

    /**
     * Display notice message to Query Monitor and WP_CLI
     *
     * @param String $message
     * @param array $context
     * @return void
     */
    public function notice($message, $context = [])
    {
        //if (defined('WP_DEBUG') && WP_DEBUG) {
            do_action('qm/notice', $message, $context);
        //}

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::log($message);
        }

        return;
    }

    /**
     * Display warning message to Query Monitor and WP_CLI
     *
     * @param String $message
     * @param array $context
     * @return void
     */
    public function warning($message, $context = [])
    {
        do_action('qm/warning', $message, $context);

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::warning($message);
        }

        return;
    }

    /**
     * Display error message to Query Monitor and WP_CLI
     *
     * @param String $message
     * @param array $context
     * @return void
     */
    public function error($message, $context = [])
    {
        //if (defined('WP_DEBUG') && WP_DEBUG) {
            do_action('qm/error', $message, $context);
        //}

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::error($message);
        }

        return;
    }

    /**
     * Display the difference of memory usage to WP CLI when using --debug flag.
     *
     * @return void
     */
    public function displayMemoryUsage()
    {
        $usage = memory_get_usage() - $this->baseMemory;
        $peak = memory_get_peak_usage() - $this->baseMemory;

        $this->debug(sprintf('Using %s of memory. Peak usage: %s', size_format($usage), size_format($peak)));

        return;
    }
}


// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
$causeway = new CausewayImporter();
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols
