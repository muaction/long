<?php

/**
 * Register the custom product type after init
 */
function stm_register_car_option_product_type() {

    /**
     * This should be in its own separate file.
     */
    class WC_Product_Car_Option extends WC_Product_Simple {

        public function __construct( $product ) {

            $this->product_type = 'car_option';

            parent::__construct( $product );

        }

    }

}
add_action( 'init', 'stm_register_car_option_product_type' );

function stm_add_car_option_product( $types ){

    // Key should be exactly the same as in the class product_type parameter
    $types[ 'car_option' ] = __( 'Car Option' );

    return $types;

}
add_filter( 'product_type_selector', 'stm_add_car_option_product' );

/**
 * Show pricing fields for simple_rental product.
 */
function stm_car_option_custom_js() {

    if ( 'product' != get_post_type() ) :
        return;
    endif;

    ?><script type='text/javascript'>
        jQuery( document ).ready( function($) {
            $( '.options_group.pricing, .options_group ._manage_stock_field' ).addClass( 'show_if_car_option' ).show();
            $('.general_options.general_tab, ' +
                '.inventory_options.inventory_tab ').show();
        });
    </script><?php
}
add_action( 'admin_footer', 'stm_car_option_custom_js' );

function stm_get_cart_current_total() {
    return apply_filters('stm_rent_current_total', WC()->cart->get_cart_total());
}

function stm_get_cart_items() {

    $total_sum = stm_get_cart_current_total();

    $fields = stm_get_rental_order_fields_values();
    $cart = WC()->cart->get_cart();
    $cart_items = array(
        'has_car' => false,
        'option_total' => 0,
        'options_list' => array(),
        'car_class' => array(),
        'options' => array(),
        'total' => $total_sum,
        'option_ids' => array()
    );

    if (!empty($cart)) {
        foreach ($cart as $cart_item) {
            $id = $cart_item['product_id'];
            $post = $cart_item['data']->post;

            $buy_type = (get_class($cart_item['data']) == 'WC_Product_Car_Option') ? 'options' : 'car_class';

            if ($buy_type == 'options') {
                $total = $cart_item['quantity'] * $cart_item['data']->price;
                $cart_items['option_total'] += $total;
                $cart_items['option_ids'][] = $id;
                $cart_items[$buy_type][] = array(
                    'id' => $id,
                    'quantity' => $cart_item['quantity'],
                    'name' => $post->post_title,
                    'price' => $cart_item['data']->price,
                    'total' => $total,
                    'subname' => get_post_meta($id, 'cars_info', true),
                );
                $cart_items['options_list'][$id] = $post->post_title;
            } else {

                $variation_id = 0;
                if (!empty($cart_item['variation_id'])) {
                    $variation_id = $cart_item['variation_id'];
                }

                $cart_items[$buy_type][] = array(
                    'id' => $id,
                    'variation_id' => $variation_id,
                    'quantity' => $cart_item['quantity'],
                    'name' => $post->post_title,
                    'price' => $cart_item['data']->price,
                    'total' => $fields['order_days'] * $cart_item['data']->price,
                    'subname' => get_post_meta($id, 'cars_info', true),
                    'payment_method' => get_post_meta($variation_id, '_stm_payment_method', true),
                    'days' => $fields['order_days']
                );
                $cart_items['has_car'] = true;
            }
        }

        /*Get only last element*/
        if (count($cart_items['car_class']) > 1) {
            $rent = array_pop($cart_items['car_class']);
            $cart_items['delete_items'] = $cart_items['car_class'];
            $cart_items['car_class'] = $rent;
        } else {
            if (!empty($cart_items['car_class'])) {
                $cart_items['car_class'] = $cart_items['car_class'][0];
            }
        }
    }


    return apply_filters('stm_cart_items_content', $cart_items);
}

/*Remove last car everytime another one added*/
add_action('template_redirect', 'stm_rental_remove_car_from_cart');

