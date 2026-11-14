<?php
/**
 * Single product page: summary order, specs, datasheet, sections, gallery motion, swatches.
 *
 * Summary order (top to bottom):
 *   finish + category · title · SKU · rating · price · short description · add to cart
 *   · datasheet button · assurances
 * After the summary: section nav, description, technical specs, reviews, related products.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Gallery + summary wrapper (keeps the sticky buy box inside the top area) ---------- */

add_action( 'woocommerce_before_single_product_summary', function () {
	echo '<div class="product-top">';
}, 1 );
add_action( 'woocommerce_after_single_product_summary', function () {
	echo '</div>';
}, 1 );

/* ---------- Summary ---------- */

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

add_action( 'woocommerce_single_product_summary', 'fv_single_eyebrow', 4 );
add_action( 'woocommerce_single_product_summary', 'fv_single_facts', 25 );
add_action( 'woocommerce_single_product_summary', 'fv_single_datasheet', 35 );
add_action( 'woocommerce_single_product_summary', 'fv_single_assurances', 45 );

/**
 * Finish and main category above the title.
 */
function fv_single_eyebrow() {
	global $product;
	$finish = fv_product_finish( $product );
	$cats   = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
	$cat    = $cats ? $cats[0] : null;
	if ( ! $finish && ! $cat ) {
		return;
	}
	echo '<p class="product-eyebrow">';
	if ( $cat ) {
		printf( '<a href="%s">%s</a>', esc_url( get_term_link( $cat ) ), esc_html( $cat->name ) );
	}
	if ( $finish && $cat ) {
		echo '<span aria-hidden="true">·</span>';
	}
	if ( $finish ) {
		printf( '<span class="product-eyebrow__finish">%s</span>', esc_html( $finish ) );
	}
	echo '</p>';
}

/**
 * Product code under the title.
 */
function fv_single_sku() {
	global $product;
	$sku = $product->get_sku();
	if ( ! $sku && ! $product->is_type( 'variable' ) ) {
		return;
	}
	printf(
		'<p class="product-sku">%1$s <span class="sku" dir="ltr">%2$s</span></p>',
		esc_html__( 'کد کالا:', 'faucets-valves' ),
		esc_html( $sku ? $sku : '—' )
	);
}

/**
 * Quick facts as a drawing title block: code, connection size, finish, stock.
 */
function fv_single_facts() {
	global $product;
	$cells = array();
	$cells[] = array( __( 'کد کالا', 'faucets-valves' ), $product->get_sku() ? $product->get_sku() : '—', true );
	$size = $product->get_attribute( 'pa_size' );
	if ( $size ) {
		$cells[] = array( __( 'اتصال', 'faucets-valves' ), $size, false );
	}
	$finish = fv_product_finish( $product );
	if ( $finish && ! $product->is_type( 'variable' ) ) {
		$cells[] = array( __( 'پرداخت', 'faucets-valves' ), $finish, false );
	}
	if ( $product->is_in_stock() ) {
		$qty = $product->managing_stock() ? $product->get_stock_quantity() : null;
		/* translators: %s: quantity */
		$stock = null !== $qty ? sprintf( __( '%s عدد در انبار', 'faucets-valves' ), number_format_i18n( $qty ) ) : __( 'موجود', 'faucets-valves' );
	} else {
		$stock = __( 'ناموجود', 'faucets-valves' );
	}
	$cells[] = array( __( 'موجودی', 'faucets-valves' ), $stock, false );
	echo '<dl class="product-facts">';
	foreach ( $cells as $c ) {
		printf( '<div><dt>%1$s</dt><dd%3$s>%2$s</dd></div>', esc_html( $c[0] ), esc_html( $c[1] ), $c[2] ? ' class="product-facts__code" dir="ltr"' : '' );
	}
	echo '</dl>';
}

/**
 * Datasheet PDF link, set per product in Product data › General.
 */
function fv_single_datasheet() {
	global $product;
	$url = $product->get_meta( '_fv_datasheet' );
	if ( ! $url ) {
		return;
	}
	printf(
		'<a class="btn btn--ghost datasheet-link" href="%1$s" target="_blank" rel="noopener">%2$s%3$s</a>',
		esc_url( $url ),
		fv_icon( 'document' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'دانلود برگهٔ مشخصات فنی (PDF)', 'faucets-valves' )
	);
}

