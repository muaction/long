<div class="stm-car-listing-sort-units clearfix">
	<div class="stm-sort-by-options clearfix">
		<span><?php esc_html_e('Sắp Xếp:', 'motors'); ?></span>
        <div class="stm-select-sorting">
            <select>
                <option value="date_high" selected><?php esc_html_e('Theo ngày: Mới nhất', 'motors'); ?></option>
                <option value="date_low"><?php esc_html_e('Theo ngày: Cũ Nhất', 'motors'); ?></option>
                <option value="price_low"><?php esc_html_e('Theo giá: Thấp Nhất', 'motors'); ?></option>
                <option value="price_high"><?php esc_html_e('Theo giá: Cao Nhất', 'motors'); ?></option>
                <option value="mileage_low"><?php esc_html_e('Kilomet : Thấp Nhất', 'motors'); ?></option>
                <option value="mileage_high"><?php esc_html_e('Kilomet : Cao Nhất', 'motors'); ?></option>
            </select>
        </div>
	</div>

	<?php
		$view_type = stm_listings_input('view_type', 'list');
		if($view_type == 'list') {
			$view_list = 'active';
			$view_grid = '';
		} else {
			$view_grid = 'active';
			$view_list = '';
		}
	?>

	<div class="stm-view-by">
		<a href="#" class="view-grid view-type <?php echo esc_attr($view_grid); ?>" data-view="grid">
			<i class="stm-icon-grid"></i>
		</a>
		<a href="#" class="view-list view-type <?php echo esc_attr($view_list); ?>" data-view="list">
			<i class="stm-icon-list"></i>
		</a>
	</div>
</div>