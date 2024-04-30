<?php

namespace NovaStream\CausewayImporter\Admin;

use NovaStream\CausewayImporter\CausewayImporter;
use RRule\RRule;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * ImportFeed class for a cronjob style import to make sure data is updated daily.
 * This is a fallback to the REST API that should instanteously update the listing
 * once the author saves it on the Causeway backend.
 */
class ImportFeed
{
    private $plugin;
    private $json;
    private $cronHandle = 'cron_import_causeway';
    private $timezone;

    public function __construct(CausewayImporter $plugin)
    {
        $this->plugin = $plugin;
        $this->json = null;

        $this->timezone = new \DateTimeZone('America/Glace_Bay');

        add_action($this->cronHandle, array($this, 'import'));

        // 13:00:00 UTC is 10am Atlantic
        if (!wp_next_scheduled($this->cronHandle)) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', $this->cronHandle);
        }
    }

    /**
     * Download the JSON feed to memory to loop through it.
     *
     * @throws Exception
     * @return Bool
     */
    public function download()
    {
        try {
            $key = $this->plugin->getOption('key');
            $url = sprintf('%s/%s', $this->plugin->getEndpointUrl(), $key);
            $this->plugin->notice("Downloading from $url");

            if (false === ($body = get_transient('causeway_data'))) {
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'Accept' => 'application/json',
                    ),
                    'timeout' => MINUTE_IN_SECONDS,
                    'sslverify' => false,
                ));

                if (is_wp_error($response)) {
                    $this->plugin->error($response);
                    return false;
                }

                $statusCode = wp_remote_retrieve_response_code($response);

                if ($statusCode !== \WP_Http::OK) {
                    $this->plugin->error("Invalid HTTP status code {$statusCode} received. Check API Key.");
                    return false;
                }
                $body = json_decode($response['body'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->plugin->error('Malformed JSON recieved. Check URL in Causeway settings.');
                    return false;
                }

                set_transient('causeway_data', $body, HOUR_IN_SECONDS);

                $this->plugin->debug('Saved response as transient');
            } else {
                $this->plugin->notice('Using data from cache');
            }


            $this->plugin->notice("Successfully recieved the feed. Saved as transient.");
            $this->json = $body;
        } catch (\Exception $e) {
            $this->plugin->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Begin the import process
     *
     * @return Bool
     */
    public function import()
    {
        $count = 0;
        $this->download();

        if (!is_array($this->json)) {
            $this->plugin->error("Invalid JSON stored");
            return false;
        }

        $oldTimeLimit = ini_get('max_execution_time');
        $oldMemoryLimit = ini_get('memory_limit');
        //ini_set('memory_limit', '512M');
        set_time_limit(0);

        $activePostIds = [];
        $this->plugin->notice('Generating posts...');
        foreach ($this->json['listings'] as $listing) {
            $count++;
            $activePostIds[] = $this->generatePost($listing);
            gc_collect_cycles();
        }

        $this->plugin->notice('Deleting unused posts...');

        $deleted = $this->deleteMissingPosts($activePostIds);

        $this->plugin->displayMemoryUsage();

        ini_set('memory_limit', $oldMemoryLimit);
        ini_set('max_execution_time', $oldTimeLimit);

        $this->plugin->notice("Finished importing $count listing(s) from Causeway.");
        $this->plugin->notice("Deleted $deleted listing(s) from WordPress.");

        return true;
    }

    /**
     * Generate the actual post
     *
     * @param Array $listing
     * @throws Exception
     * @return Int
     */
    public function generatePost($listing)
    {
        $post = [];

        foreach ($listing['types'] as $type) {
            if ($type['name'] == 'Accommodation' || $type['name'] == 'Experience') {
                $post['post_type'] = 'businesses';
                break;
            } elseif ($type['name'] == 'Event') {
                $post['post_type'] = 'events';
                break;
            } elseif ($type['name'] == 'Package') {
                $post['post_type'] = 'packages';
                break;
            }
        }

        if (empty($post['post_type'])) {
            $this->plugin->warning(
                "Post type must not be empty for {$listing['name']}",
                [
                    'types' => $listing['types']
                ]
            );
            return;
        }

        // Find the Post ID based on the Causeway ID or slug
        $id = $this->findIdByMeta((int)$listing['id'], $listing['slug'], $post['post_type']);

        $meta = array_diff_key(
            $listing,
            array_flip($this->plugin->getIgnoreMeta()),
            array_flip($this->plugin->getIgnoreMetaAcf())
        );

        if (!is_null($id)) {
            $isNew = false;
            $post['ID'] = $id;
        } else {
            $isNew = true;
            unset($post['ID']);
        }

        $post['comment_status'] = 'closed';
        $post['ping_status'] = 'closed';
        $post['post_title'] = $listing['name'];
        $post['post_name'] = $listing['slug'];
        $post['post_content'] = $listing['description'];
        $post['post_date_gmt'] = $listing['created_at'];
        $post['post_modified_gmt'] = $listing['updated_at'];
        $post['post_category'] = [];
        $post['tax_input'] = [];
        $post['post_status'] = 'publish';//($listing['status'] == 'Published' ? 'publish' : 'draft');

        $relatedListings = $meta['related'];
        foreach ($meta['categories'] as $category) {
            if (($cat = get_category_by_slug(sanitize_title($category['name']))) === false) {
                $cat = wp_create_category($category['name']);
            }

            $post['post_category'][] = $cat;
        }
        unset($meta['categories']);
        unset($meta['related']);


        //echo '<pre>';

        foreach ($this->plugin->getPostTypes()->getTaxonomies() as $tax => $data) {
            if (!isset($post['tax_input'][$tax])) {
                $post['tax_input'][$tax] = [];
            }

            $metaKeys = explode('.', $data['meta_key']);
            $totalKeys = count($metaKeys);

            $temp = &$listing;

            // Convert blah.example.key into a multi-dimensional array to access the data
            for ($i = 0; $i < $totalKeys; $i++) {
                if (is_array($temp) && array_keys(array_keys($temp)) === array_keys($temp)) {
                    // Is a numeric array
                    foreach ($temp as $idx => $v) {
                        if ($i == $totalKeys - 1) {
                            $post['tax_input'][$tax][] = $temp[$idx][$metaKeys[$i]];
                        } else {
                            $temp = &$temp[$idx][$metaKeys[$i]];
                        }
                    }
                } elseif (is_array($temp) && array_keys(array_keys($temp)) !== array_keys($temp)) {
                    // Is a assoc array
                    if ($i == $totalKeys - 1) {
                        $post['tax_input'][$tax][] = $temp[$metaKeys[$i]];
                    } else {
                        $temp = &$temp[$metaKeys[$i]];
                    }
                }
            }
            unset($metaKeys, $totalKeys);
        }
        unset($data, $tax, $temp, $i);

        if (!$isNew && have_rows('event_schedule', $id)) {
            delete_field('event_schedule', $id);
        }

        $post['ID'] = $id = wp_insert_post($post, true);
        if (is_wp_error($id) || $id === 0) {
            $this->plugin->warning(sprintf(
                'Error occurred during post %s (%s), creation: %s',
                $post['post_title'],
                $id,
                $id->get_error_message()
            ));
            return false;
        }

        foreach ($post['tax_input'] as $tax => $terms) {
            wp_set_object_terms($id, $terms, $tax, false);
        }

        foreach ($meta as $key => $value) {
            if (!is_array($value)) {
                $value = strval($value);
            }

            update_post_meta($id, $key, $value);
        }
        unset($meta, $key, $value);

        if ($post['post_type'] == 'events') {
            $this->plugin->notice("Generated {$post['post_type']} $id - {$post['post_title']}");
        }

        $this->updateAcfFields($id, $post['post_type'], $listing, $relatedListings);

        unset($post, $listing);


        return $id;
    }

    /**
     * Update ACF Fields based on the listing's metadata
     *
     * Meta keys that are currently not in use by Verb's ACF group:
     *   - parent_id
     *   - external_id (like NovaScotia.com ID)
     *   - provider (Causeway/NovaScotia.com/etc)
     *   - contact_name
     *   - tripadvisor_url
     *   - tripadvisor_rating_url
     *   - tripadvisor_count
     *
     * Meta keys not used by Causeway
     *   - patio_lantern
     *   - tunes_town
     *   - date_description
     *   - excerpt
     * @param Int $id
     * @param String $postType
     * @param Array $listing
     * @return void
     */
    private function updateAcfFields($id, $postType, $listing, $relatedListings)
    {
        if (!class_exists('ACF')) {
            return;
        }

        update_field('description', $listing['description'], $id);
        update_field('highlights', $listing['highlights'], $id);
        update_field('telephone_1', $listing['phone_primary'], $id);
        update_field('telephone_2', $listing['phone_secondary'], $id);
        update_field('telephone_off_season', $listing['phone_offseason'], $id);
        update_field('telephone_toll_free', $listing['phone_tollfree'], $id);
        update_field('email', $listing['email'], $id);
        // Not used by Causeway
        //update_field('fax', '', $id);


        // Only for packages
        if ($postType === 'packages') {
            update_field('package_date', $listing['activated_at'], $id);
            update_field('package_date_expire', $listing['expired_at'], $id);
            update_field('package_price', $listing['price'], $id);
        } elseif ($postType === 'events') {
            update_field('admission_price', $listing['price'], $id);
        }

        update_field('tripadvisor_id', $listing['tripadvisor_id'], $id);
        // Using Causeway's ID not NovaScotia.com's ID
        update_field('product_id', $listing['id'], $id);

        // Not used by Causeway
        //update_field('product_type', '', $id);
        update_field('featured', strval($listing['is_featured']), $id);
        update_field('featured_acadian_cuisine', strval($listing['is_acadian_cuisine']), $id);
        update_field('open_for_business', $listing['is_clean_it_right'], $id);
        // Not used by Causeway
        //update_field('closed_for_business', false, $id);

        if (!empty($listing['locations']) && is_array($listing['locations'])) {
            foreach ($listing['locations'] as $location) {
                update_field('feed_region', $location['community']['regions'][0]['name'], $id);
                update_field('feed_community', $location['community']['name'], $id);
                update_field('address', $location['civic_address'], $id);
                update_field('province', $location['state'], $id);
                update_field('postal_code', $location['postal_code'], $id);
                update_field('latitude', $location['latitude'], $id);
                update_field('longitude', $location['longitude'], $id);

                if ($postType === 'events') {
                    update_field('venue', $location['name'], $id);
                }
            }
        }
        unset($listing['locations'], $location);

        if (!empty($listing['dates']) && is_array($listing['dates'])) {
            global $EventsManager;
            usort($listing['dates'], function ($a, $b) {
                return $a['start_at'] <=> $b['start_at'];
            });
            $dates = [];

            foreach ($listing['dates'] as $date) {
                $dtStart = new \DateTime($date['start_at']);
                $start = $dtStart->setTimeZone($this->timezone)->format('Y-m-d H:i:s');

                $dtEnd = $end = null;
                if (!empty($date['end_at'])) {
                    $dtEnd = new \DateTime($date['end_at']);
                    $end = $dtEnd->setTimeZone($this->timezone)->format('Y-m-d H:i:s');
                }

                if (!empty($date['rrule'])) {
                    $rrule = new RRule($date['rrule']);
                    $row = array(
                        'add_or_exclude_date' => true,
                        'start_date' => $start,
                        'repeating_date' => true,
                        'end_date' => $dtEnd ? $end : null,
                        'repeat_interval' => $rrule->getRule()['INTERVAL'],
                        'repeat_frequency' => $rrule->getRule()['FREQ'],
                    );
                } else {
                    $row = array(
                        'add_or_exclude_date' => true,
                        'start_date' => $start,
                        'repeating_date' => false,
                        'end_date' => $dtEnd ? $end : null,
                    );
                }

                add_row('event_schedule', $row, $id);
            }

            $post = new \stdClass();
            $post->ID = $id;
            $post->post_type = $postType;
            $EventsManager->saveRepeatingEventData($id, $post, true);
        }
        unset($date);

        if (!empty($listing['websites']) && is_array($listing['websites'])) {
            foreach ($listing['websites'] as $website) {
                switch ($website['type']['name']) {
                    case 'General':
                        update_field('website', $website['url'], $id);
                        break;
                    case 'Facebook':
                        update_field('facebook', $website['url'], $id);
                        break;
                    case 'YouTube':
                        update_field('youtube', $website['url'], $id);
                        break;
                    case 'Instagram':
                        update_field('instagram', $website['url'], $id);
                        break;
                    case 'Twitter / X':
                        update_field('twitter', $website['url'], $id);
                        break;
                    case 'Short Term Rental':
                        //update_field('facebook', $website['url'], $id);
                        break;
                    default:
                        break;
                }
            }
        }
        unset($website);

        if (!empty($listing['attachments']) && is_array($listing['attachments'])) {
            usort($listing['attachments'], function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });
            $attachments = [];
            foreach ($listing['attachments'] as $attachment) {
                $attachments[] = $attachment['url'];
            }

            update_field('product_images', join(',', $attachments), $id);

            unset($attachment, $attachments);
        }

        if (!empty($relatedListings) && is_array($relatedListings)) {
            $relatedIds = [];
            foreach ($relatedListings as $related) {
                $relatedIds[] = $this->findIdByMeta($related['id'], $related['slug'], 'businesses');
            }

            $acfKey = ($postType === 'packages' ? 'affiliated_businesses' : 'bus_affiliated_businesses');
            update_field($acfKey, $relatedIds, $id);
        }
        unset($acfKey, $relatedIds, $relatedListings);

        return;
    }

    /**
     * Delete any posts that exist in WordPress that no longer exist on Causeway.
     *
     * @param Array $knownPostIds
     * @return Int
     */
    public function deleteMissingPosts($knownPostIds)
    {
        global $wpdb;

        $count = 0;

        // Get all known post_id for posts that have a "id" meta value.
        $metadata = $wpdb->get_results(
            "SELECT DISTINCT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = 'id'"
        );

        $postIds = array();
        if (!empty($metadata)) {
            foreach ($metadata as $meta) {
                $postIds[] = (int)$meta->post_id;
            }
        }

        // Cleanup both arrays
        $knownPostIds = array_unique(array_filter($knownPostIds), SORT_NUMERIC);
        $postIds = array_unique(array_filter($postIds), SORT_NUMERIC);

        // Get the difference of the known post IDs compared to the full list
        $deletablePostIds = array_diff($postIds, $knownPostIds);

        // Delete each post ID and attachment that isn't found in the known post IDs.
        foreach ($deletablePostIds as $postId) {
            $this->plugin->notice("Deleting post ID $postId");
            $attachmentId = get_post_thumbnail_id($postId);
            delete_post_thumbnail($postId);
            wp_delete_attachment($attachmentId, true);
            $res = wp_delete_post($postId, true);

            if ($res instanceof \WP_Post) {
                $count++;
            } else {
                $this->plugin->notice("Error deleting $postId $res");
            }
        }

        return $count;
    }

    /**
     * Get ID by meta
     *
     * @param mixed $id
     * @param string $slug
     * @param string $postType
     * @return void
     */
    public function findIdByMeta($id, $slug, $postType)
    {
        global $wpdb;

        $meta = $wpdb->get_row(
            "SELECT `post_id`
                FROM $wpdb->postmeta
                WHERE `meta_key` = 'id'
                AND `meta_value` = '{$id}'"
        );

        if (!is_null($meta)) {
            //$this->plugin->notice("Found $postType ID by meta with $id...");
            return $meta->post_id;
        }


        $ID = $this->plugin->getPostTypes()->getPostIdBySlug([
            'slug' => $slug,
            'type' => $postType,
        ]);

        if (is_wp_error($ID)) {
            $this->plugin->notice("Could not find $postType ID by meta with $id or slug $slug...");
            return null;
        }

        //$this->plugin->notice("Found $postType ID by slug {$slug}...");
        return $ID;
    }

    /**
     * Cleanup on deactivation
     *
     * @return void
     */
    public function deactivate()
    {
        wp_clear_scheduled_hook($this->cronHandle);
    }
}
