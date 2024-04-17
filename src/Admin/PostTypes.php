<?php

namespace NovaStream\CausewayImporter\Admin;

use NovaStream\CausewayImporter\CausewayImporter;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

class PostTypes
{
    private $plugin;

    private $postTypes = [
        'businesses' => [
            'single' => 'Business',
            'multiple' => 'Businesses',
            'base' => 'businesses',
        ],
        'events' => [
            'single' => 'Event',
            'multiple' => 'Events',
            'base' => 'festivals-and-events',
        ],
        'packages' => [
            'single' => 'Package',
            'multiple' => 'Packages',
            'base' => 'packages',
        ],
    ];

    private $taxonomies = [
        // 'Category' => 'Categories',
        // 'Tag' => 'Tags',
        'listing-provider' => [
            'single' => 'Provider',
            'multiple' => 'Providers',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'provider',
        ],
        'listing-type' => [
            'single' => 'Type',
            'multiple' => 'Types',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'types.name',
        ],
        'listing-communities' => [
            'single' => 'Community',
            'multiple' => 'Communities',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'locations.community.name',
        ],
        'listing-counties' => [
            'single' => 'County',
            'multiple' => 'Counties',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'locations.community.county.name',
        ],
        'listing-areas' => [
            'single' => 'Area',
            'multiple' => 'Areas',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'locations.community.areas.name',
        ],
        'listing-regions' => [
            'single' => 'Region',
            'multiple' => 'Regions',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'locations.community.regions.name',
        ],
        // 'listing-featured' => [
        //     'single' => 'Featured',
        //     'multiple' => 'Featured',
        //     'post_type' => [ 'businesses', 'events', 'packages' ],
        //     'admin_column' => true,
        //     'meta_key' => 'is_featured',
        // ],
        'listing-campaigns' => [
            'single' => 'Campaign',
            'multiple' => 'Campaigns',
            'post_type' => [ 'businesses', 'events', 'packages' ],
            'admin_column' => true,
            'meta_key' => 'campaigns.name',
        ],
        // 'listing-sponsored' => [
        //     'single' => 'Sponsored',
        //     'multiple' => 'Sponsored',
        //     'post_type' => [ 'businesses', 'events', 'packages' ],
        //     'admin_column' => true,
        //     'meta_key' => 'is_sponsored',
        // ],
        'businesses_category' => [
            'single' => 'Category',
            'multiple' => 'Categories',
            'post_type' => [ 'businesses' ],
            'admin_column' => true,
            'meta_key' => 'categories.name',
        ],
        'events_category' => [
            'single' => 'Category',
            'multiple' => 'Categories',
            'post_type' => [ 'events' ],
            'admin_column' => true,
            'meta_key' => 'categories.name',
        ],
        'packages_category' => [
            'single' => 'Category',
            'multiple' => 'Categories',
            'post_type' => [ 'packages' ],
            'admin_column' => true,
            'meta_key' => 'categories.name',
        ],
    ];

    public function __construct(CausewayImporter $plugin)
    {
        $this->plugin = $plugin;

        foreach ($this->postTypes as $slug => $data) {
            $this->registerPostType($slug, $data);
        }

        $this->plugin->notice('Post types succesfully registered');

        foreach ($this->taxonomies as $slug => $data) {
            $this->registerTaxonomy($slug, $data);
        }

        $this->plugin->notice('Taxonomies succesfully registered');

        add_filter('use_block_editor_for_post_type', array($this, 'disableGutenberg'), 10, 2);
    }

    public function deactivate()
    {
        return;
    }

    /**
     * Return the post type information
     *
     * @return Array
     */
    public function getPostTypes()
    {
        return $this->postTypes;
    }

    /**
     * Register our post types used for Causeway
     *
     * @param String $slug
     * @param Array $data
     * @return void
     */
    private function registerPostType($slug, $data)
    {
        $listingLabels = [
            'name' => __($data['multiple'], 'novastream-wp-causeway'),
            'singular_name' => __($data['single'], 'novastream-wp-causeway'),
            'menu_name' => __($data['multiple'], 'novastream-wp-causeway'),
            'name_admin_bar' => __($data['multiple'], 'novastream-wp-causeway'),
            'add_new' => __('Add New', 'novastream-wp-causeway'),
            'add_new_item' => __('Add New ' . $data['single'], 'novastream-wp-causeway'),
            'new_item' => __('New ' . $data['single'], 'novastream-wp-causeway'),
            'edit_item' => __('Edit ' . $data['single'], 'novastream-wp-causeway'),
            'view_item' => __('View ' . $data['single'], 'novastream-wp-causeway'),
            'all_items' => __('All ' . $data['multiple'], 'novastream-wp-causeway'),
            'search_items' => __('Search ' . $data['multiple'], 'novastream-wp-causeway'),
            'not_found' => __('No ' . $slug . ' found.', 'novastream-wp-causeway'),
            'not_found_in_trash' => __('No ' . $slug . ' found in Trash.', 'novastream-wp-causeway')
        ];

        $supports = [ 'title', 'editor', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields' ];


        if (post_type_exists($slug)) {
            $this->plugin->notice('Post type {slug} already exists', [ 'slug' => $slug ]);
            return;
        }

        $this->plugin->debug('Registering post type: {slug} ', [ 'slug' => $slug ]);

        $res = register_post_type($slug, [
            'label' => $data['multiple'],
            'labels' => $listingLabels,
            'has_archive' => $slug,
            'supports' => $supports,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'has_archive' => true,
            'rewrite' => [ 'slug' => $data['base'], 'with_front' => false ],
            //'menu_icon' => plugin_dir_url(__FILE__) . '/images/wp_marcato_logo.png',
            'taxonomies' => [],
        ]);

        if (is_wp_error($res)) {
            $this->plugin->error($res);
        }

        register_rest_field(
            $slug,
            'meta',
            [
                'get_callback' => function ($data) {
                    $meta = get_post_meta($data['id'], '', true);

                    // Remove "hidden/internal" metadata that begins with _
                    return array_filter($meta, function ($key) {
                        return $key[0] !== '_';
                    }, ARRAY_FILTER_USE_KEY);
                },
                'update_callback' => function ($data, $object, $fieldName) {
                    if (!is_array($data)) {
                        $data = [ $data ];
                    }

                    // Remove some of the keys that we don't have to store in metadata
                    $data = array_diff_key($data, array_flip($this->plugin->getIgnoreMeta()));

                    foreach ($data as $key => $value) {
                        update_post_meta($object->ID, $key, $value);
                    }

                    return true;
                }
            ]
        );

        add_action('rest_api_init', function () use ($slug) {
            register_rest_route('causeway/v1', '/find/(?P<type>.*)/(?P<slug>.*)/(?P<id>\d+)', [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'findPostId'),
                'permission_callback' => '__return_true',
            ]);

            // register_rest_route('causeway/v1', '/slug/(?P<type>.*)/(?P<slug>.*)', [
            //     'methods' => \WP_REST_Server::READABLE,
            //     'callback' => array($this, 'getPostIdBySlug'),
            //     'permission_callback' => '__return_true',
            // ]);
        });

        return;
    }