function stm_rental_remove_car_from_cart() {

    /*This code is only for car reservation. Redirect on date reservation if not selected yet. BEGIN*/
    $rental_datepick = get_theme_mod('rental_datepick', false);
    if(!empty($rental_datepick) and is_checkout() and !stm_check_rental_date_validation()) {
        wp_redirect(get_permalink($rental_datepick));
        exit;
    }
    /*This code is only for car reservation. Redirect on date reservation if not selected yet. END*/

    $items = stm_get_cart_items();
    $ids = array();

    if(!empty($_GET['remove-from-cart'])) {
        $items['delete_items'][] = array(
            'id' => intval($_GET['remove-from-cart'])
        );
    }

    if(!empty($items['delete_items'])) {
        foreach($items['delete_items'] as $product) {
            $ids[] = $product['id'];
        }

        $WC = WC();

        foreach ( $WC->cart->get_cart() as $cart_item_key => $cart_item ) {
            // Get the Variation or Product ID
            $prod_id = $cart_item['product_id'];

            // Check to see if IDs match
            if(in_array($prod_id, $ids)) {
                $WC->cart->set_quantity( $cart_item_key, 0, true  );
                break;
            }
        }
    }
}

/*Add quantity equal to days*/
add_action('template_redirect', 'stm_rental_add_quantity_to_cart');

function stm_rental_add_quantity_to_cart() {
    $items = stm_get_cart_items();
    $items = $items['car_class'];
    if(!empty($items)) {
        $id = $items['id'];
        $days = 1;
        if(!empty($items['days'])) {
            $days = $items['days'];
        }

        $WC = WC();

        foreach ( $WC->cart->get_cart() as $cart_item_key => $cart_item ) {
            // Get the Variation or Product ID
            $prod_id = $cart_item['product_id'];

            // Check to see if IDs match
            if($prod_id == $id) {
                $WC->cart->set_quantity( $cart_item_key, $days, true  );
                break;
            }
        }
    }
}


//Remove Car Options from main shop
function stm_remove_car_options_from_query($query) {
    if ( !is_admin() and $query->is_main_query()) {
        $tax_query = array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'car_option',
                'operator' => 'NOT IN'
            )
        );
        $query->set('tax_query', $tax_query);
    }

    return $query;
}

add_action('pre_get_posts', 'stm_remove_car_options_from_query');

function stm_get_empty_placeholder($empty = false) {
    $symbol = '--';
    if($empty) {
       $symbol = '';
    }
    return apply_filters('stm_get_empty_placeholder', $symbol);
}


/*Checkout fields styling*/
add_filter( 'woocommerce_checkout_fields' , 'stm_custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function stm_custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_address_2']);

    $billing = $fields['billing'];
    $spliced = array_splice($billing, 0, 2);

    $spliced['billing_driver_license'] = array(
        'label' => esc_html__('Driver license', 'motors'),
        'required' => false,
        'class' => array('form-row-first'),
        'autocomplete' => 'driver_license'
    );

    $fields['billing'] = array_merge($spliced, $billing);

    $unvalidated_fields = array(
        'first_name',
        'last_name',
        'billing_first_name',
        'billing_last_name',
        'billing_email'
    );

    if(!empty($fields['billing']['billing_company'])) {
        $fields['billing']['billing_company']['class'] = array(
            'form-row-last'
        );
    }

    unset($fields['billing']['billing_postcode']);

    foreach ($fields['billing'] as $key => $field) {
        $field['label_class'] = 'heading-font';
        if(!in_array($key, $unvalidated_fields)) {
            $fields['billing'][$key]['required'] = false;
        }
    }

    if(!empty($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['class'] = array(
            'address-field'
        );
    }

    return $fields;
}

add_filter( 'woocommerce_default_address_fields' , 'stm_custom_override_default_address_fields' );

function stm_custom_override_default_address_fields($fields) {
    $unvalidated_fields = array(
        'first_name',
        'last_name',
        'billing_first_name',
        'billing_last_name',
        'billing_email'
    );
    foreach ($fields as $key => $field) {
        if(!in_array($key, $unvalidated_fields)) {
            $fields[$key]['required'] = false;
        }
    }

    return $fields;
}

