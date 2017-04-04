<?php
$atts = vc_map_get_attributes($this->getShortcode(), $atts);
extract($atts);

$args = array(
    'post_type' => 'stm_office',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);

$style_type = 'style_1';
if(!empty($style) and $style == 'style_2') {
    $style_type = 'style_2';
}

$fields = stm_get_rental_order_fields_values(true);
$locations = stm_rental_locations(true);
$css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, vc_shortcode_custom_css_class($css, ' '));

$form_url = stm_woo_shop_page_url();
$items = stm_get_cart_items();
if(!empty($items['car_class'])) {
    $form_url = stm_woo_shop_checkout_url();
}

?>

<div class="stm_rent_car_form_wrapper <?php echo esc_attr($style_type . ' ' . $align . ' ' . $css_class); ?>">
    <div class="stm_rent_car_form">
        <form action="<?php echo esc_url($form_url); ?>" method="get">
            <h4><?php esc_html_e('Pick Up', 'motors'); ?></h4>
            <div class="stm_rent_form_fields">
                <h4 class="stm_form_title"><?php esc_html_e('Place to pick up the Car*', 'motors'); ?></h4>
                <div class="stm_pickup_location">
                    <i class="stm-service-icon-pin"></i>
                    <select name="pickup_location" data-class="stm_rent_location">
                        <option value=""><?php esc_html_e('Choose office', 'motors'); ?></option>
                        <?php if(!empty($locations)): ?>
                            <?php foreach($locations as $location): ?>
                                <option value="<?php echo sanitize_text_field($location[5]) ?>" <?php echo ($location[5] == $fields['pickup_location_id']) ? 'selected="selected"' : ''; ?>><?php echo sanitize_text_field($location[4]) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <label>
                    <input type="checkbox" name="return_same" <?php echo ($fields['return_same'] == 'on') ? 'checked' : ''; ?>/>
                    <?php esc_html_e('Return to the same location', 'motors'); ?>
                </label>
                <div class="stm_date_time_input">
                    <h4 class="stm_form_title"><?php esc_html_e('Pick-up Date/Time*', 'motors'); ?></h4>
                    <div class="stm_date_input">
                        <input type="text" value="<?php echo sanitize_text_field($fields['pickup_date']) ?>" class="stm-date-timepicker-start" name="pickup_date" placeholder="<?php esc_html_e('Pickup Date', 'motors') ?>" required/>
                        <i class="stm-icon-date"></i>
                    </div>
                </div>
            </div>

            <h4><?php esc_html_e('Return', 'motors'); ?></h4>
            <div class="stm_rent_form_fields stm_rent_form_fields-drop">
                <div class="stm_same_return <?php echo ($fields['return_same'] == 'on') ? '' : 'active'; ?>">
                    <h4 class="stm_form_title"><?php esc_html_e('Place to drop the Car*', 'motors'); ?></h4>
                    <div class="stm_pickup_location stm_drop_location">
                        <i class="stm-service-icon-pin"></i>
                        <select name="drop_location" data-class="stm_rent_location">
                            <option value=""><?php esc_html_e('Choose office', 'motors'); ?></option>
                            <?php if (!empty($locations)): ?>
                                <?php foreach ($locations as $location): ?>
                                    <option
                                        <?php echo ($location[5] == $fields['return_location_id']) ? 'selected="selected"' : ''; ?>
                                        value="<?php echo sanitize_text_field($location[5]) ?>"><?php echo sanitize_text_field($location[4]) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="stm_date_time_input">
                    <h4 class="stm_form_title"><?php esc_html_e('Drop Date/Time*', 'motors'); ?></h4>
                    <div class="stm_date_input">
                        <input type="text" class="stm-date-timepicker-end" name="return_date"
                               value="<?php echo sanitize_text_field($fields['return_date']) ?>"
                               placeholder="<?php esc_html_e('Return Date', 'motors') ?>" required/>
                        <i class="stm-icon-date"></i>
                    </div>
                </div>
            </div>

            <?php if ($style_type == 'style_1'): ?>
                <button type="submit"><?php esc_html_e('Find a vehicle', 'motors'); ?><i
                        class="fa fa-arrow-right"></i></button>
            <?php else: ?>
                <button type="submit"><?php esc_html_e('Continue reservation', 'motors'); ?></button>
            <?php endif; ?>


        </form>
    </div>
</div>

