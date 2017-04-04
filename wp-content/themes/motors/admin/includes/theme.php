<?php


/*Redirect to theme Welcome screen*/
global $pagenow;

if (is_admin() && 'themes.php' == $pagenow && isset($_GET['activated'])) {
    wp_redirect(admin_url("admin.php?page=stm-admin"));
}

/*Theme info*/
function stm_get_theme_info()
{
    $theme = wp_get_theme();
    $theme_name = $theme->get('Name');
    $theme_v = $theme->get('Version');

    $theme_info = array(
        'name' => $theme_name,
        'slug' => sanitize_file_name(strtolower($theme_name)),
        'v' => $theme_v,
    );

    return $theme_info;
}

function stm_beautify_theme_response($theme)
{
    return array(
        'id' => $theme['id'],
        'name' => (!empty($theme['wordpress_theme_metadata']['theme_name']) ? $theme['wordpress_theme_metadata']['theme_name'] : ''),
        'author' => (!empty($theme['wordpress_theme_metadata']['author_name']) ? $theme['wordpress_theme_metadata']['author_name'] : ''),
        'version' => (!empty($theme['wordpress_theme_metadata']['version']) ? $theme['wordpress_theme_metadata']['version'] : ''),
        'url' => (!empty($theme['url']) ? $theme['url'] : ''),
        'author_url' => (!empty($theme['author_url']) ? $theme['author_url'] : ''),
        'thumbnail_url' => (!empty($theme['thumbnail_url']) ? $theme['thumbnail_url'] : ''),
        'rating' => (!empty($theme['rating']) ? $theme['rating'] : ''),
    );
}

function stm_get_token()
{
    $token = get_option('envato_market', array());
    $return_token = '';
    if (!empty($token['token'])) {
        $return_token = $token['token'];
    }

    return $return_token;
}

function stm_check_token($args = array())
{

    $has_token = get_site_transient('stm_theme_token_added');

    $purchased = false;

    if (false === $has_token) {
        $purchased = true;
//        $defaults = array(
//            'headers' => array(
//                'Authorization' => 'Bearer ' . stm_get_token(),
//                'User-Agent' => 'WordPress - Motors',
//            ),
//            'filter_by' => 'wordpress-themes',
//            'timeout' => 20,
//        );
//        $args = wp_parse_args($args, $defaults);
//
//        $url = 'https://api.envato.com/v3/market/buyer/list-purchases?filter_by=wordpress-themes';
//
//        $response = wp_remote_get(esc_url_raw($url), $args);
//
//        // Check the response code.
//        $response_code = wp_remote_retrieve_response_code($response);
//
//        if ($response_code == '200') {
//            $return = json_decode(wp_remote_retrieve_body($response), true);
//            foreach ($return['results'] as $theme) {
//                $theme_info = stm_beautify_theme_response($theme['item']);
//
//                if ($theme_info['name'] == STM_ITEM_NAME) {
//                    $purchased = true;
//                    set_site_transient('stm_theme_token_added', 'token_set');
//                }
//            }
//
//            if (!$purchased) {
//                $purchased = false;
//                delete_site_transient('stm_theme_token_added');
//            }
//        }
    } else {
        $purchased = true;
    }

    return $purchased;
}

function stm_set_token()
{
    if (isset($_POST['stm_registration'])) {
        if (isset($_POST['stm_registration']['token'])) {
            delete_site_transient('stm_theme_token_added');

            $token = array();
            $token['token'] = sanitize_text_field($_POST['stm_registration']['token']);

            update_option('envato_market', $token);

            $envato_market = Envato_Market::instance();
            $envato_market->items()->set_themes(true);
        }
    }
}

add_action('init', 'stm_set_token');

function stm_convert_memory($size)
{
    $l = substr($size, -1);
    $ret = substr($size, 0, -1);
    switch (strtoupper($l)) {
        case 'P':
            $ret *= 1024;
        case 'T':
            $ret *= 1024;
        case 'G':
            $ret *= 1024;
        case 'M':
            $ret *= 1024;
        case 'K':
            $ret *= 1024;
    }
    return $ret;
}

function stm_theme_support_url()
{
    return 'https://stylemixthemes.com/';
}