add_filter('woocommerce_form_field_args', 'stm_fields_checkout_args');

function stm_fields_checkout_args($args) {
    $args['label_class'][] = 'heading-font';
    return $args;
}

//Add Woocommerce variation payment gateways
/**
 * Create new fields for variations
 *
 */
function variation_settings_fields( $loop, $variation_data, $variation ) {

    $payment_gateways = array();
    $available_methods = WC()->payment_gateways;
    if(!empty($available_methods->payment_gateways)) {
        foreach($available_methods->payment_gateways as $payment_gateway) {
            $payment_gateways[$payment_gateway->id] = $payment_gateway->title;
        }
    }

    // Select
    woocommerce_wp_select(
        array(
            'wrapper_class' => 'stm-custom-select',
            'id'          => '_stm_payment_method[' . $variation->ID . ']',
            'label'       => __( 'Availble payment method', 'motors' ),
            'description' => __( 'Choose payment method available only for this variable product. If this product will be in cart, all other payment methods will be disabled on checkout page.', 'woocommerce' ),
            'value'       => get_post_meta( $variation->ID, '_stm_payment_method', true ),
            'options' => $payment_gateways
        )
    );
}
add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );

//Save Woocommerce variation payment gateway
add_action( 'woocommerce_save_product_variation', 'stm_save_variation_settings_fields', 10, 2 );

function stm_save_variation_settings_fields($post_id) {

    $select = $_POST['_stm_payment_method'][ $post_id ];

    if( ! empty( $select ) ) {
        update_post_meta( $post_id, '_stm_payment_method', esc_attr( $select ) );
    }
}

add_filter('woocommerce_available_payment_gateways','stm_filter_gateways',1);
function stm_filter_gateways($gateways){
    $gateway = array();
    $cart_items = stm_get_cart_items();
    if(!empty($cart_items['car_class']) and !empty($cart_items['car_class']['payment_method'])) {
        $payment_method = $cart_items['car_class']['payment_method'];
        if(!empty($gateways[$payment_method])) {
            $gateway[$payment_method] = $gateways[$payment_method];
        }
    }

    if(!empty($gateway)) {
        $gateways = $gateway;
    }

    if(count($gateways) == 1): ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery('.stm_rental_payment_methods').addClass('stm_single_method_available');
            })
        </script>
    <?php endif;

    return $gateways;
}


function stm_rental_total_order_info() {
    $fields = stm_get_rental_order_fields_values();
    $items = stm_get_cart_items();
    $billing_fields = stm_rental_billing_info();

    $order_info = array(
        'pickup' => array(
            'title' => esc_html__('Pick Up', 'motors'),
            'content' => ''
        ),
        'dropoff' => array(
            'title' => esc_html__('Drop off', 'motors'),
            'content' => ''
        ),
        'vehicle' => array(
            'title' => esc_html__('Vehicle Type', 'motors'),
            'content' => ''
        ),
        'addons' => array(
            'title' => esc_html__('Add-ons', 'motors'),
            'content' => ''
        ),
        'info' => array(
            'title' => esc_html__('Your Information', 'motors'),
            'content' => ''
        ),
        'payment' => array(
            'title' => esc_html__('Payment information', 'motors'),
            'content' => ''
        )
    );

    if(!empty($fields['pickup_location'])) {
        $order_info['pickup']['content'] = $fields['pickup_location'] . ' ';
    }

    if(!empty($fields['pickup_date'])) {
        $order_info['pickup']['content'] .= $fields['pickup_date'];
    }

    if(!empty($fields['return_location'])) {
        $order_info['dropoff']['content'] = $fields['return_location'] . ' ';
    }

    if(!empty($fields['return_date'])) {
        $order_info['dropoff']['content'] .= $fields['return_date'];
    }

    if(!empty($items['car_class']['name'])) {
        $order_info['vehicle']['content'] = $items['car_class']['name'] . ' ';
    }

    if(!empty($items['car_class']['subname'])) {
        $order_info['vehicle']['content'] .= $items['car_class']['subname'];
    }

    if(!empty($items['options_list'])) {
        $order_info['addons']['content'] = implode(', ', $items['options_list']);
    }

    if(!empty($billing_fields['first_name']) and !empty($billing_fields['last_name'])) {
        $order_info['info']['content'] = $billing_fields['first_name'] . ' ' . $billing_fields['last_name'];
    }

    if(!empty($billing_fields['total'])) {
        $order_info['payment']['content'] = __(sprintf('Estimated Total - %s' , $billing_fields['total']), 'motors');
    }

    return apply_filters('stm_rental_order_info', $order_info);
}

