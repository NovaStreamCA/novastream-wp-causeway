<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Cleanup any left over data from our plugin.
  * These field keys are defined elsewhere.
 *
 * Scheduled hook is in /src/Admin/ImportFeed.php
 * Transient is stored in/src/Admin/ ImportFeed.php
 * Option key is in novastream-wp-causeway.php
 */
wp_clear_scheduled_hook('cron_import_causeway');
delete_transient('causeway_data');
delete_option('novastream-causeway-options');
