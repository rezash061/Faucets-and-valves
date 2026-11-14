<?php
/**
 * Shop / category / brand header: a plaster wall with the category's object, a copper pipe
 * running behind it, and a drawing title block (name, description, code count, connection note,
 * price range). Subcategories, or the main categories on the shop page, follow as drawers.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$term  = is_product_taxonomy() ? get_queried_object() : null;
$title = woocommerce_page_title( false );
$img   = $term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
$is_brand = $term && 'product_brand' === $term->taxonomy;

// Cells of the title block.
$cells = array();
if ( $term ) {
	/* translators: %s: number of products */
	$cells[] = array( __( 'کالا', 'faucets-valves' ), sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( $term->count ) ) );
	$note = (string) get_term_meta( $term->term_id, 'fv_branch_note', true );
	if ( $note ) {
		$cells[] = array( __( 'اتصال', 'faucets-valves' ), $note );
	}
	$valve = (string) get_term_meta( $term->term_id, 'fv_valve', true );
	if ( $valve && function_exists( 'fv_valve_kinds' ) && isset( fv_valve_kinds()[ $valve ] ) && ! $note ) {
		$cells[] = array( __( 'شیر', 'faucets-valves' ), fv_valve_kinds()[ $valve ] );
	}
} else {
	$counts  = wp_count_posts( 'product' );
	/* translators: %s: number of products */
	$cells[] = array( __( 'کالا', 'faucets-valves' ), sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( (int) $counts->publish ) ) );
	$cells[] = array( __( 'قیمت', 'faucets-valves' ), __( 'برای همه', 'faucets-valves' ) );
}
$cells[] = array( __( 'ارسال', 'faucets-valves' ), __( 'سراسر ایران', 'faucets-valves' ) );

// Drawers: subcategories of this category, or top-level categories on the shop page.
$drawers = array();
if ( ! $is_brand && taxonomy_exists( 'product_cat' ) ) {
	$drawers = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $term ? $term->term_id : 0,
		'hide_empty' => true,
		'orderby'    => 'menu_order',
		'number'     => 8,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	) );
	if ( is_wp_error( $drawers ) ) {
		$drawers = array();
	}
}
$desc = $term ? wp_strip_all_tags( term_description( $term ) ) : '';
?>
<header class="shop-hero surface-plaster<?php echo $img ? ' has-media' : ''; ?><?php echo $is_brand ? ' shop-hero--brand' : ''; ?>">
	<div class="container shop-hero__inner">
		<?php woocommerce_breadcrumb(); ?>
		<div class="shop-hero__grid">
			<div class="titleblock shop-hero__block">
				<div class="tb-main">
					<h1 id="shop-title"><?php echo fv_split_words( $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
					<?php if ( $desc ) : ?>
						<p><?php echo esc_html( wp_trim_words( $desc, 36 ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php foreach ( array_slice( $cells, 0, 3 ) as $c ) : ?>
					<div class="tb-cell"><?php echo esc_html( $c[0] ); ?><b><?php echo esc_html( $c[1] ); ?></b></div>
				<?php endforeach; ?>
			</div>
			<?php if ( $img ) : ?>
				<div class="shop-hero__media">
					<?php echo wp_get_attachment_image( $img, $is_brand ? 'medium' : 'woocommerce_single', false, array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<span class="shop-hero__pipe" aria-hidden="true"></span>
</header>

<?php if ( $drawers ) : ?>
	<nav class="container shop-drawers" aria-label="<?php echo esc_attr( $term ? __( 'زیردسته‌ها', 'faucets-valves' ) : __( 'دسته‌ها', 'faucets-valves' ) ); ?>">
		<ul class="drawers drawers--compact">
			<?php foreach ( $drawers as $d ) : ?>
				<?php $dimg = (int) get_term_meta( $d->term_id, 'thumbnail_id', true ); ?>
				<li>
					<a class="drawer" href="<?php echo esc_url( get_term_link( $d ) ); ?>">
						<span class="drawer__handle" aria-hidden="true"></span>
						<?php if ( $dimg ) : ?>
							<span class="drawer__media"><?php echo wp_get_attachment_image( $dimg, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?></span>
						<?php endif; ?>
						<b><?php echo esc_html( $d->name ); ?></b>
						<span>
							<?php
							/* translators: %s: number of products */
							echo esc_html( sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( $d->count ) ) );
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
<?php endif; ?>