function stm_get_order_id() {
    $order_id = false;
    if(isset($_GET['view-order'])) {
        $order_id = $_GET['view-order'];
    }
    else if(isset($_GET['order-received'])) {
        $order_id = $_GET['order-received'];
    }
    else {
        $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $template_name = strpos($url,'/order-received/') === false ? '/view-order/' : '/order-received/';
        if (strpos($url,$template_name) !== false) {
            $start = strpos($url,$template_name);
            $first_part = substr($url, $start+strlen($template_name));
            $order_id = substr($first_part, 0, strpos($first_part, '/'));
        }
    }
    return $order_id;
}


/*Update transient of user after new order*/
function stm_rental_billing_info() {

    $bill = array(
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'payment' => '',
        'total' => ''
    );

    $order_id = stm_get_order_id();

    if(!is_user_logged_in()) {

        if ($first_name = get_post_meta($order_id, '_billing_first_name', true)) {
            $bill['first_name'] = $first_name;
        }

        if ($last_name = get_post_meta($order_id, '_billing_last_name', true)) {
            $bill['last_name'] = $last_name;
        }

    } else {

        $id = get_current_user_id();
        $name = get_user_meta($id, 'billing_first_name', true);
        $last_name = get_user_meta($id, 'billing_last_name', true);

        if(!empty($name) and !empty($last_name)) {
            $bill['first_name'] = $name;
            $bill['last_name'] = $last_name;
        }

    }

    $items = stm_get_cart_items();
    $total = $items['total'];

    if (!empty($total)) {
        if(preg_replace('/\D/', '', $total)) {
            $bill['total'] = $total;
        }
    }

    if ($payment = get_post_meta($order_id, '_payment_method_title', true)) {
        $bill['payment'] = $payment;
    }

    return apply_filters('stm_billing_rental_info', $bill);
}

//change order info when on order page
add_action('stm_cart_items_content', 'stm_order_page_information_rental');

function stm_order_page_information_rental($info) {
    $order_id = stm_get_order_id();
    if(!empty($order_id)) {
        $info_car = get_post_meta($order_id, 'order_car', true);
        if(!empty($info_car['car_class'])) {
            $info = $info_car;
        }
    }

    return $info;
}

add_action('stm_rental_date_values', 'stm_order_page_date_rental');

function stm_order_page_date_rental($date) {
    $order_id = stm_get_order_id();
    if(!empty($order_id)) {
        $date_car = get_post_meta($order_id, 'order_car_date', true);
        if(!empty($date_car)) {
            $date = $date_car;
        }
    }
    return $date;
}

/*Taxes*/
function stm_rental_order_taxes() {
    $taxes = array();
    $order_id = stm_get_order_id();
    if(!empty($order_id)) {
        $order = wc_get_order($order_id);
        $order_taxes = $order->get_taxes();
        foreach($order_taxes as $order_tax) {
            $taxes[$order_tax['label']] = array(
                'label' => $order_tax['label'],
                'value' => wc_price($order_tax['tax_amount']),
            );
        }
    } else {
        $cart = WC()->cart->get_tax_totals();
        foreach($cart as $name => $cart_item) {
            $taxes[$name] = array(
                'label' => $cart_item->label,
                'value' => $cart_item->formatted_amount,
            );
        }
    }

    return $taxes;
}
add_action( 'woocommerce_new_order', 'stm_order_fields' );
function stm_order_fields($order_id){

    if(is_admin()) {
        return false;
    }

    $cart_items = stm_get_cart_items();
    $date = stm_get_rental_order_fields_values();
    update_post_meta($order_id, 'order_car', $cart_items);
    update_post_meta($order_id, 'order_car_date', $date);

    update_post_meta($order_id, 'order_pickup_date', $date['pickup_date']);
    update_post_meta($order_id, 'order_pickup_location', $date['pickup_location']);
    update_post_meta($order_id, 'order_drop_date', $date['return_date']);
    update_post_meta($order_id, 'order_drop_location', $date['return_location']);
}

