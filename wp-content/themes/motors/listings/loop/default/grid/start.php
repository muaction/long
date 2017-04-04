<?php
$taxonomies = stm_get_taxonomies();

$categories = wp_get_post_terms(get_the_ID(), $taxonomies);

$classes = array();

if(!empty($categories)) {
    foreach($categories as $category) {
        $classes[] = $category->slug.'-'.$category->term_id;
    }
}
?>


<div
    class="col-md-4 col-sm-4 col-xs-12 col-xxs-12 stm-isotope-listing-item all <?php print_r(implode(' ', $classes)); ?>"
    data-price="<?php echo esc_attr($data_price) ?>"
    data-date="<?php echo get_the_date('Ymdhi') ?>"
    data-mileage="<?php echo esc_attr($data_mileage); ?>"
>
    <a href="<?php echo esc_url(get_the_permalink()); ?>" class="rmv_txt_drctn">