/**
 * Short trust list under the buy box.
 */
function fv_single_assurances() {
	$items = apply_filters( 'fv_single_assurances', array(
		array( 'shield', __( 'ضمانت اصالت کالا', 'faucets-valves' ) ),
		array( 'truck', __( 'ارسال به سراسر ایران', 'faucets-valves' ) ),
		array( 'card', __( 'پرداخت با زرین‌پال یا کارت به کارت', 'faucets-valves' ) ),
		array( 'document', __( 'پیش‌فاکتور رسمی برای سفارش عمده', 'faucets-valves' ) ),
	) );
	echo '<ul class="product-assurances">';
	foreach ( $items as $item ) {
		printf( '<li>%s<span>%s</span></li>', fv_icon( $item[0] ), esc_html( $item[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}

/* ---------- Datasheet field in the product editor ---------- */

add_action( 'woocommerce_product_options_general_product_data', function () {
	echo '<div class="options_group">';
	woocommerce_wp_text_input( array(
		'id'          => '_fv_datasheet',
		'label'       => __( 'برگهٔ مشخصات (PDF)', 'faucets-valves' ),
		'placeholder' => 'https://',
		'desc_tip'    => true,
		'description' => __( 'فایل PDF را در کتابخانهٔ رسانه بارگذاری کنید و نشانی آن را اینجا بگذارید.', 'faucets-valves' ),
		'type'        => 'url',
	) );
	echo '</div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	if ( isset( $_POST['_fv_datasheet'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the product nonce.
		$product->update_meta_data( '_fv_datasheet', esc_url_raw( wp_unslash( $_POST['_fv_datasheet'] ) ) ); // phpcs:ignore
	}
} );

/* ---------- Sections instead of tabs ---------- */

remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
add_action( 'woocommerce_after_single_product_summary', 'fv_product_sections', 10 );

/**
 * Description, specs and reviews as stacked sections with an in-page nav.
 */
function fv_product_sections() {
	global $product, $post;

	$sections = array();
	$model    = function_exists( 'fv_product_model' ) ? fv_product_model( $product ) : '';
	if ( $model ) {
		$sections['model'] = fv_product_3d_title( $model );
	}
	if ( '' !== trim( $post->post_content ) ) {
		$sections['description'] = __( 'توضیحات', 'faucets-valves' );
	}
	$specs = fv_product_specs( $product );
	if ( $specs ) {
		$sections['specs'] = __( 'مشخصات فنی', 'faucets-valves' );
	}
	if ( ( comments_open() && wc_review_ratings_enabled() ) || $product->get_review_count() ) {
		/* translators: %s: review count */
		$sections['reviews'] = sprintf( __( 'نظرات (%s)', 'faucets-valves' ), number_format_i18n( $product->get_review_count() ) );
	}
	if ( ! $sections ) {
		return;
	}
	?>
	<div class="product-sections">
		<nav class="product-sections__nav" aria-label="<?php esc_attr_e( 'بخش‌های محصول', 'faucets-valves' ); ?>">
			<?php foreach ( $sections as $id => $label ) : ?>
				<a href="#product-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php
		if ( $model ) {
			fv_product_3d_section( $product, $model );
		}
		?>

		<?php if ( isset( $sections['description'] ) ) : ?>
			<section class="product-section" id="product-description" aria-labelledby="product-description-title">
				<h2 class="product-section__title" id="product-description-title"><?php echo esc_html( $sections['description'] ); ?></h2>
				<div class="entry-content"><?php the_content(); ?></div>
			</section>
		<?php endif; ?>

		<?php if ( $specs ) : ?>
			<section class="product-section" id="product-specs" aria-labelledby="product-specs-title">
				<h2 class="product-section__title" id="product-specs-title"><?php echo esc_html( $sections['specs'] ); ?></h2>
				<table class="specs-table">
					<tbody>
						<?php foreach ( $specs as $row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
								<td<?php echo ! empty( $row['ltr'] ) ? ' dir="ltr"' : ''; ?>><?php echo wp_kses_post( $row['value'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php endif; ?>

		<?php if ( isset( $sections['reviews'] ) ) : ?>
			<section class="product-section" id="product-reviews" aria-labelledby="product-reviews-title">
				<h2 class="product-section__title" id="product-reviews-title"><?php echo esc_html( $sections['reviews'] ); ?></h2>
				<?php comments_template(); ?>
			</section>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Spec rows: code, attributes (visible ones), weight, dimensions.
 *
 * @param WC_Product $product Product.
 * @return array[] Each: label, value (HTML), ltr (bool).
 */
function fv_product_specs( $product ) {
	$rows = array();
	if ( $product->get_sku() ) {
		$rows[] = array( 'label' => __( 'کد کالا', 'faucets-valves' ), 'value' => esc_html( $product->get_sku() ), 'ltr' => true );
	}
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() ) {
			continue;
		}
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
			: $attribute->get_options();
		if ( ! $values ) {
			continue;
		}
		$rows[] = array(
			'label' => wc_attribute_label( $attribute->get_name(), $product ),
			'value' => esc_html( implode( '، ', $values ) ),
		);
	}
	if ( $product->has_weight() ) {
		$rows[] = array( 'label' => __( 'وزن', 'faucets-valves' ), 'value' => esc_html( wc_format_weight( $product->get_weight() ) ) );
	}
	if ( $product->has_dimensions() ) {
		$rows[] = array( 'label' => __( 'ابعاد', 'faucets-valves' ), 'value' => esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ) );
	}
	return apply_filters( 'fv_product_specs', $rows, $product );
}

/* ---------- Related products ---------- */

add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );
add_filter( 'woocommerce_product_related_products_heading', function () {
	return __( 'محصولات مرتبط', 'faucets-valves' );
} );
add_filter( 'woocommerce_product_upsells_products_heading', function () {
	return __( 'پیشنهاد ما', 'faucets-valves' );
} );

/* ---------- Gallery: Material Reveal ---------- */

add_filter( 'woocommerce_single_product_image_gallery_classes', function ( $classes ) {
	$classes[] = 'material';
	return $classes;
} );

/* ---------- Finish swatches for variable products ---------- */

add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function ( $html, $args ) {
	if ( empty( $args['attribute'] ) || 'pa_finish' !== $args['attribute'] || empty( $args['product'] ) ) {
		return $html;
	}
	$terms = wc_get_product_terms( $args['product']->get_id(), 'pa_finish', array( 'fields' => 'all' ) );
	if ( ! $terms ) {
		return $html;
	}
	$buttons = '';
	foreach ( $terms as $term ) {
		if ( ! in_array( $term->slug, (array) $args['options'], true ) ) {
			continue;
		}
		$swatch   = fv_finish_swatch( $term->name );
		$buttons .= sprintf(
			'<button type="button" class="swatch" data-value="%1$s" aria-pressed="%2$s">%3$s<span>%4$s</span></button>',
			esc_attr( $term->slug ),
			$args['selected'] === $term->slug ? 'true' : 'false',
			$swatch ? '<span class="swatch-dot swatch--' . esc_attr( $swatch ) . '" aria-hidden="true"></span>' : '',
			esc_html( $term->name )
		);
	}
	return '<div class="swatches" data-for="' . esc_attr( $args['id'] ?? sanitize_title( $args['attribute'] ) ) . '" role="group">' . $buttons . '</div>' . preg_replace( '/<select([^>]*?)class="([^"]*)"/', '<select$1class="$2 swatches__select"', $html, 1 );
}, 10, 2 );

/* ---------- Assets ---------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! ( is_product() || is_cart() ) ) {
		return;
	}
	if ( is_product() ) {
		$rel = 'assets/css/product.css';
		wp_enqueue_style( 'fv-product', FV_URI . "/{$rel}", array( 'fv-woocommerce' ), fv_asset_ver( $rel ) );
	}
	$rel = 'assets/js/ui/product.js';
	wp_enqueue_script( 'fv-product', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 20 );