/*Remove notice when adding item to cart*/
add_filter( 'wc_add_to_cart_message', '__return_empty_string' );

function stm_get_car_rent_info($id)
{
    $car_info_points = stm_get_car_listings();
    $car_info = array();
    if (!empty($car_info_points)) {
        foreach ($car_info_points as $car_info_point) {
            $meta = get_post_meta($id, $car_info_point['slug'], true);
            if (!empty($meta)) {
                $slug = $car_info_point['slug'];
                $car_info[$slug] = array(
                    'name' => $car_info_point['plural_name'],
                    'value' => $meta,
                    'font' => '',
                    'numeric' => false
                );

                if (!empty($car_info_point['numeric']) and $car_info_point['numeric']) {
                    $car_info[$slug]['numeric'] = true;
                }

                if (!empty($car_info_point['font'])) {
                    $car_info[$slug]['font'] = $car_info_point['font'];
                }
            }
        }
    }
    return apply_filters('stm_car_rent_info', $car_info);
}

add_action('stm_listings_default_search_inventory', 'stm_enable_listing_search_name');

function stm_enable_listing_search_name() {
    return (get_theme_mod('enable_search', false));
}

function stm_rental_order_fileds()
{
    $blog_id = get_current_blog_id();
    $rents = array('pickup_location', 'pickup_date', 'return_date', 'drop_location', 'return_same');
    foreach ($rents as $i => $rent) {
        $rents[$i] = 'stm_' . $rent . '_' . $blog_id;
    }
    return $rents;
}

function stm_check_rental_date_validation() {
    $r = true;

    $fields = stm_get_rental_order_fields_values();

    if($fields['pickup_location'] == stm_get_empty_placeholder()
        and $fields['pickup_date'] == stm_get_empty_placeholder()
        and $fields['return_date'] == stm_get_empty_placeholder()) {
        $r = false;
    }

    return $r;
}

function stm_get_rental_order_fields_values($empty = false)
{
    $values = array(
        'pickup_location_id' => '',
        'pickup_location' => '',
        'pickup_date' => '',
        'return_date' => '',
        'return_location_id' => '',
        'return_location' => '',
        'return_same' => '',
        'order_days' => ''
    );
    $fields = stm_rental_order_fileds();

    $pickup_location = !empty($_COOKIE[$fields[0]]) ? intval($_COOKIE[$fields[0]]) : false;
    $pickup_date = !empty($_COOKIE[$fields[1]]) ? sanitize_text_field($_COOKIE[$fields[1]]) : false;
    $return_date = !empty($_COOKIE[$fields[2]]) ? sanitize_text_field($_COOKIE[$fields[2]]) : false;
    $return_location = !empty($_COOKIE[$fields[3]]) ? intval($_COOKIE[$fields[3]]) : false;
    $return_same = !empty($_COOKIE[$fields[4]]) ? sanitize_text_field($_COOKIE[$fields[4]]) : 'on';

    /*Pickup Location*/

    if ($pickup_location) {
        $values['pickup_location_id'] = $pickup_location;
        $values['pickup_location'] = get_post_meta($pickup_location, 'address', true);
    } else {
        $values['pickup_location'] = stm_get_empty_placeholder($empty);
    }

    /*Pickup date*/
    if ($pickup_date) {
        $values['pickup_date'] = $pickup_date;
    } else {
        $values['pickup_date'] = stm_get_empty_placeholder($empty);
    }

    /*Return date*/
    if ($return_date) {
        $values['return_date'] = $return_date;

    } else {
        $values['return_date'] = stm_get_empty_placeholder($empty);
    }

    /*Drop Location*/
    if ($return_same == 'on') {
        $values['return_location'] = $values['pickup_location'];
    } else {
        if (!empty($return_location)) {
            $values['return_location_id'] = $return_location;
            $values['return_location'] = get_post_meta($return_location, 'address', true);
        } else {
            $values['return_location'] = $values['pickup_location'];
        }
    }

    $values['return_same'] = $return_same;

    if ($values['return_date'] != stm_get_empty_placeholder() and $values['pickup_date'] != stm_get_empty_placeholder()) {
        $date1 = new DateTime($values['pickup_date']);
        $date2 = new DateTime($values['return_date']);

        $diff = $date2->diff($date1)->format("%a");
        if(empty($diff)) {
            $diff = 1;
        }
        $values['order_days'] = $diff;
    } else {
        $values['order_days'] = 1;
    }

    return apply_filters('stm_rental_date_values', $values);

}