function stm_get_plugin_tgm_link($plugin_path, $plugin_name)
{
    $installed_plugins = get_plugins();
    $plugins = TGM_Plugin_Activation::$instance->plugins;

    $plugin = array();
    if (!empty($plugins) and !empty($plugins[$plugin_name])) {
        $plugin = $plugins[$plugin_name];
    }


    $url = '';
    $install = false;

    if (empty($installed_plugins[$plugin_path])) {
        $url = esc_url(wp_nonce_url(
            add_query_arg(
                array(
                    'page' => urlencode(TGM_Plugin_Activation::$instance->menu),
                    'plugin' => urlencode($plugin['slug']),
                    'plugin_name' => urlencode($plugin['name']),
                    'tgmpa-install' => 'install-plugin',
                    'return_url' => 'stm-admin-demos',
                ),
                TGM_Plugin_Activation::$instance->get_tgmpa_url()
            ),
            'tgmpa-install',
            'tgmpa-nonce'
        ));
        $install = true;
    } else {
        $url = esc_url(wp_nonce_url(
            add_query_arg(
                array(
                    'page' => urlencode(TGM_Plugin_Activation::$instance->menu),
                    'plugin' => urlencode($plugin['slug']),
                    'plugin_name' => urlencode($plugin['name']),
                    'tgmpa-install' => 'activate-plugin',
                    'return_url' => 'stm-admin-demos',
                ),
                TGM_Plugin_Activation::$instance->get_tgmpa_url()
            ),
            'tgmpa-install',
            'tgmpa-nonce'
        ));
    }

    if ($install) {
        $plugin['plugin_url_activate'] = '<a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Install', 'motors') . '</a>';
    } else {
        $plugin['plugin_url_activate'] = '<a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Activate', 'motors') . '</a>';
    }

    return $plugin;
}

function stm_get_admin_images_url($image)
{
    return esc_url(get_template_directory_uri() . '/assets/admin/images/' . $image);
}

// Add hidden price before user can update plugin
function stm_add_genuine_price_hidden()
{
    add_meta_box('stm_genuine_price', 'stm genuine price', 'stm_genuine_price_hidden', stm_listings_post_type());
}

add_action('add_meta_boxes', 'stm_add_genuine_price_hidden');

function stm_genuine_price_hidden()
{
}

add_action('init', 'stm_patching_redirect');

function stm_patching_redirect()
{
    $patched = get_option('stm_price_patched', '');

    /*If already patched*/
    if (!empty($patched)) {
        return false;
    }

    $patching = false;
    if (isset($_POST['action']) and $_POST['action'] == 'stm_admin_patch_price') {
        $patching = true;
    }

    $theme = stm_get_theme_info();

    $listings_created = wp_count_posts(stm_listings_post_type());
    if (!is_wp_error($listings_created)) {
        if (empty($listings_created->publish)) {
            $patched = stm_patch_status('dismiss_patch');
        }
    } else {
        $patched = stm_patch_status('dismiss_patch');
    }

    /*if patch in progress*/
    $current_patching = false;
    if (isset($_GET['page']) and $_GET['page'] == 'stm-admin-patching') {
        $current_patching = true;
    }

    if (empty($patched) and !$current_patching and !$patching) {
        wp_redirect(esc_url_raw(admin_url('admin.php?page=stm-admin-patching')));
        exit;
    }
}

function stm_patch_status($status)
{
    update_option('stm_price_patched', $status);
    return $status;
}

function stm_admin_patch_price()
{
    $r = array();
    $offset = intval($_POST['offset']);

    $args = array(
        'post_type' => stm_listings_post_type(),
        'posts_per_page' => '10',
        'post_status' => 'publish',
        'offset' => $offset,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'stm_genuine_price',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'price',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'sale_price',
                'compare' => 'NOT EXISTS'
            )
        )
    );

    $q = new WP_Query($args);
    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $id = get_the_ID();
            $price = get_post_meta($id, 'price', true);
            $sale_price = get_post_meta($id, 'sale_price', true);

            if (!empty($sale_price)) {
                $price = $sale_price;
            }

            if (!empty($price)) {
                update_post_meta($id, 'stm_genuine_price', $price);
            }
        }
    }

    $new_offset = $offset + 10;

    if ($q->found_posts < $new_offset) {
        $new_offset = 'none';
        stm_patch_status('updated');
    }

    $r['offset'] = $new_offset;

    $r = json_encode($r);
    echo $r;

    exit();
}

add_action('wp_ajax_stm_admin_patch_price', 'stm_admin_patch_price');