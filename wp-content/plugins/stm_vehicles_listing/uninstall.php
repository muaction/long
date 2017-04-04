<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/*Drop custom post type information*/
global $wpdb, $wp_version;
$posts_table = $wpdb->posts;
$postmeta_table = $wpdb->postmeta;

$wpdb->query("DELETE FROM " . $posts_table . " WHERE post_type = 'listings'");
$wpdb->query( "DELETE meta FROM " . $postmeta_table . " meta LEFT JOIN " . $posts_table . " posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

/*Delete terms*/
$options_list = get_option('stm_vehicle_listing_options');

$taxonomies = array();

foreach($options_list as $option) {
    $taxonomies[] = $option['slug'];
}

if ( version_compare( $wp_version, '4.2', '>=' ) ) {
    foreach ($taxonomies as $taxonomy) {
        $wpdb->delete(
            $wpdb->term_taxonomy,
            array(
                'taxonomy' => $taxonomy,
            )
        );
    }
}

/*Remove options*/
$option_name = 'stm_vehicle_listing_options';
$options_stored = array(
    'stm_vehicle_listing_options',
    'stm_xml_url_tmp',
    'stm_xml_associations_tmp',
    'stm_current_template',
    'stm_xml_templates',
    'stm_enable_cron_automanager',
    'stm_automanager_car_ids',
);

foreach($options_stored as $option) {
    delete_option($option);

    // for site options in Multisite
    delete_site_option($option);
}