/*Rental locations*/
function stm_rental_locations($full = false)
{
    $args = array(
        'post_type' => 'stm_office',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $offices = new WP_Query($args);
    $locations = array();
    $i = 0;

    if ($offices->have_posts()):
        while ($offices->have_posts()): $offices->the_post();
            $id = get_the_ID();
            $lat = get_post_meta($id, 'latitude', true);
            $lng = get_post_meta($id, 'longitude', true);
            $phone = get_post_meta($id, 'phone', true);
            $fax = get_post_meta($id, 'fax', true);
            $address = get_post_meta($id, 'address', true);
            $content = '';

            if (!empty($lng) and !empty($lat)) {
                $google_api_key = get_theme_mod('google_api_key', '');
                $class = '';
                if (!empty($google_api_key)) {
                    $class = 'with-map';
                }

                $content = '<div class="stm_offices_wrapper ' . esc_attr($class) . '">';
                $content .= '<div class="location heading-font">' . get_the_title() . '</div>';
                if (!empty($address)) {
                    $content .= '<div class="address"><i class="stm-icon-pin"></i>' . $address . '</div>';
                }
                if (!empty($phone) or !empty($fax)) {
                    $content .= '<div class="phone_fax"><i class="stm-icon-phone"></i> ';
                }
                if (!empty($phone)) {
                    $content .= '<div class="phone">' . esc_html__('Phone:', 'motors') . ' ' . $phone . '</div>';
                }
                if (!empty($fax)) {
                    $content .= '<div class="fax">' . esc_html__('Fax:', 'motors') . ' ' . $fax . '</div>';
                }
                if (!empty($phone) or !empty($fax)) {
                    $content .= '</div>';
                }

                if ($full) {
                    $hours = get_post_meta($id, 'work_hours', true);
                    if (!empty($hours)) {
                        $content .= '<div class="stm_work_hours"><i class="stm-icon-time"></i>';
                        $content .= $hours;
                        $content .= '</div>';
                    }
                    if (!empty($google_api_key) and !empty($lng) and !empty($lat)) {
                        $g_map_url = 'https://maps.googleapis.com/maps/api/staticmap?zoom=13&size=253x253&markers=color:red%7Clabel:C%7C' . $lat . ',' . $lng . '&key=' . $google_api_key;
                        $g_map_full_url = 'https://www.google.com/maps/place/' . $lat . ',' . $lng;
                        $content .= '<a href="' . $g_map_full_url . '" target="_blank"><img src="' . $g_map_url . '" /></a>';
                    }
                }

                $content .= '</div>';

                $locations[] = array(
                    $content,
                    $lat,
                    $lng,
                    $i,
                    get_the_title(),
                    get_the_ID()
                );

                $i++;
            }

        endwhile;
        wp_reset_postdata();
    endif;

    return $locations;

}