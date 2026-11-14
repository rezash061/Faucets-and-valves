<?php
/**
 * Shop, product category, tag and brand listings.
 *
 * Layout (RTL): filter panel on the right (start), results on the left. On screens under
 * 1024px the panel becomes a sheet opened from the toolbar.
 * Above them: a plaster header with the category's title block and its subcategories as drawers.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// Plaster header with title block and drawers (template-parts/shop-head.php); it prints the breadcrumb itself.
get_template_part( 'template-parts/shop-head' );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
do_action( 'woocommerce_before_main_content' ); // Container.
?>
<div class="shop-layout">
	<?php get_template_part( 'template-parts/shop-filters' ); ?>

	<div class="shop-main" id="shop-results" aria-live="polite">
		<?php woocommerce_output_all_notices(); ?>

		<div class="shop-toolbar">
			<button class="btn btn--ghost filters-open" type="button" aria-controls="shop-filters" aria-expanded="false">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
				<?php esc_html_e( 'فیلترها', 'faucets-valves' ); ?>
				<?php $count_active = count( fv_active_filters() ); ?>
				<?php if ( $count_active ) : ?>
					<span class="filters-open__count"><?php echo esc_html( number_format_i18n( $count_active ) ); ?></span>
				<?php endif; ?>
			</button>

			<p class="shop-toolbar__count">
				<?php
				$total = (int) wc_get_loop_prop( 'total' );
				/* translators: %s: number of products */
				echo esc_html( sprintf( _n( '%s محصول', '%s محصول', $total, 'faucets-valves' ), number_format_i18n( $total ) ) );
				?>
			</p>

			<div class="shop-toolbar__sort">
				<label for="shop-orderby"><?php esc_html_e( 'مرتب‌سازی:', 'faucets-valves' ); ?></label>
				<?php
				$current_order = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) ); // phpcs:ignore WordPress.Security.NonceVerification
				?>
				<select id="shop-orderby" name="orderby" form="shop-filter-form">
					<?php foreach ( fv_orderby_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_order, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button class="btn btn--ghost" type="submit" form="shop-filter-form"><?php esc_html_e( 'اعمال', 'faucets-valves' ); ?></button></noscript>
			</div>
		</div>

		<?php $active = fv_active_filters(); ?>
		<?php if ( $active ) : ?>
			<ul class="active-filters" aria-label="<?php esc_attr_e( 'فیلترهای فعال', 'faucets-valves' ); ?>">
				<?php foreach ( $active as $filter ) : ?>
					<li>
						<a class="active-filter" href="<?php echo esc_url( $filter['url'] ); ?>" data-filter-link>
							<?php echo esc_html( $filter['label'] ); ?>
							<span class="sr-only"><?php esc_html_e( '(حذف)', 'faucets-valves' ); ?></span>
							<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M7 7l10 10M17 7 7 17"/></svg>
						</a>
					</li>
				<?php endforeach; ?>
				<li><a class="active-filters__clear" href="<?php echo esc_url( fv_shop_base_url() ); ?>" data-filter-link><?php esc_html_e( 'حذف همه', 'faucets-valves' ); ?></a></li>
			</ul>
		<?php endif; ?>

		<?php if ( woocommerce_product_loop() ) : ?>
			<?php
			woocommerce_product_loop_start();
			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();
					do_action( 'woocommerce_shop_loop' );
					wc_get_template_part( 'content', 'product' );
				}
			}
			woocommerce_product_loop_end();
			woocommerce_pagination();
			?>
		<?php else : ?>
			<div class="shop-empty">
				<p class="shop-empty__title"><?php esc_html_e( 'محصولی با این فیلترها پیدا نشد.', 'faucets-valves' ); ?></p>
				<p><?php esc_html_e( 'چند فیلتر را بردارید یا نام و کد کالا را جستجو کنید.', 'faucets-valves' ); ?></p>
				<?php if ( $active ) : ?>
					<a class="btn btn--brass" href="<?php echo esc_url( fv_shop_base_url() ); ?>" data-filter-link><?php esc_html_e( 'حذف همهٔ فیلترها', 'faucets-valves' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
