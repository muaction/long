<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('template_redirect', 'stm_listings_template_actions');

function stm_listings_template_actions($template)
{

    if ($action = stm_listings_input('ajax_action')) {
        switch ($action) {
            case 'listings-result':
                stm_listings_ajax_results();
                break;
            case 'listings-binding':
                stm_listings_binding_results();
                break;
            case 'listings-items':
                stm_listings_items();
                break;
        }
    }
}


/**
 * Ajax filter cars
 */
function stm_listings_ajax_results()
{
    $r = stm_listings_filter();

    ob_start();
    stm_listings_load_results();
    $r['html'] = ob_get_clean();
    $r = json_encode($r);

    echo apply_filters('stm_listings_ajax_results', $r);
    exit;
}

/**
 * Ajax filter binding
 */
function stm_listings_binding_results()
{
    $r = stm_listings_filter();

    $r = json_encode($r);

    echo apply_filters('stm_listings_binding_results', $r);
    exit;
}

/**
 * Ajax filter items
 */
function stm_listings_items()
{

    ob_start();
    stm_listings_load_results();
    $r['html'] = ob_get_clean();

    $r = json_encode($r);

    echo apply_filters('stm_listings_items', $r);
    exit;
}

function stm_listings_ajax_save_user_data()
{

    $response = array();

    if (!is_user_logged_in()) {
        die('You are not logged in');
    }

    $got_error_validation = false;
    $error_msg = esc_html__('Settings Saved.', 'stm_vehicles_listing');

    $user_current = wp_get_current_user();
    $user_id = $user_current->ID;
    $user = stm_get_user_custom_fields($user_id);

    /*Get current editing values*/
    $user_mail = stm_listings_input('stm_email', $user['email']);
    $user_mail = sanitize_email($user_mail);
    /*Socials*/
    $socs = array('facebook', 'twitter', 'linkedin', 'youtube');
    $socials = array();
    if (empty($user['socials'])) {
        $user['socials'] = array();
    }
    foreach ($socs as $soc) {
        if (empty($user['socials'][$soc])) {
            $user['socials'][$soc] = '';
        }
        $socials[$soc] = stm_listings_input('stm_user_' . $soc, $user['socials'][$soc]);
    }

    $password_check = false;
    if (!empty($_POST['stm_confirm_password'])) {
        $password_check = wp_check_password($_POST['stm_confirm_password'], $user_current->data->user_pass, $user_id);
    }

    if (!$password_check and !empty($_POST['stm_confirm_password'])) {
        $got_error_validation = true;
        $error_msg = esc_html__('Confirmation password is wrong', 'stm_vehicles_listing');
    }

    $demo = stm_is_site_demo_mode();

    if ($password_check and !$demo) {
        //Editing/adding user filled fields
        /*Image changing*/
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF');
        if (!empty($_FILES['stm-avatar'])) {
            $file = $_FILES['stm-avatar'];
            if (is_array($file) and !empty($file['name'])) {
                $ext = pathinfo($file['name']);
                $ext = $ext['extension'];
                if (in_array($ext, $allowed)) {

                    $upload_dir = wp_upload_dir();
                    $upload_url = $upload_dir['url'];
                    $upload_path = $upload_dir['path'];


                    /*Upload full image*/
                    if (!function_exists('wp_handle_upload')) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    }
                    $original_file = wp_handle_upload($file, array('test_form' => false));

                    if (!is_wp_error($original_file)) {
                        $image_user = $original_file['file'];
                        /*Crop image to square from full image*/
                        $image_cropped = image_make_intermediate_size($image_user, 160, 160, true);

                        /*Delete full image*/
                        if (file_exists($image_user)) {
                            unlink($image_user);
                        }

                        if (!$image_cropped) {
                            $got_error_validation = true;
                            $error_msg = esc_html__('Error, please try again', 'motors');

                        } else {

                            /*Get path and url of cropped image*/
                            $user_new_image_url = $upload_url . '/' . $image_cropped['file'];
                            $user_new_image_path = $upload_path . '/' . $image_cropped['file'];

                            /*Delete from site old avatar*/

                            $user_old_avatar = get_the_author_meta('stm_user_avatar_path', $user_id);
                            if (!empty($user_old_avatar) and $user_new_image_path != $user_old_avatar and file_exists($user_old_avatar)) {

                                /*Check if prev avatar exists in another users except current user*/
                                $args = array(
                                    'meta_key' => 'stm_user_avatar_path',
                                    'meta_value' => $user_old_avatar,
                                    'meta_compare' => '=',
                                    'exclude' => array($user_id),
                                );
                                $users_db = get_users($args);
                                if (empty($users_db)) {
                                    unlink($user_old_avatar);
                                }
                            }

                            /*Set new image tmp*/
                            $user['image'] = $user_new_image_url;


                            /*Update user meta path and url image*/
                            update_user_meta($user_id, 'stm_user_avatar', $user_new_image_url);
                            update_user_meta($user_id, 'stm_user_avatar_path', $user_new_image_path);

                            $response = array();
                            $response['new_avatar'] = $user_new_image_url;

                        }

                    }

                } else {
                    $got_error_validation = true;
                    $error_msg = esc_html__('Please load image with right extension (jpg, jpeg, png and gif)', 'stm_vehicles_listing');
                }
            }
        }

        /*Check if delete*/
        if (empty($_FILES['stm-avatar']['name'])) {
            if (!empty($_POST['stm_remove_img']) and $_POST['stm_remove_img'] == 'delete') {
                $user_old_avatar = get_the_author_meta('stm_user_avatar_path', $user_id);
                /*Check if prev avatar exists in another users except current user*/
                $args = array(
                    'meta_key' => 'stm_user_avatar_path',
                    'meta_value' => $user_old_avatar,
                    'meta_compare' => '=',
                    'exclude' => array($user_id),
                );
                $users_db = get_users($args);
                if (empty($users_db)) {
                    unlink($user_old_avatar);
                }
                update_user_meta($user_id, 'stm_user_avatar', '');
                update_user_meta($user_id, 'stm_user_avatar_path', '');

                $response['new_avatar'] = '';
            }
        }

        /*Change email*/
        $new_user_data = array(
            'ID' => $user_id,
            'user_email' => $user_mail
        );

        /*Change email visiblity*/
        if (!empty($_POST['stm_show_mail']) and $_POST['stm_show_mail'] == 'on') {
            update_user_meta($user_id, 'stm_show_email', 'show');
        } else {
            update_user_meta($user_id, 'stm_show_email', '');
        }

        if (!empty($_POST['stm_new_password']) and !empty($_POST['stm_new_password_confirm'])) {
            if ($_POST['stm_new_password_confirm'] == $_POST['stm_new_password']) {
                $new_user_data['user_pass'] = $_POST['stm_new_password'];
            } else {
                $got_error_validation = true;
                $error_msg = esc_html__('New password not saved, because of wrong confirmation.', 'stm_vehicles_listing');
            }
        }

        $user_error = wp_update_user($new_user_data);
        if (is_wp_error($user_error)) {
            $got_error_validation = true;
            $error_msg = $user_error->get_error_message();
        }

        /*Change fields with secondary privilegy*/
        /*POST key => user_meta_key*/
        $changed_info = array(
            'stm_first_name' => 'first_name',
            'stm_last_name' => 'last_name',
            'stm_phone' => 'stm_phone',
            'stm_user_facebook' => 'stm_user_facebook',
            'stm_user_twitter' => 'stm_user_twitter',
            'stm_user_linkedin' => 'stm_user_linkedin',
            'stm_user_youtube' => 'stm_user_youtube',
        );

        foreach ($changed_info as $change_to_key => $change_info) {
            if (!empty($_POST[$change_to_key])) {
                $escaped_value = sanitize_text_field($_POST[$change_to_key]);
                update_user_meta($user_id, $change_info, $escaped_value);
            }
        }

    } else {
        if ($demo) {
            $got_error_validation = true;
            $error_msg = esc_html__('Site is on demo mode', 'stm_vehicles_listing');
        }
    }

    $response['error'] = $got_error_validation;
    $response['error_msg'] = $error_msg;

    $response = json_encode($response);
    echo $response;
    exit;
}

add_action('wp_ajax_stm_listings_ajax_save_user_data', 'stm_listings_ajax_save_user_data');