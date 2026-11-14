<?php
/**
 * Quick order (page template «سفارش سریع»): search by name or code, set quantities,
 * paste a list of "code quantity" lines, add everything to the cart at once.
 *
 * AJAX: fv_qo_search (term → products), fv_qo_skus (codes → products), fv_qo_add (lines → cart).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product row data for the quick order table.
 *
 * @param WC_Product $p Product (simple or variation).
 */
function fv_qo_product_data( $p ) {
	$image = wp_get_attachment_image_url( $p->get_image_id() ? $p->get_image_id() : ( $p->get_parent_id() ? get_post_thumbnail_id( $p->get_parent_id() ) : 0 ), 'thumbnail' );
	$max   = $p->get_max_purchase_quantity();
	return array(
		'id'       => $p->get_id(),
		'name'     => wp_strip_all_tags( $p->get_name() ),
		'sku'      => $p->get_sku(),
		'price'    => (float) wc_get_price_to_display( $p ),
		'in_stock' => $p->is_in_stock(),
		'stock'    => wp_strip_all_tags( wc_get_stock_html( $p ) ),
		'max'      => $max > 0 ? $max : 0,
		'image'    => $image ? $image : '',
	);
}

/**
 * Purchasable simple products and variations only.
 */
function fv_qo_usable( $p ) {
	return $p && $p->is_purchasable() && in_array( $p->get_type(), array( 'simple', 'variation' ), true )
		&& ( 'variation' !== $p->get_type() || 'publish' === get_post_status( $p->get_parent_id() ) )
		&& ( 'variation' === $p->get_type() || 'publish' === $p->get_status() );
}

function fv_qo_verify() {
	if ( ! check_ajax_referer( 'fv_quick_order', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'نشست منقضی شده است؛ صفحه را تازه کنید.', 'faucets-valves' ) ), 403 );
	}
}

/**
 * Search by name or code.
 */
function fv_qo_search() {
	fv_qo_verify();
	$term = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
	$term = function_exists( 'fv_latin_digits' ) ? trim( fv_latin_digits( $term ) ) : trim( $term );
	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array() );
	}
	$ids = WC_Data_Store::load( 'product' )->search_products( $term, '', true, false, 20 );
	$out = array();
	foreach ( array_unique( array_filter( array_map( 'absint', $ids ) ) ) as $id ) {
		$p = wc_get_product( $id );
		if ( fv_qo_usable( $p ) ) {
			$out[] = fv_qo_product_data( $p );
		}
		if ( count( $out ) >= 10 ) {
			break;
		}
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_fv_qo_search', 'fv_qo_search' );
add_action( 'wp_ajax_nopriv_fv_qo_search', 'fv_qo_search' );

/**
 * Exact code lookup for pasted lists.
 */
function fv_qo_skus() {
	fv_qo_verify();
	$skus = isset( $_POST['skus'] ) ? (array) wp_unslash( $_POST['skus'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$out  = array();
	foreach ( array_slice( $skus, 0, 100 ) as $sku ) {
		$sku = sanitize_text_field( fv_latin_digits( $sku ) );
		$id  = wc_get_product_id_by_sku( $sku );
		$p   = $id ? wc_get_product( $id ) : null;
		$out[ $sku ] = fv_qo_usable( $p ) ? fv_qo_product_data( $p ) : null;
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_fv_qo_skus', 'fv_qo_skus' );
add_action( 'wp_ajax_nopriv_fv_qo_skus', 'fv_qo_skus' );

/**
 * Add lines to the cart.
 */
function fv_qo_add() {
	fv_qo_verify();
	$lines = json_decode( wp_unslash( $_POST['lines'] ?? '[]' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $lines ) || ! $lines ) {
		wp_send_json_error( array( 'message' => __( 'کالایی انتخاب نشده است.', 'faucets-valves' ) ) );
	}
	wc_clear_notices();
	$added  = 0;
	$errors = array();
	foreach ( array_slice( $lines, 0, 100 ) as $line ) {
		$id  = absint( $line['id'] ?? 0 );
		$qty = wc_stock_amount( $line['qty'] ?? 0 );
		$p   = wc_get_product( $id );
		if ( ! fv_qo_usable( $p ) || $qty <= 0 ) {
			continue;
		}
		$ok = $p->is_type( 'variation' )
			? WC()->cart->add_to_cart( $p->get_parent_id(), $qty, $p->get_id(), $p->get_variation_attributes() )
			: WC()->cart->add_to_cart( $p->get_id(), $qty );
		if ( $ok ) {
			$added++;
		}
	}
	foreach ( wc_get_notices( 'error' ) as $notice ) {
		$errors[] = wp_strip_all_tags( is_array( $notice ) ? $notice['notice'] : $notice );
	}
	wc_clear_notices();
	wp_send_json_success( array(
		'added'    => $added,
		'errors'   => $errors,
		'count'    => WC()->cart->get_cart_contents_count(),
		'cart_url' => wc_get_cart_url(),
	) );
}
add_action( 'wp_ajax_fv_qo_add', 'fv_qo_add' );
add_action( 'wp_ajax_nopriv_fv_qo_add', 'fv_qo_add' );

/**
 * Script + data on the quick order page.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page_template( 'page-templates/quick-order.php' ) ) {
		return;
	}
	$rel = 'assets/js/ui/quick-order.js';
	wp_enqueue_script( 'fv-quick-order', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'fv-quick-order', 'window.FV_QO = ' . wp_json_encode( array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'fv_quick_order' ),
		'currency' => fv_currency_label(),
		'decimals' => wc_get_price_decimals(),
		'i18n'     => array(
			'search'    => __( 'نام یا کد کالا…', 'faucets-valves' ),
			'none'      => __( 'کالایی پیدا نشد', 'faucets-valves' ),
			'notFound'  => __( 'این کدها پیدا نشدند:', 'faucets-valves' ),
			'adding'    => __( 'در حال افزودن…', 'faucets-valves' ),
			'added'     => __( 'کالا به سبد اضافه شد.', 'faucets-valves' ),
			'empty'     => __( 'دست‌کم یک کالا با تعداد انتخاب کنید.', 'faucets-valves' ),
			'remove'    => __( 'حذف ردیف', 'faucets-valves' ),
			'qty'       => __( 'تعداد', 'faucets-valves' ),
			'outStock'  => __( 'ناموجود', 'faucets-valves' ),
		),
	) ) . ';', 'before' );
}, 20 );

/**
 * Start the customer session before the page renders, so the nonce made for a guest stays
 * valid after the first item is added (WooCommerce ties guest nonces to the session).
 */
add_action( 'template_redirect', function () {
	if ( is_page_template( 'page-templates/quick-order.php' ) && WC()->session && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
} );
