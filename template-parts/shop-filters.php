<?php
/**
 * Filter panel for product listings. Works as a plain GET form; shop-filters.js applies it
 * without a page reload.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$cats       = fv_filter_categories();
$attributes = fv_filter_attributes();
$brands     = taxonomy_exists( 'product_brand' ) ? get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true ) ) : array();
$brands     = is_wp_error( $brands ) ? array() : $brands;
$chosen_br  = fv_get_list_param( 'brand' );
$bounds     = fv_price_bounds();
// phpcs:disable WordPress.Security.NonceVerification
$min_price  = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : '';
$max_price  = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : '';
$in_stock   = ! empty( $_GET['stock'] );
$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
// phpcs:enable
$currency   = get_woocommerce_currency_symbol();
?>
<aside class="shop-filters" id="shop-filters" aria-labelledby="shop-filters-title">
	<div class="shop-filters__head">
		<h2 class="shop-filters__title" id="shop-filters-title"><?php esc_html_e( 'فیلترها', 'faucets-valves' ); ?></h2>
		<button class="icon-btn filters-close" type="button" aria-label="<?php esc_attr_e( 'بستن فیلترها', 'faucets-valves' ); ?>">
			<?php echo fv_icon( 'close' ); // phpcs:ignore ?>
		</button>
	</div>

	<form class="filter-form" id="shop-filter-form" method="get" action="<?php echo esc_url( fv_shop_base_url() ); ?>">
		<?php if ( $search ) : ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
			<input type="hidden" name="post_type" value="product">
		<?php endif; ?>

		<?php if ( $cats['terms'] ) : ?>
			<details class="filter-group" open>
				<summary class="filter-group__title"><?php esc_html_e( 'دسته‌بندی', 'faucets-valves' ); ?></summary>
				<ul class="filter-cats">
					<?php if ( $cats['parent'] ) : ?>
						<li><a class="filter-cats__back" href="<?php echo esc_url( get_term_link( $cats['parent'] ) ); ?>"><?php echo fv_icon( 'arrow', 'flip' ); // phpcs:ignore ?><?php echo esc_html( $cats['parent']->name ); ?></a></li>
					<?php else : ?>
						<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"<?php echo is_shop() ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'همهٔ محصولات', 'faucets-valves' ); ?></a></li>
					<?php endif; ?>
					<?php foreach ( $cats['terms'] as $t ) : ?>
						<li>
							<a href="<?php echo esc_url( get_term_link( $t ) ); ?>"<?php echo $cats['current'] === $t->term_id ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $t->name ); ?>
								<span class="filter-count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<?php if ( $brands ) : ?>
			<details class="filter-group" open>
				<summary class="filter-group__title"><?php esc_html_e( 'برند', 'faucets-valves' ); ?></summary>
				<ul class="filter-options">
					<?php foreach ( $brands as $t ) : ?>
						<li>
							<label class="check">
								<input type="checkbox" name="brand[]" value="<?php echo esc_attr( rawurldecode( $t->slug ) ); ?>" <?php checked( in_array( $t->slug, $chosen_br, true ) ); ?>>
								<span class="check__box" aria-hidden="true"></span>
								<span class="check__label"><?php echo esc_html( $t->name ); ?></span>
								<span class="filter-count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<?php foreach ( $attributes as $attr ) : ?>
			<details class="filter-group" open>
				<summary class="filter-group__title"><?php echo esc_html( $attr['label'] ); ?></summary>
				<input type="hidden" name="query_type_<?php echo esc_attr( $attr['name'] ); ?>" value="or" data-pair="filter_<?php echo esc_attr( $attr['name'] ); ?>">
				<ul class="filter-options">
					<?php foreach ( $attr['terms'] as $t ) : ?>
						<?php $swatch = 'finish' === $attr['name'] ? fv_finish_swatch( $t->name ) : ''; ?>
						<li>
							<label class="check">
								<input type="checkbox" name="filter_<?php echo esc_attr( $attr['name'] ); ?>[]" value="<?php echo esc_attr( rawurldecode( $t->slug ) ); ?>" <?php checked( in_array( $t->slug, $attr['chosen'], true ) ); ?>>
								<span class="check__box" aria-hidden="true"></span>
								<?php if ( $swatch ) : ?>
									<span class="swatch-dot swatch--<?php echo esc_attr( $swatch ); ?>" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="check__label"><?php echo esc_html( $t->name ); ?></span>
								<span class="filter-count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endforeach; ?>

		<details class="filter-group" open>
			<summary class="filter-group__title">
				<?php
				/* translators: %s: currency */
				echo esc_html( sprintf( __( 'قیمت (%s)', 'faucets-valves' ), wp_strip_all_tags( $currency ) ) );
				?>
			</summary>
			<div class="price-range">
				<label>
					<span><?php esc_html_e( 'از', 'faucets-valves' ); ?></span>
					<input type="text" inputmode="numeric" name="min_price" value="<?php echo esc_attr( '' === $min_price ? '' : $min_price ); ?>" placeholder="<?php echo esc_attr( number_format_i18n( $bounds['min'] ) ); ?>" autocomplete="off">
				</label>
				<label>
					<span><?php esc_html_e( 'تا', 'faucets-valves' ); ?></span>
					<input type="text" inputmode="numeric" name="max_price" value="<?php echo esc_attr( '' === $max_price ? '' : $max_price ); ?>" placeholder="<?php echo esc_attr( number_format_i18n( $bounds['max'] ) ); ?>" autocomplete="off">
				</label>
			</div>
		</details>

		<div class="filter-group filter-group--flat">
			<label class="switch">
				<input type="checkbox" name="stock" value="1" <?php checked( $in_stock ); ?>>
				<span class="switch__track" aria-hidden="true"></span>
				<span><?php esc_html_e( 'فقط کالاهای موجود', 'faucets-valves' ); ?></span>
			</label>
		</div>

		<?php if ( is_active_sidebar( 'shop-filters' ) ) : ?>
			<div class="filter-widgets"><?php dynamic_sidebar( 'shop-filters' ); ?></div>
		<?php endif; ?>

		<div class="filter-actions">
			<button class="btn btn--brass" type="submit"><?php esc_html_e( 'نمایش نتایج', 'faucets-valves' ); ?></button>
			<a class="btn btn--ghost" href="<?php echo esc_url( fv_shop_base_url() ); ?>" data-filter-link><?php esc_html_e( 'حذف همه', 'faucets-valves' ); ?></a>
		</div>
	</form>
</aside>
<div class="shop-filters__scrim" data-filters-close hidden></div>