    /**
     * Register taxonomy for our custom post types
     *
     * @param String $slug
     * @param Array $data
     * @return void
     */
    public function registerTaxonomy($slug, $data)
    {
        $taxLabels = [
            'name' => _x($data['multiple'], 'Taxonomy General Name', 'novastream-wp-causeway'),
            'singular_name' => _x($data['single'], 'Taxonomy Singular Name', 'novastream-wp-causeway'),
            'menu_name' => __($data['single'], 'novastream-wp-causeway'),
            'all_items' => __('All ' . $data['multiple'], 'novastream-wp-causeway'),
            'parent_item' => __('Parent ' . $data['single'], 'novastream-wp-causeway'),
            'parent_item_colon' => __('Parent '  . $data['single'] . ':', 'novastream-wp-causeway'),
            'new_item_name' => __('New ' . $data['single'], 'novastream-wp-causeway'),
            'add_new_item' => __('Add New ' . $data['single'], 'novastream-wp-causeway'),
            'edit_item' => __('Edit ' . $data['single'], 'novastream-wp-causeway'),
            'update_item' => __('Update ' . $data['single'], 'novastream-wp-causeway'),
            'view_item' => __('View ' . $data['single'], 'novastream-wp-causeway'),
        ];

        if (taxonomy_exists($slug)) {
            $this->plugin->notice('Taxonomy {slug} already exists', [ 'slug' => $slug ]);
            return;
        }

        $this->plugin->debug('Registering taxonomy: {slug} ', [ 'slug' => $slug ]);

        $res = register_taxonomy($slug, $data['post_type'], [
            'labels' => $taxLabels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => $data['admin_column'] ?? false,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'query_var' => $slug,
            'rewrite' => true
        ]);

        if (is_wp_error($res)) {
            $this->plugin->error($res);
        }

        $res = register_taxonomy_for_object_type($slug, $data['post_type']);

        if (is_wp_error($res)) {
            $this->plugin->error($res);
        }

        return;
    }

    /**
     * Get a list of taxonomies
     *
     * @return Array
     */
    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    /**
     * Disable gutenberg editor for our custom post types.
     *
     * @param Bool $currentStatus
     * @param String $postType
     * @return Bool
     */
    public function disableGutenberg($currentStatus, $postType)
    {
        if (in_array($postType, array_keys($this->postTypes))) {
            return false;
        }

        return $currentStatus;
    }

    /**
     * Get the WordPress post ID based on the post_name and post_type from the WP database.
     *
     * @param Array $info
     * @return Int
     */
    public function getPostIdBySlug($info)
    {
        if (!in_array($info['type'], array_keys($this->postTypes))) {
            return new \WP_Error('bad_post_type', 'Invalid Post Type', [ 'status' => 400 ]);
        }

        $args = [
            'post_type' => $info['type'],
            'name' => $info['slug'],
            'posts_per_page' => 1,
            'status' => 'any',
        ];

        $query = new \WP_Query($args);

        $ID = null;
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ID = get_the_ID();
            }
        }

        wp_reset_postdata();

        return $ID;
    }

    /**
     * Get the WordPress post ID based on the meta_key "id" and value provided by Causeway.
     * If it cannot be found, attempt to find the post ID based on the post's slug.
     *
     * @param Array $info
     * @return Int
     */
    public function findPostId($info)
    {
        if (!in_array($info['type'], array_keys($this->postTypes))) {
            return new \WP_Error('bad_post_type', 'Invalid Post Type', [ 'status' => 400 ]);
        }

        $posts = get_posts([
            'post_type' => $info['type'],
            'meta_key' => 'id',
            'meta_value' => (int)$info['id'],
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);


        if (!empty($posts)) {
            return rest_ensure_response((int)$posts[0]->ID);
        }

        $ID = $this->getPostIdBySlug([
            'slug' => $info['slug'],
            'type' => $info['type'],
            'id' => $info['id'],
        ]);

        if (empty($ID)) {
            $this->plugin->warning('Could not find post ID based on causeway ID or slug');
        }

        return rest_ensure_response((int)$ID);
    }
}
