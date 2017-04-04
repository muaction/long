<?php
$car_guru_code = get_theme_mod('carguru', '');
$vin = get_post_meta(get_the_ID(), 'vin_number', true);
$price = get_post_meta(get_the_ID(), 'price', true);
$sale_price = get_post_meta(get_the_ID(), 'sale_price', true);

if (!empty($sale_price)) {
    $price = $sale_price;
}

if (!empty($car_guru_code) and !empty($vin) and !empty($price)): ?>

    <?php echo $car_guru_code; ?>

    <div class="stm_cargurus_wrapper">
        <span data-cg-vin="<?php echo esc_attr($vin); ?>" data-cg-price="<?php echo intval($price); ?>"></span>
    </div>

<?php endif; ?>