<script type="text/javascript">
    (function($) {
        "use strict";

        $(document).ready(function(){

            $('input[name="return_same"]').on('change', function(){
               if($(this).prop('checked')) {
                   $('.stm_same_return').slideUp();
               } else {
                   $('.stm_same_return').slideDown();
               }
            });

            $('.stm_pickup_location select').on('select2:open', function() {
                $('body').addClass('stm_background_overlay');
                $('.select2-container').css('width', $('.select2-dropdown').outerWidth());
            });

            $('.stm_pickup_location select').on('select2:close', function(){
                $('body').removeClass('stm_background_overlay');
            });

            $('.stm_date_time_input input').on('change', function(){
               if($(this).val() == '') {
                   $(this).removeClass('active');
               } else {
                   $(this).addClass('active');
               }
            });


            var locations = <?php echo json_encode( $locations ); ?>;
            var contents = [];
            var content = '';
            var i = 0;


            for (i = 0; i < locations.length; i++) {
                content = '<ul class="stm_locations_description <?php echo esc_attr($align . '_position') ?>">';
                content += '<li>' + locations[i][0] + '</li>';
                content += '</ul>';

                contents.push(content);
            }

            $(document).on('mouseover', '.stm_rent_location .select2-results__options li', function(){
                var currentLi = ($(this).index()) - 1;
                $('.stm_rent_location .stm_locations_description').remove();
                $('.stm_rent_location').append(contents[currentLi]);
            });


            /*Timepicker*/
            var stmTomorrow = new Date(+new Date() + 86400000);
            var stmStartDate = false;
            var stmEndDate = false;
            var startDate = false;
            var endDate = false;
            $('.stm-date-timepicker-start').stm_datetimepicker({
                format: 'Y/m/d H:i',
                timepicker: true,
                defaultDate: new Date(),
                onShow: function( ct ) {
                    $('body').addClass('stm_background_overlay');
                    var stmEndDate = $('.stm-date-timepicker-end').val() ? $('.stm-date-timepicker-end').val() : false;
                    if(stmEndDate) {
                        stmEndDate = stmEndDate.split(' ');
                        stmEndDate = stmEndDate[0];
                    }

                    this.setOptions({
                        minDate: 0,
                        maxDate: stmEndDate
                    });
                },
                onSelectDate: function( ) {
                    $('.stm-date-timepicker-start').stm_datetimepicker('close');
                },
                onClose: function( ) {
                    $('body').removeClass('stm_background_overlay');
                }
            });
            $('.stm-date-timepicker-end').stm_datetimepicker({
                format:'Y/m/d H:i',
                timepicker: true,
                defaultDate: stmTomorrow,
                onShow:function( ct ){
                    $('body').addClass('stm_background_overlay');
                    var stmStartDate = $('.stm-date-timepicker-start').val() ? $('.stm-date-timepicker-start').val() : false;
                    if(stmStartDate) {
                        stmStartDate = stmStartDate.split(' ');
                        stmStartDate = new Date(stmStartDate[0]);
                    } else {
                        stmStartDate = new Date();
                    }

                    stmStartDate.setDate(stmStartDate.getDate() + 1);

                    this.setOptions({
                        minDate: stmStartDate
                    })
                },
                onSelectDate: function( ) {
                    $('.stm-date-timepicker-end').stm_datetimepicker('close');
                },
                onClose: function( ) {
                    $('body').removeClass('stm_background_overlay');
                }
            });

            /*Set cookie with order data*/
            $('.stm_rent_car_form form').on('submit', function (e) {
                $('.stm_pickup_location').removeClass('stm_error');
                /*Save in cookies all fields*/
                $.each($(this).serializeArray(), function (i, field) {
                    $.cookie('stm_' + field.name + '_' + stm_site_blog_id, field.value, {expires: 7, path: '/'});
                });

                var stm_pickup_location = $('.stm_pickup_location select').val();
                var return_same = $('input[name="return_same"]').prop('checked');
                var stm_drop_location = $('.stm_drop_location select').val();

                var error = false;
                if (stm_pickup_location == '') {
                    $('.stm_pickup_location:not(".stm_drop_location")').addClass('stm_error');
                    error = true;
                }

                if (return_same == '' && stm_drop_location == '') {
                    $('.stm_drop_location').addClass('stm_error');
                    error = true;
                }

                if (error) {
                    e.preventDefault();
                }
            });

            $('.stm-template-car_rental .stm_rent_order_info .image.image-placeholder a').on('click', function(e){
                var $stmThis = $('.stm_rent_car_form form');
                $stmThis.submit();
                e.preventDefault();
            })

        })

    })(jQuery);
</script>